<?php

    namespace hw\cameras {

        require_once __DIR__ . '/../cameras.php';

        class hikvision extends cameras {

            public string $user = 'admin';

            protected string $def_pass = 'password123';
            protected string $api_prefix = '/ISAPI';

            protected function api_call($resource, $method = 'GET', $params = [], $payload = null) {
                $req = $this->url . $this->api_prefix . $resource;

                if ($params) {
                    $req .= '?' . http_build_query($params);
                }

                $ch = curl_init($req);

                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANYSAFE);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                curl_setopt($ch, CURLOPT_USERPWD, "$this->user:$this->pass");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_VERBOSE, false);

                if ($payload) {
                    $postfields = $payload;

                    if (isset($params['format']) && $params['format'] == 'json') {
                        $postfields = json_encode($payload);
                    }

                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
                }

                $res = curl_exec($ch);
                curl_close($ch);

                if (curl_getinfo($ch, CURLINFO_CONTENT_TYPE) == 'application/xml') {
                    return json_decode(json_encode(simplexml_load_string($res)), true);
                }

                if (curl_getinfo($ch, CURLINFO_CONTENT_TYPE) == 'application/json') {
                    return json_decode($res, true);
                }

                return $res;
            }

            public function camshot(): string {
                return $this->api_call(
                    '/Streaming/channels/101/picture',
                    'GET',
                    [ 'snapShotImageType' => 'JPEG' ]
                );
            }

            public function get_sysinfo(): array {
                $res = $this->api_call('/System/deviceInfo');

                $sysinfo['DeviceID'] = $res['deviceID'];
                $sysinfo['DeviceModel'] = $res['model'];
                $sysinfo['HardwareVersion'] = $res['hardwareVersion'];
                $sysinfo['SoftwareVersion'] = $res['firmwareVersion'] . ' ' . $res['firmwareReleasedDate'];

                return $sysinfo;
            }

            public function set_admin_password(string $password) {
                $this->api_call(
                    'Security/users/1',
                    'PUT',
                    [],
                    "<User>
                        <id>1</id>
                        <userName>admin</userName>
                        <password>$password</password>
                        <userLevel>Administrator</userLevel>
                        <loginPassword>$this->pass</loginPassword>
                    </User>"
                );
            }

            public function write_config() {
                // not used
            }

            public function reboot() {
                $this->api_call('System/reboot', 'PUT');
            }

            public function reset() {
                $this->api_call('System/factoryReset', 'PUT', [ 'mode' => 'basic' ]);
            }
        }
    }
