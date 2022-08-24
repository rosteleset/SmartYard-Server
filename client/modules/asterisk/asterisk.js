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
        // add icon-button to top-right menu
        $(`
            <li class="nav-item">
                <span class="nav-link text-secondary" role="button" style="cursor: pointer" title="${i18n("asterisk.asterisk")}" id="asteriskMenuRight">
                    <i class="fas fa-lg fa-fw fa-phone-square"></i>
                </span>
            </li>
        `).insertAfter("#rightTopDynamic");

        moduleLoaded("asterisk", this);

        $("#asteriskMenuRight").off("click").on("click", modules.asterisk.asteriskMenuRight);

        if (config.ws && config.ice && config.sipDomain && myself.webRtcExtension && myself.webRtcPassword) {
            modules.asterisk.options = {
                eventHandlers: {
                    progress: modules.asterisk.onProgress,
                    failed: modules.asterisk.onFailed,
                    ended: modules.asterisk.onEnded,
                    confirmed: modules.asterisk.onConfirmed,
                },
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
            modules.asterisk.ua.on('connected', modules.asterisk.onConnected);
            modules.asterisk.ua.on('disconnected', modules.asterisk.onDisconnected);
            modules.asterisk.ua.on('registered', modules.asterisk.onRegistered);
            modules.asterisk.ua.on('unregistered', modules.asterisk.onUnregistered);
            modules.asterisk.ua.on('registrationFailed', modules.asterisk.onRegistrationFailed);

            modules.asterisk.ua.start();
        }
    },

    onProgress: function (e) {

    },

    onFailed: function (e) {

    },

    onEnded: function (e) {

    },

    onConfirmed: function (e) {

    },

    newRTCSession: function (e) {
        console.log('newRTCSession');

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
//                notify(extension(session.remote_identity.uri), "Входящий вызов", "/images/phone.png", [], 'phone: incoming');
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
            console.log('session.confirmed');

            new Beep(22050).play(1500, 0.1, [ Beep.utils.amplify(modules.asterisk.beepAmplify) ]);
        });

        session.on('ended', function () {
            console.log('session.ended');

            let s = this;
            if (modules.asterisk.currentSession == s) {
                if (typeof modules.asterisk.currentSession.subject == 'undefined' && modules.asterisk.currentSession.answered === true) {
//                        pin_call2();
                }
                modules.asterisk.currentSession = false;
            }
            if (modules.asterisk.holdedSession == s) {
                if (typeof modules.asterisk.holdedSession.subject == 'undefined' && modules.asterisk.holdedSession.answered === true) {
//                        pin_call2(false);
                }
                modules.asterisk.holdedSession = false;
            }
            s.data = 'bye';
//                update_bar();
//                notify_close('phone');
            if (parseInt(new Date().getTime()/1000) - modules.asterisk.beepBeep >= 2) {
                modules.asterisk.beepBeep = parseInt(new Date().getTime()/1000);
                new Beep(22050).play(2000, 0.06, [ Beep.utils.amplify(modules.asterisk.beepAmplify) ], function () {
                    new Beep(22050).play(1, 0.01, [ Beep.utils.amplify(modules.asterisk.beepAmplify) ], function () {
                        new Beep(22050).play(2000, 0.06, [ Beep.utils.amplify(modules.asterisk.beepAmplify) ]);
                    });
                });
            }
        });

        session.on('failed', function () {
            console.log('session.failed');

            let s = this;
//                stop_audio('audio-ringtone');
//                $('#sip-ua').dialog('close');
            if (modules.asterisk.currentSession == s) {
                modules.asterisk.currentSession = false;
            }
            if (modules.asterisk.holdedSession == s) {
                modules.asterisk.holdedSession = false;
            }
            s.data = 'bye';
//                update_bar();
//                notify_close('phone');
            if (parseInt(new Date().getTime()/1000) - modules.asterisk.beepBeep >= 2) {
                modules.asterisk.beepBeep = parseInt(new Date().getTime()/1000);
                new Beep(22050).play(2000, 0.06, [ Beep.utils.amplify(modules.asterisk.beepAmplify) ], function () {
                    new Beep(22050).play(1, 0.01, [ Beep.utils.amplify(modules.asterisk.beepAmplify) ], function () {
                        new Beep(22050).play(2000, 0.06, [ Beep.utils.amplify(modules.asterisk.beepAmplify) ]);
                    });
                });
            }
        });

        session.on('accepted', function () {
            console.log('session.accepted');

            this.data = 'accepted';
            this.answered = true;
//                update_bar();
//                notify_close('phone');
        });

//            update_bar();
    },

    onConnected: function (e) {
        console.log('connected');

        $('#asteriskMenuRight').addClass("text-success");
        $('#asteriskMenuRight').removeClass("text-secondary");
        modules.asterisk.ready = false;
        modules.asterisk.currentSession = false;
        modules.asterisk.holdedSession = false;
//            update_bar();
    },

    onDisconnected: function (e) {
        console.log('disconnected');

        $('#asteriskMenuRight').addClass("text-secondary");
        $('#asteriskMenuRight').removeClass("text-success");
        modules.asterisk.ready = false;
        modules.asterisk.currentSession = false;
        modules.asterisk.holdedSession = false;
//            update_bar();
        modules.asterisk.registered = false;
    },

    onRegistered: function (e) {
        console.log('registered');

        modules.asterisk.ready = true;
    },

    onUnregistered: function (e) {
        console.log('unregistered');

        $('#asteriskMenuRight').addClass("text-secondary");
        $('#asteriskMenuRight').removeClass("text-success");
        modules.asterisk.ready = false;
        modules.asterisk.currentSession = false;
        modules.asterisk.holdedSession = false;
        modules.asterisk.registered = false;
//            update_bar();
    },

    onRegistrationFailed: function (e) {
        console.log('registrationFailed');

        $('#asteriskMenuRight').addClass("text-secondary");
        $('#asteriskMenuRight').removeClass("text-success");
        modules.asterisk.ready = false;
        modules.asterisk.currentSession = false;
        modules.asterisk.holdedSession = false;
        modules.asterisk.registered = false;
//            update_bar();
        setTimeout('ua.register()', 5000);
    },


    extension: function (uri) {
        return uri.toString().split('sip:')[1].split('@')[0];
    },

    call: function (number) {
        if (!ua || !ready) {
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
            if (modules.asterisk.currentSession && extension(modules.asterisk.currentSession.remote_identity.uri) == number) {
                hangup();
            } else {
                if (!modules.asterisk.currentSession) {
//                        notify(number, "Исходящий вызов", "/images/phone.png", [], 'phone');
                    ua.call(number, options);
                } else {
                    if (!modules.asterisk.holdedSession) {
                        hold(number);
                    } else {
                        new Beep(22050).play(2000, 0.1, [ Beep.utils.amplify(modules.asterisk.beepAmplify) ]);
                    }
                }
            }
//                update_bar();
//                if (document.visibilityState != 'visible') {
//                    play_audio('audio-chordo');
//                }
        } else {
            new Beep(22050).play(2000, 0.1, [ Beep.utils.amplify(modules.asterisk.beepAmplify) ]);
        }
    },

    end: function (callback) {
        if (ua) {
            ua.stop();
        }
//            notify_close('phone');
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
//            update_bar();
    },

    hold: function(dial_after_hold) {
        if (modules.asterisk.currentSession && !modules.asterisk.holdedSession) {
            modules.asterisk.currentSession.hold();
            modules.asterisk.holdedSession = modules.asterisk.currentSession;
            modules.asterisk.currentSession = false;
            if (dial_after_hold) {
                ua.call(dial_after_hold, options);
            }
//                update_bar();
        }
    },

    unhold: function() {
        if (modules.asterisk.holdedSession && !modules.asterisk.currentSession) {
            modules.asterisk.holdedSession.unhold();
            modules.asterisk.audio.srcObject = modules.asterisk.holdedSession.remote_stream;
            modules.asterisk.currentSession = modules.asterisk.holdedSession;
            modules.asterisk.holdedSession = false;
//                update_bar();
        }
    },

    hangup: function() {
        if (modules.asterisk.currentSession) {
            modules.asterisk.currentSession.terminate();
        }
//            notify_close('phone');
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
//                stop_audio('audio-ringtone');
//                notify_close('phone');
            modules.asterisk.currentSession.answer(options);
            modules.asterisk.currentSession.connection.addEventListener('addstream', modules.asterisk.addStream);
        }
//            update_bar();
    },

    asteriskMenuRight: function () {
        //
    },

}).init();