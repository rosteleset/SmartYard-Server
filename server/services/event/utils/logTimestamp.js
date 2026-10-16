const tzOffset = new Date().getTimezoneOffset() * 60_000;

export function enableLogTimestamp() {
    for (const method of ["log", "info", "warn", "error", "debug"]) {
        const original = console[method].bind(console);

        console[method] = (...args) => {
            const timestamp = new Date(Date.now() - tzOffset)
                .toISOString()
                .slice(0, -1)
                .replace("T", " ");

            original(`[${timestamp}]`, ...args);
        };
    }
}
