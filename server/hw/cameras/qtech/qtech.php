<?php

    namespace hw\cameras {

        require_once __DIR__ . '/../cameras.php';

        class qtech extends cameras {

            public string $user = 'admin';

            protected string $def_pass = 'admin';
            protected string $api_prefix = '/api/';

            /** Сделать API-вызов */
            protected function api_call(string $target, string $action, array $data = null) {
                $req = $this->url . $this->api_prefix;

                $postfields = [
                    'target' => $target,
                    'action' => $action,
                    'session' => '',
                    'data' => $data,
                ];

                $ch = curl_init($req);

                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_USERPWD, "$this->user:$this->pass");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_VERBOSE, false);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postfields));

                $res = curl_exec($ch);
                curl_close($ch);

                return json_decode($res, true);
            }

            /** Преобразовать массив с параметрами в строку */
            protected function params_to_str(array $arr): string {
                $str = '';

                foreach ($arr as $key => $value) {
                    $str .= "$key:$value;";
                }

                return $str;
            }

            /** Установить параметры в секции config */
            protected function set_params(string $params) {
                return $this->api_call('config', 'set', [ 'config_key_value' => $params ]);
            }

            public function camshot(): string {
                $filename = uniqid('qtech_');
                $host = parse_url($this->url)['host'];
                system("ffmpeg -y -i http://$host:8080/streamvideo.cgi -vframes 1 /tmp/$filename.jpg 1>/dev/null 2>/dev/null");
                $shot = file_get_contents("/tmp/$filename.jpg");
                system("rm /tmp/$filename.jpg");
                return $shot;
            }

            public function get_sysinfo(): array {
                $res = $this->api_call('firmware', 'status');

                $sysinfo['DeviceID'] = str_replace(':', '', $res['data']['mac']);
                $sysinfo['DeviceModel'] = $res['data']['model'];
                $sysinfo['HardwareVersion'] = $res['data']['hardware'];
                $sysinfo['SoftwareVersion'] = $res['data']['firmware'];

                return $sysinfo;
            }

            public function set_admin_password(string $password) {
                $params = $this->params_to_str([
                    'Config.DoorSetting.APIFCGI.AuthMode' => 3,
                    'Config.Settings.WEB_LOGIN.Password' => $password, // WEB
                    'Config.DoorSetting.APIFCGI.Password' => $password, // API
                    'Config.DoorSetting.RTSP.Password' => $password, // RTSP
                ]);
                $this->set_params($params);
            }

            public function write_config() {
                // не используется
            }

            public function reboot() {
                $this->api_call('remote', 'reboot');
            }

            public function reset() {
                $this->api_call('remote', 'reset_factory');
            }

        }

    }
