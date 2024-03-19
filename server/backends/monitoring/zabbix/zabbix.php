<?php

namespace backends\monitoring;
require_once __DIR__ . '/../../../utils/api_exec.php';

class zabbix extends monitoring
{
    const  hostGroups = ['Intercoms', 'Cameras'];
    const  templateNames = ['Intercom_AKUVOX', 'Intercom_BEWARD', 'Intercom_QTECH'];
    const  templateGroups = ['Templates/Intercoms', 'Templates/Cameras'];
    protected array $zbx_data = [];
    protected string $zbx_api, $zbx_token;
    protected int $zbx_store_days;
    public function __construct($config, $db, $redis, $login = false)
    {
        $this->zbx_api = $config["backends"]["monitoring"]["zbx_api_url"];
        $this->zbx_token = $config["backends"]["monitoring"]["zbx_token"];
        $this->zbx_store_days = $config["backends"]["monitoring"]["store_days"];

        // TODO: проверить наличие шаблонов, если их нет - остановить работу
        /**
         * get actual item ids
         * TODO: store data to redis, update every hour for example
         */
        $this->getActualIds();
    }
    /**
     * TODO: send debug msg
     * @param $payload
     * @return bool|string
     */
    private function send_echo($payload)
    {

        global $config;
        $debug = $config["backends"]["monitoring"]["debug"];;
        $debug_url = $config["backends"]["monitoring"]["debug_url"];;

        if ($debug){
            $payload = [
                'response' => $payload,
            ];

            return $this->apiCall("POST", $debug_url, $payload, 'application/json', false);
        }
    }
    /**
     * Get actual item id from zabbix api
     * @return void
     */
    private function getActualIds(): void
    {
        //FIXME: refactor getTemplateIds and getGroupIds
        $templates = $this->getTemplateIds(self::templateNames);
        $groups = $this->getGroupIds(self::hostGroups);

        if ($templates){
            foreach ($templates as $template) {
                $this->zbx_data['templates'][$template['host']] = $template['templateid'];
            }
        }

        if ($groups){
            foreach ($groups as $group) {
                $this->zbx_data['groups'][$group['name']] = $group['groupid'];
            }
        }
    }
    public function deviceStatus($deviceType, $deviceId)
    {
        switch ($deviceType) {
            case 'domophone':
                return [
                    "status" => "unknown",
                    "message" => i18n("monitoring.unknown"),
                ];

            case 'camera':
                return [
                    "status" => "unknown",
                    "message" => i18n("monitoring.unknown"),
                ];
        }
    }

    /**
     * Call Zabbix API
     * @param $method
     * @param $url
     * @param $payload
     * @param $contentType
     * @param $token
     * @return false|object
     */
    public function apiCall(array $payload)
    {
        $method = 'POST';
        $url = $this->zbx_api;
        $token = $this->zbx_token;
        $contentType = 'application/json';
        return apiExec($method, $url, $payload, $contentType, $token);
    }
    private function zbxObjectMapping($source): array
    {
        $mapped = [];
        foreach ($source as $item) {
            $mapped_item = [
                "zbx_hostid" => $item["hostid"],
                "status" => $item["status"] === "0",
                "host" => $item["host"],
                "name" => $item["name"],
                "template" => $item["parentTemplates"][0]["host"],
                "interface" => $item["interfaces"][0]["ip"]
            ];

            // mapping macros
            foreach ($item['macros'] as $macros) {
                if ($macros["macro"] === '{$INTERCOM_PASSWORD}'){
                    $mapped_item["credentials"] = $macros["value"];
                    break;
                }
            }

            // mapping tags
            if (count($item['tags']) > 0) {
                foreach ($item['tags'] as $tag) {
                    $mapped_item["tags"] = [$tag['tag'] => $tag['value']];
                }
            }

            $mapped[] = $mapped_item;
        }
        return $mapped;
    }
    private function rbtObjectMapping($source): array
    {
        $mapped = [];
        foreach ($source as $item) {
            $mapped_item = [
                'rbt_domophoneId' => $item['domophoneId'],
                'status' => $item['enabled'] === 1,
                'host' => $item['ip'],
                'name' => $item['ip'] . ' | ' . $item['name'],
                'template' => 'Intercom_' . $item['vendor'],
                'interface' => $item['ip'],
                'credentials' => $item['credentials']
            ];
            $mapped[] = $mapped_item;
        }
        return $mapped;
    }

    /**
     * Get current intercom list
     * @return array
     */
    private function getDomophones(): array
    {
        $households = loadBackend("households");
        $configs = loadBackend("configs");
        $domophonesModels = $configs->getDomophonesModels();
        $domophones = $households->getDomophones("all");
        foreach ($domophones as $domophone) {
            $subset [] = [
                "enabled" => $domophone["enabled"],
                "domophoneId" => $domophone["domophoneId"],
                "vendor" => $domophonesModels[$domophone["model"]]["vendor"],
                "name" => $domophone["name"],
                "ip" => $domophone["ip"],
                "credentials" => $domophone["credentials"]
            ];
        }

        return $subset;
    }

    /**
     * Get current camera list
     * @return array
     */
    private function getCameras(): array
    {
        $cameras = loadBackend("cameras");
        $configs = loadBackend("configs");
        $camerasModels = $configs->getCamerasModels();
        $allCameras = $cameras->getCameras();
        foreach ($allCameras as $camera) {
            $subset [] = [
                "cameraId" => $camera["cameraId"],
                "enabled" => $camera["enabled"],
                "vendor" => $camerasModels[$camera["model"]]["vendor"],
                "stream" => $camera["stream"],
                "credentials" => $camera["credentials"],
                "dvrStream" => $camera["dvrStream"],
            ];
        }

        return $subset;
    }

    /**
     *  Create monitored host item
     * @param array $item
     * @param string $group_name
     * @return void
     */
    private function createHost(array $item, string $group_name): void
    {
        $params = [
        'host' => $item['host'],
        'name' => $item['name'],
        'interfaces' => [
            [
                "type" => 1,
                "main" => 1,
                "useip" => 1,
                "ip" => $item['interface'],
                "dns" => "",
                "port" => "10050"
            ]
        ],
        'groups' => [
            [
                "groupid" => $this->zbx_data['groups'][$group_name]
            ]
        ],
        'tags' => [
            [
                "tag" => "Host type",
                "value" => "Intercom"
            ]
        ],
        'templates' => [
            [
                "templateid" => $this->zbx_data['templates'][$item['template']],
            ]
        ],
        'macros' => [
            [
                "macro" => '{$INTERCOM_PASSWORD}',
                "value" => $item['credentials'],
            ]
        ]
         ];

        $body = [
            'jsonrpc' => "2.0",
            'method' => "host.create",
            'params' => $params,
            'id' => 1
        ];

        $this->apiCall($body);
    }
    /**
     * TODO: not used
     * Create hots group
     * @param array $groupNames
     * @return void
     * @example  ['Cameras', 'Intercoms']
     */
    private function createHostGroups(array $groupNames): void
    {
        $params = [];

        foreach ($groupNames as $groupName) {
            $params[] = ['name'=> $groupName];
        }

        $body = [
            'jsonrpc' => '2.0',
            'method' => 'hostgroup.create',
            'params' => $params,
            'id' => 1
        ];

        $this->apiCall('POST', $this->zbx_api, $body, 'application/json', $this->zbx_token);
    }
    private function createHostGroup(string $groupName): void
    {
        $body = [
            'jsonrpc' => '2.0',
            'method' => 'hostgroup.create',
            'params' => ['name'=> $groupName],
            'id' => 1
        ];

        $this->apiCall($body);
    }
    // TODO: not used
    private function createTemplateGroups(array $templateNames): void
    {
        $params = [];

        foreach ($templateNames as $templateName) {
            $params[] = ['name'=> $templateName];
        }

        $body = [
            'jsonrpc' => '2.0',
            'method' => 'templategroup.create',
            'params' => $params,
            'id' => 1
        ];

        $this->apiCall($body);
    }
    /**
     * Create group template
     * @param string $templateName
     * @return void
     */
    private function createTemplateGroup(string $templateName): void
    {
        $body = [
            'jsonrpc' => '2.0',
            'method' => 'templategroup.create',
            'params' => ['name'=> $templateName],
            'id' => 1
        ];

        $this->apiCall($body);
    }
    /**
     * Disable host and add tag "DISABLED: 1710495601 || 03/15/2024 09:40:01"
     * @param array $item
     * @return void
     */
    private function disableHost(array $item):void
    {
        $now = time();
        $body = [
            'jsonrpc' => '2.0',
            'method' => 'host.update',
            'params' => [
                'hostid' => $item['zbx_hostid'],
                'status' => 1,
                'tags' => [
                    [
                        "tag" => "DISABLED",
                        "value" => $now .' || '. date('m/d/Y H:i:s', $now),
                    ]
                ]
            ],
            'id' => 1
        ];
        $this->apiCall($body);
    }
    /**
     * Enable monitoring
     * @param array $item -  zabbix hostid
     * @return void
     */
    private function enableHost(array $item):void
    {
        $body = [
            'jsonrpc' => '2.0',
            'method' => 'host.update',
            'params' => [
                'hostid' => $item['zbx_hostid'],
                'status' => 0,
                'tags' => [],
            ],
            'id' => 1
        ];
        $this->apiCall($body);
    }
    // TODO: refactor to mass delete
    private function deleteHosts($item):void
    {
        $body = [
            'jsonrpc' => '2.0',
            'method' => 'host.delete',
            'params' => [$item['zbx_hostid']],
            'id' => 1
        ];
        $this->apiCall($body);
    }
    private function getItems()
    {
        // implement api call to get monitored items from zabbix
        $body = [
            "jsonrpc" => "2.0",
            "method" => "hostgroup.get",
            "params" => [
                "output" => [
                    "groupid",
                    "name",
                ],
                "filter" => [
                    "name" => [
                        "Intercoms",
                        "Cameras"
                    ]
                ]
            ],
            "id" => 1
        ];

        $response = $this->apiCall($body);
        $response = json_decode($response, true);
        if ($response && $response['result']) {
            return $response['result'];
        }
        return null;
    }
    /**
     * Get group id by name
     * @param $name
     * @return mixed|null
     */
    private function getGroupId($name):int|null
    {
        // implement api call to get monitored items from zabbix
        $body = [
            'jsonrpc' => '2.0',
            'method' => 'hostgroup.get',
            'params' => [
                'output' => ['groupid'],
                'filter' => [
                    "name" => [$name]
                ],
                'limit' => 1
            ],
            'id' => 1
        ];
        $response = $this->apiCall($body);
        $response = json_decode($response, true);
        if ($response && $response['result']) {
            return $response['result'][0]['groupid'];
        }

        return null;
    }
    private function getGroupIds(array $names):array|null
    {
        // implement api call to get monitored items from zabbix
        $body =  [
            'jsonrpc' => '2.0',
            'method' => 'hostgroup.get',
            'params' => [
                'output' => [
                    'groupid',
                    'name'
                ],
                'filter' => [
                    'name' => $names
                ]
            ],
            'id' => 1
        ];

        $response = $this->apiCall($body);
        $response = json_decode($response, true);

        if ($response && $response['result']) {
            return $response['result'];
        }

        return null;
    }
    private function getTemplateIds($name)
    {
        // implement api call to get monitored items from zabbix
        $body = [
            'jsonrpc' => '2.0',
            'method' => 'template.get',
            'params' => [
                'output' => [
                    'host',
                    'templateid',
                    'name'
                ],
                'filter' => [
                    'host' => $name
                ]
            ],
            'id' => 1
        ];

        $response = $this->apiCall($body);
        $response = json_decode($response, true);

        if ($response && $response['result']) {
            return $response['result'];
        }

        return null;
    }
    private function getHostsByGroupId($id){
        $body = [
            "jsonrpc" => "2.0",
            "method" => "host.get",
            "params" => [
                "output" => [
                    "hostid",
                    "status",
                    "host",
                    "name",
                ],
                "groupids" => $id,
                "selectInterfaces" => ["ip", "dns"],
                "selectParentTemplates" => ["host"],
                "selectMacros" => ["hostmacroid", "macro", "value"],
                "selectTags" => "extend"
            ],
            "id" => 1
        ];
        $response = $this->apiCall($body);
        $response = json_decode($response, true);

        if ($response && $response['result']) {
            return $response['result'];
        }

        return null;
    }
    /**
     * Find items missing in arr "b"
     * @param array $a
     * @param array $b
     * @param string $compare_key
     * @return array
     */
    private function compare_arr(array $a, array $b, string $compare_key = 'host'): array
    {
        $result = [];
        // check if arr "b" is empty
        if (!empty($b)) {
            foreach ($a as $a_item) {
                $found = false;
                // find item "a" in array "b"
                foreach ($b as $b_item) {
                    if ($a_item[$compare_key] === $b_item[$compare_key]) {
                        $found = true;
                        break;
                    }
                }
                // If no matches are found, add the current element to the result
                if (!$found) {
                    $result[] = $a_item;
                }
            }
        } else {
            // If array $b is empty, add all items from array $a to result
            $result = $a;
        }

        return $result;
    }
    /**
     * Create starter configuration on Zabbix server
     * @return void
     */
    private function configureTemplates(): void
    {
        //
        /** TODO:
         *  - implement starter zabbix template, groups ...
         *  - check if template already exist
         *  - create templates
         */
        foreach (self::hostGroups as $hostGroup) {
            $this->createHostGroup($hostGroup);
        }

        // Create template croups
        foreach (self::templateGroups as $templateGroup){
            $this->createTemplateGroup($templateGroup);
        }


    }
    private function handle(): void
    {
        // implement main logic
        // get data from RBT
        $domophones = $this->getDomophones();
        // get hosts from Zabbix API
        $intercomsFromZbx = $this->getHostsByGroupId($this->zbx_data['groups']['Intercoms']);
        //  mapping zabbix data
        $intercomsFromZbxMapped = $this->zbxObjectMapping($intercomsFromZbx);
        // mapping db data
        $intercomsFromRBTMapped = $this->rbtObjectMapping($domophones);
        // Compare
        /**
         * Hosts missing on monitoring
         */
        $missing_in_zbx = $this->compare_arr($intercomsFromRBTMapped, $intercomsFromZbxMapped);
        /**
         * Extra hosts on monitoring
         */
        $exclude_in_zbx = $this->compare_arr($intercomsFromZbxMapped, $intercomsFromRBTMapped);

        // create missing hosts
        if ($missing_in_zbx) {
            foreach ($missing_in_zbx as $item){
                // FIXME: refactor group_name param
                $this->createHost($item, "Intercoms");
            }
        }

        // disable or remove extra hosts on monitoring
        if ($exclude_in_zbx) {
            foreach ($exclude_in_zbx as $item){
                /**
                 * Set host state disabled
                 * Before deleting, turn off the host, add a tag with the date of shutdown
                 */
                if ($item['status'] === true) {
                    $this->disableHost($item);
                }
                /**
                 * Delete host
                 * Zabbix does not store host shutdown history.
                 * Getting the host shutdown date from the previously added tag.
                 * Example: "DISABLED": "1703333582 || 12/23/2023 12:13:02"
                 */
                if ($item['status'] === false && $item['tags']['DISABLED']){
                    $timestamp = (int)explode(' || ', $item['tags']['DISABLED'])[0];
                    $delete_after = $timestamp + ($this->zbx_store_days * 24 * 60 * 60);
                    if ($delete_after < time()){
                        $this->deleteHosts($item);
                    }
                }
            }
        }
    }
    public function cron($part):bool
    {
        global $config;
        $result = true;
        if ($part === "5min"){
            $this->handle();
        }
        return $result;
    }
}