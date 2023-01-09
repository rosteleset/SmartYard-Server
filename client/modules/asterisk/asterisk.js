({
    options: false,
    audio: false,
    ua: false,
    currentSession: false,
    holdedSession: false,
    ready: false,
    registered: false,
    beepBeep: 0,
    beepAmplify: 500,
    
    init: function () {
        if (config.ws && config.ice && config.sipDomain && myself.webRtcExtension && myself.webRtcPassword) {
            moduleLoaded("asterisk", this);

            $(`
                <li class="nav-item">
                    <span class="nav-link text-secondary" role="button" style="cursor: pointer" title="${i18n("asterisk.asterisk")}" id="asteriskMenuRight">
                        <i class="fas fa-lg fa-fw fa-phone-square"></i>
                    </span>
                </li>
            `).insertAfter("#rightTopDynamic");

            $("#asteriskMenuRight").off("click").on("click", modules.asterisk.asteriskMenuRight);

            modules.asterisk.options = {
                mediaConstraints: {
                    audio: true,
                    video: false
                },
                pcConfig: {
                    iceServers: config.ice,
                }
            };

            modules.asterisk.audio = document.createElement('audio');

            JsSIP.debug.disable('JsSIP:*');

            modules.asterisk.ua = new JsSIP.UA({
                sockets: [ new JsSIP.WebSocketInterface(config.ws) ],
                uri: "sip:" + myself.webRtcExtension + "@" + config.sipDomain,
                password : myself.webRtcPassword,
            });

            modules.asterisk.ua.on('newRTCSession', modules.asterisk.newRTCSession);

            modules.asterisk.ua.on('connected', modules.asterisk.onConnectionBroken);
            modules.asterisk.ua.on('disconnected', modules.asterisk.onConnectionBroken);
            modules.asterisk.ua.on('unregistered', modules.asterisk.onConnectionBroken);

            modules.asterisk.ua.on('registered', modules.asterisk.onRegistered);
            modules.asterisk.ua.on('registrationFailed', modules.asterisk.onRegistrationFailed);

            modules.asterisk.ua.start();
        } else {
            moduleLoaded("asterisk");
        }
    },

    newRTCSession: function (e) {
        let session = e.session;
        if ((modules.asterisk.holdedSession || modules.asterisk.currentSession) && session.direction == 'incoming') {
            session.terminate();
            return;
        }
        if (modules.asterisk.holdedSession && modules.asterisk.currentSession && session.direction == 'outgoing') {
            session.terminate();
            return;
        }

        modules.asterisk.currentSession = session;

        if (session.direction == 'incoming') {
            session.data = 'incoming';
//                notify(modules.asterisk.extension(session.remote_identity.uri), "Входящий вызов", "/images/phone.png", [], 'phone: incoming');
//                play_audio('audio-ringtone');
        } else {
            modules.asterisk.currentSession.connection.addEventListener('addstream', modules.asterisk.addStream);
        }

        session.on("icecandidate", function (event) {
            if (event.candidate.type === "srflx" && event.candidate.relatedAddress !== null && event.candidate.relatedPort !== null) {
                event.ready();
            }
        });

        session.on('confirmed', function () {
            new Beep(22050).play(1500, 0.1, [ Beep.utils.amplify(modules.asterisk.beepAmplify) ]);
        });

        session.on('ended', modules.asterisk.onCallEnded);

        session.on('failed', modules.asterisk.onCallEnded);

        session.on('accepted', function () {
            this.data = 'accepted';
            this.answered = true;
            modules.asterisk.updateButton();
        });

        modules.asterisk.updateButton();
    },

    onCallEnded: function () {
        let s = this;
        if (modules.asterisk.currentSession == s) {
            modules.asterisk.currentSession = false;
        }
        if (modules.asterisk.holdedSession == s) {
            modules.asterisk.holdedSession = false;
        }
        s.data = 'bye';
        modules.asterisk.updateButton();
        if (parseInt(new Date().getTime()/1000) - modules.asterisk.beepBeep >= 2) {
            modules.asterisk.beepBeep = parseInt(new Date().getTime()/1000);
            new Beep(22050).play(2000, 0.06, [ Beep.utils.amplify(modules.asterisk.beepAmplify) ], () => {
                new Beep(22050).play(1, 0.01, [ Beep.utils.amplify(modules.asterisk.beepAmplify) ], () => {
                    new Beep(22050).play(2000, 0.06, [ Beep.utils.amplify(modules.asterisk.beepAmplify) ]);
                });
            });
        }
    },

    onConnectionBroken: function () {
        modules.asterisk.ready = false;
        modules.asterisk.currentSession = false;
        modules.asterisk.holdedSession = false;
        modules.asterisk.registered = false;
        modules.asterisk.updateButton();
    },

    onRegistrationFailed: function () {
        modules.asterisk.ready = false;
        modules.asterisk.currentSession = false;
        modules.asterisk.holdedSession = false;
        modules.asterisk.registered = false;
        setTimeout(() => {
            ua.register();
        }, 5000);
        modules.asterisk.updateButton();
    },

    onRegistered: function () {
        modules.asterisk.ready = true;
        modules.asterisk.updateButton()
    },

    extension: function (uri) {
        return uri.toString().split('sip:')[1].split('@')[0];
    },

    call: function (number) {
        if (!modules.asterisk.ua || !modules.asterisk.ready) {
            return;
        }
        let _n = number.toString();
        number = '';
        for (let i = 0; i < _n.length; i++) {
            if (('0' <= _n[i] && _n[i] <= '9') || _n[i] == '*' || _n[i] == '#') {
                number += _n[i];
            }
        }
        if (number) {
            if (modules.asterisk.currentSession && modules.asterisk.extension(modules.asterisk.currentSession.remote_identity.uri) == number) {
                hmodules.asterisk.hangup();
            } else {
                if (!modules.asterisk.currentSession) {
                    modules.asterisk.ua.call(number, modules.asterisk.options);
                } else {
                    if (!modules.asterisk.holdedSession) {
                        modules.asterisk.hold(number);
                    } else {
                        new Beep(22050).play(2000, 0.1, [ Beep.utils.amplify(modules.asterisk.beepAmplify) ]);
                    }
                }
            }
            modules.asterisk.updateButton();
        } else {
            new Beep(22050).play(2000, 0.1, [ Beep.utils.amplify(modules.asterisk.beepAmplify) ]);
        }
    },

    end: function (callback) {
        if (modules.asterisk.ua) {
            modules.asterisk.ua.stop();
        }
        if (typeof callback == "function") {
            callback();
        }
    },

    transfer: function() {
        if (modules.asterisk.currentSession && modules.asterisk.holdedSession) {
            modules.asterisk.holdedSession.refer(modules.asterisk.currentSession.remote_identity.uri, { extraHeaders: [ 'Contact: ' + modules.asterisk.currentSession.remote_identity.uri ], replaces: modules.asterisk.currentSession });
            setTimeout(() => {
                if (modules.asterisk.currentSession) {
                    modules.asterisk.currentSession.terminate();
                }
            }, 100);
            setTimeout(() => {
                if (modules.asterisk.holdedSession) {
                    modules.asterisk.holdedSession.terminate();
                }
            }, 100);
        }
        modules.asterisk.updateButton();
    },

    hold: function(dialAfterHold) {
        if (modules.asterisk.currentSession && !modules.asterisk.holdedSession) {
            modules.asterisk.currentSession.hold();
            modules.asterisk.holdedSession = modules.asterisk.currentSession;
            modules.asterisk.currentSession = false;
            if (dialAfterHold) {
                ua.call(dialAfterHold, options);
            }
            modules.asterisk.updateButton();
        }
    },

    unhold: function() {
        if (modules.asterisk.holdedSession && !modules.asterisk.currentSession) {
            modules.asterisk.holdedSession.unhold();
            modules.asterisk.audio.srcObject = modules.asterisk.holdedSession.remote_stream;
            modules.asterisk.currentSession = modules.asterisk.holdedSession;
            modules.asterisk.holdedSession = false;
            modules.asterisk.updateButton();
        }
    },

    hangup: function() {
        if (modules.asterisk.currentSession) {
            modules.asterisk.currentSession.terminate();
        }
        modules.asterisk.updateButton();
    },

    addStream: function (e) {
        let stream = e.stream;
        modules.asterisk.currentSession.remote_stream = stream;
        modules.asterisk.audio.srcObject = e.stream;
        modules.asterisk.audio.play();
    },

    answer: function() {
        if (modules.asterisk.currentSession && modules.asterisk.currentSession.data == 'incoming') {
            modules.asterisk.currentSession.data = 'accepted';
            modules.asterisk.currentSession.answered = true;
            modules.asterisk.currentSession.answer(options);
            modules.asterisk.currentSession.connection.addEventListener('addstream', modules.asterisk.addStream);
        }
        modules.asterisk.updateButton();
    },

    updateButton: function () {
        if (modules.asterisk.ready) {
            if (modules.asterisk.currentSession) {
                $('#asteriskMenuRight').removeClass("text-success");
                $('#asteriskMenuRight').removeClass("text-secondary");
                $('#asteriskMenuRight').addClass("text-danger");
            } else {
                $('#asteriskMenuRight').addClass("text-success");
                $('#asteriskMenuRight').removeClass("text-secondary");
                $('#asteriskMenuRight').removeClass("text-danger");
            }
        } else {
            $('#asteriskMenuRight').removeClass("text-success");
            $('#asteriskMenuRight').addClass("text-secondary");
            $('#asteriskMenuRight').removeClass("text-danger");
        }
    },

    asteriskMenuRight: function () {
        if (modules.asterisk.currentSession) {
            modules.asterisk.hangup();
        }
    },

}).init();