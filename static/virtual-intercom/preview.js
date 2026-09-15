(() => {
    'use strict';
    // The canvas supplies both the local preview and the outgoing video, so
    // portrait cameras have the same framing on the visitor and resident sides.
    const WIDTH = 640, HEIGHT = 360, FPS = 15;
    window.VirtualIntercomPreview = {
        start(video, canvas) {
            const unavailable = () => new Error('Браузер не поддерживает подготовку видео. Попробуйте другой браузер.');
            if (!canvas?.captureStream) throw unavailable();
            const context = canvas.getContext('2d', {alpha: false});
            if (!context) throw unavailable();
            canvas.width = WIDTH; canvas.height = HEIGHT;
            const stream = canvas.captureStream(FPS);
            if (!stream.getVideoTracks().length) throw unavailable();
            let stopped = false, handle, orientation, pending, pendingSince, lastPaint = -Infinity;
            // Do not depend on native video-layer presentation callbacks: that
            // layer becomes transparent once the canvas has its first frame.
            const schedule = () => { handle = requestAnimationFrame(paint); };
            function stop() {
                if (stopped) return;
                stopped = true;
                cancelAnimationFrame(handle);
                stream.getTracks().forEach(track => track.stop());
                canvas.hidden = true;
                canvas.width = canvas.height = 1;
                video.classList.remove('canvas-source');
            }
            function paint(now) {
                if (stopped) return;
                const width = video.videoWidth, height = video.videoHeight;
                if (video.readyState >= 2 && width && height && now - lastPaint >= 1000 / FPS) {
                    const next = width > height ? 'landscape' : 'portrait';
                    if (orientation && next !== orientation) {
                        if (pending !== next) { pending = next; pendingSince = now; }
                    } else pending = null;
                    // Keep the last good frame through a transient 90-degree
                    // flip. A sustained physical rotation is accepted normally.
                    if (!pending || now - pendingSince >= 250) {
                        const cropWidth = Math.min(width, height * WIDTH / HEIGHT);
                        const cropHeight = Math.min(height, width * HEIGHT / WIDTH);
                        try {
                            // Center crop without stretching. CSS mirrors only
                            // the self-view; outgoing pixels remain unmirrored.
                            context.drawImage(video, (width - cropWidth) / 2, (height - cropHeight) / 2,
                                cropWidth, cropHeight, 0, 0, WIDTH, HEIGHT);
                            orientation = next; pending = null; lastPaint = now;
                            canvas.hidden = false;
                            video.classList.add('canvas-source');
                        } catch (_) {
                            // A source changing dimensions may temporarily have
                            // no drawable frame. Keep the last frame and retry.
                        }
                    }
                }
                schedule();
            }
            // Paint immediately after video.play(), before the first JPEG and
            // SIP offer. captureStream keeps the encoded size fixed at 16:9.
            paint(performance.now());
            return {stream, canvas, stop};
        },
    };
})();
