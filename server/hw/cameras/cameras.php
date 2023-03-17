<?php

    namespace hw\cameras {

        require_once __DIR__ . '/../hw.php';

        use Exception;
        use hw\hw;

        abstract class cameras extends hw {

            public string $user;
            public string $pass;

            protected string $def_pass;

            /**
             * @throws Exception если камера недоступна
             */
            public function __construct(string $url, string $pass, bool $first_time = false) {
                parent::__construct($url);

                if ($first_time) {
                    $this->pass = $this->def_pass;
                    $this->set_admin_password($pass);
                    $this->write_config();
                }

                $this->pass = $pass;

                if (!$this->ping()) {
                    throw new Exception("$this->url is unavailable");
                }
            }

            public function __destruct() {
                $this->write_config();
            }

            public function ping(): bool {
                $errno = false;
                $errstr = '';

                $url = parse_url($this->url);
                if (!@$url['port']) {
                    switch (@strtolower($url['scheme'])) {
                        case 'http':
                            $url['port'] = 80;
                            break;
                        case 'https':
                            $url['port'] = 443;
                            break;
                        default:
                            $url['port'] = 22;
                            break;
                    }
                }
                $fp = @stream_socket_client($url['host'] . ":" . $url['port'], $errno, $errstr, 1);

                if ($fp) {
                    fclose($fp);

                    if (@$this->get_sysinfo()['DeviceID']) {
                        return true;
                    }

                    return false;
                }

                return false;
            }

            /** Сделать снимок */
            abstract public function camshot(): string;

            /** Получить системную информацию */
            abstract public function get_sysinfo(): array;

            /** Задать пароль доступа для admin */
            abstract public function set_admin_password(string $password);

            /** Принудительно сохранить настройки */
            abstract public function write_config();

        }

    }
