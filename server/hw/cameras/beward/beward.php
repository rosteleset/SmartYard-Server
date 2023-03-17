<?php

    namespace hw\cameras {

        require_once __DIR__ . '/../cameras.php';

        class beward extends cameras {

            public string $user = 'admin';

            protected string $def_pass = 'admin';

            /** Сделать API-вызов */
            protected function api_call($method, $params = [], $post = false, $referer = false) {

                $query = '';

                foreach ($params as $param => $value) {
                    $query .= $param.'='.urlencode($value).'&';
                }

                if ($query) {
                    $query = substr($query, 0, -1);
                }

                if (!$post && $query) {
                    $req = $this->url.'/'.$method.'?'.$query;
                } else {
                    $req = $this->url.'/'.$method;
                }

                $ch = curl_init($req);

                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                curl_setopt($ch, CURLOPT_USERPWD, "$this->user:$this->pass");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.96 Safari/537.36');
                curl_setopt($ch, CURLOPT_VERBOSE, false);

                if ($post) {
                    curl_setopt($ch, CURLOPT_POST, true);
                    if ($query) {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
                    }
                }

                if ($referer) {
                    curl_setopt($ch, CURLOPT_REFERER, $referer);
                }

                $r = curl_exec($ch);
                curl_close($ch);

                return $r;
            }

            /** Распарсить ответ в массив */
            protected function parse_param_value(string $res): array {
                $ret = [];
                $res = explode("\n", trim($res));

                foreach ($res as $r) {
                    $r = explode('=', trim($r));
                    $ret[$r[0]] = @$r[1];
                }

                return $ret;
            }

            public function camshot(): string {
                return $this->api_call('cgi-bin/images_cgi', [ 'channel' => 0 ]);
            }

            public function get_sysinfo(): array {
                return $this->parse_param_value($this->api_call('cgi-bin/systeminfo_cgi', [ 'action' => 'get' ]));
            }

            public function set_admin_password(string $password) {
                $this->api_call('webs/umanageCfgEx', [
                    'uflag' => 0,
                    'uname' => $this->user,
                    'passwd' => $password,
                    'passwd1' => $password,
                    'newpassword' => '',
                ], true, "http://$this->url/umanage.asp");

                $this->api_call('cgi-bin/pwdgrp_cgi', [
                    'action' => 'update',
                    'username' => 'admin',
                    'password' => $password,
                    'blockdoors' => 1,
                ]);
            }

            public function write_config() {
                $this->api_call('cgi-bin/config_cgi', [ 'action' => 'forcesave' ]);
            }

            public function reboot() {
                $this->api_call('webs/btnHitEx', [ 'flag' => 21 ]);
            }

            public function reset() {
                $this->api_call('cgi-bin/hardfactorydefault_cgi');
            }

        }

    }
