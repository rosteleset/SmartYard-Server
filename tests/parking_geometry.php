<?php
// Run: php tests/parking_geometry.php
// Geometry of parking configurations: homography, validation and layout. No database is needed.
require_once __DIR__ . '/../server/backends/parking/geometry.php';

use backends\parking\geometry;

function check($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function codes(array $problems) {
    $codes = array_values(array_unique(array_column($problems, 'code')));
    sort($codes);
    return $codes;
}

$fixtures = json_decode(file_get_contents(__DIR__ . '/parking_fixtures.json'), true);
$failed = 0;

// used by tests/parking_geometry.test.cjs to compare the JS port with this implementation
if (in_array('--dump', $argv)) {
    echo json_encode(array_map(fn ($case) => geometry::analyze($case['config'], $case['options'] ?? []), $fixtures['cases']));
    exit(0);
}

foreach ($fixtures['cases'] as $case) {
    try {
        $result = geometry::analyze($case['config'], $case['options'] ?? []);
        $expect = $case['expect'];

        $expectedCodes = $expect['problems'];
        sort($expectedCodes);
        check(codes($result['problems']) === $expectedCodes, 'problems ' . json_encode(codes($result['problems'])) . ', expected ' . json_encode($expectedCodes));
        check($result['valid'] === $expect['valid'], 'valid');
        check($result['ready'] === $expect['ready'], 'ready');

        if (array_key_exists('capacity', $expect)) {
            check(array_column($result['areas'], 'capacity') === $expect['capacity'], 'capacity ' . json_encode(array_column($result['areas'], 'capacity')));
        }

        foreach ($expect['parameters'] ?? [] as $key => $value) {
            check($result[$key] === $value, "$key " . json_encode($result[$key]));
        }

        if (array_key_exists('baseEdge', $expect)) {
            check(array_column($result['areas'], 'baseEdge') === $expect['baseEdge'], 'baseEdge');
        }

        // metric vertices match as a cycle: the order may be reversed by canonicalization
        foreach ($expect['metric'] ?? [] as $i => $points) {
            $actual = $result['areas'][$i]['metricPoints'];
            $matched = false;
            foreach ([ $points, array_reverse($points) ] as $cycle) {
                for ($shift = 0; $shift < 4 && !$matched; $shift++) {
                    $same = true;
                    for ($j = 0; $j < 4; $j++) {
                        $e = $cycle[($j + $shift) % 4];
                        $same = $same && abs($actual[$j]['x'] - $e['x']) < 1e-6 && abs($actual[$j]['y'] - $e['y']) < 1e-6;
                    }
                    $matched = $same;
                }
            }
            check($matched, "metric points $i " . json_encode($actual));
        }

        // canonical order is clockwise on screen
        foreach ($result['areas'] as $area) {
            $px = array_map(fn ($p) => [ $p['u'] * $result['frameWidth'], $p['v'] * $result['frameHeight'] ], $area['imagePoints']);
            check(geometry::signedArea($px) > 0, 'canonical order');
        }

        echo "ok - {$case['name']}\n";
    } catch (Throwable $e) {
        $failed++;
        echo "not ok - {$case['name']}: {$e->getMessage()}\n";
    }
}

// homography: exact recovery, inverse transform and normalization
$tests = [
    'homography maps calibration points and back' => function () {
        $h0 = [ 0.03, 0.02, 0.1, -0.01, 0.025, 0.2, 0.0004, 0.06, 1 ];
        $src = [];
        $dst = [];
        foreach ([ [ 0, 0 ], [ 30, 0 ], [ 30, 12 ], [ 0, 12 ] ] as $m) {
            [ $u, $v ] = geometry::apply($h0, $m[0], $m[1]);
            $src[] = [ $u, $v ];
            $dst[] = $m;
        }
        $h = geometry::homography($src, $dst);
        check($h !== null, 'solved');
        $norm = sqrt(array_sum(array_map(fn ($v) => $v * $v, $h)));
        check(abs($norm - 1) < 1e-12, 'unit norm');
        $hi = geometry::invert($h);
        foreach ([ [ 5, 5 ], [ 29, 1 ], [ 15, 11 ] ] as $m) {
            [ $u, $v ] = geometry::apply($h0, $m[0], $m[1]);
            [ $x, $y, $w ] = geometry::apply($h, $u, $v);
            check($w > 0, 'positive w');
            check(abs($x - $m[0]) < 1e-7 && abs($y - $m[1]) < 1e-7, 'forward');
            [ $u2, $v2 ] = geometry::apply($hi, $x, $y);
            check(abs($u2 - $u) < 1e-9 && abs($v2 - $v) < 1e-9, 'inverse');
        }
    },
    'degenerate homography' => function () {
        check(geometry::homography([ [ 0, 0 ], [ 1, 0 ], [ 2, 0 ], [ 3, 0 ] ], [ [ 0, 0 ], [ 1, 0 ], [ 1, 1 ], [ 0, 1 ] ]) === null, 'collinear');
        check(geometry::homography([ [ 0, 0 ], [ 1, 0 ], [ 1, 1 ] ], [ [ 0, 0 ], [ 1, 0 ], [ 1, 1 ] ]) === null, 'three points');
    },
    'layout spaces are inside the area and do not overlap' => function () {
        foreach ([ 0, 30, 45, -45, 60, 90 ] as $angle) {
            $area = [ [ 0, 0 ], [ 25, 0 ], [ 27, 12 ], [ -1, 11 ] ];
            $r = geometry::layout($area, 0, 2.5, 5, $angle, true);
            check(count($r['spaces']) === $r['capacity'] && $r['capacity'] > 0, "count $angle");
            foreach ($r['spaces'] as $i => $space) {
                foreach ($space['points'] as $p) {
                    for ($k = 0; $k < 4; $k++) {
                        $a = $area[$k];
                        $b = $area[($k + 1) % 4];
                        // clockwise in a y-up plane: inside is to the left of each edge for this counter-clockwise area
                        $cross = ($b[0] - $a[0]) * ($p[1] - $a[1]) - ($b[1] - $a[1]) * ($p[0] - $a[0]);
                        check($cross / hypot($b[0] - $a[0], $b[1] - $a[1]) >= -geometry::EDGE_TOLERANCE - 1e-9, "inside $angle");
                    }
                }
                foreach (array_slice($r['spaces'], $i + 1) as $other) {
                    check(!geometry::convexOverlap($space['points'], $other['points'], 1e-6), "overlap $angle");
                }
            }
        }
    },
    'layout is independent of the vertex numbering' => function () {
        $area = [ [ 0, 0 ], [ 25, 0 ], [ 25, 5 ], [ 0, 5 ] ];
        $rotated = [ $area[1], $area[2], $area[3], $area[0] ];
        check(geometry::layout($area, 0, 2.5, 5, 0)['capacity'] === geometry::layout($rotated, 3, 2.5, 5, 0)['capacity'], 'rotation');
    },
    'invalid input' => function () {
        check(geometry::analyze(null)['valid'] === false, 'null');
        check(geometry::analyze('x')['valid'] === false, 'string');
        check(geometry::analyze([ 'frameWidth' => 1920, 'frameHeight' => 1080, 'areas' => [ 'a' => 1 ] ])['valid'] === false, 'areas object');
        check(geometry::analyze([ 'frameWidth' => 1920, 'frameHeight' => 1080, 'calibration' => [ 'points' => 'x' ] ])['valid'] === false, 'calibration');
        check(geometry::analyze([ 'frameWidth' => 1920, 'frameHeight' => 1080, 'areas' => [ [ 'parkingAreaId' => 1 ], [ 'parkingAreaId' => 1 ] ] ])['valid'] === false, 'duplicate ids');
    },
];

foreach ($tests as $name => $test) {
    try {
        $test();
        echo "ok - $name\n";
    } catch (Throwable $e) {
        $failed++;
        echo "not ok - $name: {$e->getMessage()}\n";
    }
}

if ($failed) {
    echo "$failed failed\n";
    exit(1);
}

echo "all passed\n";
