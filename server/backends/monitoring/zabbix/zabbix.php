<?php

namespace backends\monitoring;
use backends\configs\json;

require_once __DIR__ . '/../../../utils/api_exec.php';

class zabbix extends monitoring
{
    const hostGroups = ['Intercoms', 'Cameras'];
    const intercomTemplateNames = ['Intercom_AKUVOX', 'Intercom_BEWARD', 'Intercom_QTECH'];
    const cameraTemplateNames = ['Camera_simple'];
    const templateGroups = ['Templates/Intercoms', 'Templates/Cameras'];
    const pluggedTemplateNames = ['ICMP Ping'];
    const intercomTemplateFiles = [
        'zbx_beward_intercom_template.yaml',
        'zbx_qtech_intercom_template.yaml',
        'zbx_akuvox_intercom_template.yaml',
    ];
    const intercomTemolatesDir = __DIR__ . "/../../../../install/zabbix/templates/intercom/";
    protected array $zbxData = [];
    protected string $zbxApi, $zbxToken;
    protected int $zbxStoreDays;
    public function __construct($config, $db, $redis, $login = false)
    {
        $this->zbxApi = $config["backends"]["monitoring"]["zbx_api_url"];
        $this->zbxToken = $config["backends"]["monitoring"]["zbx_token"];
        $this->zbxStoreDays = $config["backends"]["monitoring"]["store_days"];

        // TODO: проверить наличие шаблонов, если их нет - остановить работу
        /**
         * get actual item ids
         * TODO: store data to redis, update every hour for example
         */
        $this->getActualIds();
    }
    public function cron($part):bool
    {
        $result = true;
        if ($part === "5min"){
            $this->handle();
        }
        return $result;
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
     * Create starter configuration on Zabbix server
     * @return void
     */
    public function configureZbx(): void
    {
        /** TODO:
         *  - implement starter zabbix template, groups ...
         *  1 create host group
         *  2 create template group
         *  3 get template group ids
         *  4 get plugged template ids
         *  5 create target template
         *  6 import template from YAML file
         */

        // 01 - Create hot groups
        foreach (self::hostGroups as $hostGroup) {
            $this->createHostGroup($hostGroup);
        }

        // 02 - Create template croups
        foreach (self::templateGroups as $templateGroup){
            $this->createTemplateGroup($templateGroup);
        }

        // 03 - Get template group ids
        $templateGroups = $this->getTemplateGroups(self::templateGroups);
        foreach ($templateGroups as $templateGroup) {
            $this->zbxData['templateGroups'][$templateGroup['name']] = $templateGroup['groupid'];
        }

        // 04 - get plugged template ids
        $pluggetTemplates = $this->getTemplateIds(self::pluggedTemplateNames);
        foreach ($pluggetTemplates as $pluggedTemplate){
            $this->zbxData['pluggedTemplates'][$pluggedTemplate['host']] = $pluggedTemplate['templateid'];
        }

        // 05 - create target template
        foreach (self::intercomTemplateNames as $item) {
            $this->createTemplate($item, [$this->zbxData['templateGroups']['Templates/Intercoms']], array_values($this->zbxData['pluggedTemplates']));
        }

        // 06 - import config from YAML file
        // TODO: refactor
        foreach (self::intercomTemplateFiles as $fileName){
            $this->importConfig(self::intercomTemolatesDir . $fileName);
        }

        error_log("finish configure zabbix");

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
    public function apiCall($payload)
    {
        $method = 'POST';
        $url = $this->zbxApi;
        $token = $this->zbxToken;
        $contentType = 'application/json';
        $response = apiExec($method, $url, $payload, $contentType, $token);
        if ($response) return json_decode($response, true);
    }
    /**
     * Get actual item id from zabbix api
     * @return void
     */
    private function getActualIds(): void
    {
        //FIXME: refactor getTemplateIds and getGroupIds
        $templates = $this->getTemplateIds(self::intercomTemplateNames);
        $groups = $this->getGroupIds(self::hostGroups);

        if ($templates){
            foreach ($templates as $template) {
                $this->zbxData['templates'][$template['host']] = $template['templateid'];
            }
        }

        if ($groups){
            foreach ($groups as $group) {
                $this->zbxData['groups'][$group['name']] = $group['groupid'];
            }
        }
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
        if ($response && $response['result']) {
            return $response['result'];
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

        if ($response['result']) {
            return $response['result'];
        }

        return null;
    }
    /**
     * Get template groups
     * @param array $templateGroups
     * @return false|object
     */
    private function getTemplateGroups(array $templateGroups)
    {
        $body = [
            "jsonrpc" => "2.0",
            "method" => "templategroup.get",
            "params" => [
                "output" => [
                    "groupid",
                    "name"
                ],
                "filter" => [
                    "name" => $templateGroups,
                ]
            ],
            "id" => 1
        ];
        $response =  $this->apiCall($body);

        if ($response['result']) return $response['result'];

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
        if ($response && $response['result']) {
            return $response['result'][0]['groupid'];
        }

        return null;
    }

    /**
     * Import Zabbix template from YAML file
     * @param $fileName
     * @return mixed
     */
    private function importConfig($fileName)
    {
        $fileContent = file_get_contents($fileName);
        $templateData = yaml_parse($fileContent);
        if ($templateData === false) {
            error_log("Error reading file: $fileName");
            exit(1);
        }

        // yaml to string
        $templateDataStr = yaml_emit($templateData);

        $body = [
            "jsonrpc" => "2.0",
            "method" => "configuration.import",
            "params" => [
                "format" => "yaml",
                "rules" => [
                    "templates" => [
                        "createMissing" => true,
                        "updateExisting" => true
                    ],
                    "items" => [
                        "createMissing" => true,
                        "updateExisting" => true,
                        "deleteMissing" => true
                    ],
                    "triggers" => [
                        "createMissing" => true,
                        "updateExisting" => true,
                        "deleteMissing" => true
                    ],
                    "valueMaps" => [
                        "createMissing" => true,
                        "updateExisting" => false
                    ]
                ],
                "source" => $templateDataStr
            ],
            "id" => 1
        ];

        return $this->apiCall($body);

    }
    /**
     *  Create monitored host item
     * @param array $item
     * @param string $groupName
     * @return void
     */
    private function createHost(array $item, string $groupName): void
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
                "groupid" => $this->zbxData['groups'][$groupName]
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
                "templateid" => $this->zbxData['templates'][$item['template']],
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
    /** Create template
     * @param string $templateName
     * @param array $templategroups
     * @param array $pluggetTemplates
     * @return false|object
     */
    private function createTemplate(string $templateName, array $templategroups, array $pluggetTemplates)
    {
        $templates = [];
        $groups = [];
        // add plugged templates
        foreach ($pluggetTemplates as $item){
            $templates[] = ["templateid" => $item];
        }
        // add template groups
        foreach ($templategroups as $item){
            $groups[] = ["groupid" => $item];
        }

        $body = [
            "jsonrpc" => "2.0",
            "method" => "template.create",
            "params" => [
                "host" => $templateName,
                "groups" => $groups,
                "templates" => $templates
            ],
            "id" => 1
        ];

        return $this->apiCall($body);
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

        $this->apiCall('POST', $this->zbxApi, $body, 'application/json', $this->zbxToken);
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
    //
    /**
     * Find items missing in arr "b"
     * @param array $a
     * @param array $b
     * @param string $compareKey
     * @return array
     */
    private function compareArr(array $a, array $b, string $compareKey = 'host'): array
    {
        $result = [];
        // check if arr "b" is empty
        if (!empty($b)) {
            foreach ($a as $a_item) {
                $found = false;
                // find item "a" in array "b"
                foreach ($b as $b_item) {
                    if ($a_item[$compareKey] === $b_item[$compareKey]) {
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
     * Main handle, get actual hosts from Smart-Yard server and sync Zabbix server
     * @return void
     */
    private function handle(): void
    {
        // get data from RBT
        $domophones = $this->getDomophones();
        // get hosts from Zabbix API
        $intercomsFromZbx = $this->getHostsByGroupId($this->zbxData['groups']['Intercoms']);
        //  mapping zabbix data
        $intercomsFromZbxMapped = $this->zbxObjectMapping($intercomsFromZbx);
        // mapping db data
        $intercomsFromRBTMapped = $this->rbtObjectMapping($domophones);
        // Compare
        /**
         * Hosts missing on monitoring
         */
        $missing_in_zbx = $this->compareArr($intercomsFromRBTMapped, $intercomsFromZbxMapped);
        /**
         * Extra hosts on monitoring
         */
        $exclude_in_zbx = $this->compareArr($intercomsFromZbxMapped, $intercomsFromRBTMapped);
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
                    $delete_after = $timestamp + ($this->zbxStoreDays * 24 * 60 * 60);
                    if ($delete_after < time()){
                        $this->deleteHosts($item);
                    }
                }
            }
        }
    }
}