({
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

        $("#asteriskMenuRight").off("click").on("click", modules.asterisk.asteriskMenuRight)
    },

    asteriskMenuRight: function () {
        let current_session, holded_session, ready, registered;
        let beep_beep = 0;
        let beep_amplify = 500;

        let options = {
            eventHandlers: {
                progress: function() {
                    //
                },
                failed: function(e) {
                    //
                },
                ended: function(e) {
                    //
                },
                confirmed: function() {
                    //
                }
            },
            mediaConstraints: {
                audio: true,
                video: false
            },
            pcConfig: {
                iceServers: [
                    {
                        urls: [ 'stun:stun.l.google.com:19302' ]
                    }
                ]
            }
        }

        let audio = document.createElement('audio');

        JsSIP.debug.disable('JsSIP:*');

        let wss = new JsSIP.WebSocketInterface('ws://localhost:8088/ws');

        let ua = new JsSIP.UA({
            sockets: [ wss ],
            uri: "sip:" + myself.webRtcExtension + "@localhost",
            password : myself.webRtcPassword,
        });

        ua.on('newRTCSession', function(e) {
            console.log('newRTCSession');

            let session = e.session;
            if ((holded_session || current_session) && session.direction == 'incoming') {
                session.terminate();
                return;
            }
            if (holded_session && current_session && session.direction == 'outgoing') {
                session.terminate();
                return;
            }

            current_session = session;

            if (session.direction == 'incoming') {
                session.data = 'incoming';
//                notify(extension(session.remote_identity.uri), "Входящий вызов", "/images/phone.png", [], 'phone: incoming');
//                play_audio('audio-ringtone');
            } else {
                current_session.connection.addEventListener('addstream', addstream);
            }

            session.on("icecandidate", function (event) {
                /*
                                These candidate types are listed in order of priority; the higher in the list they are, the more efficient they are.
                                host
                                The candidate is a host candidate, whose IP address as specified in the RTCIceCandidate.ip property is in fact the true address of the remote peer.
                                srflx
                                The candidate is a server reflexive candidate; the ip indicates an intermediary address assigned by the STUN server to represent the candidate's peer anonymously.
                                prflx
                                The candidate is a peer reflexive candidate; the ip is an intermediary address assigned by the STUN server to represent the candidate's peer anonymously.
                                relay
                                The candidate is a relay candidate, obtained from a TURN server. The relay candidate's IP address is an address the TURN server uses to forward the media between the two peers.
                */
                if (event.candidate.type === "srflx" && event.candidate.relatedAddress !== null && event.candidate.relatedPort !== null) {
                    event.ready();
                }
            });

            session.on('confirmed', function () {
                console.log('session.confirmed');

                new Beep(22050).play(1500, 0.1, [ Beep.utils.amplify(beep_amplify) ]);
            });

            session.on('ended', function () {
                console.log('session.ended');

                let s = this;
                if (current_session == s) {
                    if (typeof current_session.subject == 'undefined' && current_session.answered === true) {
//                        pin_call2();
                    }
                    current_session = false;
                }
                if (holded_session == s) {
                    if (typeof holded_session.subject == 'undefined' && holded_session.answered === true) {
//                        pin_call2(false);
                    }
                    holded_session = false;
                }
                s.data = 'bye';
//                update_bar();
//                notify_close('phone');
                if (parseInt(new Date().getTime()/1000) - beep_beep >= 2) {
                    beep_beep = parseInt(new Date().getTime()/1000);
                    new Beep(22050).play(2000, 0.06, [ Beep.utils.amplify(beep_amplify) ], function () {
                        new Beep(22050).play(1, 0.01, [ Beep.utils.amplify(beep_amplify) ], function () {
                            new Beep(22050).play(2000, 0.06, [ Beep.utils.amplify(beep_amplify) ]);
                        });
                    });
                }
            });

            session.on('failed', function () {
                console.log('session.failed');

                let s = this;
//                stop_audio('audio-ringtone');
//                $('#sip-ua').dialog('close');
                if (current_session == s) {
                    current_session = false;
                }
                if (holded_session == s) {
                    holded_session = false;
                }
                s.data = 'bye';
//                update_bar();
//                notify_close('phone');
                if (parseInt(new Date().getTime()/1000) - beep_beep >= 2) {
                    beep_beep = parseInt(new Date().getTime()/1000);
                    new Beep(22050).play(2000, 0.06, [ Beep.utils.amplify(beep_amplify) ], function () {
                        new Beep(22050).play(1, 0.01, [ Beep.utils.amplify(beep_amplify) ], function () {
                            new Beep(22050).play(2000, 0.06, [ Beep.utils.amplify(beep_amplify) ]);
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
        });

        ua.on('connected', function () {
            console.log('connected');

//            $('#sip_phone').addClass(sip_xx_class);
//            $('#sip_phone').removeClass(sip_on_class);
//            $('#queue_agent').hide();
//            $('#queue_out_agent').hide();
            ready = false;
            current_session = false;
            holded_session = false;
//            update_bar();
        });

        ua.on('disconnected', function() {
            console.log('disconnected');

//            $('#sip_phone').addClass(sip_xx_class);
//            $('#sip_phone').removeClass(sip_on_class);
//            $('#queue_agent').hide();
//            $('#queue_out_agent').hide();
            ready = false;
            current_session = false;
            holded_session = false;
//            update_bar();
            registered = false;
        });

        ua.on('registered', function () {
            console.log('registered');

            ready = true;

            call('6000000002');
        });

        ua.on('unregistered', function () {
            console.log('unregistered');

//            $('#sip_phone').addClass(sip_xx_class);
//            $('#sip_phone').removeClass(sip_on_class);
//            $('#queue_agent').hide();
//            $('#queue_out_agent').hide();
            ready = false;
            current_session = false;
            holded_session = false;
            registered = false;
//            update_bar();
        });

        ua.on('registrationFailed', function () {
            console.log('registrationFailed');

//            $('#sip_phone').addClass(sip_xx_class);
//            $('#sip_phone').removeClass(sip_on_class);
//            $('#queue_agent').hide();
//            $('#queue_out_agent').hide();
            ready = false;
            current_session = false;
            holded_session = false;
            registered = false;
//            update_bar();
            setTimeout('ua.register()', 5000);
        });

        function extension(uri) {
            return uri.toString().split('sip:')[1].split('@')[0];
        }

        function call(number) {
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
                if (current_session && extension(current_session.remote_identity.uri) == number) {
                    hangup();
                } else {
                    if (!current_session) {
//                        notify(number, "Исходящий вызов", "/images/phone.png", [], 'phone');
                        ua.call(number, options);
                    } else {
                        if (!holded_session) {
                            hold(number);
                        } else {
                            new Beep(22050).play(2000, 0.1, [ Beep.utils.amplify(beep_amplify) ]);
                        }
                    }
                }
//                update_bar();
//                if (document.visibilityState != 'visible') {
//                    play_audio('audio-chordo');
//                }
            } else {
                new Beep(22050).play(2000, 0.1, [ Beep.utils.amplify(beep_amplify) ]);
            }
        }

        function end(callback) {
            if (ua) {
                ua.stop();
            }
//            notify_close('phone');
            if (typeof callback == "function") {
                callback();
            }
        }

        function transfer() {
            if (current_session && holded_session) {
                holded_session.refer(current_session.remote_identity.uri, { extraHeaders: [ 'Contact: ' + current_session.remote_identity.uri ], replaces: current_session });
                setTimeout(() => {
                    if (current_session) {
                        current_session.terminate();
                    }
                }, 100);
                setTimeout(() => {
                    if (holded_session) {
                        holded_session.terminate();
                    }
                }, 100);
            }
//            update_bar();
        }

        function hold(dial_after_hold) {
            if (current_session && !holded_session) {
                current_session.hold();
                holded_session = current_session;
                current_session = false;
                if (dial_after_hold) {
                    ua.call(dial_after_hold, options);
                }
//                update_bar();
            }
        }

        function unhold() {
            if (holded_session && !current_session) {
                holded_session.unhold();
                audio.srcObject = holded_session.remote_stream;
                current_session = holded_session;
                holded_session = false;
//                update_bar();
            }
        }

        function hangup() {
            if (current_session) {
                current_session.terminate();
            }
//            notify_close('phone');
        }

        function addstream(event) {
            let stream = event.stream;
            current_session.remote_stream = stream;
            audio.srcObject = event.stream;
            audio.play();
        }

        function answer() {
            if (current_session && current_session.data == 'incoming') {
                current_session.data = 'accepted';
                current_session.answered = true;
//                stop_audio('audio-ringtone');
//                notify_close('phone');
                current_session.answer(options);
                current_session.connection.addEventListener('addstream', addstream);
            }
//            update_bar();
        }

        ua.start();
    },

}).init();