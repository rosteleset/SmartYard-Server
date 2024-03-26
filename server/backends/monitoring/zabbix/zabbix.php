<?php

namespace backends\monitoring;

require_once __DIR__ . '/../../../utils/api_exec.php';

class zabbix extends monitoring
{
    const hostGroups = ['Intercoms', 'Cameras'];
    const templateGroups = ['Templates/Intercoms', 'Templates/Cameras'];
    const intercomTemplateNames = ['Intercom_AKUVOX', 'Intercom_BEWARD', 'Intercom_QTECH'];
    const cameraTemplateNames = ['Camera_simple'];
    const pluggedTemplateNames = ['ICMP Ping'];
    const intercomTemplateFiles = [
        'zbx_beward_intercom_template.yaml',
        'zbx_qtech_intercom_template.yaml',
        'zbx_akuvox_intercom_template.yaml',
    ];
    const cameraTemplateFiles = ['zbx_simple_camera_template.yaml'];
    const temolatesDir = __DIR__ . "/../../../../install/zabbix/templates";
    protected $zbxData = [];
    protected $zbxApi, $zbxToken;
    protected int $zbxStoreDays;

    /**
     * @throws \Exception
     */
    public function __construct($config, $db, $redis, $login = false)
    {
        try {
            $this->initializeZabbixApi($config);
            $this->getActualIds();
        } catch (\Exception $e) {
            $this->log("Zabbix Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * @inheritDoc
     */
    public function cron($part):bool
    {
        $result = true;
        if ($part === "5min"){
            $this->handleIntercoms();
            $this->handleCameras();
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
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
     * Create start configuration on Zabbix server
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
        // TODO : refactor "templategoupid" on running createTemplate
        foreach (self::intercomTemplateNames as $item) {
            $this->createTemplate(
                $item,
                [$this->zbxData['templateGroups']['Templates/Intercoms']],
                array_values($this->zbxData['pluggedTemplates'])
            );
        }

        // Camera templates
        foreach (self::cameraTemplateNames as $item) {
            $this->createTemplate(
                $item,
                [$this->zbxData['templateGroups']['Templates/Cameras']],
                array_values($this->zbxData['pluggedTemplates'])
            );
        }

        // 06 - import config from YAML file
        // TODO: refactor
        foreach (self::intercomTemplateFiles as $fileName){
            $this->importConfig(self::temolatesDir . "/intercom/" . $fileName);
        }

        foreach (self::cameraTemplateFiles as $fileName){
            $this->importConfig(self::temolatesDir . "/camera/" . $fileName);
        }

        $this->log("Finish configure zabbix");
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
     * Initialize Zabbix API
     * @param $config
     * @return void
     * @throws \Exception
     */
    private function initializeZabbixApi($config): void
    {
        $this->zbxApi = $config["backends"]["monitoring"]["zbx_api_url"];
        $this->zbxToken = $config["backends"]["monitoring"]["zbx_token"];
        $this->zbxStoreDays = $config["backends"]["monitoring"]["store_days"];
        if (!$this->zbxApi || !$this->zbxToken || !$this->zbxStoreDays) {
            throw new \Exception("Zabbix API configuration is incomplete, check './server/config/config.json'");
        }
    }

    /**
     * Get actual item id from zabbix api
     * @return void
     */
    private function getActualIds(): void
    {
        /**
         * TODO: store data to redis, update every hour for example
         */

        try {
            $templates = $this->getTemplateIds([... self::intercomTemplateNames, ... self::cameraTemplateNames]);
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
        } catch (\Exception $e) {
            $this->log("Error fetching template and group IDs from API");
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
            $subset[] = [
                "cameraId" => $camera["cameraId"],
                "enabled" => $camera["enabled"],
                "name" => $camera["name"],
                "vendor" => $camerasModels[$camera["model"]]["vendor"],
                "credentials" => $camera["credentials"],
                "ip" => $camera["ip"],
//                "stream" => $camera["stream"],
//                "dvrStream" => $camera["dvrStream"],
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

    private function getGroupIds(array $names): array|null
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
    private function importConfig(string $fileName)
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
     *  Create host on Zabbix server
     * @param array $item
     * @param string $groupName
     */
    private function createHost(array $item, string $groupName)
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
                "value" => $groupName
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

        return $this->apiCall($body);
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
    private function createTemplateGroup(string $templateName)
    {
        $body = [
            'jsonrpc' => '2.0',
            'method' => 'templategroup.create',
            'params' => ['name'=> $templateName],
            'id' => 1
        ];

        return $this->apiCall($body);
    }

    /**
     * Disable host and add tag "DISABLED: 1710495601 || 03/15/2024 09:40:01"
     * @param array $item
     */
    private function disableHost(array $item)
    {
        $now = time();
        $updateTags = $this->formatTags($item['tags']);
        $updateTags[] = [
            "tag" => "DISABLED",
            "value" => $now .' || '. date('m/d/Y H:i:s', $now),
        ];
        $body = [
            'jsonrpc' => '2.0',
            'method' => 'host.update',
            'params' => [
                'hostid' => $item['zbx_hostid'],
                'status' => 1,
                'tags' => $updateTags,
            ],
            'id' => 1
        ];

        return $this->apiCall($body);
    }

    private function formatTags(array $tags): array
    {
        $formatTags = [];
        foreach ($tags as $tag => $value){
            $formatTags[] = [
                'tag' => $tag,
                'value' => $value,
            ];
        }
        return  $formatTags;
    }

    /**
     * Enable monitoring. Enable host and remove tag "DISABLED"
     * @param array $item
     * @return void
     */
    private function enableHost(array $item)
    {
        $tags = $this->formatTags($item['tags']);
        $tags = array_filter($tags, function($item) {
            return $item['tag'] !== 'DISABLED';
        });

        $body = [
            'jsonrpc' => '2.0',
            'method' => 'host.update',
            'params' => [
                'hostid' => $item['zbx_hostid'],
                'status' => 0,
                'tags' => $tags,
            ],
            'id' => 1
        ];

        return $this->apiCall($body);
    }

    // TODO: refactor to mass delete
    private function deleteHosts($item): void
    {
        $body = [
            'jsonrpc' => '2.0',
            'method' => 'host.delete',
            'params' => [$item['zbx_hostid']],
            'id' => 1
        ];
        $this->apiCall($body);
    }

    /**
     * Delete host from Zabbix server by id
     * @param $id
     * @return false|object
     */
    private function deleteHost($id)
    {
        $body = [
            'jsonrpc' => '2.0',
            'method' => 'host.delete',
            'params' => [$id],
            'id' => 1
        ];
        return $this->apiCall($body);
    }

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

    private function isAllKeysNotEmpty(array $array): bool
    {
        $keys = array_keys($array);
        foreach ($keys as $key) {
            if (!isset($array[$key]) || $array[$key] === null || $array[$key] === '') {
                return false;
            }
        }
        return true;
    }

    /**
     * TODO: refactor "template"
     * @return array
     */
    private function getCamerasFromRBT()
    {
        $cameras = $this->getCameras();
        $mapped = [];
        foreach ($cameras as $item) {
            // FIXME: only vendor "FAKE"
            if ($item['vendor'] === 'FAKE' && $this->isAllKeysNotEmpty($item)){
                $mapped_item = [
                    'rbt_cameraId' => $item['cameraId'],
                    'status' => $item['enabled'] === 1,
                    'host' => $item['ip'],
                    'name' => $item['ip'] . ' | ' . $item['name'],
                    'template' => self::cameraTemplateNames[0],
                    'interface' => $item['ip'],
                    'credentials' => $item['credentials']
                ];
                $mapped[] = $mapped_item;
            }
        }
        return $mapped;
    }

    private function getDomophonesFromRBT()
    {
        $intercoms = $this->getDomophones();
        $mapped = [];

        foreach ($intercoms as $item) {
            if ($this->isAllKeysNotEmpty($item)){
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
            } else {
                $this->log("Error: Empty value found in RBT data for domophoneId: " . $item['domophoneId']);
            }
        }
        return $mapped;
    }

    private function getDomophonesFromZBX()
    {
        $raw = $this->getHostsByGroupId($this->zbxData['groups']['Intercoms']);
//        $this->log(var_export($raw, true));
        $mapped = [];

        foreach ($raw as $item) {
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
                    $mapped_item["tags"][$tag['tag']] =  $tag['value'];
                }
            }

            $mapped[] = $mapped_item;
        }
        return $mapped;
    }

    private function getCamerasFromZBX()
    {
        $raw = $this->getHostsByGroupId($this->zbxData['groups']['Cameras']);
        $mapped = [];
        if (!$raw) return  null;
        foreach ($raw as $item) {
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
                if ($macros["macro"] === '{$CAMERA_PASSWORD}'){
                    $mapped_item["credentials"] = $macros["value"];
                    break;
                }
            }

            // mapping tags
            if (count($item['tags']) > 0) {
                foreach ($item['tags'] as $tag) {
                    $mapped_item["tags"][$tag['tag']] =  $tag['value'];
                }
            }

            $mapped[] = $mapped_item;
        }

        return $mapped;
    }

    private function findHostInArray(array $targetHost, array $hostsArr): ?array
    {
        foreach ($hostsArr as $item) {
            if ($item['host'] === $targetHost['host']) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Handle sync intercoms with Zabbix server
     * @return void
     */
    public function handleIntercoms(): void
    {
        $rbtIntercoms = $this->getDomophonesFromRBT();
        $zbxIntercoms = $this->getDomophonesFromZBX();

        if ($rbtIntercoms){
            /**
             *  actions:
             *  - create missing host
             *  - enable host in monitoring
             *  - set disable status for disabled host
             *  - remove disabled host after expire store days
             */
            foreach ($rbtIntercoms as $rbtIntercom) {
                $zbxIntercom = $this->findHostInArray($rbtIntercom, $zbxIntercoms);

                // If host found in Zabbix server
                if ($zbxIntercom) {
                    if ($rbtIntercom['status'] === false && $zbxIntercom['status'] === true) {
                        $this->disableHost($zbxIntercom);
                    } elseif ($rbtIntercom['status'] === true && $zbxIntercom['status'] === false) {
                        $this->enableHost($zbxIntercom);
                    } elseif (
                        $rbtIntercom['status'] === false
                        && $zbxIntercom['status'] === false
                        && isset($zbxIntercom['tags']['DISABLED'])
                    ) {
                        // Remove disabled host over storage days
                        $disableTimestamp = (int)explode(' || ', $zbxIntercom['tags']['DISABLED'])[0];
                        $deleteAfter = $disableTimestamp + ($this->zbxStoreDays * 24 * 60 * 60);
                        if ($deleteAfter < time()){
                            $this->deleteHost($zbxIntercom['zbx_hostid']);
                        }
                    }
                } else {
                    if ($rbtIntercom['status'] === true) {
                        $this->createHost($rbtIntercom, "Intercoms");
                    }
                }
            }

            /**
             *  actions:
             *  - disable exclude host
             *  - delete disabled host after expire store days
             */
            foreach ($zbxIntercoms as $zbxIntercom){
                $host = $this->findHostInArray($zbxIntercom, $rbtIntercoms);
                if (!$host ) {
                    if ($zbxIntercom['status'] === true) {
                        $this->disableHost($zbxIntercom);
                    }
                    if ($zbxIntercom['status'] === false &&  $zbxIntercom['tags']['DISABLED']) {
                        $disableTimestamp = (int)explode(' || ', $zbxIntercom['tags']['DISABLED'])[0];
                        $deleteAfter = $disableTimestamp + ($this->zbxStoreDays * 24 * 60 * 60);
                        if ($deleteAfter < time()){
                            $this->deleteHost($zbxIntercom['zbx_hostid']);
                        }
                    }
                }
            }
        }
    }

    /**
     * Handle sync cameras with Zabbix server
     * @return void
     */
    public function handleCameras(): void
    {
        $rbtCameras = $this->getCamerasFromRBT();
        $zbxCameras = $this->getCamerasFromZBX();

        if ($rbtCameras){
            // First start, create all found cams
            if (!$zbxCameras) {
                foreach ($rbtCameras as $rbtCamera){
                    $this->createHost($rbtCamera, "Cameras");
                }
                exit(0);
            }

            foreach ($rbtCameras as $rbtCamera) {
            $zbxCamera = $this->findHostInArray($rbtCamera, $zbxCameras);

            // If host found in Zabbix server
            if ($zbxCamera) {
                if ($rbtCamera['status'] === false && $zbxCamera['status'] === true) {
                    // Set disabled status
                    $this->disableHost($zbxCamera);
                } elseif ($rbtCamera['status'] === true && $zbxCamera['status'] === false) {
                    // Set enabled status
                    $this->enableHost($zbxCamera);
                } elseif (
                    $rbtCamera['status'] === false
                    && $zbxCamera['status'] === false
                    && $zbxCamera['tags']['DISABLED']
                ) {
                    // Remove disabled host over storage days
                    $disableTimestamp = (int)explode(' || ', $zbxCamera['tags']['DISABLED'])[0];
                    $deleteAfter = $disableTimestamp + ($this->zbxStoreDays * 24 * 60 * 60);
                    if ($deleteAfter < time()){
                        $this->deleteHost($zbxCamera['zbx_hostid']);
                    }
                }
            } else {
                // Create missing host on Zabbix server
                if ($rbtCamera['status'] === true) {
                    $this->createHost($rbtCamera, "Cameras");
                }
            }
            }

            foreach ($zbxCameras as $zbxCamera) {
                // find exclude host in RBT
                $host = $this->findHostInArray($zbxCamera, $rbtCameras);
                if (!$host) {
                    // call disable host
                    if ($zbxCamera['status'] === true){
                        $this->disableHost($zbxCamera);
                    }

                    // handle disable host, delete after expire store days
                    if ($zbxCamera['status'] === false && isset($zbxCamera['tags']['DISABLED'])){
                        $disableTimestamp = (int)explode(' || ', $zbxCamera['tags']['DISABLED'])[0];
                        $deleteAfter = $disableTimestamp + ($this->zbxStoreDays * 24 * 60 * 60);
                        if ($deleteAfter < time()){
                            $this->deleteHost($zbxCamera['zbx_hostid']);
                        }
                    }
                }
            }
        }
    }

    private function log(string $text): void
    {
        $message = "ZBX || " . $text;
        error_log($message);
    }
}