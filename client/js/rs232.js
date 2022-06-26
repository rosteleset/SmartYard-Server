var NFC;
var mifare_buffer = '';
var mifare_port = false;

function mobile_nfc_scanner() {
    NFC.scan().then(() => {
        timeoutStart();
        setTimeout(() => {
            timeoutDone();
            NFC.onreading = null;
            NFC.onerror = null;
            NFC = null;
            NFC = new NDEFReader();
        }, 15000);
        NFC.onerror = () => {
            error(i18n("errors.cantReadNfcTag"));
            timeoutDone();
            NFC.onreading = null;
            NFC.onerror = null;
            NFC = null;
            NFC = new NDEFReader();
        };
        NFC.onreading = event => {
            timeoutDone();
            NFC.onreading = null;
            NFC.onerror = null;
            NFC = null;
            NFC = new NDEFReader();
            let k = event.serialNumber;
            if (k.length === 14) {
                k = k[12] + k[13] + k[10] + k[11] + k[8] + k[9] + k[6] + k[7] + k[4] + k[5] + k[2] + k[3] + k[0] + k[1];
            }
            mifare_key_entered(k);
        };
    }).catch(e => {
        error(e);
    });
}

function desktop_nfc_scanner() {

    const appendStream = new WritableStream({
        write (chunk) {
            mifare_buffer += chunk;
            if (mifare_buffer.split("\n").length > 1) {
                let k = $.trim(mifare_buffer);
                mifare_buffer = '';
                k = k.toUpperCase().replace(/[^\dA-F]/g, '');
                mifare_key_entered(k);
            }
        }
    });

    if (!mifare_port) {
        navigator.serial.requestPort({ filters: [{ usbVendorId: 0x0403 }] }).then(port => {
            mifare_port = port;
            port.open({ baudRate: 9600, baudrate: 9600 }).then(() => {
                port.readable.pipeThrough(new TextDecoderStream()).pipeTo(appendStream);
                $('.rs232-scanner').off('click').removeClass('bg-dark bg-danger bg-success bg-warning pointer').addClass('bg-success').attr('title', i18n("scannerConnected")).show();
            });
            navigator.serial.ondisconnect = () => {
                $('.rs232-scanner').off('click').removeClass('bg-dark bg-danger bg-success bg-warning pointer').addClass('bg-danger').attr('title', i18n("scannerDisConnected")).show();
            };
        }).catch(() => {
            mifare_port = null;
        });
    }
}

function mifare_key_entered(key) {
    key = key.toUpperCase().replace(/[^\dA-F]/g, '');
    if (key.length === 16) {
        key = "000000" + key.substr(6, 8);
    } else
    if (key.length === 8) {
        key = "000000" + key[6] + key[7] + key[4] + key[5] + key[2] + key[3] + key[0] + key[1];
    }
    if (key.length !== 14) {
        error(i18n("errors.invalidKey"));
    } else {
        let f = $(':focus');

        if (!f.length) {
            f = $('#searchInput');
        }

        if (f[0].nodeName === 'INPUT') {
            f.val(key);
        }

        if (f[0].nodeName === 'TEXTAREA') {
            f.val(f.val() + key + "\n");
        }

        if (f[0].id === 'searchInput') {
            $("#searchButton").click();
        }
    }
}

$('.rs232-scanner').off('click').on('click', e => {
    if (NFC) {
        mobile_nfc_scanner();
    } else {
        if ('serial' in navigator) {
            desktop_nfc_scanner();
        }
    }
    return false;
});

try {
    navigator.permissions.query({ name: 'nfc' }).then(result => {
        if (result.state == 'granted' || result.state == 'prompt') {
            try {
                NFC = new NDEFReader();
            } catch (e) {
                //
            }
        }
    }).catch(() => {
        //
    });
} catch (e) {
    //
}

setTimeout(() => {
    if (!('serial' in navigator) && !NFC) {
        $('.rs232-scanner').off('click').removeClass('bg-dark bg-danger bg-success bg-warning pointer').addClass('bg-warning').attr('title', i18n("scannerUnavailable")).show();
    }
}, 500);
