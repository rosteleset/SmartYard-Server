// pixel size of a JPEG from its header, null when it is not a JPEG

function jpegSize(buffer) {
    if (!buffer || buffer.length < 4 || buffer[0] != 0xff || buffer[1] != 0xd8) {
        return null;
    }

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
            let height = buffer.readUInt16BE(i + 5);
            let width = buffer.readUInt16BE(i + 7);

            return (width > 0 && height > 0) ? { width, height } : null;
        }

        i += 2 + length;
    }

    return null;
}

module.exports = { jpegSize };
