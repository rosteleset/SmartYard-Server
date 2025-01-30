<?php

namespace hw\ip\common\sputnik;

/**
 * Trait providing common functionality related to Sputnik devices.
 */
trait sputnik
{

    public string $motherboardID;
    public string $uuid;

    public function configureEventServer(string $url): void
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

    public function getSysinfo(): array
    {
        $intercom = $this->apiCall('query', 'intercom', ['motherboardID' => $this->motherboardID], ['uuid']);
        $this->uuid = $uuid = $intercom['data']['intercom']['uuid'] ?? '';
        return ['DeviceID' => $uuid];
    }

    public function setAdminPassword(string $password): void
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

    protected function getWebhookUUIDs(): array
    {
        $webhooks = $this->apiCall('query', 'webhooks', [], ['uuid']);
        return array_column($webhooks['data']['webhooks'], 'uuid');
    }

    protected function initializeProperties(): void
    {
        $urlParts = explode('/', $this->url);
        $this->motherboardID = array_pop($urlParts);
        $this->url = implode('/', $urlParts);
    }
}
