// type and pixel size of a camera frame from its header: JPEG, PNG, BMP or WebP, null for anything else

const TYPES = {
    jpeg: { mime: "image/jpeg", extension: "jpg" },
    png: { mime: "image/png", extension: "png" },
    bmp: { mime: "image/bmp", extension: "bmp" },
    webp: { mime: "image/webp", extension: "webp" },
};

function jpegSize(buffer) {
    let i = 2;

    while (i + 9 < buffer.length) {
        if (buffer[i] != 0xff) {
            return null;
        }

        let marker = buffer[i + 1];

        // fill bytes and markers without a length
        if (marker == 0xff) {
            i++;
            continue;
        }

        if (marker == 0xd8 || marker == 0x01 || (marker >= 0xd0 && marker <= 0xd7)) {
            i += 2;
            continue;
        }

        let length = buffer.readUInt16BE(i + 2);

        // start of frame markers, except DHT (c4), JPG (c8) and DAC (cc)
        if (marker >= 0xc0 && marker <= 0xcf && marker != 0xc4 && marker != 0xc8 && marker != 0xcc) {
            return { width: buffer.readUInt16BE(i + 7), height: buffer.readUInt16BE(i + 5) };
        }

        i += 2 + length;
    }

    return null;
}

function pngSize(buffer) {
    // signature, then the IHDR chunk: length, type, width, height
    if (buffer.length < 24 || buffer.toString("latin1", 12, 16) != "IHDR") {
        return null;
    }

    return { width: buffer.readUInt32BE(16), height: buffer.readUInt32BE(20) };
}

function bmpSize(buffer) {
    if (buffer.length < 26) {
        return null;
    }

    // BITMAPCOREHEADER has 16-bit sizes, the later headers 32-bit ones, a negative height is a top-down bitmap
    if (buffer.readUInt32LE(14) == 12) {
        return { width: buffer.readUInt16LE(18), height: buffer.readUInt16LE(20) };
    }

    return { width: buffer.readInt32LE(18), height: Math.abs(buffer.readInt32LE(22)) };
}

function webpSize(buffer) {
    if (buffer.length < 30) {
        return null;
    }

    let chunk = buffer.toString("latin1", 12, 16);

    // lossy: frame tag, start code, 14-bit sizes
    if (chunk == "VP8 " && buffer[23] == 0x9d && buffer[24] == 0x01 && buffer[25] == 0x2a) {
        return { width: buffer.readUInt16LE(26) & 0x3fff, height: buffer.readUInt16LE(28) & 0x3fff };
    }

    // lossless: signature, then 14-bit sizes minus one
    if (chunk == "VP8L" && buffer[20] == 0x2f) {
        let bits = buffer.readUInt32LE(21);
        return { width: (bits & 0x3fff) + 1, height: ((bits >> 14) & 0x3fff) + 1 };
    }

    // extended: 24-bit canvas sizes minus one
    if (chunk == "VP8X") {
        return { width: buffer.readUIntLE(24, 3) + 1, height: buffer.readUIntLE(27, 3) + 1 };
    }

    return null;
}

/**
 * @return { type, mime, extension, width, height } or null
 */

function imageInfo(buffer) {
    if (!buffer || buffer.length < 4) {
        return null;
    }

    let type = null;
    let size = null;

    if (buffer[0] == 0xff && buffer[1] == 0xd8) {
        type = "jpeg";
        size = jpegSize(buffer);
    } else if (buffer.length >= 8 && buffer.toString("latin1", 0, 8) == "\x89PNG\r\n\x1a\n") {
        type = "png";
        size = pngSize(buffer);
    } else if (buffer.toString("latin1", 0, 2) == "BM") {
        type = "bmp";
        size = bmpSize(buffer);
    } else if (buffer.length >= 12 && buffer.toString("latin1", 0, 4) == "RIFF" && buffer.toString("latin1", 8, 12) == "WEBP") {
        type = "webp";
        size = webpSize(buffer);
    }

    if (!size || !(size.width > 0) || !(size.height > 0)) {
        return null;
    }

    return Object.assign({ type }, TYPES[type], size);
}

module.exports = { imageInfo };
