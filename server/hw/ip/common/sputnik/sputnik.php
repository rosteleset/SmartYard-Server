<?php

namespace hw\ip\common\sputnik;

use DateTime;
use DateTimeZone;
use Exception;

/**
 * Trait providing common functionality related to Sputnik devices.
 */
trait sputnik
{

    public string $motherboardID;
    public string $uuid;

    public function configureEventServer(string $url)
    {
        foreach ($this->getWebhookUUIDs() as $webhookUUID) { // removing existing webhooks
            $this->apiCall('mutation', 'deleteWebhook', ['uuid' => $webhookUUID]);
        }

        $this->apiCall('mutation', 'createWebhook', [
            'event' => '*', // catch all events
            'url' => $url,
            'deviceIDS' => ['*'], // catch events from all panels
        ]);
    }

    public function configureNtp(string $server, int $port = 123, string $timezone = 'Europe/Moscow')
    {
        // TODO: doesn't change time in OSD, wait for fix
        $this->apiCall('mutation', 'updateIntercomTimeZone', [
            'intercomID' => $this->uuid,
            'timeZone' => $this->getOffsetByTimezone($timezone),
        ]);
    }

    public function getSysinfo(): array
    {
        $intercom = $this->apiCall('query', 'intercom', ['motherboardID' => $this->motherboardID], ['uuid']);
        $this->uuid = $uuid = $intercom['data']['intercom']['uuid'];
        return ['DeviceID' => $uuid];
    }

    public function reboot()
    {
        $this->apiCall('mutation', 'rebootIntercom', ['intercomID' => $this->uuid]);
    }

    public function reset()
    {
        $this->apiCall('mutation', 'restoreDefaultIntercomConfig', ['intercomID' => $this->uuid]);
    }

    public function setAdminPassword(string $password)
    {
        // Empty implementation
    }

    protected function apiCall($operation, $object, $args = [], $fields = ['success'])
    {
        if ($args) {
            $objectFunc = "$object(input:" . preg_replace_callback('/"ENUM::([^"]+)"/',
                    fn($match) => $match[1], preg_replace('/"([^"]+)"\s*:/', '\1:', json_encode($args))) . ')';
        } else {
            $objectFunc = $object;
        }

        $query = $operation . '{' . $objectFunc . '{' . $this->buildFields($fields) . '}}';
        // echo $query . PHP_EOL;

        $context = stream_context_create([
            'http' => [
                'header' =>
                    "Authorization: Bearer $this->password\r\n" .
                    "Content-Type: application/json\r\n",
                'method' => 'POST',
                'content' => json_encode(['query' => $query]),
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        return json_decode(file_get_contents($this->url, false, $context), true);
    }

    protected function buildFields($fields): string
    {
        $result = '';
        $addSpace = count($fields) > 1;

        foreach ($fields as $key => $value) {
            if (is_array($value)) {
                $result .= "$key{" . $this->buildFields($value) . '}';
            } else {
                $result .= $value;
            }

            if ($addSpace) {
                $result .= ' ';
            }
        }

        return trim($result);
    }

    protected function getEventServer(): string
    {
        $webhooks = $this->apiCall('query', 'webhooks', [], ['url'])['data']['webhooks'];
        return $webhooks[0]['url'] ?? 'http://127.0.0.1';
    }

    protected function getNtpConfig(): array
    {
        $intercom = $this->apiCall('query', 'intercom', ['uuid' => $this->uuid], [
            'configShadow' => ['timeZone'],
        ]);

        $timezone = $intercom['data']['intercom']['configShadow']['timeZone'];

        return [
            'server' => '',
            'port' => 123,
            'timezone' => $timezone,
        ];
    }

    /**
     * Get timezone representation for Sputnik.
     *
     * @param string $timezone Timezone identifier.
     *
     * @return string Offset without zeros (+3 for example).
     */
    protected function getOffsetByTimezone(string $timezone): string
    {
        try {
            $time = new DateTime('now', new DateTimeZone($timezone));
            $offset = $time->format('P');
            return preg_replace('/(?<=\+|)(0)(?=\d:\d{2})|:00/', '', $offset);
        } catch (Exception $e) {
            return '+3';
        }
    }

    protected function initializeProperties()
    {
        $urlParts = explode('/', $this->url);
        $this->motherboardID = array_pop($urlParts);
        $this->url = implode('/', $urlParts);
    }
}
