function rectangles(id, image, fallback, rectangles, callback) {
    $("#" + id).
    css("position", "relative").html("").
    append(`<canvas id="${id}-canvas" style="cursor: crosshair; position: absolute; left: 0; top: 0" oncontextmenu="return false;">Your browser does not support the HTML5 canvas tag</canvas>`).
    append(`<canvas id="${id}-drawer" style="cursor: crosshair; position: absolute; left: 0; top: 0" oncontextmenu="return false;">Your browser does not support the HTML5 canvas tag</canvas>`);

    let old_url = false;

    let canvas = document.getElementById(id + "-canvas");
    let ctx = canvas.getContext("2d");

    let drawer = document.getElementById(id + "-drawer");
    let dtx = drawer.getContext("2d");

    let canvasOffset = drawer.getBoundingClientRect();
    let offsetX = canvasOffset.left;
    let offsetY = canvasOffset.top;

    let isDown = false;

    let startX;
    let startY;

    let x = false;
    let w = false;
    let y = false;
    let h = false;

    let rectangless = [];

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

    function handleMouseDown(e) {
        e.preventDefault();
        e.stopPropagation();

        startX = parseInt(e.clientX - offsetX);
        startY = parseInt(e.clientY - offsetY);

        isDown = true;
    }

    function checkIntersect() {
        for (let i in rectangless) {
            if (
                (rectangless[i].x < x && x < rectangless[i].x + rectangless[i].w && rectangless[i].y < y && y < rectangless[i].y + rectangless[i].h)
                ||
                (rectangless[i].x < x + w && x + w < rectangless[i].x + rectangless[i].w && rectangless[i].y < y + h && y + h < rectangless[i].y + rectangless[i].h)
                ||
                (x < rectangless[i].x && rectangless[i].x < x + w && y < rectangless[i].y && rectangless[i].y < y + h)
                ||
                (x < rectangless[i].x + rectangless[i].w && rectangless[i].x + rectangless[i].w < x + w && y < rectangless[i].y + rectangless[i].h && rectangless[i].y + rectangless[i].h < y + h)
            ) {
                return false;
            }
        }

        return true;
    }

    function handleMouseUp(e) {
        e.preventDefault();
        e.stopPropagation();

        isDown = false;

        dtx.clearRect(0, 0, drawer.width, drawer.height);

        if (x !== false && checkIntersect()) {
            ctx.lineWidth = 3;
            ctx.strokeStyle = "green";
            ctx.lineCap = "square";
            ctx.fillStyle = 'rgba(0, 255, 0, 0.5)';
            ctx.fillRect(x, y, w, h);
            ctx.strokeRect(x, y, w, h);
            rectangless.push({x, y, w, h});
            if (typeof callback == "function") {
                let p = [];
                for (let i in rectangless) {
                    p.push({
                        x: toPercentX((rectangless[i].w > 0) ? rectangless[i].x : (rectangless[i].x + rectangless[i].w)),
                        w: toPercentX((rectangless[i].w > 0) ? rectangless[i].w : -rectangless[i].w),
                        y: toPercentY((rectangless[i].h > 0) ? rectangless[i].y : (rectangless[i].y + rectangless[i].h)),
                        h: toPercentY((rectangless[i].h > 0) ? rectangless[i].h : -rectangless[i].h),
                    });
                }
                callback(p);
            }
            x = false;
        }
    }

    function handleMouseOut(e) {
        e.preventDefault();
        e.stopPropagation();

        isDown = false;

        dtx.clearRect(0, 0, drawer.width, drawer.height);
    }

    function handleMouseMove(e) {
        e.preventDefault();
        e.stopPropagation();

        if (!isDown) {
            return;
        }

        mouseX = parseInt(e.clientX - offsetX);
        mouseY = parseInt(e.clientY - offsetY);

        dtx.clearRect(0, 0, drawer.width, drawer.height);

        let width = mouseX - startX;
        let height = mouseY - startY;

        dtx.lineWidth = 3;
        dtx.strokeStyle = "green";
        dtx.strokeRect(startX, startY, width, height);

        x = startX;
        y = startY;
        w = width;
        h = height;
    }

    $("#" + id + "-drawer").off("mousedown").on("mousedown", handleMouseDown);
    $("#" + id + "-drawer").off("mousemove").on("mousemove", handleMouseMove);
    $("#" + id + "-drawer").off("mouseup").on("mouseup", handleMouseUp);
    $("#" + id + "-drawer").off("mouseout").on("mouseout", handleMouseOut);

    function start() {
        function load(url) {
            if (old_url != url) {
                old_url = url;

                let img = new Image();
                img.src = url;

                img.onload = function() {
                    canvas.height = img.height / img.width * canvas.width;
                    drawer.height = img.height / img.width * canvas.width;
                    ctx = canvas.getContext("2d");
                    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                    $("#" + id).css("width", canvas.width + "px").css("height", canvas.height + "px");

                    if (rectangles && rectangles.length) {
                        rectangless = [];

                        for (let i in rectangles) {
                            rectangless.push({
                                x: fromPercentX(rectangles[i].x),
                                w: fromPercentX(rectangles[i].w),
                                y: fromPercentY(rectangles[i].y),
                                h: fromPercentY(rectangles[i].h),
                            });
                        }

                        for (let i in rectangless) {
                            ctx.lineWidth = 3;
                            ctx.strokeStyle = "green";
                            ctx.lineCap = "square";
                            ctx.fillStyle = 'rgba(0, 255, 0, 0.5)';
                            ctx.fillRect(rectangless[i].x, rectangless[i].y, rectangless[i].w, rectangless[i].h);
                            ctx.strokeRect(rectangless[i].x, rectangless[i].y, rectangless[i].w, rectangless[i].h);
                        }
                    }
                }
            }
        }

        image(result => {
            load(result);
        });

        load(fallback);
    }

    canvas.width = $("#" + id).parent().width();
    drawer.width = $("#" + id).parent().width();

    start();
}
