<?php

namespace hw\ip\common\is;

/**
 * Trait providing common functionality related to Sokol ISCom X1 (rev.2) device.
 */
trait iscomx1
{

    public function configureEventServer(string $url)
    {
        ['host' => $server, 'port' => $port] = parse_url_ext($url);

        $template = file_get_contents(__DIR__ . '/templates/custom.conf');
        $template .= "*.*;cron.none     @$server:$port;ProxyForwardFormat";
        $host = parse_url($this->url)['host'];
        exec(__DIR__ . "/scripts/upload_syslog_conf $host $this->login $this->password '$template'");
    }

    protected function getEventServer(): string
    {
        $host = parse_url($this->url)['host'];
        exec(__DIR__ . "/scripts/get_syslog_conf $host $this->login $this->password", $output);
        [$server, $port] = explode(':', explode(';', explode('@', $output[7])[1])[0]);

        return 'syslog.udp' . ':' . $server . ':' . $port;
    }
}
