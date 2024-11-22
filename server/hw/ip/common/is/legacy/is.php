<?php

namespace hw\ip\common\is\legacy;

trait is
{

    /**
     * @param string $url
     *
     * @return void
     *
     * @deprecated
     * @see configureEventSever()
     */
    protected function configureEventServerLegacy(string $url): void
    {
        ['host' => $server, 'port' => $port] = parse_url_ext($url);

        $template = file_get_contents(__DIR__ . '/templates/custom.conf');
        $template .= "*.*;cron.none     @$server:$port;ProxyForwardFormat";
        $host = parse_url($this->url)['host'];
        exec(__DIR__ . "/scripts/upload_syslog_conf $host $this->login $this->password '$template'");
    }

    /**
     * @return string
     *
     * @deprecated
     * @see getEventServer()
     */
    protected function getEventServerLegacy(): string
    {
        $host = parse_url($this->url)['host'];
        exec(__DIR__ . "/scripts/get_syslog_conf $host $this->login $this->password", $output);
        [$server, $port] = explode(':', explode(';', explode('@', $output[7])[1])[0]);

        return 'syslog.udp' . ':' . $server . ':' . $port;
    }
}
