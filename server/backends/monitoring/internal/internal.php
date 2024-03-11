<?php

    namespace backends\monitoring;
    require_once __DIR__ . '/../../../utils/api_exec.php';

    class internal extends monitoring
    {
        protected $debug, $zbx_api, $zbx_token, $debug_url;
        public function __construct($config, $db, $redis, $login = false)
        {
            $this->zbx_api = $config["backends"]["monitoring"]["zbx_api_url"];
            $this->zbx_token = $config["backends"]["monitoring"]["zbx_token"];
            $this->debug = $config["backends"]["monitoring"]["debug"];
            $this->debug_url = $config["backends"]["monitoring"]["debug_url"];
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

        public function apiExec($method, $url, $payload = false, $contentType = false, $token = false)
        {
            return apiExec($method, $url, $payload, $contentType, $token);
        }

        /**
         * TODO: send debug msg
         * @param $payload
         * @return bool|string
         */
        private function send_echo($payload)
        {
            $payload = [
                'response' => $payload,
            ];

            return $this->apiExec("POST", $this->debug_url, $payload, 'application/json', false);
        }

        private function getDomophones()
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

        private function getCameras()
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

        private function configureTemplates()
        {
            // implement starter zabbix template, groups
        }

        private function createItems()
        {
            // implement api call to create monitoring item
        }

        private function removeItems()
        {
            // implement api call to remove monitoring item
        }

        private function handle()
        {
            // implement main logic
        }

        public function cron($part):bool
        {
            return true;
        }
    }