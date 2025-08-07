<?php

namespace backends\monitoring;

use Exception;

enum Triggers: string
{
    case ICMP = 'ICMP: Unavailable by ICMP ping';
    case SIP =  'SIP: Registration failure';
    case HTTP = 'HTTP: port/service unreachable (ICMP OK)';
}

enum DeviceType: string
{
    case INTERCOMS = 'Intercoms';
    case CAMERAS = 'Cameras';
}

class zabbix extends monitoring
{
    protected array $zbxData = [];
    protected readonly string $zbxApi, $zbxToken, $scheduler;
    protected bool $useCache, $enableLogging;
    protected int $zbxStoreDays;
    protected array $hostGroups = [];
    protected array $templateGroups = [];
    protected array $intercomTemplateNames = [];
    protected array $cameraTemplateNames = [];
    protected array $pluggedTemplateNames = [];
    protected string $templatesDir;
    protected string $cameraVendor = 'FAKE';

    /**
     * @throws Exception
     */
    public function __construct($config, $db, $redis, $login = false)
    {
        try {
            parent::__construct($config, $db, $redis, $login);
            require_once __DIR__ . '/../../../utils/api_exec.php';

            $this->initializeZabbixApi($config);
            $this->checkApiConnection();
        } catch (Exception $e) {
            error_log("Zabbix Error: " . $e->getMessage());
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
                $this->getActualIds();
                $this->handleDevicesByType(
                    DeviceType::INTERCOMS->value,
                    fn() => $this->getDomophonesFromRBT(),
                    fn() => $this->getDomophonesFromZBX(),
                );

                $this->handleDevicesByType(
                    DeviceType::CAMERAS->value,
                    fn() => $this->getCamerasFromRBT(),
                    fn() => $this->getCamerasFromZBX(),
                );

                $result = true;
                $this->log("Сron task finish");
            }
        } catch (Exception $e) {
            $this->log('Сron error: ' . $e->getMessage());
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function deviceStatus($deviceType, $host)
    {
        try {
            switch ($deviceType) {
                case 'domophone':
                case 'camera':
                    return $this->processHostTriggers($host['ip']);
            }
        } catch (Exception $e){
            $this->log("method deviceStatus: " . $e->getMessage());
            return null;
        }
    }

    /**
     * @inheritDoc
     */
    public function devicesStatus($deviceType, $hosts)
    {
        try {
            switch ($deviceType) {
                case 'domophone':
                case 'camera':
                    return $this->processHostsTriggers($hosts);
            }
        } catch (Exception $e){
            $this->log("method devicesStatus: " . $e->getMessage());
            return null;
        }
    }

    /**
     * @throws Exception
     */
    public function configureMonitoring(): void
    {
        $this->configureZbx();
    }

    private function createStatusResponse($status, $message): array
    {
        return [
            'status' => $status,
            'message' => i18n($message),
        ];
    }

    /**
     * Create start configuration on Zabbix server
     * 1 create host group
     * 2 create template group
     * 3 get template group ids
     * 4 get plugged template ids
     * 5 create target template
     * 6 import template from YAML file
     * @return void
     * @throws Exception
     */
    private function configureZbx(): void
    {
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

    private function saveToRedis($key, $value, $ttl = 3600): void
    {
        $this->redis->set($key, json_encode($value, true));
        $this->redis->expire($key, $ttl);
        $this->redis->close();
    }

    private function getFromRedis($key)
    {
        $value = $this->redis->get($key);
        $this->redis->close();
        return $value ? json_decode($value, true) : null;
    }

    /**
     * Check Zabbix API connection
     * @throws Exception
     */
    private function checkApiConnection(): void
    {
        $body = [
            'jsonrpc' => '2.0',
            'method' => 'apiinfo.version',
            'params' => [],
            'id' => 1,
        ];

        $this->apiCall($body, false);
    }

    /**
     * Call Zabbix API
     * @param $method
     * @param $url
     * @param $payload
     * @param $contentType
     * @param $token
     * @return false|object
     * @throws Exception
     */
    public function apiCall($payload, $withAuth = true ): mixed
    {
        $method = 'POST';
        $url = $this->zbxApi;
        $token = $this->zbxToken;
        $contentType = 'application/json';

        $response = apiExec($method, $url, $payload, $contentType, $withAuth ? $token : false, 3);

        if (is_object($response)
            && property_exists($response, 'message')
            && property_exists($response, 'code')) {
            throw new Exception("{$response->message} (code: {$response->code})");
        }

        $response = json_decode($response, true);

        // Check Zabbix API jsonrpc error
        if (isset($response['error'])) {
            $err = $response['error'];
            throw new Exception("Zabbix API error [{$err['code']}] {$err['message']}: {$err['data']}");
        }

        return $response['result'] ?? null;
    }

    /**
     * Initialize Zabbix API
     * @param $config
     * @return void
     * @throws Exception
     */
    private function initializeZabbixApi($config): void
    {
        $zbxConfig = $config["backends"]["monitoring"];
        $requiredConfigKeys = [
            'zbx_api_url',
            'zbx_token',
            'zbx_data_collection',
//            'cron_sync_data_scheduler',
//            'zbx_store_days',
//            'use_cache',
        ];

        foreach ($requiredConfigKeys as $key) {
            if (!isset($zbxConfig[$key])) {
                throw new Exception("Required key '$key' is missing in Zabbix API configuration. Check config.");
            }
        }

        $this->zbxApi = $zbxConfig["zbx_api_url"];
        $this->zbxToken = $zbxConfig["zbx_token"] ;
        $this->zbxStoreDays = $zbxConfig["zbx_store_days"] ?? 7;
        $this->scheduler = $zbxConfig["cron_sync_data_scheduler"] ?? "5min";
        $this->useCache = $zbxConfig["use_cache"] ?? false;
        $this->enableLogging = $zbxConfig['logging'] ?? false;

        $this->hostGroups = $zbxConfig["zbx_data_collection"]["host_groups"];
        $this->templateGroups = $zbxConfig["zbx_data_collection"]["template_groups"];
        $this->intercomTemplateNames = $zbxConfig["zbx_data_collection"]["intercom_template_names"];
        $this->cameraTemplateNames = $zbxConfig["zbx_data_collection"]["camera_template_names"];
        $this->pluggedTemplateNames = $zbxConfig["zbx_data_collection"]["plugged_template_names"];

        $templatePath = __DIR__ . "/../../../.." . $zbxConfig["zbx_data_collection"]["templates_dir"];
        if (!is_dir($templatePath)) {
            throw new Exception("template directory does not exist: $templatePath");
        }
        $this->templatesDir = realpath($templatePath);
    }

    /**
     * Create host groups in Zabbix.
     * @throws Exception
     */
    private function createHostGroups(array $hostGroups): void
    {
        $existGroups = $this->getGroupIds($hostGroups);

        // First start, missing target groups
        if (!$existGroups) {
            // Create missing groups
            foreach ($hostGroups as $hostGroupName) {
                $this->log("Init. Create missing host group: " . $hostGroupName);
                $this->createHostGroup($hostGroupName);
            }
            return;
        }

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
     * Get actual item IDs from Zabbix API and update local cache if necessary
     * @return void
     * @throws Exception
     */
    private function getActualIds(): void
    {
        $templates = $this->getTemplatesData();
        $groups = $this->getGroupsData();

        $this->updateZbxData($templates, $groups);

        $this->log('Successfully updated Zabbix data.');
    }

    /**
     * Get templates data from cache or API
     * @return array
     * @throws Exception
     */
    private function getTemplatesData(): array
    {
        if ($this->useCache) {
            $cachedTemplates = $this->getFromRedis('zbx_templates');
            if (!empty($cachedTemplates)) {
                $this->log('Cache hit for templates.');
                return $cachedTemplates;
            }
        }

        $templates = $this->getTemplateIds(array_merge(
            $this->intercomTemplateNames,
            $this->cameraTemplateNames));

        if (empty($templates)) {
            throw new Exception('no templates found from API');
        }

        if ($this->useCache) {
            $this->saveToRedis("zbx_templates", $templates);
        }
        return $templates;
    }

    /**
     * Get groups data from cache or API
     * @return array
     * @throws Exception
     */
    private function getGroupsData(): array
    {
        if ($this->useCache) {
            $cachedGroups = $this->getFromRedis('zbx_groups');
            if (!empty($cachedGroups)) {
                $this->log('Cache hit for groups.');
                return $cachedGroups;
            }
        }

        $this->log('Cache miss for groups. Fetching from API.');
        $groups = $this->getGroupIds($this->hostGroups);

        if (empty($groups)) {
            $this->log('No host groups found from API.', 'error');
            throw new Exception('No host groups found from Zabbix API. Check hostGroups list.');
        }

        if ($this->useCache) {
            $this->saveToRedis("zbx_groups", $groups);
        }

        return $groups;
    }

    /**
     * Update Zabbix data with fetched templates and groups
     * @param array $templates
     * @param array $groups
     * @return void
     * @throws Exception
     */
    private function updateZbxData(array $templates, array $groups): void
    {
        if (!$templates || !$groups) {
            throw new Exception("Failed to fetch template or group IDs from Zabbix API.");
        }

        foreach ($templates as $template) {
            $this->zbxData['templates'][$template['host']] = $template['templateid'];
        }

        foreach ($groups as $group) {
            $this->zbxData['groups'][$group['name']] = $group['groupid'];
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
//                "stream" => $camera["stream"],
//                "dvrStream" => $camera["dvrStream"],
            ];
        }

        return $subset;
    }

    private function getGroupIds(array $names): mixed
    {
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
     * @return array
     * @throws Exception
     */
    private function getTemplateIds($name): array
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

        return $this->apiCall($body) ?? [];
    }

    /**
     * Get template groups from Zabbix server
     * @param array $templateGroups An array of template group names to fetch from the Zabbix server
     * @return object|null The response from the Zabbix API call, or null if the call fails
     * @throws Exception
     */
    private function getTemplateGroups(array $templateGroups): array
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
        return  $this->apiCall($body) ?? [];
    }

    /**
     * Get hosts by "groupid" from Zabbix server
     * @param $id
     * @return mixed|null
     * @throws Exception
     */
    private function getHostsByGroupId($id): array
    {
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
        return $this->apiCall($body) ?? [];
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
     * @throws Exception
     */
    private function importConfig(string $fileName): void
    {
        $fileContent = file_get_contents($fileName);
        $templateData = yaml_parse($fileContent);
        if ($templateData === false) {
            throw new Exception("Error reading file: $fileName");
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
                    ],
                    "discoveryRules" => [
                        "createMissing" => true,
                        "deleteMissing" => true,
                        "updateExisting" => true
                    ],
                ],

                "source" => $templateDataStr
            ],
            "id" => 1
        ];

        $this->apiCall($body);
    }

    /**
     * Import template configuration files in Zabbix.
     * @param $templatePath
     * @param $templateDir
     * @return void
     * @throws Exception
     */
    private function importTemplateConfigFiles($templatePath, $templateDir ): void
    {
        $fullTemplateDir = rtrim($templatePath, '/') . '/' . $templateDir;
        if (!is_dir($fullTemplateDir)) {
            throw new Exception("error: dir does not exist '$fullTemplateDir' ");
        }

        // gel yaml files
        $files = glob($fullTemplateDir . '/*.yaml');
        if (empty($files)) {
            throw new Exception("error: no YAML files found in directory '$fullTemplateDir'.");
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
     * @throws Exception
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

        try {
            $this->log("Create host: " . $item['name']);
            $this->apiCall($body);
        }catch (Exception $err) {
            $message = $err->getMessage();
            if (str_contains($message, 'already exists')) {
                $this->log("Failed to create, host already exists. Group: " . $groupName . ". Item: " . $item['name']);
            } else {
                throw $err;
            }
        }
    }

    /**
     * Create template on Zabbix server
     * @param string $templateName
     * @param array $templategroups
     * @param array $pluggetTemplates
     * @throws Exception
     */
    private function createTemplate(string $templateName, array $templategroups, array $pluggetTemplates): void
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

        $this->apiCall($body);
    }

    /**
     * Create host group on Zabbix server
     * @param string $groupName
     * @return void
     * @throws Exception
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
     * @throws Exception
     */
    private function createTemplateGroups(array $templateGroups): void
    {
        $existTemplateGroups = $this->getTemplateGroups($templateGroups);

        // First start, missing target template groups
        if (empty($existTemplateGroups)) {
            // Create missing template groups
            foreach ($templateGroups as $templateGroup) {
                $this->log("Init. Create missing template group: " . $templateGroup);
                $this->createTemplateGroup($templateGroup);
            }
            return;
        }

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
     * Creates a new template group in Zabbix.
     *
     * @param string $templateName The name of the template group to create.
     *
     * @return void The API response, typically containing 'groupids'.
     *
     * @throws Exception If the Zabbix API returns an error.
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
     * @throws Exception
     */
    private function disableHost(array $item): void
    {
        $now = time();
        $updateTags = $this->formatTags($item['tags']);
        $updateTags[] = [
            "tag" => "DISABLED",
            "value" => $now .' || '. date('d/m/Y H:i:s', $now),
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

        $this->log("Disable host: " . $item['name']);
        $this->apiCall($body);
    }

    /**
     * @param array $tags
     * @return array
     */
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
     * @throws Exception
     */
    private function enableHost(array $item): void
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

        $this->log("Enable host: " . $item['name']);
        $this->apiCall($body);
    }

    /**
     * @throws Exception
     */
    private function updateHost(array $zbxDevice, array $changes): void
    {
        $this->log("Updating host with ID:" .  $zbxDevice['zbx_hostid']);
        $updateParams = [
            'hostid' => $zbxDevice['zbx_hostid'],
        ];
        if (isset($changes['status'])) {
            $updateParams['status'] = $changes['status'] ? 0 : 1; // Assuming Zabbix uses 0 for enabled and 1 for disabled
        }
        if (isset($changes['name'])) {
            $updateParams['name'] = $changes['name'];
        }
        if (isset($changes['credentials'])) {
            $updateParams['macros'] = [
                [
                    "macro" => '{$HOST_PASSWORD}',
                    "value" => $changes['credentials'],
                ]
            ];
        }
        if (isset($changes['template'])){
            $currentTemplateID = $this->zbxData['templates'][$zbxDevice['template']] ?? null;
            $newTemplateID = $this->zbxData['templates'][$changes['template']] ?? null;
            if ($currentTemplateID && $newTemplateID) {
                $updateParams = array_merge($updateParams, [
                    'templates_clear' => [
                        ['templateid' => $currentTemplateID],
                    ],
                    'templates' => [
                        ['templateid' => $newTemplateID],
                    ],
                ]);
            }
        }

        $body = [
            'jsonrpc' => '2.0',
            'method' => 'host.update',
            'params' => $updateParams,
            'id' => 1
        ];

        $this->apiCall($body);
    }

    /**
     * @throws Exception
     */
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
     * @param string $id
     * @throws Exception
     */
    private function deleteHost(string $id): void
    {
        $body = [
            'jsonrpc' => '2.0',
            'method' => 'host.delete',
            'params' => [$id],
            'id' => 1
        ];
        $this->apiCall($body);
    }

    /**
     * Create target templates on Zabbix server
     * @param array $templateNames
     * @param int $templateGroupId
     * @return void
     * @throws Exception
     */
    private function createTargetTemplates(array $templateNames, int $templateGroupId): void
    {
        $exitsTemplates = $this->getTemplateIds($templateNames);

        if (!$exitsTemplates) {
            foreach ($templateNames as $templateName) {
                $this->log("Init. Create missing template >> " . $templateName);
                $this->createTemplate(
                    $templateName,
                    [$templateGroupId],
                    array_values($this->zbxData['pluggedTemplates'])
                );
            }
            return;
        }

        foreach ($templateNames as $templateName) {
            $templateExist = false;
            foreach ($exitsTemplates as $exitsTemplate) {
                if ($exitsTemplate['host'] === $templateName){
                    $templateExist = true;
                    break;
                }
            }

            if (!$templateExist){
                $this->log("Create missing template: " . $templateName);
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
     * @throws Exception
     */
    private function getTemplateGroupIds(array $templateGroups): void
    {
        $templateGroupsInfo = $this->getTemplateGroups($templateGroups);
        if (empty($templateGroupsInfo)){
            throw new Exception("getTemplateGroupIds");
        }
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
    private function getCamerasFromRBT(): array
    {
        $cameras = $this->getCameras();
        $mapped = [];
        foreach ($cameras as $item) {
            // FIXME: only vendor "FAKE"
            if ($item['vendor'] === $this->cameraVendor && $this->isAllKeysNotEmpty($item)){
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

    /**
     * @throws Exception
     */
    private function getDomophonesFromRBT(): array
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

    // TODO: check
    private function getDomophonesFromZBX(): array
    {
        $mapped = [];
        $raw = $this->getHostsByGroupId($this->zbxData['groups']['Intercoms']);
        if (empty($raw)) {
            return [];
        }

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

    /**
     * @throws Exception
     */
    private function getCamerasFromZBX(): array
    {
        $mapped = [];
        $raw = $this->getHostsByGroupId($this->zbxData['groups']['Cameras']);
        if (!$raw) {
            return [];
        }

        foreach ($raw as $item) {
            $mapped_item = [
                "zbx_hostid" => $item["hostid"],
                "status" => $item["status"] === "0",
                "host" => $item["host"],
                "name" => $item["name"],
                "template" => $item["parentTemplates"][0]["host"],
                "interface" => $item["interfaces"][0]["ip"]
            ];

            foreach ($item['macros'] as $macros) {
                if ($macros["macro"] === '{$HOST_PASSWORD}'){
                    $mapped_item["credentials"] = $macros["value"];
                    break;
                }
            }

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
            $this->log("delete: " . $item['name']);
            $this->deleteHost($item['zbx_hostid']);
        }
    }

    /**
     * @throws Exception
     */
    private function handleDevicesByType(string $type, callable $getRbtDevicesFn, callable $getZbxDevicesFn): void
    {
        $rbtDevices = $getRbtDevicesFn();
        $zbxDevices = $getZbxDevicesFn();

        if (!empty($rbtDevices) && !empty($zbxDevices)) {
            $this->handleDevices($rbtDevices, $zbxDevices, $type);
        } elseif (!empty($rbtDevices) && empty($zbxDevices)) {
            $this->log("First start, creating {$type} items");
            foreach ($rbtDevices as $device) {
                $this->createHost($device, $type);
            }
        }
    }

    /**
     * @throws Exception
     */
    private function handleDevices(array $rbtDevices, array $zbxDevices, string $groupName): void
    {
        if (empty($rbtDevices))
        {
            $this->log("Not found device for process");
            return;
        }

        foreach ($rbtDevices as $rbtDevice) {
            $zbxDevice = $this->findHostInArray($rbtDevice, $zbxDevices);

            if ($zbxDevice) {
                $this->processExistingDevice($rbtDevice, $zbxDevice);
            } else {
                $this->processNewDevice($rbtDevice, $groupName);
            }
        }

        $this->handleUnmatchedZbxDevices($rbtDevices, $zbxDevices);
    }

    /**
     * @param array $rbtDevice
     * @param array $zbxDevice
     * @return void
     * @throws Exception
     */
    private function processExistingDevice(array $rbtDevice, array $zbxDevice): void
    {
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

        $changes = $this->compareDevices($rbtDevice, $zbxDevice);
        if (!empty($changes)){
            $this->updateHost($zbxDevice, $changes);
        }
    }

    private function compareDevices($rbtDevice, $zbxDevice): array
    {
        $keysToCompare = ['status', 'host', 'name', 'template', 'interface', 'credentials'];
        $changes = [];

        foreach ($keysToCompare as $key) {
            if (isset($rbtDevice[$key]) && (!isset($zbxDevice[$key]) || $rbtDevice[$key] !== $zbxDevice[$key])) {
                $changes[$key] = $rbtDevice[$key];
            }
        }

        return $changes;
    }

    /**
     * @throws Exception
     */
    private function processNewDevice(array $rbtDevice, string $groupName): void
    {
        if ($rbtDevice['status'] === true) {
            $this->createHost($rbtDevice, $groupName);
        }
    }

    private function handleUnmatchedZbxDevices(array $rbtDevices, array $zbxDevices): void
    {
        foreach ($zbxDevices as $zbxDevice) {
            $device = $this->findHostInArray($zbxDevice, $rbtDevices);
            if (!$device) {
                if ($zbxDevice['status'] === true) {
                    $this->disableHost($zbxDevice);
                } elseif ($zbxDevice['status'] === false && isset($zbxDevice['tags']['DISABLED'])) {
                    $disableTimestamp = (int)explode(' || ', $zbxDevice['tags']['DISABLED'])[0];
                    $deleteAfter = $disableTimestamp + ($this->zbxStoreDays * 24 * 60 * 60);
                    $this->deleteHostIfNeeded($zbxDevice, $deleteAfter);
                }
            }
        }
    }

    /**
     * @throws Exception
     */
    private function getTriggers(array $hosts)
    {
        /**
         * TODO: use method "trigger.get"
         */
        $body = [
            'jsonrpc' => '2.0',
            'method' => 'host.get',
            'params' => [
                'output' => ['hostid', 'host'],
                'filter' => ['host' => $hosts],
                'selectTriggers' => [
                    'description',
                    'status',
                    'value',
                ],
            ],
            'id' => 1
        ];

        return $this->apiCall($body) ?? [];
    }

    /**
     * @throws Exception
     */
    private function getHostId($hostName)
    {
        $body = [
            'jsonrpc' => '2.0',
            'method' => 'host.get',
            'params' => [
                'output' => ['hostid'],
                'filter' => ['host' => $hostName],
            ],
            'id' => 1
        ];

        $response = $this->apiCall($body);
        if ($response && $response[0]['hostid']){
            return $response[0]['hostid'];
        }

        return null;
    }

    /**
     * @throws Exception
     */
    private function processHostTriggers($hostname): array
    {
        /**
         * 1    Getting status of device triggers
         */
        $triggers = $this->getTriggers([$hostname]);
        if (!$triggers) {
            return $this->createStatusResponse("Unknown", "monitoring.unknown");
        }

        /**
         * 2    Processing triggers, find ICMP and SIP
         *  -   Check only enabled triggers with the problem status
         *  -   If an ICMP and SIP triggers is found, return the corresponding status
         *  -   Not found  triggers with problem - return the status 'OK'
         */
        foreach ($triggers[0]['triggers'] as $trigger) {
            if ($trigger['status'] !== '1' && $trigger['value'] === '1'){
                switch ($trigger['description']) {
                    case Triggers::ICMP->value:
                        return $this->createStatusResponse("Offline", "monitoring.offline");
                    case Triggers::SIP->value:
                        return $this->createStatusResponse("SIP error", "monitoring.sipRegistrationFail");
                    default: $this->createStatusResponse("Other", "monitoring.otherErr");
                }
            }
        }

        return $this->createStatusResponse("OK", "monitoring.online");
    }

    /**
     * @throws Exception
     */
    private function processHostsTriggers(array $hosts): array
    {
        $hostStatus = [];
        $targetHosts = [];

        // 1 make associative array: "hostId" => ["ip", "status"]
        foreach ($hosts as $host){
            $hostStatus[$host['hostId']] = [
                'ip' => $host['ip'],
                'status' => [],
            ];
            // TODO: refactor?

            $host['ip'] && $targetHosts[] = $host['ip'];
        }

        // 2 get triggers per hosts
        $triggers = $this->getTriggers((array)$targetHosts);

        // Filter active triggers
        $triggers = array_map(function ($host){
            $filtered_triggers = array_filter($host['triggers'], function ($trigger){
                return $trigger['value'] === "1" && $trigger['status'] !== "1";
            });

            return [
                'host' => $host['host'],
                'triggers' => array_map(function ($trigger){
                    return [
                        'triggerid' => $trigger['triggerid'],
                        'description' => $trigger['description'],
                    ];
                }, $filtered_triggers)
            ];
        }, (array)$triggers);

        // Make associative array:  host => triggers
        $hostTriggers = [];
        foreach ($triggers as $item) {
            $hostTriggers[$item['host']] = $item['triggers'];
        }

        // Update host status result
        foreach ($hostStatus as $hostId => &$host) {
            $ip = $host['ip'];
            // Check host triggers
            if (isset($hostTriggers[$ip])) {
                // Triggers found, check
                if (empty($hostTriggers[$ip])) {
                    $host['status'] =
                        $this->createStatusResponse("OK", "monitoring.online");
                } else {
                    foreach ($hostTriggers[$ip] as $trigger) {
                        $host['status'] = match ($trigger['description']) {
                            Triggers::ICMP->value => $this->createStatusResponse("Offline", "monitoring.offline"),
                            Triggers::SIP->value => $this->createStatusResponse("SIP error", "monitoring.sipRegistrationFail"),
                            default => $this->createStatusResponse("Other", "monitoring.otherErr"),
                        };
                        // Skip
                        if ($host['status']['status'] !== 'OK') {
                            break;
                        }
                    }
                }
            } else {
                // Triggers not found
                $host['status'] = $this->createStatusResponse("unknown", "monitoring.unknown");
            }
        }

        return $hostStatus;
    }

    private function log(string $text): void
    {
        if (!$this->enableLogging) {
            return;
        }

        $dateTime = date('Y-m-d H:i:s');
        $message = "[$dateTime] || ZBX || " . $text;
        error_log($message);
    }
}