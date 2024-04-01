<?php

namespace hw\ip\common\is;

/**
 * Trait providing common functionality related to Sokol ISCom X1 Plus (rev.5) device.
 */
trait iscomx1plus
{

    public function configureEventServer(string $url)
    {
        ['host' => $server, 'port' => $port] = parse_url_ext($url);

        $this->apiCall('/v1/network/syslog', 'PUT', [
            'addr' => $server,
            'port' => (int)$port,
        ]);
    }

    protected function getEventServer(): string
    {
        ['addr' => $server, 'port' => $port] = $this->apiCall('/v1/network/syslog');
        return 'syslog.udp' . ':' . $server . ':' . $port;
    }
}
