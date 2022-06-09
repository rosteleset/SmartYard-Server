<?php

    class snr {

        public $ip, $pass, $cookies;

        function req($uri, $cookiesIn = '', $follow = false) {
            $options = [
                CURLOPT_RETURNTRANSFER => true,     // return web page
                CURLOPT_HEADER         => true,     // return headers in addition to content
                CURLOPT_FOLLOWLOCATION => $follow,    // follow redirects
                CURLOPT_ENCODING       => "",       // handle all encodings
                CURLOPT_AUTOREFERER    => true,     // set referer on redirect
                CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
                CURLOPT_TIMEOUT        => 120,      // timeout on response
                CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
                CURLINFO_HEADER_OUT    => true,
                CURLOPT_SSL_VERIFYPEER => true,     // Validate SSL Certificates
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_COOKIE         => $cookiesIn
            ];

            $ch = curl_init("http://".$this->ip."/".$uri);
            curl_setopt_array($ch, $options);
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
                $this->pass = 'public';
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
            $this->req("commsett.cgi?sysn_set=SNR-ERD-4&sysl_set=&psw_set=".$this->pass, $this->cookies, false);
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

        function clean() {
            $this->req("time.cgi?time=10%3A10%3A00&date=12.02.2020&ntptz=3&ntpsrv=".MANAGEMENT_SRV, $this->cookies, false);
        }

        function read_rfids() {
            return [];
        }

        function doors() {
            // dummy
        }

        function sysinfo() {
            $i = $this->req("checkpassword.cgi?psw_check=".$this->pass, '', false);
            $this->cookies = $i['cookies'];
            $i = $this->req("index.shtml", $this->cookies, false)['content'];

            $s = explode("'", explode("sysName", $i)[1])[2];

            $v = explode("'", explode("Версия прошивки", $i)[1])[2];

            return [ 'DeviceID' => $s, 'SoftwareVersion' => $v, ];
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
            $this->req("outputs.cgi?outp0t=1&reload0=on", $this->cookies, false);
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

        function user1() {
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
