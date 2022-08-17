<?php

    /**
     * backends isdn namespace
     */

    namespace backends\isdn
    {

        /**
         * LanTa's variant of flash calls and sms sending
         */
        class lanta extends isdn
        {

            /**
             * @inheritDoc
             */
            function flashCall($id)
            {
                return file_get_contents("https://isdn.lanta.me/isdn_api.php?action=flashCall&mobile=$id&secret=" . $this->config["backends"]["isdn"]["secret"]);
            }

            /**
             * @inheritDoc
             */
            function getCode($id)
            {
                return file_get_contents("https://isdn.lanta.me/isdn_api.php?action=checkFlash&mobile=$id&secret=" . $this->config["backends"]["isdn"]["secret"]);
            }

            /**
             * @inheritDoc
             */
            function sendCode($id)
            {
                return file_get_contents("https://isdn.lanta.me/isdn_api.php?action=sendCode&mobile=$id&secret=" . $this->config["backends"]["isdn"]["secret"]);
            }

            /**
             * @inheritDoc
             */
            function confirmNumbers()
            {
                return [
                    "84752429949"
                ];
            }

            /**
             * @inheritDoc
             */
            function checkIncomng($id)
            {
                return file_get_contents("https://isdn.lanta.me/isdn_api.php?action=checkIncoming&mobile=$id&secret=" . $this->config["backends"]["isdn"]["secret"]);
            }

            /**
             * @inheritDoc
             */
            function push($push)
            {
                /*
                 * hash ([sip] password)
                 * server (sip server)
                 * port (sip port)
                 * transport (tcp|udp)
                 * extension
                 * dtmf
                 * image (url of first camshot)
                 * live (url of "live" jpeg stream)
                 * callerId
                 * platform (ios|android)
                 * flatId
                 * flatNumber
                 * turn (turn:server:port)
                 * turnTransport (tcp|udp)
                 * stun (stun:server:port)
                 * type
                 * token
                 * msg
                 * title
                 * badge
                 * messageId
                 * pushAction (action)
                 */

                /*
                    if (req.query.hash) {
                        let data = {
                            server: 'dm.lanta.me',
                            port: '54675',
                            transport: 'tcp',
                            extension: req.query.extension.toString(),
                            hash: req.query.hash,
                            dtmf: req.query.dtmf?req.query.dtmf:'1',
                            timestamp: Math.round((new Date()).getTime()/1000).toString(),
                            ttl: '30',
                            callerId: req.query.caller_id,
                            platform: req.query.platform,
                            flatId: req.query.flat_id,
                            flatNumber: req.query.flat_number,
                        };
                        if (false) {
                            data.turn = 'turn:37.235.209.140:3478';
                            data.turnTransport = 'udp';
                        }
                        if (true) {
                            data.stun = 'stun:37.235.209.140:3478';
                            data.stun_transport = 'udp';
                            data.stunTransport = 'udp';
                        }
                        if (req.query.platform == 'ios') {
                            realPush({
                                title: "Входящий вызов",
                                body: req.query.caller_id,
                                tag: "voip",
                            }, data, {
                                priority: 'high',
                                mutableContent: true,
                                collapseKey: 'voip',
                            }, req.query.token, req.query.type, res);
                            pushed = true;
                        }
                        if (req.query.platform == 'android') {
                            realPush({}, data, {
                                priority: 'high',
                                mutableContent: false,
                            }, req.query.token, req.query.type, res);
                            pushed = true;
                        }
                    }
                    if (req.query.msg) {
                        realPush({
                            title: "LanTa",
                            body: req.query.msg,
                            badge: req.query.badge?req.query.badge:'1',
                            sound: "default",
                        }, {
                            messageId: req.query.message_id?req.query.message_id:'',
                            badge: req.query.badge?req.query.badge:'1',
                            action: req.query.action?req.query.action:'inbox',
                        }, {
                            priority: 'high',
                            mutableContent: false,
                        }, req.query.token, 0, res);
                        pushed = true;
                    }
                 */

                $query = "";
                foreach ($push as $param => $value) {
                    if ($param != "action" && $param != "secret") {
                        $query = $param . "=" . urlencode($value) . "&";
                    }
                }
                if ($query) {
                    $query = substr($query, 0, -1);
                }

                $result = file_get_contents("https://isdn.lanta.me/isdn_api.php?action=push&secret=" . $this->config["backends"]["isdn"]["secret"] . "&" . $query);

                if (strtolower(trim($result)) !== "ok") {
                    $households = loadBackend("households");
                    $households->dismissToken($push["token"]);
                }
                return $result;
            }
        }
    }
