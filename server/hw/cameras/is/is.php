<?php

    namespace hw\cameras {

        require_once __DIR__ . '/../cameras.php';

        class is extends cameras {

            public string $user = 'root';
            protected string $def_pass = '123456';

            protected function api_call($resource, $method = 'GET', $payload = null) {
                $req = $this->url . $resource;

                $ch = curl_init($req);

                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                curl_setopt($ch, CURLOPT_USERPWD, "$this->user:$this->pass");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_VERBOSE, false);

                if ($payload) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [ 'Content-Type: application/json' ]);
                }

                $res = curl_exec($ch);
                curl_close($ch);

                $array_res = json_decode($res, true);

                if ($array_res === null) {
                    return $res;
                }

                return $array_res;
            }

            public function camshot(): string {
                return $this->api_call('/camera/snapshot');
            }

            public function get_sysinfo(): array {
                $info = $this->api_call('/system/info');
                $versions = $this->api_call('/v2/system/versions')['opt'];

                $sysinfo['DeviceID'] = $info['chipId'];
                $sysinfo['DeviceModel'] = $info['model'];
                $sysinfo['HardwareVersion'] = $versions['versions']['hw']['name'];
                $sysinfo['SoftwareVersion'] = $versions['name'];

                return $sysinfo;
            }

            public function set_admin_password(string $password) {
                $this->api_call('/user/change_password', 'PUT', [ 'newPassword' => $password ]);
            }

            public function write_config() {
                // не используется
            }

            public function reboot() {
                $this->api_call('/system/reboot', 'PUT');
            }

            public function reset() {
                $this->api_call('/system/factory-reset', 'PUT');
            }

        }

    }
