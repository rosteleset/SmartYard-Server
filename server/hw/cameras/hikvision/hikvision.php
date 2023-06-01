<?php

    namespace hw\cameras {

        require_once __DIR__ . '/../cameras.php';

        class hikvision extends cameras {

            public string $user = 'admin';

            protected string $def_pass = 'password123';
            protected string $api_prefix = '';

            protected function api_call($resource, $method = 'GET', $params = [], $payload = null) {
                $req = $this->url . $this->api_prefix . $resource;

                if ($params) {
                    $req .= '?' . http_build_query($params);
                }

                echo $req . PHP_EOL; // TODO: delete later

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

                return json_decode($res, true);
            }

            public function camshot(): string {
                $filename = uniqid('hikvision_');
                $host = parse_url($this->url, PHP_URL_HOST);
                $snapshotFile = "/tmp/$filename.jpg";
                $rtspUrl = "rtsp://$this->user:$this->pass@$host:554/Streaming/Channels/101";

                exec("ffmpeg -y -i $rtspUrl -vframes 1 $snapshotFile 2>&1", $output, $returnCode);

                if ($returnCode === 0 && file_exists($snapshotFile)) {
                    $shot = file_get_contents($snapshotFile);
                    unlink($snapshotFile);
                    return $shot;
                } else {
                    return '';
                }
            }

            public function get_sysinfo(): array {
                $res = $this->api_call('/ISAPI/System/deviceInfo');

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
