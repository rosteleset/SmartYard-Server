<?php

    class rds {

        public $ip, $pass, $cookies;

        function req($uri, $data = '', $cookiesIn = '', $follow = false) {
            $options = [
                CURLOPT_RETURNTRANSFER => true,       // return web page
                CURLOPT_HEADER         => true,       // return headers in addition to content
                CURLOPT_FOLLOWLOCATION => $follow,    // follow redirects
                CURLOPT_ENCODING       => "",         // handle all encodings
                CURLOPT_AUTOREFERER    => true,       // set referer on redirect
                CURLOPT_CONNECTTIMEOUT => 120,        // timeout on connect
                CURLOPT_TIMEOUT        => 120,        // timeout on response
                CURLOPT_MAXREDIRS      => 10,         // stop after 10 redirects
                CURLINFO_HEADER_OUT    => true,
                CURLOPT_SSL_VERIFYPEER => true,       // Validate SSL Certificates
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_COOKIE         => $cookiesIn
            ];

            $ch = curl_init("http://".$this->ip."/".$uri);
            curl_setopt_array($ch, $options);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, "admin:{$this->pass}");
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [ 'application/x-www-form-urlencoded' ]);
            }
            $rough_content = curl_exec($ch);
            $header['errno'] = curl_errno($ch);
            $header['errmsg'] = curl_error($ch);
            $header = curl_getinfo($ch);
            curl_close($ch);

            $header_content = substr($rough_content, 0, $header['header_size']);
            $header['content'] = trim(str_replace($header_content, '', $rough_content));
            $pattern = "#Set-Cookie:\\s+(?<cookie>[^=]+=[^;]+)#m";
            preg_match_all($pattern, $header_content, $matches);
            $header['cookies'] = implode("; ", $matches['cookie']);

            $header['headers'] = $header_content;

            return $header;
        }

        function __construct($_ip, $_pass, $first_time = false) {
            $this->ip = $_ip;

            if ($first_time) {
                $this->pass = 'admin';
            } else {
                $this->pass = $_pass;
            }

            if (!$this->ping()) {
                throw new Exception("{$this->ip} is unavailable");
            }

            $this->pass = $_pass;
        }

        function ping() {
            $errno = false;
            $errstr = '';
            $fp = @fsockopen($this->ip, 80, $errno, $errstr, 1);
            if ($fp) {
                fclose($fp);
                $hn = @$this->sysinfo()['DeviceID'];
                if ($hn) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }

        function first_time() {
            $t = $this->pass;
            $this->pass = 'admin';
            $this->req("protect/config.htm", "dhcp=1&logn=admin&pass={$t}&hpr=80&upr=8283&b0=Save+%26+reboot");
        }

        function read() {
            // dummy
        }

        function begin() {
            // dummy
        }

        function done() {
            // dummy
        }

        function user1() {
            // dummy
        }

        function clean() {
            // dummy
        }

        function read_rfids() {
            return [];
        }

        function doors() {
            // dummy
        }

        function sysinfo() {
            $r = explode("\n", $this->req("protect/config.htm")['content']);

            $mac = '';
            $ver = '';

            foreach ($r as $l) {
                if (strpos($l, 'MAC Address:') !== false) {
                    $mac = explode('"', explode("value=", $l)[1])[1];
                }
                if (strpos($l, 'RODOS-') !== false && strpos($l, 'footer') !== false) {
                    $ver = explode(' ', explode("RODOS-", $l)[1])[1];
                }
            }

            return [ 'DeviceID' => $mac, 'SoftwareVersion' => $ver, ];
        }

        function display() {
            // dummy
        }

        function entrance() {
            // dummy
        }

        function configure_cms() {
            // dummy
        }

        function configure_apartment() {
            // dummy
        }

        function clear_apartment() {
            // dummy
        }

        function get_apartment() {
            // dummy
        }

        function add_rfid() {
            // dummy
        }

        function clear_rfid() {
            // dummy
        }

        function camshot() {
            return null;
        }

        function open() {
            $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
            $msg = "admin {$this->pass} k1=2";
            socket_sendto($socket, $msg, strlen($msg), 0, $this->ip, 8283);
        }

        function get_global_levels() {
            // dummy
        }

        function set_global_levels() {
            // dummy
        }

        function gate() {
            // dummy
        }

        function doorcode() {
            // dummy
        }

        function video_encoding() {
            // dummy
        }

        function has_individual_levels() {
            return false;
        }

        function md($level, $left = 0, $top = 0, $width = 0, $height = 0) {
            // dummy
        }

        function set_kms_levels() {
            // dummy
        }
    }
