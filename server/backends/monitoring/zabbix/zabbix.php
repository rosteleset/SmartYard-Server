<?php

namespace backends\monitoring;

require_once __DIR__ . '/../../../utils/api_exec.php';

class zabbix extends monitoring
{
    protected $zbxData = [];
    protected $zbxApi, $zbxToken, $scheduler;
    protected $zbxStoreDays;
    protected $hostGroups = [];
    protected $templateGroups = [];
    protected $intercomTemplateNames = [];
    protected $cameraTemplateNames = [];
    protected $pluggedTemplateNames = [];
    protected $templatesDir;

    /**
     * @throws \Exception
     */
    public function __construct($config, $db, $redis, $login = false)
    {
        try {
            parent::__construct($config, $db, $redis, $login);
            require_once __DIR__ . '/../../../utils/api_exec.php';

            $this->initializeZabbixApi($config);
            $this->getActualIds();
        } catch (\Exception $e) {
            $this->log("Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * @inheritDoc
     */
    public function cron($part)
    {
        try {
            $result = false;
            if ($part === $this->scheduler){
                $this->handleIntercoms();
                $this->handleCameras();
                $result = true;
                $this->log("cron task finish");
            }
        } catch (\Exception $e) {
            $this->log($e);
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
     * @throws \Exception
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
        $this->createHostGroups($this->hostGroups);
        $this->createTemplateGroups($this->templateGroups);

        $this->getTemplateGroupIds($this->templateGroups);
        $this->getPluggedTemplateIds($this->pluggedTemplateNames);

        $this->createTargetTemplates($this->intercomTemplateNames, $this->zbxData['templateGroups']['Templates/Intercoms']);
        $this->createTargetTemplates($this->cameraTemplateNames, $this->zbxData['templateGroups']['Templates/Cameras']);

        $this->importTemplateConfigFiles($this->templatesDir, "intercom");
        $this->importTemplateConfigFiles($this->templatesDir, "camera");

        $this->importTemplateConfigFiles($this->templatesDir, "services");

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

        if (is_object($response)
            && property_exists($response, 'message')
            && property_exists($response, 'code')
        ) {
            throw new \Exception("api call error: " . $response->message . " (code: $response->code)");
        }

        $response = json_decode($response, true);

        // Check Zabbix API jsonrpc error
        if (isset($response['error'])) {
            throw new \Exception("Zabbix API error: " . var_export($response['error'], true));
        }

        if ($response['result']) {
            return $response['result'];
        } else {
            return null;
        }
    }

    /**
     * Initialize Zabbix API
     * @param $config
     * @return void
     * @throws \Exception
     */
    private function initializeZabbixApi($config): void
    {
        $zbxConfig = $config["backends"]["monitoring"];
        $requiredConfigKeys = [
            'cron_sync_data_scheduler',
            'zbx_api_url',
            'zbx_token',
            'zbx_store_days',
            'cron_sync_data_scheduler',
            'zbx_data_collection',
        ];

        foreach ($requiredConfigKeys as $key) {
            if (!isset($zbxConfig[$key])) {
                throw new \Exception("Required key '$key' is missing in Zabbix API configuration. Check config.");
            }
        }

        $this->zbxApi = $zbxConfig["zbx_api_url"];
        $this->zbxToken = $zbxConfig["zbx_token"];
        $this->zbxStoreDays = $zbxConfig["zbx_store_days"];
        $this->scheduler = $zbxConfig["cron_sync_data_scheduler"];
        $this->useCashe = $zbxConfig["use_cache"];

        $this->hostGroups = $zbxConfig["zbx_data_collection"]["host_groups"];
        $this->templateGroups = $zbxConfig["zbx_data_collection"]["template_groups"];
        $this->intercomTemplateNames = $zbxConfig["zbx_data_collection"]["intercom_template_names"];
        $this->cameraTemplateNames = $zbxConfig["zbx_data_collection"]["camera_template_names"];
        $this->pluggedTemplateNames = $zbxConfig["zbx_data_collection"]["plugged_template_names"];

        $templatePath = __DIR__ . "/../../../.." . $zbxConfig["zbx_data_collection"]["templates_dir"];
        if (!is_dir($templatePath)) {
           throw new Exception("Error: template directory does not exist: $templatePath");
        }
        $this->templatesDir = realpath($templatePath);
    }

    /**
     * Create host groups in Zabbix.
     */
    private function createHostGroups(array $hostGroups): void
    {
        /**
         * TODO:
         *  - get existing groups on zabbix server
         *  - create missing groups
         */
        $this->log("RUN createHostGroups, groups:");

        $existGroups = $this->getGroupIds($hostGroups);

        foreach ($hostGroups as $hostGroupName) {
            $groupExist = false;

            // find target hot group name in existing groups
            foreach ($existGroups as $existGroup) {
               if ($existGroup['name'] === $hostGroupName){
                   $groupExist = true;
                   break;
               }
            }

            // Create missing group
            if (!$groupExist) {
                $this->log("Create missing host group: " . $hostGroupName);
                $this->createHostGroup($hostGroupName);
            }
        }
    }

    /**
     * Get actual item id from zabbix api
     * @return void
     * @throws \Exception
     */
    private function getActualIds(): void
    {
        /**
         * TODO: store data to redis, update every hour for example
         */
        $templates = $this->getTemplateIds([... $this->intercomTemplateNames, ... $this->cameraTemplateNames]);
        $groups = $this->getGroupIds($this->hostGroups);
        if ($templates) {
            foreach ($templates as $template) {
                $this->zbxData['templates'][$template['host']] = $template['templateid'];
            }
        }
        if ($groups) {
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
        $subset = [];

        foreach ($domophones as $domophone) {
            $subset [] = [
                "enabled" => $domophone["enabled"],
                "domophoneId" => $domophone["domophoneId"],
                "vendor" => rtrim(
                    $domophonesModels[$domophone["model"]]["vendor"]
                    . "_"
                    . $domophonesModels[$domophone["model"]]["model"],
                    "*"
                ),
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
        $subset = [];

        foreach ($allCameras as $camera) {
            $subset[] = [
                "cameraId" => $camera["cameraId"],
                "enabled" => $camera["enabled"],
                "name" => $camera["name"],
                "vendor" => $camerasModels[$camera["model"]]["vendor"],
                "credentials" => $camera["credentials"],
                "ip" => $camera["ip"],
                // TODO: not used fields
                //"stream" => $camera["stream"],
                //"dvrStream" => $camera["dvrStream"],
            ];
        }

        return $subset;
    }

    /**
     * @throws \Exception
     */
    private function getGroupIds(array $names)
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

        if ($response) {
            return $response;
        }

        return null;
    }

    /**
     * Get monitored items from Zabbix server
     * @param $name
     * @return mixed|null
     */
    private function getTemplateIds($name)
    {
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

        if ($response) {
            return $response;
        }

        return null;
    }

    /**
     * Get template groups from Zabbix server
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

        if ($response) {
            return $response;
        }

        return null;
    }

    /**
     * Get hosts by "groupid" from Zabbix server
     * @param $id
     * @return mixed|null
     */
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

        if ($response) {
            return $response;
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
        if ($response) {
            return $response[0]['groupid'];
        }

        return null;
    }

    /**
     * Import Zabbix template from YAML file
     * @param $fileName
     * @return mixed
     * @throws \Exception
     */
    private function importConfig(string $fileName)
    {
        $fileContent = file_get_contents($fileName);
        $templateData = yaml_parse($fileContent);
        if ($templateData === false) {
            throw new \Exception("Error reading file: $fileName");
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
                        "deleteMissing" => true,
                        "updateExisting" => true
                    ],
                    "httptests" => [
                        "createMissing" => true,
                        "deleteMissing" => true,
                        "updateExisting" => true
                    ]
                ],
                "source" => $templateDataStr
            ],
            "id" => 1
        ];

        return $this->apiCall($body);
    }

    /**
     * Import template configuration files in Zabbix.
     * @param $templatePath
     * @param $templateDir
     * @return void
     */
    private function importTemplateConfigFiles($templatePath, $templateDir )
    {
        $fullTemplateDir = rtrim($templatePath, '/') . '/' . $templateDir;
        if (!is_dir($fullTemplateDir)) {
            $this->log("error: '$fullTemplateDir' does not exist.");
            return;
        }

        // gel yaml files
        $files = glob($fullTemplateDir . '/*.yaml');
        if (empty($files)) {
            $this->log("error: no YAML files found in directory '$fullTemplateDir'.");
            return;
        }

        foreach ($files as $file) {
            $this->importConfig($file);
        }

        $this->log("Import $templateDir templates finish");
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
                "macro" => '{$HOST_PASSWORD}',
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

    /**
     * Create template on Zabbix server
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
     * Create host group on Zabbix server
     * @param string $groupName
     * @return void
     */
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

    /**
     * Create template groups in Zabbix.
     * @throws \Exception
     */
    private function createTemplateGroups(array $templateGroups): void
    {
        $this->log("RUN createTemplateGroups, groups:");
        $existTemplateGroups = $this->getTemplateGroups($templateGroups);

        foreach ($templateGroups as $templateGroup) {
            $groupExist = false;
            foreach ($existTemplateGroups as $existTemplateGroup) {
                if($existTemplateGroup['name'] === $templateGroup) {
                    $groupExist = true;
                    break;
                }
            }

            if (!$groupExist) {
                $this->log("Create missing template group: " . $templateGroup);
                $this->createTemplateGroup($templateGroup);
            }
        }
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
     * @throws \Exception
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
     * Create target templates on Zabbix server
     * @param array $templateNames
     * @param int $templateGroupId
     * @return void
     * @throws \Exception
     */
    private function createTargetTemplates(array $templateNames, int $templateGroupId): void
    {
        $this->log("RUN createTargetTemplates:");
        $exitsTemplates = $this->getTemplateIds($templateNames);

        foreach ($templateNames as $templateName) {
            $templateExist = false;
            foreach ($exitsTemplates as $exitsTemplate) {
                if ($exitsTemplate['host'] === $templateName){
                    $templateExist = true;
                    break;
                }
            }

            if (!$templateExist){
                $this->log("create template > " . $templateName);

                $this->createTemplate(
                    $templateName,
                    [$templateGroupId],
                    array_values($this->zbxData['pluggedTemplates'])
                );

            }
        }
    }

    /**
     * Check array helper
     * @param array $array
     * @return bool
     */
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
     * Get template group IDs on Zabbix server
     */
    private function getTemplateGroupIds(array $templateGroups): void
    {
        $templateGroupsInfo = $this->getTemplateGroups($templateGroups);
        foreach ($templateGroupsInfo as $templateGroup) {
            $this->zbxData['templateGroups'][$templateGroup['name']] = $templateGroup['groupid'];
        }
    }

    /**
     * Get plugged template IDs on Zabbix server
     * @param array $pluggedTemplateNames
     * @return void
     */
    private function getPluggedTemplateIds(array $pluggedTemplateNames): void
    {
        $pluggedTemplates = $this->getTemplateIds($pluggedTemplateNames);
        foreach ($pluggedTemplates as $pluggedTemplate) {
            $this->zbxData['pluggedTemplates'][$pluggedTemplate['host']] = $pluggedTemplate['templateid'];
        }
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
                    'template' => $this->cameraTemplateNames[0],
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
                    'template' => 'Intercom_' . strtoupper(str_replace(' ', '_', $item['vendor'])),
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
                if ($macros["macro"] === '{$HOST_PASSWORD}'){
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

    /**
     * Find host in array helper
     * @param array $targetHost
     * @param array $hostsArr
     * @return array|null
     */
    private function findHostInArray(array $targetHost, array $hostsArr): ?array
    {
        foreach ($hostsArr as $item) {
            if ($item['host'] === $targetHost['host']) {
                return $item;
            }
        }
        return null;
    }

    private function deleteHostIfNeeded(array $item, int $deleteTimestamp): void
    {
        if ($deleteTimestamp < time()) {
            $this->deleteHost($item['zbx_hostid']);
        }
    }

    /**
     * Handle sync intercoms with Zabbix server
     * @return void
     */
    public function handleIntercoms(): void
    {
        $rbtIntercoms = $this->getDomophonesFromRBT();
        $zbxIntercoms = $this->getDomophonesFromZBX();

        if ($rbtIntercoms && $zbxIntercoms) {
            $this->handleDevices($rbtIntercoms, $zbxIntercoms, "Intercoms");
        } elseif (!$zbxIntercoms) {
            $this->log("first start, create intercom items");
            foreach ($rbtIntercoms as $rbtIntercoms) {
                $this->createHost($rbtIntercoms, "Intercoms");
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

        if ($rbtCameras && $zbxCameras) {
            $this->handleDevices($rbtCameras, $zbxCameras, "Cameras");
        } elseif (!$zbxCameras){
            $this->log("first start, create camera items");
            foreach ($rbtCameras as $rbtCamera) {
                $this->createHost($rbtCamera, "Cameras");
            }
        }
    }

    private function handleDevices(array $rbtDevices, array $zbxDevices, string $groupName): void
    {
        if ($rbtDevices) {
            foreach ($rbtDevices as $rbtDevice) {
                $zbxDevice = $this->findHostInArray($rbtDevice, $zbxDevices);

                if ($zbxDevice) {
                    if ($rbtDevice['status'] === false && $zbxDevice['status'] === true) {
                        $this->disableHost($zbxDevice);
                    } elseif ($rbtDevice['status'] === true && $zbxDevice['status'] === false) {
                        $this->enableHost($zbxDevice);
                    } elseif (
                        $rbtDevice['status'] === false
                        && $zbxDevice['status'] === false
                        && isset($zbxDevice['tags']['DISABLED'])
                    ) {
                        $disableTimestamp = (int)explode(' || ', $zbxDevice['tags']['DISABLED'])[0];
                        $deleteAfter = $disableTimestamp + ($this->zbxStoreDays * 24 * 60 * 60);
                        $this->deleteHostIfNeeded($zbxDevice, $deleteAfter);
                    }
                } else {
                    if ($rbtDevice['status'] === true) {
                        $this->createHost($rbtDevice, $groupName);
                    }
                }
            }

            foreach ($zbxDevices as $zbxDevice) {
                $device = $this->findHostInArray($zbxDevice, $rbtDevices);
                if (!$device) {
                    if ($zbxDevice['status'] === true) {
                        $this->disableHost($zbxDevice);
                    }
                    if ($zbxDevice['status'] === false &&  $zbxDevice['tags']['DISABLED']) {
                        $disableTimestamp = (int)explode(' || ', $zbxDevice['tags']['DISABLED'])[0];
                        $deleteAfter = $disableTimestamp + ($this->zbxStoreDays * 24 * 60 * 60);
                        $this->deleteHostIfNeeded($zbxDevice, $deleteAfter);
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