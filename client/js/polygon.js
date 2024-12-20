function polygon(id, image, fallback, polygon, callback) {
    /*
        jPolygon - a ligthweigth javascript library to draw polygons over HTML5 canvas images.
        Project URL: http://www.matteomattei.com/projects/jpolygon
        Author: Matteo Mattei <matteo.mattei@gmail.com>
        Version: 1.0
        License: MIT License
    */

    let perimeter = [];

    let complete = false;

    $("#" + id).html("").append(`<canvas id="${id}-canvas" style="cursor: crosshair" oncontextmenu="return false;">Your browser does not support the HTML5 canvas tag</canvas>`);

    let canvas = document.getElementById(id + "-canvas");
    let ctx;

    function toPercentX(x) {
        return Math.round((x / canvas.width) * 10000) / 100;
    }

    function toPercentY(y) {
        return Math.round((y / canvas.height) * 10000) / 100;
    }

    function fromPercentX(x) {
        return Math.round(canvas.width * x / 100);
    }

    function fromPercentY(y) {
        return Math.round(canvas.height * y / 100);
    }

    function line_intersects(p0, p1, p2, p3) {
        let s1_x, s1_y, s2_x, s2_y;

        s1_x = p1['x'] - p0['x'];
        s1_y = p1['y'] - p0['y'];
        s2_x = p3['x'] - p2['x'];
        s2_y = p3['y'] - p2['y'];

        let s, t;

        s = (-s1_y * (p0['x'] - p2['x']) + s1_x * (p0['y'] - p2['y'])) / (-s2_x * s1_y + s1_x * s2_y);
        t = ( s2_x * (p0['y'] - p2['y']) - s2_y * (p0['x'] - p2['x'])) / (-s2_x * s1_y + s1_x * s2_y);

        if (s >= 0 && s <= 1 && t >= 0 && t <= 1) {
            // Collision detected
            return true;
        }

        return false; // No collision
    }

    function point(x, y) {
        ctx.fillStyle = "green";
        ctx.strokeStyle = "green";
        ctx.fillRect(x - 4, y - 4, 8, 8);
        ctx.moveTo(x, y);
    }

    function draw(end) {
        ctx.lineWidth = 3;
        ctx.strokeStyle = "green";
        ctx.lineCap = "square";
        ctx.beginPath();

        for (let i = 0; i < perimeter.length; i++) {
            if (i == 0) {
                ctx.moveTo(perimeter[i]['x'], perimeter[i]['y']);
                end || point(perimeter[i]['x'], perimeter[i]['y']);
            } else {
                ctx.lineTo(perimeter[i]['x'], perimeter[i]['y']);
                end || point(perimeter[i]['x'], perimeter[i]['y']);
            }
        }

        if (end) {
            ctx.lineTo(perimeter[0]['x'], perimeter[0]['y']);
            ctx.closePath();
            ctx.fillStyle = 'rgba(0, 255, 0, 0.5)';
            ctx.fill();
            ctx.strokeStyle = 'green';
            complete = true;
            if (typeof callback == "function") {
                let p = [];

                for (let i in perimeter) {
                    p.push({
                        x: toPercentX(perimeter[i].x),
                        y: toPercentY(perimeter[i].y),
                    });
                }

                callback(p);
            }
        }

        ctx.stroke();
    }

    function check_intersect(x, y) {
        if (perimeter.length < 4) {
            return false;
        }

        let p0 = [];
        let p1 = [];
        let p2 = [];
        let p3 = [];

        p2['x'] = perimeter[perimeter.length - 1]['x'];
        p2['y'] = perimeter[perimeter.length - 1]['y'];
        p3['x'] = x;
        p3['y'] = y;

        for(let i = 0; i < perimeter.length - 1; i++) {
            p0['x'] = perimeter[i]['x'];
            p0['y'] = perimeter[i]['y'];
            p1['x'] = perimeter[i+1]['x'];
            p1['y'] = perimeter[i+1]['y'];

            if(p1['x'] == p2['x'] && p1['y'] == p2['y']) {
                continue;
            }

            if (p0['x'] == p3['x'] && p0['y'] == p3['y']) {
                continue;
            }

            if (line_intersects(p0,p1,p2,p3) == true) {
                return true;
            }
        }

        return false;
    }

    function point_it(event) {
        if (complete) {
            return false;
        }

        let rect, x, y;

        if (event.ctrlKey || event.which === 3 || event.button === 2) {
            if (perimeter.length <= 2) {
                return false;
            }

            x = perimeter[0]['x'];
            y = perimeter[0]['y'];

            if (check_intersect(x,y)) {
                return false;
            }

            draw(true);
            event.preventDefault();

            return false;
        } else {
            rect = canvas.getBoundingClientRect();

            x = event.clientX - rect.left;
            y = event.clientY - rect.top;

            if (perimeter.length > 0 && x == perimeter[perimeter.length - 1]['x'] && y == perimeter[perimeter.length - 1]['y']) {
                return false;
            }

            if (check_intersect(x, y)) {
                return false;
            }

            perimeter.push({x, y});
            draw(false);

            return false;
        }
    }

    function start() {
        function load(url) {
            let img = new Image();
            img.src = url;

            img.onload = function() {
                canvas.height = img.height / img.width * canvas.width;
                ctx = canvas.getContext("2d");
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                $("#" + id).css("width", canvas.width + "px").css("height", canvas.height + "px");

                if (polygon && polygon.length) {
                    perimeter = [];

                    for (let i in polygon) {
                        perimeter.push({
                            x: fromPercentX(polygon[i].x),
                            y: fromPercentY(polygon[i].y),
                        });
                    }
                    draw(true);
                }
            }
        }

        image(result => {
            load(result);
        });

        load(fallback);
    }

    $("#" + id).off("mousedown").on("mousedown", point_it);

    canvas.width = $("#" + id).parent().width();

    ctx = undefined;
    perimeter = [];
    complete = false;

    start();
}