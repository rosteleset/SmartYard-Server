<?php

    /**
     * backends dvr namespace
     */

    namespace backends\dvr
    {
        class internal extends dvr
        {

            /**
             * @inheritDoc
             */
            public function serverType($url)
            {
                $dvr_servers = $this->getDVRServers();

                $url = parse_url($url);
                $scheme = $url["scheme"] ?: 'http';
                $port = (int)$url["port"];

                if (!$port && $scheme == 'http') $port = 80;
                if (!$port && $scheme == 'https') $port = 443;

                $result = [ 'type' => 'flussonic' ]; // result by default if server not found in dvr_servers settings

                foreach ($dvr_servers as $server) {
                    $u = parse_url($server['url']);

                    if (
                        ($u['scheme'] == $scheme) &&
                        (!@$u['user'] || @$u['user'] == @$url["user"]) &&
                        (!@$u['pass'] || @$u['pass'] == @$url["pass"]) &&
                        ($u['host'] == $url["host"]) &&
                        (!$u['port'] || $u['port'] == $port)
                    ) {
                        $result = $server;
                        break;
                    }
                }

                return $result;
            }

            /**
             * @inheritDoc
             */
            public function getDVRServers()
            {
                return @$this->config["backends"]["dvr"]["servers"];
            }
        }
    }
