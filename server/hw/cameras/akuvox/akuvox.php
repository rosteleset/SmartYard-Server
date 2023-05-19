<?php

    namespace hw\cameras {

        require_once __DIR__ . '/../cameras.php';

        class akuvox extends cameras {

            public string $user = 'admin';

            protected string $def_pass = 'httpapi';
            protected string $api_prefix = '/api';

            /** Make an API call */
            protected function api_call($resource, $method = 'GET', $payload = null) {
                $req = $this->url . $this->api_prefix . $resource;
                $ch = curl_init($req);

                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                curl_setopt($ch, CURLOPT_USERPWD, "$this->user:$this->pass");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_VERBOSE, false);

                if ($payload) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Expect:', // Workaround for the 100-continue expectation
                    ]);
                }

                $res = curl_exec($ch);
                curl_close($ch);

                return json_decode($res, true);
            }

            /** Set data in config section */
            protected function setConfigParams(array $data) {
                $this->api_call('', 'POST', [
                    'target' => 'config',
                    'action' => 'set',
                    'data' => $data,
                ]);
            }

            public function camshot(): string {
                $host = parse_url($this->url)['host'];
                return file_get_contents("http://$this->user:$this->pass@$host:8080/picture.jpg");
            }

            public function get_sysinfo(): array {
                $info = $this->api_call('/system/info')['data']['Status'];

                $sysinfo['DeviceID'] = str_replace(':', '', $info['MAC']);
                $sysinfo['DeviceModel'] = $info['Model'];
                $sysinfo['HardwareVersion'] = $info['HardwareVersion'];
                $sysinfo['SoftwareVersion'] = $info['FirmwareVersion'];

                return $sysinfo;
            }

            public function set_admin_password(string $password) {
                $this->setConfigParams([
                    'Config.Settings.WEB_LOGIN.Password' => $password, // WEB
                    'Config.DoorSetting.APIFCGI.Password' => $password, // API
                    'Config.DoorSetting.RTSP.Password' => $password, // RTSP
                ]);

                sleep(1);
            }

            public function write_config() {
                // not used
            }

            public function reboot() {
                $this->api_call('/system/reboot');
            }

            public function reset() {
                $this->api_call('/config/reset_factory');
            }
        }
    }
