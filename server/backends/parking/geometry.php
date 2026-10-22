<?php

    /**
     * backends parking namespace
     */

    namespace backends\parking {

        /**
         * parking geometry: homography, markup validation and layout of parking spaces
         *
         * frame coordinates are normalized (u, v in 0..1, origin top-left, v down),
         * plane coordinates are metres. the same algorithms are implemented in client/js/parkingGeometry.js
         */

        class geometry {

            const LAYOUT_VERSION = 1;
            const SNAPSHOT_VERSION = 1;

            const MAX_AREAS = 32;
            const MAX_CALIBRATION_POINTS = 16;
            const MAX_CALIBRATION_CHECKS = 16;
            const MAX_PLANE_COORDINATE = 1000;
            const MAX_AREA_SQUARE = 100000;

            const EDGE_TOLERANCE = 0.05;
            const OVERLAP_TOLERANCE_PX = 2;
            const MIN_VERTEX_DISTANCE = 0.005;
            const MIN_AREA_FRACTION = 0.0005;
            const MAX_REPROJECTION_FRACTION = 0.01;
            const CHECK_WARNING = 0.10;
            const CHECK_FAILURE = 0.25;
            const MAX_METRES_PER_PIXEL = 0.2;

            const SPACE_WIDTH = [ 1.5, 6 ];
            const SPACE_LENGTH = [ 2, 25 ];
            const SPACE_ANGLE = [ -90, 90 ];

            /**
             * solve a square linear system with partial pivoting
             *
             * @param array $a n x n matrix
             * @param array $b n vector
             * @return array|null solution or null for a singular system
             */

            public static function solveLinear(array $a, array $b): ?array {
                $n = count($b);

                $scale = 0;
                for ($i = 0; $i < $n; $i++) {
                    for ($j = 0; $j < $n; $j++) {
                        $scale = max($scale, abs($a[$i][$j]));
                    }
                }

                if ($scale == 0) {
                    return null;
                }

                for ($c = 0; $c < $n; $c++) {
                    $p = $c;
                    for ($r = $c + 1; $r < $n; $r++) {
                        if (abs($a[$r][$c]) > abs($a[$p][$c])) {
                            $p = $r;
                        }
                    }

                    if (abs($a[$p][$c]) < 1e-12 * $scale) {
                        return null;
                    }

                    [ $a[$c], $a[$p] ] = [ $a[$p], $a[$c] ];
                    [ $b[$c], $b[$p] ] = [ $b[$p], $b[$c] ];

                    for ($r = $c + 1; $r < $n; $r++) {
                        $f = $a[$r][$c] / $a[$c][$c];
                        if ($f != 0) {
                            for ($k = $c; $k < $n; $k++) {
                                $a[$r][$k] -= $f * $a[$c][$k];
                            }
                            $b[$r] -= $f * $b[$c];
                        }
                    }
                }

                $x = array_fill(0, $n, 0);

                for ($r = $n - 1; $r >= 0; $r--) {
                    $s = $b[$r];
                    for ($k = $r + 1; $k < $n; $k++) {
                        $s -= $a[$r][$k] * $x[$k];
                    }
                    $x[$r] = $s / $a[$r][$r];
                }

                return $x;
            }

            /**
             * multiply two row-major 3x3 matrices
             */

            public static function multiply(array $a, array $b): array {
                $m = [];

                for ($i = 0; $i < 3; $i++) {
                    for ($j = 0; $j < 3; $j++) {
                        $m[$i * 3 + $j] = $a[$i * 3] * $b[$j] + $a[$i * 3 + 1] * $b[3 + $j] + $a[$i * 3 + 2] * $b[6 + $j];
                    }
                }

                return $m;
            }

            /**
             * determinant of a row-major 3x3 matrix
             */

            public static function determinant(array $m): float {
                return
                    $m[0] * ($m[4] * $m[8] - $m[5] * $m[7]) -
                    $m[1] * ($m[3] * $m[8] - $m[5] * $m[6]) +
                    $m[2] * ($m[3] * $m[7] - $m[4] * $m[6]);
            }

            /**
             * invert a row-major 3x3 matrix
             *
             * @return array|null inverse or null for a singular matrix
             */

            public static function invert(array $m): ?array {
                $d = self::determinant($m);

                $norm = 0;
                foreach ($m as $v) {
                    $norm += $v * $v;
                }

                if ($norm == 0 || abs($d) < 1e-12 * pow($norm, 1.5)) {
                    return null;
                }

                return [
                    ($m[4] * $m[8] - $m[5] * $m[7]) / $d,
                    ($m[2] * $m[7] - $m[1] * $m[8]) / $d,
                    ($m[1] * $m[5] - $m[2] * $m[4]) / $d,
                    ($m[5] * $m[6] - $m[3] * $m[8]) / $d,
                    ($m[0] * $m[8] - $m[2] * $m[6]) / $d,
                    ($m[2] * $m[3] - $m[0] * $m[5]) / $d,
                    ($m[3] * $m[7] - $m[4] * $m[6]) / $d,
                    ($m[1] * $m[6] - $m[0] * $m[7]) / $d,
                    ($m[0] * $m[4] - $m[1] * $m[3]) / $d,
                ];
            }

            /**
             * apply a homography to a point
             *
             * @return array [ x, y, w ], x and y are null when w is zero
             */

            public static function apply(array $h, float $x, float $y): array {
                $X = $h[0] * $x + $h[1] * $y + $h[2];
                $Y = $h[3] * $x + $h[4] * $y + $h[5];
                $W = $h[6] * $x + $h[7] * $y + $h[8];

                if (abs($W) < 1e-15) {
                    return [ null, null, $W ];
                }

                return [ $X / $W, $Y / $W, $W ];
            }

            /**
             * Hartley normalization matrix for a point set
             */

            private static function normalization(array $points): ?array {
                $n = count($points);
                $cx = 0;
                $cy = 0;

                foreach ($points as $p) {
                    $cx += $p[0];
                    $cy += $p[1];
                }

                $cx /= $n;
                $cy /= $n;

                $d = 0;
                foreach ($points as $p) {
                    $d += sqrt(($p[0] - $cx) ** 2 + ($p[1] - $cy) ** 2);
                }
                $d /= $n;

                if ($d < 1e-12) {
                    return null;
                }

                $s = M_SQRT2 / $d;

                return [ $s, 0, -$s * $cx, 0, $s, -$s * $cy, 0, 0, 1 ];
            }

            /**
             * compute a homography from point correspondences (DLT, least squares for more than 4 points)
             *
             * the result has unit Frobenius norm and positive w at the centroid of the source points
             *
             * @param array $src [ [ x, y ], ... ]
             * @param array $dst [ [ x, y ], ... ]
             * @return array|null row-major 3x3 matrix or null for a degenerate configuration
             */

            public static function homography(array $src, array $dst): ?array {
                $n = count($src);

                if ($n < 4 || $n != count($dst)) {
                    return null;
                }

                $ts = self::normalization($src);
                $td = self::normalization($dst);

                if (!$ts || !$td) {
                    return null;
                }

                $rows = [];
                $rhs = [];

                for ($i = 0; $i < $n; $i++) {
                    $a = self::apply($ts, $src[$i][0], $src[$i][1]);
                    $b = self::apply($td, $dst[$i][0], $dst[$i][1]);

                    [ $x, $y ] = $a;
                    [ $X, $Y ] = $b;

                    $rows[] = [ $x, $y, 1, 0, 0, 0, -$X * $x, -$X * $y ];
                    $rhs[] = $X;
                    $rows[] = [ 0, 0, 0, $x, $y, 1, -$Y * $x, -$Y * $y ];
                    $rhs[] = $Y;
                }

                if ($n == 4) {
                    $h = self::solveLinear($rows, $rhs);
                } else {
                    $ata = array_fill(0, 8, array_fill(0, 8, 0));
                    $atb = array_fill(0, 8, 0);

                    foreach ($rows as $r => $row) {
                        for ($i = 0; $i < 8; $i++) {
                            $atb[$i] += $row[$i] * $rhs[$r];
                            for ($j = 0; $j < 8; $j++) {
                                $ata[$i][$j] += $row[$i] * $row[$j];
                            }
                        }
                    }

                    $h = self::solveLinear($ata, $atb);
                }

                if (!$h) {
                    return null;
                }

                $h[] = 1;

                if (abs(self::determinant($h)) < 1e-9) {
                    return null;
                }

                $tdi = self::invert($td);

                if (!$tdi) {
                    return null;
                }

                $h = self::multiply(self::multiply($tdi, $h), $ts);

                $norm = 0;
                foreach ($h as $v) {
                    $norm += $v * $v;
                }
                $norm = sqrt($norm);

                $cx = 0;
                $cy = 0;
                foreach ($src as $p) {
                    $cx += $p[0] / $n;
                    $cy += $p[1] / $n;
                }

                if ($h[6] * $cx + $h[7] * $cy + $h[8] < 0) {
                    $norm = -$norm;
                }

                foreach ($h as $i => $v) {
                    $h[$i] = $v / $norm;
                }

                return $h;
            }

            /**
             * doubled signed area of a polygon, positive for clockwise order on screen (y down)
             */

            public static function signedArea(array $p): float {
                $s = 0;
                $n = count($p);

                for ($i = 0; $i < $n; $i++) {
                    $j = ($i + 1) % $n;
                    $s += $p[$i][0] * $p[$j][1] - $p[$j][0] * $p[$i][1];
                }

                return $s / 2;
            }

            private static function cross(array $o, array $a, array $b): float {
                return ($a[0] - $o[0]) * ($b[1] - $o[1]) - ($a[1] - $o[1]) * ($b[0] - $o[0]);
            }

            /**
             * check whether segments ab and cd intersect (including touching)
             */

            public static function segmentsIntersect(array $a, array $b, array $c, array $d): bool {
                $d1 = self::cross($c, $d, $a);
                $d2 = self::cross($c, $d, $b);
                $d3 = self::cross($a, $b, $c);
                $d4 = self::cross($a, $b, $d);

                if ((($d1 > 0 && $d2 < 0) || ($d1 < 0 && $d2 > 0)) && (($d3 > 0 && $d4 < 0) || ($d3 < 0 && $d4 > 0))) {
                    return true;
                }

                $on = function ($p, $q, $r) {
                    return min($p[0], $q[0]) <= $r[0] && $r[0] <= max($p[0], $q[0]) && min($p[1], $q[1]) <= $r[1] && $r[1] <= max($p[1], $q[1]);
                };

                return
                    ($d1 == 0 && $on($c, $d, $a)) ||
                    ($d2 == 0 && $on($c, $d, $b)) ||
                    ($d3 == 0 && $on($a, $b, $c)) ||
                    ($d4 == 0 && $on($a, $b, $d));
            }

            /**
             * check whether a quadrilateral is self-intersecting
             */

            public static function selfIntersects(array $p): bool {
                return self::segmentsIntersect($p[0], $p[1], $p[2], $p[3]) || self::segmentsIntersect($p[1], $p[2], $p[3], $p[0]);
            }

            /**
             * check whether a polygon is strictly convex
             */

            public static function isConvex(array $p): bool {
                $n = count($p);
                $sign = 0;

                $scale = 0;
                for ($i = 0; $i < $n; $i++) {
                    $j = ($i + 1) % $n;
                    $scale = max($scale, hypot($p[$j][0] - $p[$i][0], $p[$j][1] - $p[$i][1]));
                }

                if ($scale == 0) {
                    return false;
                }

                for ($i = 0; $i < $n; $i++) {
                    $c = self::cross($p[$i], $p[($i + 1) % $n], $p[($i + 2) % $n]);

                    if (abs($c) < 1e-9 * $scale * $scale) {
                        return false;
                    }

                    $s = $c > 0 ? 1 : -1;

                    if ($sign && $s != $sign) {
                        return false;
                    }

                    $sign = $s;
                }

                return true;
            }

            /**
             * check whether two convex polygons overlap by more than a tolerance (separating axis theorem)
             */

            public static function convexOverlap(array $a, array $b, float $tolerance): bool {
                foreach ([ $a, $b ] as $poly) {
                    $n = count($poly);

                    for ($i = 0; $i < $n; $i++) {
                        $j = ($i + 1) % $n;
                        $ax = $poly[$i][1] - $poly[$j][1];
                        $ay = $poly[$j][0] - $poly[$i][0];
                        $len = hypot($ax, $ay);

                        if ($len == 0) {
                            continue;
                        }

                        $ax /= $len;
                        $ay /= $len;

                        $minA = $minB = INF;
                        $maxA = $maxB = -INF;

                        foreach ($a as $q) {
                            $v = $q[0] * $ax + $q[1] * $ay;
                            $minA = min($minA, $v);
                            $maxA = max($maxA, $v);
                        }

                        foreach ($b as $q) {
                            $v = $q[0] * $ax + $q[1] * $ay;
                            $minB = min($minB, $v);
                            $maxB = max($maxB, $v);
                        }

                        if (min($maxA, $maxB) - max($minA, $minB) <= $tolerance) {
                            return false;
                        }
                    }
                }

                return true;
            }

            /**
             * check whether three points are (nearly) collinear
             */

            private static function collinear(array $a, array $b, array $c): bool {
                $scale = max(
                    ($a[0] - $b[0]) ** 2 + ($a[1] - $b[1]) ** 2,
                    ($b[0] - $c[0]) ** 2 + ($b[1] - $c[1]) ** 2,
                    ($c[0] - $a[0]) ** 2 + ($c[1] - $a[1]) ** 2
                );

                return $scale == 0 || abs(self::cross($a, $b, $c)) < 1e-3 * $scale;
            }

            /**
             * layout of parking spaces inside a convex metric quadrilateral (layout version 1)
             *
             * rows are placed along the base edge (vertex baseEdge to vertex baseEdge + 1) starting from it,
             * the long axis of a space is rotated by angle degrees from the inward normal of the base edge
             * towards the base edge direction, only spaces fully inside the area (with tolerance) are counted
             *
             * @param array $points metric vertices [ [ x, y ] x 4 ]
             * @param int $baseEdge
             * @param float $width space width, metres
             * @param float $length space length, metres
             * @param float $angle degrees
             * @param bool $withSpaces return space footprints
             * @return array [ "capacity" => int, "spaces" => [ [ "row" => int, "slot" => int, "points" => [ [ x, y ] x 4 ] ] ] ]
             */

            public static function layout(array $points, int $baseEdge, float $width, float $length, float $angle, bool $withSpaces = false): array {
                $p = [];
                for ($i = 0; $i < 4; $i++) {
                    $p[] = $points[($baseEdge + $i) % 4];
                }

                $ex = $p[1][0] - $p[0][0];
                $ey = $p[1][1] - $p[0][1];
                $el = hypot($ex, $ey);

                $result = [ "capacity" => 0, "spaces" => [] ];

                if ($el == 0 || $width <= 0 || $length <= 0) {
                    return $result;
                }

                $ex /= $el;
                $ey /= $el;

                $nx = -$ey;
                $ny = $ex;

                $cx = ($p[0][0] + $p[1][0] + $p[2][0] + $p[3][0]) / 4;
                $cy = ($p[0][1] + $p[1][1] + $p[2][1] + $p[3][1]) / 4;

                if (($cx - $p[0][0]) * $nx + ($cy - $p[0][1]) * $ny < 0) {
                    $nx = -$nx;
                    $ny = -$ny;
                }

                // area in local coordinates: s along the base edge, t inwards
                $q = [];
                foreach ($p as $v) {
                    $q[] = [
                        ($v[0] - $p[0][0]) * $ex + ($v[1] - $p[0][1]) * $ey,
                        ($v[0] - $p[0][0]) * $nx + ($v[1] - $p[0][1]) * $ny,
                    ];
                }

                $qc = [ ($q[0][0] + $q[1][0] + $q[2][0] + $q[3][0]) / 4, ($q[0][1] + $q[1][1] + $q[2][1] + $q[3][1]) / 4 ];

                // inward half-planes: m . (X - Q_i) >= -tolerance
                $planes = [];
                for ($i = 0; $i < 4; $i++) {
                    $a = $q[$i];
                    $b = $q[($i + 1) % 4];
                    $mx = $a[1] - $b[1];
                    $my = $b[0] - $a[0];
                    $ml = hypot($mx, $my);

                    if ($ml == 0) {
                        continue;
                    }

                    $mx /= $ml;
                    $my /= $ml;

                    if (($qc[0] - $a[0]) * $mx + ($qc[1] - $a[1]) * $my < 0) {
                        $mx = -$mx;
                        $my = -$my;
                    }

                    $planes[] = [ $mx, $my, $a ];
                }

                $theta = deg2rad($angle);
                $sin = sin($theta);
                $cos = cos($theta);

                // footprint: width axis (cos, -sin), long axis (sin, cos)
                $f = [
                    [ 0, 0 ],
                    [ $width * $cos, -$width * $sin ],
                    [ $width * $cos + $length * $sin, -$width * $sin + $length * $cos ],
                    [ $length * $sin, $length * $cos ],
                ];

                $fs = min(array_column($f, 0));
                $ft = min(array_column($f, 1));

                foreach ($f as $i => $v) {
                    $f[$i] = [ $v[0] - $fs, $v[1] - $ft ];
                }

                $depth = max(array_column($f, 1));

                $pitch = INF;
                if (abs($cos) > 1e-9) {
                    $pitch = min($pitch, $width / abs($cos));
                }
                if (abs($sin) > 1e-9) {
                    $pitch = min($pitch, $length / abs($sin));
                }

                $tMax = max(array_column($q, 1));
                $tolerance = self::EDGE_TOLERANCE;

                for ($row = 0; ($row + 1) * $depth <= $tMax + $tolerance; $row++) {
                    $t0 = $row * $depth;
                    $lo = -INF;
                    $hi = INF;

                    foreach ($planes as [ $mx, $my, $a ]) {
                        foreach ($f as $v) {
                            $rhs = -$tolerance - ($mx * ($v[0] - $a[0]) + $my * ($v[1] + $t0 - $a[1]));

                            if ($mx > 1e-12) {
                                $lo = max($lo, $rhs / $mx);
                            } else if ($mx < -1e-12) {
                                $hi = min($hi, $rhs / $mx);
                            } else if ($rhs > 1e-12) {
                                $lo = INF;
                            }
                        }
                    }

                    if (!is_finite($lo) || !is_finite($hi) || $hi < $lo) {
                        continue;
                    }

                    $count = (int)floor(($hi - $lo) / $pitch + 1e-9) + 1;
                    $result["capacity"] += $count;

                    if ($withSpaces) {
                        for ($slot = 0; $slot < $count; $slot++) {
                            $s0 = $lo + $slot * $pitch;
                            $corners = [];

                            foreach ($f as $v) {
                                $s = $v[0] + $s0;
                                $t = $v[1] + $t0;
                                $corners[] = [ $p[0][0] + $s * $ex + $t * $nx, $p[0][1] + $s * $ey + $t * $ny ];
                            }

                            $result["spaces"][] = [ "row" => $row, "slot" => $slot, "points" => $corners ];
                        }
                    }
                }

                return $result;
            }

            private static function isNumber($v): bool {
                return (is_int($v) || is_float($v)) && is_finite($v);
            }

            private static function isFlag($v): bool {
                return $v === true || $v === false || $v === 0 || $v === 1;
            }

            /**
             * validate and analyze a parking configuration
             *
             * input (API format):
             * {
             *   enabled, frameWidth, frameHeight,
             *   calibration: null | { points: [ { u, v, x, y } ], checks: [ { u1, v1, u2, v2, length } ] },
             *   areas: [ { parkingAreaId?, name, enabled, imagePoints: [ { u, v } x 4 ], baseEdge, spaceWidth, spaceLength, spaceAngle } ]
             * }
             *
             * areas are canonicalized (clockwise order on screen), values are rounded as stored in the database,
             * only valid areas are returned, "index" is the position of an area in the input
             *
             * problem severities: "error" - can't be saved, "notReady" - saved but not ready for processing, "warning" - informational
             *
             * @param mixed $input
             * @return array normalized configuration with homography, capacity, metric points, problems and ready flag
             */

            public static function analyze($input): array {
                $problems = [];

                $problem = function ($code, $severity, $area = null) use (&$problems) {
                    $p = [ "code" => $code, "severity" => $severity ];

                    if ($area !== null) {
                        $p["area"] = $area;
                    }

                    $problems[] = $p;
                };

                $result = [
                    "enabled" => 1,
                    "frameWidth" => 0,
                    "frameHeight" => 0,
                    "calibration" => null,
                    "homography" => null,
                    "calibrationError" => null,
                    "areas" => [],
                ];

                if (!is_array($input)) {
                    $problem("invalidConfig", "error");
                    return self::finish($result, $problems);
                }

                if (array_key_exists("enabled", $input)) {
                    if (self::isFlag($input["enabled"])) {
                        $result["enabled"] = (int)$input["enabled"];
                    } else {
                        $problem("invalidValue", "error");
                    }
                }

                $fw = @$input["frameWidth"];
                $fh = @$input["frameHeight"];

                if (!is_int($fw) || !is_int($fh) || $fw < 16 || $fh < 16 || $fw > 16384 || $fh > 16384) {
                    $problem("invalidFrame", "error");
                    return self::finish($result, $problems);
                }

                $result["frameWidth"] = $fw;
                $result["frameHeight"] = $fh;

                $diagonal = hypot($fw, $fh);

                $pixel = function ($u, $v) use ($fw, $fh) {
                    return [ $u * $fw, $v * $fh ];
                };

                $isUV = function ($u, $v) {
                    return self::isNumber($u) && self::isNumber($v) && $u >= 0 && $u <= 1 && $v >= 0 && $v <= 1;
                };

                // calibration

                $h = null;
                $calibration = @$input["calibration"];

                if ($calibration !== null) {
                    $points = @$calibration["points"];
                    $checks = array_key_exists("checks", is_array($calibration) ? $calibration : []) ? $calibration["checks"] : [];

                    if (!is_array($calibration) || !is_array($points) || !array_is_list($points) || !is_array($checks) || !array_is_list($checks)) {
                        $problem("invalidCalibration", "error");
                    } else if (count($points) < 4 || count($points) > self::MAX_CALIBRATION_POINTS || count($checks) > self::MAX_CALIBRATION_CHECKS) {
                        $problem("calibrationPointsCount", "error");
                    } else {
                        $valid = true;
                        $cp = [];

                        foreach ($points as $point) {
                            if (!is_array($point) || !$isUV(@$point["u"], @$point["v"]) || !self::isNumber(@$point["x"]) || !self::isNumber(@$point["y"]) || abs($point["x"]) > self::MAX_PLANE_COORDINATE || abs($point["y"]) > self::MAX_PLANE_COORDINATE) {
                                $valid = false;
                                break;
                            }

                            $cp[] = [ "u" => (float)$point["u"], "v" => (float)$point["v"], "x" => (float)$point["x"], "y" => (float)$point["y"] ];
                        }

                        $cc = [];

                        foreach ($checks as $check) {
                            if (!is_array($check) || !$isUV(@$check["u1"], @$check["v1"]) || !$isUV(@$check["u2"], @$check["v2"]) || !self::isNumber(@$check["length"]) || $check["length"] <= 0 || $check["length"] > self::MAX_PLANE_COORDINATE) {
                                $valid = false;
                                break;
                            }

                            $cc[] = [ "u1" => (float)$check["u1"], "v1" => (float)$check["v1"], "u2" => (float)$check["u2"], "v2" => (float)$check["v2"], "length" => (float)$check["length"] ];
                        }

                        if (!$valid) {
                            $problem("invalidCalibration", "error");
                        } else {
                            $src = [];
                            $dst = [];

                            foreach ($cp as $point) {
                                $src[] = $pixel($point["u"], $point["v"]);
                                $dst[] = [ $point["x"], $point["y"] ];
                            }

                            $degenerate = false;
                            $n = count($src);

                            for ($i = 0; $i < $n && !$degenerate; $i++) {
                                for ($j = $i + 1; $j < $n; $j++) {
                                    if (hypot($src[$i][0] - $src[$j][0], $src[$i][1] - $src[$j][1]) < self::MIN_VERTEX_DISTANCE * $diagonal || ($dst[$i][0] == $dst[$j][0] && $dst[$i][1] == $dst[$j][1])) {
                                        $degenerate = true;
                                        break;
                                    }
                                }
                            }

                            if (!$degenerate && $n == 4) {
                                foreach ([ [ 0, 1, 2 ], [ 0, 1, 3 ], [ 0, 2, 3 ], [ 1, 2, 3 ] ] as [ $a, $b, $c ]) {
                                    if (self::collinear($src[$a], $src[$b], $src[$c]) || self::collinear($dst[$a], $dst[$b], $dst[$c])) {
                                        $degenerate = true;
                                        break;
                                    }
                                }
                            }

                            if (!$degenerate) {
                                $uv = array_map(fn ($point) => [ $point["u"], $point["v"] ], $cp);
                                $h = self::homography($uv, $dst);

                                if ($h) {
                                    foreach ($uv as $i => $point) {
                                        [ $x, $y, $w ] = self::apply($h, $point[0], $point[1]);

                                        if ($w <= 0 || $x === null) {
                                            $h = null;
                                            break;
                                        }
                                    }
                                }

                                if (!$h) {
                                    $degenerate = true;
                                }
                            }

                            if ($degenerate) {
                                $problem("calibrationDegenerate", "error");
                            } else {
                                $result["calibration"] = [ "model" => "homography", "points" => $cp, "checks" => $cc ];
                                $result["homography"] = $h;

                                $hi = self::invert($h);

                                if ($n > 4 && $hi) {
                                    $sum = 0;

                                    foreach ($cp as $point) {
                                        [ $u, $v ] = self::apply($hi, $point["x"], $point["y"]);
                                        $e = $pixel($u, $v);
                                        $o = $pixel($point["u"], $point["v"]);
                                        $sum += ($e[0] - $o[0]) ** 2 + ($e[1] - $o[1]) ** 2;
                                    }

                                    if (sqrt($sum / $n) > self::MAX_REPROJECTION_FRACTION * $diagonal) {
                                        $problem("calibrationReprojection", "notReady");
                                    }
                                }

                                $error = null;

                                foreach ($cc as $check) {
                                    $a = self::apply($h, $check["u1"], $check["v1"]);
                                    $b = self::apply($h, $check["u2"], $check["v2"]);

                                    if ($a[2] <= 0 || $b[2] <= 0 || $a[0] === null || $b[0] === null) {
                                        $error = INF;
                                    } else {
                                        $error = max((float)$error, abs(hypot($a[0] - $b[0], $a[1] - $b[1]) - $check["length"]) / $check["length"]);
                                    }
                                }

                                if ($error !== null) {
                                    if ($error > self::CHECK_FAILURE) {
                                        $problem("calibrationCheckFailed", "notReady");
                                    } else if ($error > self::CHECK_WARNING) {
                                        $problem("calibrationCheckWarning", "warning");
                                    }

                                    $result["calibrationError"] = is_finite($error) ? round($error, 6) : null;
                                }
                            }
                        }
                    }
                }

                // areas

                $areas = array_key_exists("areas", $input) ? $input["areas"] : [];

                if (!is_array($areas) || !array_is_list($areas)) {
                    $problem("invalidAreas", "error");
                    $areas = [];
                }

                if (count($areas) > self::MAX_AREAS) {
                    $problem("tooManyAreas", "error");
                    $areas = [];
                }

                $ids = [];
                $polygons = [];

                foreach ($areas as $index => $area) {
                    if (!is_array($area)) {
                        $problem("invalidArea", "error", $index);
                        continue;
                    }

                    $id = array_key_exists("parkingAreaId", $area) ? $area["parkingAreaId"] : null;

                    if ($id !== null && (!is_int($id) || $id <= 0 || isset($ids[$id]))) {
                        $problem("invalidArea", "error", $index);
                        continue;
                    }

                    if ($id !== null) {
                        $ids[$id] = true;
                    }

                    $name = array_key_exists("name", $area) ? $area["name"] : "";

                    if (!is_string($name) || mb_strlen($name) > 64) {
                        $problem("invalidAreaName", "error", $index);
                        continue;
                    }

                    $enabled = array_key_exists("enabled", $area) ? $area["enabled"] : 1;

                    if (!self::isFlag($enabled)) {
                        $problem("invalidValue", "error", $index);
                        continue;
                    }

                    $baseEdge = array_key_exists("baseEdge", $area) ? $area["baseEdge"] : 0;

                    if (!is_int($baseEdge) || $baseEdge < 0 || $baseEdge > 3) {
                        $problem("invalidBaseEdge", "error", $index);
                        continue;
                    }

                    $params = [];
                    $valid = true;

                    foreach ([ "spaceWidth" => self::SPACE_WIDTH, "spaceLength" => self::SPACE_LENGTH, "spaceAngle" => self::SPACE_ANGLE ] as $key => $range) {
                        $v = @$area[$key];

                        if (!self::isNumber($v) || round($v, 2) < $range[0] || round($v, 2) > $range[1]) {
                            $problem("invalid" . ucfirst($key), "error", $index);
                            $valid = false;
                        } else {
                            $params[$key] = round((float)$v, 2);
                        }
                    }

                    if (!$valid) {
                        continue;
                    }

                    $ip = @$area["imagePoints"];

                    if (!is_array($ip) || !array_is_list($ip) || count($ip) != 4) {
                        $problem("areaPointsCount", "error", $index);
                        continue;
                    }

                    $uv = [];

                    foreach ($ip as $point) {
                        if (!is_array($point) || !$isUV(@$point["u"], @$point["v"])) {
                            $valid = false;
                            break;
                        }

                        $uv[] = [ (float)$point["u"], (float)$point["v"] ];
                    }

                    if (!$valid) {
                        $problem("invalidPoint", "error", $index);
                        continue;
                    }

                    // canonical order: clockwise on screen, the base edge and the angle keep their physical meaning
                    $px = array_map(fn ($point) => $pixel($point[0], $point[1]), $uv);

                    if (self::signedArea($px) < 0) {
                        $uv = [ $uv[0], $uv[3], $uv[2], $uv[1] ];
                        $px = [ $px[0], $px[3], $px[2], $px[1] ];
                        $baseEdge = (3 - $baseEdge + 4) % 4;
                        $params["spaceAngle"] = -$params["spaceAngle"] + 0;
                    }

                    $distinct = true;

                    for ($i = 0; $i < 4; $i++) {
                        for ($j = $i + 1; $j < 4; $j++) {
                            if (hypot($px[$i][0] - $px[$j][0], $px[$i][1] - $px[$j][1]) < self::MIN_VERTEX_DISTANCE * $diagonal) {
                                $distinct = false;
                            }
                        }
                    }

                    if (!$distinct) {
                        $problem("areaDuplicateVertices", "error", $index);
                        continue;
                    }

                    if (self::selfIntersects($px)) {
                        $problem("areaSelfIntersecting", "error", $index);
                        continue;
                    }

                    if (!self::isConvex($px)) {
                        $problem("areaNotConvex", "error", $index);
                        continue;
                    }

                    if (abs(self::signedArea($px)) < self::MIN_AREA_FRACTION * $fw * $fh) {
                        $problem("areaTooSmall", "error", $index);
                        continue;
                    }

                    foreach ($polygons as $other => $polygon) {
                        if (self::convexOverlap($px, $polygon, self::OVERLAP_TOLERANCE_PX)) {
                            $problem("areasOverlap", "error", $index);
                            break;
                        }
                    }

                    $polygons[$index] = $px;

                    if ($params["spaceLength"] < $params["spaceWidth"]) {
                        $problem("spaceLengthLessThanWidth", "warning", $index);
                    }

                    $item = [
                        "index" => $index,
                        "parkingAreaId" => $id,
                        "name" => $name,
                        "enabled" => (int)$enabled,
                        "imagePoints" => array_map(fn ($point) => [ "u" => $point[0], "v" => $point[1] ], $uv),
                        "baseEdge" => $baseEdge,
                        "spaceWidth" => $params["spaceWidth"],
                        "spaceLength" => $params["spaceLength"],
                        "spaceAngle" => $params["spaceAngle"],
                        "capacity" => null,
                        "metricPoints" => null,
                    ];

                    if ($h) {
                        $metric = [];

                        foreach ($uv as $point) {
                            [ $x, $y, $w ] = self::apply($h, $point[0], $point[1]);

                            if ($w <= 0 || $x === null) {
                                $metric = false;
                                break;
                            }

                            $metric[] = [ $x, $y ];
                        }

                        if ($metric === false) {
                            $problem("areaBeyondHorizon", "notReady", $index);
                        } else if (self::selfIntersects($metric) || !self::isConvex($metric) || abs(self::signedArea($metric)) > self::MAX_AREA_SQUARE) {
                            $problem("areaMetricInvalid", "notReady", $index);
                        } else {
                            $item["metricPoints"] = array_map(fn ($point) => [ "x" => $point[0], "y" => $point[1] ], $metric);
                            $item["capacity"] = self::layout($metric, $baseEdge, $params["spaceWidth"], $params["spaceLength"], $params["spaceAngle"])["capacity"];

                            if ($item["capacity"] == 0) {
                                $problem("areaNoSpaces", "warning", $index);
                            }

                            $cu = ($uv[0][0] + $uv[1][0] + $uv[2][0] + $uv[3][0]) / 4;
                            $cv = ($uv[0][1] + $uv[1][1] + $uv[2][1] + $uv[3][1]) / 4;
                            $c = self::apply($h, $cu, $cv);
                            $du = self::apply($h, $cu + 1 / $fw, $cv);
                            $dv = self::apply($h, $cu, $cv + 1 / $fh);

                            if ($du[0] !== null && $dv[0] !== null && max(hypot($du[0] - $c[0], $du[1] - $c[1]), hypot($dv[0] - $c[0], $dv[1] - $c[1])) > self::MAX_METRES_PER_PIXEL) {
                                $problem("areaLowResolution", "warning", $index);
                            }
                        }
                    }

                    $result["areas"][$index] = $item;
                }

                $result["areas"] = array_values($result["areas"]);

                return self::finish($result, $problems);
            }

            private static function finish(array $result, array $problems): array {
                $hasErrors = false;
                $notReady = false;

                foreach ($problems as $p) {
                    $hasErrors = $hasErrors || $p["severity"] == "error";
                    $notReady = $notReady || $p["severity"] != "warning";
                }

                if (!$hasErrors) {
                    if (!$result["homography"]) {
                        $problems[] = [ "code" => "noCalibration", "severity" => "notReady" ];
                        $notReady = true;
                    }

                    $usable = false;

                    foreach ($result["areas"] as $area) {
                        $usable = $usable || ($area["enabled"] && $area["capacity"]);
                    }

                    if ($result["homography"] && !$usable) {
                        $problems[] = [ "code" => "noUsableAreas", "severity" => "notReady" ];
                        $notReady = true;
                    }

                    if (!$result["enabled"]) {
                        $problems[] = [ "code" => "disabled", "severity" => "notReady" ];
                        $notReady = true;
                    }
                }

                $result["problems"] = $problems;
                $result["valid"] = !$hasErrors;
                $result["ready"] = !$hasErrors && !$notReady;

                return $result;
            }
        }
    }
