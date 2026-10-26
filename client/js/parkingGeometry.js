/*
    parking geometry: homography, markup validation and layout of parking spaces

    frame coordinates are normalized (u, v in 0..1, origin top-left, v down), plane coordinates are metres.
    this is a port of server/backends/parking/geometry.php, both must produce the same results
    (shared fixtures: tests/parking_fixtures.json); freeSpaces() exists only here, it is used by the
    parking occupancy service (tests/parking_free_spaces.test.cjs)
*/

var parkingGeometry = (function () {
    const g = {
        LAYOUT_VERSION: 1,

        MAX_AREAS: 32,
        MAX_CALIBRATION_POINTS: 16,
        MAX_CALIBRATION_CHECKS: 16,
        MAX_PLANE_COORDINATE: 1000,
        MAX_AREA_SQUARE: 100000,

        EDGE_TOLERANCE: 0.05,
        ROW_SHIFT_STEP: 0.05,
        OVERLAP_TOLERANCE_PX: 2,
        MIN_VERTEX_DISTANCE: 0.005,
        MIN_AREA_FRACTION: 0.0005,
        MAX_REPROJECTION_FRACTION: 0.01,
        CHECK_WARNING: 0.10,
        CHECK_FAILURE: 0.25,
        MAX_METRES_PER_PIXEL: 0.2,

        SPACE_WIDTH: [ 1.5, 6 ],
        SPACE_LENGTH: [ 2, 25 ],
        SPACE_ANGLE: [ -90, 90 ],

        DETECTION_IMAGE_SIZE: [ 160, 1024 ],
        DEFAULT_DETECTION_IMAGE_SIZE: 320,
        CONFIDENCE_THRESHOLD: [ 0, 1 ],
        DEFAULT_CONFIDENCE_THRESHOLD: 0.5,
        MAX_PROCESSING_INTERVAL: 86400,
    };

    function round(v, digits) {
        // half away from zero, as PHP round()
        let m = Math.pow(10, digits);
        let r = Math.round(Math.abs(v) * m) / m;
        return (v < 0 ? -r : r) + 0;
    }

    g.solveLinear = function (a, b) {
        a = a.map(r => r.slice());
        b = b.slice();

        let n = b.length;
        let scale = 0;

        for (let i = 0; i < n; i++) {
            for (let j = 0; j < n; j++) {
                scale = Math.max(scale, Math.abs(a[i][j]));
            }
        }

        if (scale == 0) {
            return null;
        }

        for (let c = 0; c < n; c++) {
            let p = c;
            for (let r = c + 1; r < n; r++) {
                if (Math.abs(a[r][c]) > Math.abs(a[p][c])) {
                    p = r;
                }
            }

            if (Math.abs(a[p][c]) < 1e-12 * scale) {
                return null;
            }

            [ a[c], a[p] ] = [ a[p], a[c] ];
            [ b[c], b[p] ] = [ b[p], b[c] ];

            for (let r = c + 1; r < n; r++) {
                let f = a[r][c] / a[c][c];
                if (f != 0) {
                    for (let k = c; k < n; k++) {
                        a[r][k] -= f * a[c][k];
                    }
                    b[r] -= f * b[c];
                }
            }
        }

        let x = new Array(n).fill(0);

        for (let r = n - 1; r >= 0; r--) {
            let s = b[r];
            for (let k = r + 1; k < n; k++) {
                s -= a[r][k] * x[k];
            }
            x[r] = s / a[r][r];
        }

        return x;
    };

    g.multiply = function (a, b) {
        let m = [];

        for (let i = 0; i < 3; i++) {
            for (let j = 0; j < 3; j++) {
                m[i * 3 + j] = a[i * 3] * b[j] + a[i * 3 + 1] * b[3 + j] + a[i * 3 + 2] * b[6 + j];
            }
        }

        return m;
    };

    g.determinant = function (m) {
        return m[0] * (m[4] * m[8] - m[5] * m[7]) -
            m[1] * (m[3] * m[8] - m[5] * m[6]) +
            m[2] * (m[3] * m[7] - m[4] * m[6]);
    };

    g.invert = function (m) {
        let d = g.determinant(m);
        let norm = m.reduce((s, v) => s + v * v, 0);

        if (norm == 0 || Math.abs(d) < 1e-12 * Math.pow(norm, 1.5)) {
            return null;
        }

        return [
            (m[4] * m[8] - m[5] * m[7]) / d,
            (m[2] * m[7] - m[1] * m[8]) / d,
            (m[1] * m[5] - m[2] * m[4]) / d,
            (m[5] * m[6] - m[3] * m[8]) / d,
            (m[0] * m[8] - m[2] * m[6]) / d,
            (m[2] * m[3] - m[0] * m[5]) / d,
            (m[3] * m[7] - m[4] * m[6]) / d,
            (m[1] * m[6] - m[0] * m[7]) / d,
            (m[0] * m[4] - m[1] * m[3]) / d,
        ];
    };

    g.apply = function (h, x, y) {
        let X = h[0] * x + h[1] * y + h[2];
        let Y = h[3] * x + h[4] * y + h[5];
        let W = h[6] * x + h[7] * y + h[8];

        if (Math.abs(W) < 1e-15) {
            return [ null, null, W ];
        }

        return [ X / W, Y / W, W ];
    };

    function normalization(points) {
        let n = points.length;
        let cx = 0, cy = 0;

        for (let p of points) {
            cx += p[0];
            cy += p[1];
        }

        cx /= n;
        cy /= n;

        let d = 0;
        for (let p of points) {
            d += Math.sqrt((p[0] - cx) ** 2 + (p[1] - cy) ** 2);
        }
        d /= n;

        if (d < 1e-12) {
            return null;
        }

        let s = Math.SQRT2 / d;

        return [ s, 0, -s * cx, 0, s, -s * cy, 0, 0, 1 ];
    }

    g.homography = function (src, dst) {
        let n = src.length;

        if (n < 4 || n != dst.length) {
            return null;
        }

        let ts = normalization(src);
        let td = normalization(dst);

        if (!ts || !td) {
            return null;
        }

        let rows = [];
        let rhs = [];

        for (let i = 0; i < n; i++) {
            let [ x, y ] = g.apply(ts, src[i][0], src[i][1]);
            let [ X, Y ] = g.apply(td, dst[i][0], dst[i][1]);

            rows.push([ x, y, 1, 0, 0, 0, -X * x, -X * y ]);
            rhs.push(X);
            rows.push([ 0, 0, 0, x, y, 1, -Y * x, -Y * y ]);
            rhs.push(Y);
        }

        let h;

        if (n == 4) {
            h = g.solveLinear(rows, rhs);
        } else {
            let ata = [];
            let atb = new Array(8).fill(0);

            for (let i = 0; i < 8; i++) {
                ata.push(new Array(8).fill(0));
            }

            rows.forEach((row, r) => {
                for (let i = 0; i < 8; i++) {
                    atb[i] += row[i] * rhs[r];
                    for (let j = 0; j < 8; j++) {
                        ata[i][j] += row[i] * row[j];
                    }
                }
            });

            h = g.solveLinear(ata, atb);
        }

        if (!h) {
            return null;
        }

        h.push(1);

        if (Math.abs(g.determinant(h)) < 1e-9) {
            return null;
        }

        let tdi = g.invert(td);

        if (!tdi) {
            return null;
        }

        h = g.multiply(g.multiply(tdi, h), ts);

        let norm = Math.sqrt(h.reduce((s, v) => s + v * v, 0));

        let cx = 0, cy = 0;
        for (let p of src) {
            cx += p[0] / n;
            cy += p[1] / n;
        }

        if (h[6] * cx + h[7] * cy + h[8] < 0) {
            norm = -norm;
        }

        return h.map(v => v / norm);
    };

    g.signedArea = function (p) {
        let s = 0;
        let n = p.length;

        for (let i = 0; i < n; i++) {
            let j = (i + 1) % n;
            s += p[i][0] * p[j][1] - p[j][0] * p[i][1];
        }

        return s / 2;
    };

    function cross(o, a, b) {
        return (a[0] - o[0]) * (b[1] - o[1]) - (a[1] - o[1]) * (b[0] - o[0]);
    }

    g.segmentsIntersect = function (a, b, c, d) {
        let d1 = cross(c, d, a);
        let d2 = cross(c, d, b);
        let d3 = cross(a, b, c);
        let d4 = cross(a, b, d);

        if (((d1 > 0 && d2 < 0) || (d1 < 0 && d2 > 0)) && ((d3 > 0 && d4 < 0) || (d3 < 0 && d4 > 0))) {
            return true;
        }

        let on = (p, q, r) => Math.min(p[0], q[0]) <= r[0] && r[0] <= Math.max(p[0], q[0]) && Math.min(p[1], q[1]) <= r[1] && r[1] <= Math.max(p[1], q[1]);

        return (d1 == 0 && on(c, d, a)) ||
            (d2 == 0 && on(c, d, b)) ||
            (d3 == 0 && on(a, b, c)) ||
            (d4 == 0 && on(a, b, d));
    };

    g.selfIntersects = function (p) {
        return g.segmentsIntersect(p[0], p[1], p[2], p[3]) || g.segmentsIntersect(p[1], p[2], p[3], p[0]);
    };

    g.isConvex = function (p) {
        let n = p.length;
        let sign = 0;
        let scale = 0;

        for (let i = 0; i < n; i++) {
            let j = (i + 1) % n;
            scale = Math.max(scale, Math.hypot(p[j][0] - p[i][0], p[j][1] - p[i][1]));
        }

        if (scale == 0) {
            return false;
        }

        for (let i = 0; i < n; i++) {
            let c = cross(p[i], p[(i + 1) % n], p[(i + 2) % n]);

            if (Math.abs(c) < 1e-9 * scale * scale) {
                return false;
            }

            let s = c > 0 ? 1 : -1;

            if (sign && s != sign) {
                return false;
            }

            sign = s;
        }

        return true;
    };

    g.convexOverlap = function (a, b, tolerance) {
        for (let poly of [ a, b ]) {
            let n = poly.length;

            for (let i = 0; i < n; i++) {
                let j = (i + 1) % n;
                let ax = poly[i][1] - poly[j][1];
                let ay = poly[j][0] - poly[i][0];
                let len = Math.hypot(ax, ay);

                if (len == 0) {
                    continue;
                }

                ax /= len;
                ay /= len;

                let minA = Infinity, minB = Infinity, maxA = -Infinity, maxB = -Infinity;

                for (let q of a) {
                    let v = q[0] * ax + q[1] * ay;
                    minA = Math.min(minA, v);
                    maxA = Math.max(maxA, v);
                }

                for (let q of b) {
                    let v = q[0] * ax + q[1] * ay;
                    minB = Math.min(minB, v);
                    maxB = Math.max(maxB, v);
                }

                if (Math.min(maxA, maxB) - Math.max(minA, minB) <= tolerance) {
                    return false;
                }
            }
        }

        return true;
    };

    function collinear(a, b, c) {
        let scale = Math.max(
            (a[0] - b[0]) ** 2 + (a[1] - b[1]) ** 2,
            (b[0] - c[0]) ** 2 + (b[1] - c[1]) ** 2,
            (c[0] - a[0]) ** 2 + (c[1] - a[1]) ** 2
        );

        return scale == 0 || Math.abs(cross(a, b, c)) < 1e-3 * scale;
    }

    /*
        geometry of the single row of spaces of a convex metric quadrilateral (layout version 1), shared by
        layout() and freeSpaces(): local coordinates s along the base edge and t inwards, the footprint f of
        a space whose corner is at the origin, the pitch between neighbouring spaces, range(t0) - the
        interval [ lo, hi ] of the start s of a space at depth t0 that keeps it inside the area (with tolerance)
        shift - the deepest t0 at which a space may still fit and slide - ds / dt of a space moved deeper along
        the sides it shares with its neighbours
    */

    function rowGeometry(points, baseEdge, width, length, angle) {
        let p = [];
        for (let i = 0; i < 4; i++) {
            p.push(points[(baseEdge + i) % 4]);
        }

        let ex = p[1][0] - p[0][0];
        let ey = p[1][1] - p[0][1];
        let el = Math.hypot(ex, ey);

        if (el == 0 || width <= 0 || length <= 0) {
            return null;
        }

        ex /= el;
        ey /= el;

        let nx = -ey;
        let ny = ex;

        let cx = (p[0][0] + p[1][0] + p[2][0] + p[3][0]) / 4;
        let cy = (p[0][1] + p[1][1] + p[2][1] + p[3][1]) / 4;

        if ((cx - p[0][0]) * nx + (cy - p[0][1]) * ny < 0) {
            nx = -nx;
            ny = -ny;
        }

        // area in local coordinates: s along the base edge, t inwards
        let q = p.map(v => [
            (v[0] - p[0][0]) * ex + (v[1] - p[0][1]) * ey,
            (v[0] - p[0][0]) * nx + (v[1] - p[0][1]) * ny,
        ]);

        let qc = [ (q[0][0] + q[1][0] + q[2][0] + q[3][0]) / 4, (q[0][1] + q[1][1] + q[2][1] + q[3][1]) / 4 ];

        // inward half-planes: m . (X - Q_i) >= -tolerance
        let planes = [];
        for (let i = 0; i < 4; i++) {
            let a = q[i];
            let b = q[(i + 1) % 4];
            let mx = a[1] - b[1];
            let my = b[0] - a[0];
            let ml = Math.hypot(mx, my);

            if (ml == 0) {
                continue;
            }

            mx /= ml;
            my /= ml;

            if ((qc[0] - a[0]) * mx + (qc[1] - a[1]) * my < 0) {
                mx = -mx;
                my = -my;
            }

            planes.push([ mx, my, a ]);
        }

        let theta = angle * Math.PI / 180;
        let sin = Math.sin(theta);
        let cos = Math.cos(theta);

        // footprint: width axis (cos, -sin), long axis (sin, cos)
        let f = [
            [ 0, 0 ],
            [ width * cos, -width * sin ],
            [ width * cos + length * sin, -width * sin + length * cos ],
            [ length * sin, length * cos ],
        ];

        let fs = Math.min(...f.map(v => v[0]));
        let ft = Math.min(...f.map(v => v[1]));

        f = f.map(v => [ v[0] - fs, v[1] - ft ]);

        let depth = Math.max(...f.map(v => v[1]));

        let pitchWidth = Math.abs(cos) > 1e-9 ? width / Math.abs(cos) : Infinity;
        let pitchLength = Math.abs(sin) > 1e-9 ? length / Math.abs(sin) : Infinity;
        let pitch = Math.min(pitchWidth, pitchLength);

        // neighbouring spaces share their sides along the long axis (sin, cos) or along the width axis (cos, -sin):
        // a space moved deeper slides along them, s changes by slide per metre of t
        let slide = pitchWidth <= pitchLength ? sin / cos : -cos / sin;

        let tMax = Math.max(...q.map(v => v[1]));
        let tolerance = g.EDGE_TOLERANCE;

        // range [ lo, hi ] of the start s of a space whose row begins at depth t0, null when nothing fits
        let range = t0 => {
            if (t0 + depth > tMax + tolerance) {
                return null;
            }

            let lo = -Infinity;
            let hi = Infinity;

            for (let [ mx, my, a ] of planes) {
                for (let v of f) {
                    let rhs = -tolerance - (mx * (v[0] - a[0]) + my * (v[1] + t0 - a[1]));

                    if (mx > 1e-12) {
                        lo = Math.max(lo, rhs / mx);
                    } else if (mx < -1e-12) {
                        hi = Math.min(hi, rhs / mx);
                    } else if (rhs > 1e-12) {
                        lo = Infinity;
                    }
                }
            }

            if (!isFinite(lo) || !isFinite(hi) || hi < lo) {
                return null;
            }

            return [ lo, hi ];
        };

        return {
            f,
            depth,
            pitch,
            range,
            shift: tMax + tolerance - depth,
            slide,
            local: (x, y) => [ (x - p[0][0]) * ex + (y - p[0][1]) * ey, (x - p[0][0]) * nx + (y - p[0][1]) * ny ],
            space: (s0, t0 = 0) => f.map(v => [ p[0][0] + (v[0] + s0) * ex + (v[1] + t0) * nx, p[0][1] + (v[0] + s0) * ey + (v[1] + t0) * ny ]),
        };
    }

    /*
        layout of parking spaces inside a convex metric quadrilateral, see geometry::layout():
        one row along the base edge, multipleRows tells that the area is deep enough for a second row
    */

    g.layout = function (points, baseEdge, width, length, angle, withSpaces) {
        let result = { capacity: 0, multipleRows: false, spaces: [] };
        let r = rowGeometry(points, baseEdge, width, length, angle);

        if (!r) {
            return result;
        }

        result.multipleRows = r.range(r.depth) !== null;

        let row = r.range(0);

        if (row === null) {
            return result;
        }

        let [ lo, hi ] = row;

        let count = Math.floor((hi - lo) / r.pitch + 1e-9) + 1;
        result.capacity = count;

        if (withSpaces) {
            for (let slot = 0; slot < count; slot++) {
                result.spaces.push({ slot, points: r.space(lo + slot * r.pitch) });
            }
        }

        return result;
    };

    function isNumber(v) {
        return typeof v === "number" && isFinite(v);
    }

    function isFlag(v) {
        return v === true || v === false || v === 0 || v === 1;
    }

    function has(o, key) {
        return o !== null && typeof o === "object" && Object.prototype.hasOwnProperty.call(o, key);
    }

    function get(o, key) {
        return has(o, key) ? o[key] : null;
    }

    /*
        validate and analyze a parking configuration, see geometry::analyze()

        problem severities: "error" - can't be saved, "notReady" - saved but not ready for processing, "warning" - informational,
        readiness doesn't depend on the enabled flag: a configuration is processed when it is both ready and enabled
    */

    g.analyze = function (input, options) {
        let problems = [];

        function problem(code, severity, area) {
            let p = { code, severity };

            if (typeof area !== "undefined" && area !== null) {
                p.area = area;
            }

            problems.push(p);
        }

        let result = {
            enabled: 1,
            detectionImageSize: g.DEFAULT_DETECTION_IMAGE_SIZE,
            confidenceThreshold: g.DEFAULT_CONFIDENCE_THRESHOLD,
            processingInterval: null,
            frameWidth: 0,
            frameHeight: 0,
            calibration: null,
            homography: null,
            calibrationError: null,
            areas: [],
        };

        if (input === null || typeof input !== "object" || Array.isArray(input)) {
            problem("invalidConfig", "error");
            return finish(result, problems);
        }

        if (has(input, "enabled")) {
            if (isFlag(input.enabled)) {
                result.enabled = Number(input.enabled);
            } else {
                problem("invalidValue", "error");
            }
        }

        if (has(input, "detectionImageSize")) {
            let v = input.detectionImageSize;

            if (!Number.isInteger(v) || v < g.DETECTION_IMAGE_SIZE[0] || v > g.DETECTION_IMAGE_SIZE[1]) {
                problem("invalidDetectionImageSize", "error");
            } else {
                result.detectionImageSize = v;
            }
        }

        if (has(input, "confidenceThreshold")) {
            let v = input.confidenceThreshold;

            if (!isNumber(v) || round(v, 2) < g.CONFIDENCE_THRESHOLD[0] || round(v, 2) > g.CONFIDENCE_THRESHOLD[1]) {
                problem("invalidConfidenceThreshold", "error");
            } else {
                result.confidenceThreshold = round(v, 2);
            }
        }

        if (has(input, "processingInterval") && input.processingInterval !== null) {
            let v = input.processingInterval;
            let min = Math.max(1, Math.trunc(Number((options || {}).minInterval)) || 0);

            if (!Number.isInteger(v) || v < min || v > g.MAX_PROCESSING_INTERVAL) {
                problem("invalidProcessingInterval", "error");
            } else {
                result.processingInterval = v;
            }
        }

        let fw = input.frameWidth;
        let fh = input.frameHeight;

        if (!Number.isInteger(fw) || !Number.isInteger(fh) || fw < 16 || fh < 16 || fw > 16384 || fh > 16384) {
            problem("invalidFrame", "error");
            return finish(result, problems);
        }

        result.frameWidth = fw;
        result.frameHeight = fh;

        let diagonal = Math.hypot(fw, fh);

        let pixel = (u, v) => [ u * fw, v * fh ];
        let isUV = (u, v) => isNumber(u) && isNumber(v) && u >= 0 && u <= 1 && v >= 0 && v <= 1;

        // calibration

        let h = null;
        let calibration = has(input, "calibration") ? input.calibration : null;

        if (calibration !== null && typeof calibration !== "undefined") {
            let isObject = typeof calibration === "object" && !Array.isArray(calibration);
            let points = isObject ? get(calibration, "points") : null;
            let checks = isObject && has(calibration, "checks") ? calibration.checks : [];

            if (!isObject || !Array.isArray(points) || !Array.isArray(checks)) {
                problem("invalidCalibration", "error");
            } else if (points.length < 4 || points.length > g.MAX_CALIBRATION_POINTS || checks.length > g.MAX_CALIBRATION_CHECKS) {
                problem("calibrationPointsCount", "error");
            } else {
                let valid = true;
                let cp = [];

                for (let point of points) {
                    if (!isUV(get(point, "u"), get(point, "v")) || !isNumber(get(point, "x")) || !isNumber(get(point, "y")) || Math.abs(point.x) > g.MAX_PLANE_COORDINATE || Math.abs(point.y) > g.MAX_PLANE_COORDINATE) {
                        valid = false;
                        break;
                    }

                    cp.push({ u: point.u, v: point.v, x: point.x, y: point.y });
                }

                let cc = [];

                for (let check of checks) {
                    if (!isUV(get(check, "u1"), get(check, "v1")) || !isUV(get(check, "u2"), get(check, "v2")) || !isNumber(get(check, "length")) || check.length <= 0 || check.length > g.MAX_PLANE_COORDINATE) {
                        valid = false;
                        break;
                    }

                    cc.push({ u1: check.u1, v1: check.v1, u2: check.u2, v2: check.v2, length: check.length });
                }

                if (!valid) {
                    problem("invalidCalibration", "error");
                } else {
                    let src = cp.map(point => pixel(point.u, point.v));
                    let dst = cp.map(point => [ point.x, point.y ]);
                    let n = src.length;
                    let degenerate = false;

                    for (let i = 0; i < n && !degenerate; i++) {
                        for (let j = i + 1; j < n; j++) {
                            if (Math.hypot(src[i][0] - src[j][0], src[i][1] - src[j][1]) < g.MIN_VERTEX_DISTANCE * diagonal || (dst[i][0] == dst[j][0] && dst[i][1] == dst[j][1])) {
                                degenerate = true;
                                break;
                            }
                        }
                    }

                    if (!degenerate && n == 4) {
                        for (let [ a, b, c ] of [ [ 0, 1, 2 ], [ 0, 1, 3 ], [ 0, 2, 3 ], [ 1, 2, 3 ] ]) {
                            if (collinear(src[a], src[b], src[c]) || collinear(dst[a], dst[b], dst[c])) {
                                degenerate = true;
                                break;
                            }
                        }
                    }

                    if (!degenerate) {
                        let uv = cp.map(point => [ point.u, point.v ]);
                        h = g.homography(uv, dst);

                        if (h) {
                            for (let point of uv) {
                                let [ x, , w ] = g.apply(h, point[0], point[1]);

                                if (w <= 0 || x === null) {
                                    h = null;
                                    break;
                                }
                            }
                        }

                        if (!h) {
                            degenerate = true;
                        }
                    }

                    if (degenerate) {
                        problem("calibrationDegenerate", "error");
                    } else {
                        result.calibration = { model: "homography", points: cp, checks: cc };
                        result.homography = h;

                        let hi = g.invert(h);

                        if (n > 4 && hi) {
                            let sum = 0;

                            for (let point of cp) {
                                let [ u, v ] = g.apply(hi, point.x, point.y);
                                let e = pixel(u, v);
                                let o = pixel(point.u, point.v);
                                sum += (e[0] - o[0]) ** 2 + (e[1] - o[1]) ** 2;
                            }

                            if (Math.sqrt(sum / n) > g.MAX_REPROJECTION_FRACTION * diagonal) {
                                problem("calibrationReprojection", "notReady");
                            }
                        }

                        let error = null;

                        for (let check of cc) {
                            let a = g.apply(h, check.u1, check.v1);
                            let b = g.apply(h, check.u2, check.v2);

                            if (a[2] <= 0 || b[2] <= 0 || a[0] === null || b[0] === null) {
                                error = Infinity;
                            } else {
                                error = Math.max(Number(error), Math.abs(Math.hypot(a[0] - b[0], a[1] - b[1]) - check.length) / check.length);
                            }
                        }

                        if (error !== null) {
                            if (error > g.CHECK_FAILURE) {
                                problem("calibrationCheckFailed", "notReady");
                            } else if (error > g.CHECK_WARNING) {
                                problem("calibrationCheckWarning", "warning");
                            }

                            result.calibrationError = isFinite(error) ? round(error, 6) : null;
                        }
                    }
                }
            }
        }

        // areas

        let areas = has(input, "areas") ? input.areas : [];

        if (!Array.isArray(areas)) {
            problem("invalidAreas", "error");
            areas = [];
        }

        if (areas.length > g.MAX_AREAS) {
            problem("tooManyAreas", "error");
            areas = [];
        }

        let ids = {};
        let polygons = [];

        areas.forEach((area, index) => {
            if (area === null || typeof area !== "object" || Array.isArray(area)) {
                problem("invalidArea", "error", index);
                return;
            }

            let id = has(area, "parkingAreaId") ? area.parkingAreaId : null;

            if (id !== null && (!Number.isInteger(id) || id <= 0 || ids[id])) {
                problem("invalidArea", "error", index);
                return;
            }

            if (id !== null) {
                ids[id] = true;
            }

            let name = has(area, "name") ? area.name : "";

            if (typeof name !== "string" || [ ...name ].length > 64) {
                problem("invalidAreaName", "error", index);
                return;
            }

            let enabled = has(area, "enabled") ? area.enabled : 1;

            if (!isFlag(enabled)) {
                problem("invalidValue", "error", index);
                return;
            }

            let baseEdge = has(area, "baseEdge") ? area.baseEdge : 0;

            if (!Number.isInteger(baseEdge) || baseEdge < 0 || baseEdge > 3) {
                problem("invalidBaseEdge", "error", index);
                return;
            }

            let params = {};
            let valid = true;

            for (let [ key, range ] of [ [ "spaceWidth", g.SPACE_WIDTH ], [ "spaceLength", g.SPACE_LENGTH ], [ "spaceAngle", g.SPACE_ANGLE ] ]) {
                let v = get(area, key);

                if (!isNumber(v) || round(v, 2) < range[0] || round(v, 2) > range[1]) {
                    problem("invalid" + key.charAt(0).toUpperCase() + key.slice(1), "error", index);
                    valid = false;
                } else {
                    params[key] = round(v, 2);
                }
            }

            if (!valid) {
                return;
            }

            let ip = get(area, "imagePoints");

            if (!Array.isArray(ip) || ip.length != 4) {
                problem("areaPointsCount", "error", index);
                return;
            }

            let uv = [];

            for (let point of ip) {
                if (!isUV(get(point, "u"), get(point, "v"))) {
                    valid = false;
                    break;
                }

                uv.push([ point.u, point.v ]);
            }

            if (!valid) {
                problem("invalidPoint", "error", index);
                return;
            }

            // canonical order: clockwise on screen, the base edge and the angle keep their physical meaning
            let px = uv.map(point => pixel(point[0], point[1]));

            if (g.signedArea(px) < 0) {
                uv = [ uv[0], uv[3], uv[2], uv[1] ];
                px = [ px[0], px[3], px[2], px[1] ];
                baseEdge = (3 - baseEdge + 4) % 4;
                params.spaceAngle = -params.spaceAngle + 0;
            }

            for (let i = 0; i < 4; i++) {
                for (let j = i + 1; j < 4; j++) {
                    if (Math.hypot(px[i][0] - px[j][0], px[i][1] - px[j][1]) < g.MIN_VERTEX_DISTANCE * diagonal) {
                        valid = false;
                    }
                }
            }

            if (!valid) {
                problem("areaDuplicateVertices", "error", index);
                return;
            }

            if (g.selfIntersects(px)) {
                problem("areaSelfIntersecting", "error", index);
                return;
            }

            if (!g.isConvex(px)) {
                problem("areaNotConvex", "error", index);
                return;
            }

            if (Math.abs(g.signedArea(px)) < g.MIN_AREA_FRACTION * fw * fh) {
                problem("areaTooSmall", "error", index);
                return;
            }

            for (let other in polygons) {
                if (g.convexOverlap(px, polygons[other], g.OVERLAP_TOLERANCE_PX)) {
                    problem("areasOverlap", "error", index);
                    break;
                }
            }

            polygons[index] = px;

            if (params.spaceLength < params.spaceWidth) {
                problem("spaceLengthLessThanWidth", "warning", index);
            }

            let item = {
                index,
                parkingAreaId: id,
                name,
                enabled: Number(enabled),
                imagePoints: uv.map(point => ({ u: point[0], v: point[1] })),
                baseEdge,
                spaceWidth: params.spaceWidth,
                spaceLength: params.spaceLength,
                spaceAngle: params.spaceAngle,
                capacity: null,
                metricPoints: null,
            };

            if (h) {
                let metric = [];

                for (let point of uv) {
                    let [ x, y, w ] = g.apply(h, point[0], point[1]);

                    if (w <= 0 || x === null) {
                        metric = false;
                        break;
                    }

                    metric.push([ x, y ]);
                }

                if (metric === false) {
                    problem("areaBeyondHorizon", "notReady", index);
                } else if (g.selfIntersects(metric) || !g.isConvex(metric) || Math.abs(g.signedArea(metric)) > g.MAX_AREA_SQUARE) {
                    problem("areaMetricInvalid", "notReady", index);
                } else {
                    item.metricPoints = metric.map(point => ({ x: point[0], y: point[1] }));
                    let layout = g.layout(metric, baseEdge, params.spaceWidth, params.spaceLength, params.spaceAngle);
                    item.capacity = layout.capacity;

                    if (item.capacity == 0) {
                        problem("areaNoSpaces", "warning", index);
                    }

                    if (layout.multipleRows) {
                        problem("areaMultipleRows", "warning", index);
                    }

                    let cu = (uv[0][0] + uv[1][0] + uv[2][0] + uv[3][0]) / 4;
                    let cv = (uv[0][1] + uv[1][1] + uv[2][1] + uv[3][1]) / 4;
                    let c = g.apply(h, cu, cv);
                    let du = g.apply(h, cu + 1 / fw, cv);
                    let dv = g.apply(h, cu, cv + 1 / fh);

                    if (du[0] !== null && dv[0] !== null && Math.max(Math.hypot(du[0] - c[0], du[1] - c[1]), Math.hypot(dv[0] - c[0], dv[1] - c[1])) > g.MAX_METRES_PER_PIXEL) {
                        problem("areaLowResolution", "warning", index);
                    }
                }
            }

            result.areas.push(item);
        });

        return finish(result, problems);
    };

    function finish(result, problems) {
        let hasErrors = false;
        let notReady = false;

        for (let p of problems) {
            hasErrors = hasErrors || p.severity == "error";
            notReady = notReady || p.severity != "warning";
        }

        if (!hasErrors) {
            if (!result.homography) {
                problems.push({ code: "noCalibration", severity: "notReady" });
                notReady = true;
            }

            let usable = result.areas.some(area => area.enabled && area.capacity);

            if (result.homography && !usable) {
                problems.push({ code: "noUsableAreas", severity: "notReady" });
                notReady = true;
            }
        }

        result.problems = problems;
        result.valid = !hasErrors;
        result.ready = !hasErrors && !notReady;

        return result;
    }

    /*
        metric footprints of the spaces of an analyzed area projected back to normalized frame coordinates
    */

    g.spaces = function (area, h) {
        let hi = h ? g.invert(h) : null;

        if (!hi || !area.metricPoints) {
            return [];
        }

        let metric = area.metricPoints.map(p => [ p.x, p.y ]);

        return g.layout(metric, area.baseEdge, area.spaceWidth, area.spaceLength, area.spaceAngle, true).spaces.map(space => ({
            slot: space.slot,
            points: space.points.map(p => {
                let [ u, v ] = g.apply(hi, p[0], p[1]);
                return { u, v };
            }),
        }));
    };

    /*
        interval of starts s0 at which the footprint f shifted by (s0, 0) intersects the convex polygon v
        (both in local row coordinates): the open interval ( a, b ) or null, touching doesn't count;
        separating axis test over the edge normals of both polygons
    */

    function blockedStarts(f, v) {
        let a = -Infinity;
        let b = Infinity;

        for (let poly of [ f, v ]) {
            let n = poly.length;

            for (let i = 0; i < n; i++) {
                let j = (i + 1) % n;
                let ax = poly[i][1] - poly[j][1];
                let ay = poly[j][0] - poly[i][0];
                let len = Math.hypot(ax, ay);

                if (len == 0) {
                    continue;
                }

                ax /= len;
                ay /= len;

                let fp = f.map(q => q[0] * ax + q[1] * ay);
                let vp = v.map(q => q[0] * ax + q[1] * ay);
                let fMin = Math.min(...fp), fMax = Math.max(...fp);
                let vMin = Math.min(...vp), vMax = Math.max(...vp);

                // projections overlap when fMin + s0 * ax < vMax and fMax + s0 * ax > vMin
                if (Math.abs(ax) < 1e-12) {
                    if (fMin >= vMax - 1e-9 || fMax <= vMin + 1e-9) {
                        return null;
                    }
                    continue;
                }

                let x1 = (vMax - fMin) / ax;
                let x2 = (vMin - fMax) / ax;

                a = Math.max(a, Math.min(x1, x2));
                b = Math.min(b, Math.max(x1, x2));
            }
        }

        return b - a > 1e-9 ? [ a, b ] : null;
    }

    /*
        closed intervals of the starts s0 in [ lo, hi ] at which the footprint f (shifted by (s0, 0)) intersects
        none of the convex polygons (local row coordinates)
    */

    function freeStarts(f, polygons, lo, hi) {
        let blocked = polygons.
            map(v => blockedStarts(f, v)).
            filter(b => b && b[1] > lo && b[0] < hi).
            sort((x, y) => x[0] - y[0]);

        let gaps = [];
        let from = lo;

        for (let [ a, b ] of blocked) {
            if (a >= from) {
                gaps.push([ from, Math.min(a, hi) ]);
            }
            from = Math.max(from, b);
        }

        if (from <= hi) {
            gaps.push([ from, hi ]);
        }

        return gaps;
    }

    /*
        free spaces of a revision snapshot on one processed frame

        snapshot: revision snapshot (calibration.homography, confidenceThreshold, areas with metricPoints and space parameters),
        detections: FALPRS detectVehiclesObb objects { classId, className, confidence, box, angle, corners: [ x1, y1, ..., x4, y4 ] }
        with corners in pixels of the processed frame, frame: { width, height } of that frame in pixels

        detections below the confidence threshold are ignored (any class counts), the corners of every other one are mapped
        to the parking plane as if they lay on it (vehicle footprint); along the single row of every enabled area a start
        of a space (within the starts of the layout row on the base edge) is free when the space, moved deeper along the
        sides it shares with its neighbours to some depth t0 up to the deepest fitting one (sampled every ROW_SHIFT_STEP),
        lies inside the area and intersects no footprint; the free starts form gaps, each gap holds the most spaces of
        the layout pitch that fit, as one block centred in it; neighbouring blocks keep at least one pitch between starts;
        every space takes its own depth, the nearest to the base edge at which it is free; neighbours stay on the
        opposite sides of their shared side at any depths, so spaces never overlap and an area never has more free
        spaces than its capacity, an empty area has exactly its capacity

        returns [ { parkingAreaId, spaces: [ [ { x, y } x 4 ] ] } ] for enabled areas, points in pixels of the frame
    */

    g.freeSpaces = function (snapshot, detections, frame) {
        let h = snapshot && snapshot.calibration ? snapshot.calibration.homography : null;
        let inverse = h ? g.invert(h) : null;

        if (!inverse || !frame || !(frame.width > 0) || !(frame.height > 0)) {
            return [];
        }

        let threshold = isNumber(snapshot.confidenceThreshold) ? snapshot.confidenceThreshold : g.DEFAULT_CONFIDENCE_THRESHOLD;

        // vehicle footprints on the parking plane
        let footprints = [];

        for (let d of detections || []) {
            if (!d || !isNumber(d.confidence) || d.confidence < threshold || !Array.isArray(d.corners) || d.corners.length != 8 || !d.corners.every(isNumber)) {
                continue;
            }

            let footprint = [];

            for (let i = 0; i < 4; i++) {
                let [ x, y, w ] = g.apply(h, d.corners[2 * i] / frame.width, d.corners[2 * i + 1] / frame.height);

                // a corner beyond the horizon of the calibration is not on the parking plane, nor is the vehicle
                if (x === null || w <= 0) {
                    footprint = null;
                    break;
                }

                footprint.push([ x, y ]);
            }

            if (footprint) {
                footprints.push(footprint);
            }
        }

        let pixels = points => points.map(([ x, y ]) => {
            let [ u, v ] = g.apply(inverse, x, y);
            return { x: u * frame.width, y: v * frame.height };
        });

        let result = [];

        for (let area of snapshot.areas || []) {
            if (!area.enabled || !area.metricPoints) {
                continue;
            }

            let item = { parkingAreaId: area.parkingAreaId, spaces: [] };
            result.push(item);

            let r = rowGeometry(area.metricPoints.map(p => [ p.x, p.y ]), area.baseEdge, area.spaceWidth, area.spaceLength, area.spaceAngle);
            let row = r ? r.range(0) : null;

            if (!row) {
                continue;
            }

            let [ lo, hi ] = row;

            let local = footprints.map(fp => fp.map(([ x, y ]) => r.local(x, y)));
            let shifted = (polygons, t0) => polygons.map(v => v.map(([ s, t ]) => [ s, t - t0 ]));

            // free starts at every sampled depth, nearest to the base edge first; a start s0 is the start on the base edge,
            // at depth t0 the space begins at s0 + t0 * slide
            let depths = [];
            let steps = Math.max(0, Math.ceil(r.shift / g.ROW_SHIFT_STEP - 1e-9));

            for (let j = 0; j <= steps; j++) {
                let t0 = Math.max(0, Math.min(j * g.ROW_SHIFT_STEP, r.shift));
                let range = r.range(t0);
                let ds = t0 * r.slide;

                if (range && Math.max(lo + ds, range[0]) <= Math.min(hi + ds, range[1])) {
                    let gaps = freeStarts(r.f, shifted(local, t0), Math.max(lo + ds, range[0]), Math.min(hi + ds, range[1]));
                    depths.push({ t0, gaps: gaps.map(([ a, b ]) => [ a - ds, b - ds ]) });
                }
            }

            // gaps: starts free at any depth
            let gaps = [];

            for (let [ a, b ] of depths.flatMap(d => d.gaps).sort((x, y) => x[0] - y[0])) {
                if (gaps.length && a <= gaps[gaps.length - 1][1] + 1e-9) {
                    gaps[gaps.length - 1][1] = Math.max(gaps[gaps.length - 1][1], b);
                } else {
                    gaps.push([ a, b ]);
                }
            }

            let depthOf = s0 => {
                let d = depths.find(({ gaps }) => gaps.some(([ a, b ]) => s0 >= a - 1e-9 && s0 <= b + 1e-9));
                return d ? d.t0 : null;
            };

            let last = -Infinity;

            for (let [ g0, g1 ] of gaps) {
                g0 = Math.max(g0, last + r.pitch);

                if (g1 - g0 < -1e-9) {
                    continue;
                }

                let count = Math.floor(Math.max(0, g1 - g0) / r.pitch + 1e-9) + 1;
                let start = g0 + Math.max(0, (g1 - g0) - (count - 1) * r.pitch) / 2;

                for (let k = 0; k < count; k++) {
                    let s0 = start + k * r.pitch;
                    let t0 = depthOf(s0);

                    if (t0 !== null) {
                        item.spaces.push(pixels(r.space(s0 + t0 * r.slide, t0)));
                    }
                }

                last = start + (count - 1) * r.pitch;
            }
        }

        return result;
    };

    return g;
})();
