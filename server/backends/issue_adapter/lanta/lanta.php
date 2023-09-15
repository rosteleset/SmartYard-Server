<?php

namespace backends\issue_adapter {

    class lanta extends issue_adapter {
        /*
            {
                "issue": {
                    "description": "Обработать запрос на добавление видеофрагмента из архива видовой видеокамеры Видовая / Чичерина 9/2 (id = 2621) по  парамертам: дата: 12.09.2023, время: 12-59, продолжительность фрагмента: 10 минут. Комментарий  пользователя: test123.",
                    "project": "REM",
                    "summary": "Авто: Запрос на получение видеофрагмента с  архива",
                    "type": 32
                },
                "customFields": {
                    "10011": "-5",
                    "11840": "12.09.23 12:09",
                    "12440": "Приложение"
                },
                "actions": [
                    "Начать работу",
                    "Менеджеру ВН"
                ]
            }
        */

        public function createIssueForDVRFragment($phone, $description, $camera_id, $datetime, $duration, $comment) {
            /*
             {
                "project": "RTL",
                "query": {
                    "catalog": {"$regex": "^\\[9002\\].*"}
                },
                "sortBy": {"created": -1},
                "limit": 1,
                "fields": true
             }

             */

            $prev_issue = $this->getLastOpenedIssue($phone, 9002, time() - 24 * 60 * 60);
            if ($prev_issue !== false)
                return ['isNew' => false, 'issueId' => $prev_issue['issueId']];

            $cam_id = 900000000 + $this->extractCameraId($description);
            $issue = json_decode(file_get_contents($this->tt_url . "/issue", false, stream_context_create([
                "http" => [
                    "method" => "POST",
                    "header" => [
                        "Content-Type: application/json; charset=utf-8",
                        "Accept: application/json; charset=utf-8",
                        "Authorization: Bearer $this->tt_token",
                    ],
                    "content" => json_encode([
                        "issue" => [
                            "project" => "RTL",
                            "workflow" => "lanta",
                            "catalog" => "[9002] Запрос на получение видеофрагмента",
                            "subject" => "Запрос на получение видеофрагмента",
                            "assigned" => "cctv",
                            "_cf_object_id" => strval($cam_id),
                            "_cf_phone" => $phone,
                            "description" => $description,
                        ],
                    ]),
                ],
            ])), true);

            if (empty($issue['id']))
                return ['isNew' => false, 'issueId' => null];
            else
                return ['isNew' => true, 'issueId' => $issue['id']];
        }

        public function createIssueCallback($phone)
        {
            $prev_issue = $this->getLastOpenedIssue($phone, 9005, time() - 24 * 60 * 60);
            if ($prev_issue !== false)
                return ['isNew' => false, 'issueId' => $prev_issue['issueId']];

            $issue = json_decode(file_get_contents($this->tt_url . "/issue", false, stream_context_create([
                "http" => [
                    "method" => "POST",
                    "header" => [
                        "Content-Type: application/json; charset=utf-8",
                        "Accept: application/json; charset=utf-8",
                        "Authorization: Bearer $this->tt_token",
                    ],
                    "content" => json_encode([
                        "issue" => [
                            "project" => "RTL",
                            "workflow" => "lanta",
                            "catalog" => "[9005] Исходящий звонок",
                            "subject" => "Исходящий звонок",
                            "assigned" => "callcenter",
                            "_cf_phone" => $phone,
                            "description" => "Выполнить звонок клиенту по запросу из приложения.",
                        ],
                    ]),
                ],
            ])), true);

            if (empty($issue['id']))
                return ['isNew' => false, 'issueId' => null];
            else
                return ['isNew' => true, 'issueId' => $issue['id']];
        }

        public function createIssueForgotEverything($phone)
        {
            $prev_issue = $this->getLastOpenedIssue($phone, 9005, time() - 24 * 60 * 60);
            if ($prev_issue !== false)
                return ['isNew' => false, 'issueId' => $prev_issue['issueId']];

            $issue = json_decode(file_get_contents($this->tt_url . "/issue", false, stream_context_create([
                "http" => [
                    "method" => "POST",
                    "header" => [
                        "Content-Type: application/json; charset=utf-8",
                        "Accept: application/json; charset=utf-8",
                        "Authorization: Bearer $this->tt_token",
                    ],
                    "content" => json_encode([
                        "issue" => [
                            "project" => "RTL",
                            "workflow" => "lanta",
                            "catalog" => "[9005] Исходящий звонок",
                            "subject" => "Исходящий звонок",
                            "assigned" => "callcenter",
                            "_cf_phone" => $phone,
                            "description" => "Выполнить звонок клиенту для напоминания номера договора и пароля от личного кабинета.",
                        ],
                    ]),
                ],
            ])), true);

            if (empty($issue['id']))
                return ['isNew' => false, 'issueId' => null];
            else
                return ['isNew' => true, 'issueId' => $issue['id']];
        }

        public function createIssueConfirmAddress($phone, $description, $name, $address, $lat, $lon)
        {
            $prev_issue = $this->getLastOpenedIssue($phone, 9007, time() - 24 * 60 * 60);
            if ($prev_issue !== false)
                return ['isNew' => false, 'issueId' => $prev_issue['issueId']];

            $params = $this->extractValuesForConfirmAddress($description);
            $name = $params['name'] ?? "";
            $address = $params['address'] ?? "";
            $content = [
                "issue" => [
                    "project" => "RTL",
                    "workflow" => "lanta",
                    "catalog" => "[9007] Нужен код доступа",
                    "subject" => "Нужен код доступа",
                    "assigned" => "office",
                    "_cf_phone" => $phone,
                    "description" => "ФИО: $name\n"
                        . "Адрес, введенный пользователем: $address\n"
                        . "Подготовить конверт с qr-кодом. Далее заявку отправить курьеру."
                ],
            ];
            return $this->createLantaIssue($lat, $lon, $content);
        }

        public function createIssueDeleteAddress($phone, $description, $name, $address, $lat, $lon, $reason)
        {
            $prev_issue = $this->getLastOpenedIssue($phone, 9005, time() - 24 * 60 * 60);
            if ($prev_issue !== false)
                return ['isNew' => false, 'issueId' => $prev_issue['issueId']];

            $params = $this->extractValuesForDeleteAddress($description);
            $name = $params['name'] ?? "";
            $address = $params['address'] ?? "";
            $reason = $params['reason'] ?? "";
            $content = [
                "issue" => [
                    "project" => "RTL",
                    "workflow" => "lanta",
                    "catalog" => "[9005] Исходящий звонок",
                    "subject" => "Исходящий звонок",
                    "assigned" => "callcenter",
                    "_cf_phone" => $phone,
                    "description" => "ФИО: $name\n"
                        . "Адрес, введенный пользователем: $address\n"
                        . "Удаление адреса из приложения. Причина: $reason"
                ],
            ];
            return $this->createLantaIssue($lat, $lon, $content);
        }

        public function createIssueUnavailableServices($phone, $description, $name, $address, $lat, $lon, $services)
        {
            $prev_issue = $this->getLastOpenedIssue($phone, 9001, time() - 24 * 60 * 60);
            if ($prev_issue !== false)
                return ['isNew' => false, 'issueId' => $prev_issue['issueId']];

            $params = $this->extractValuesForUnavailableServices($description);
            $name = $params['name'] ?? "";
            $address = $params['address'] ?? "";
            $services = $params['services'] ?? "";
            $content = [
                "issue" => [
                    "project" => "RTL",
                    "workflow" => "lanta",
                    "catalog" => "[9001] Заявка из приложения",
                    "subject" => "Заявка из приложения",
                    "assigned" => "callcenter",
                    "_cf_phone" => $phone,
                    "description" => "ФИО: $name\n"
                        . "Адрес, введенный пользователем: $address\n"
                        . "Список подключаемых услуг: $services"
                ],
            ];
            return $this->createLantaIssue($lat, $lon, $content);
        }

        public function createIssueAvailableWithSharedServices($phone, $description, $name, $address, $lat, $lon, $services)
        {
            $prev_issue = $this->getLastOpenedIssue($phone, 9006, time() - 24 * 60 * 60);
            if ($prev_issue !== false)
                return ['isNew' => false, 'issueId' => $prev_issue['issueId']];

            $params = $this->extractValuesForAvailableWithSharedServices($description);
            $name = $params['name'] ?? "";
            $address = $params['address'] ?? "";
            $services = $params['services'] ?? "";
            $content = [
                "issue" => [
                    "project" => "RTL",
                    "workflow" => "lanta",
                    "catalog" => "[9006] Подключение",
                    "subject" => "Подключение",
                    "assigned" => "callcenter",
                    "_cf_phone" => $phone,
                    "description" => "ФИО: $name\n"
                        . "Адрес, введенный пользователем: $address\n"
                        . "Список подключаемых услуг: $services\n"
                        . "Требуется подтверждение адреса и подключение выбранных услуг."
                ],
            ];
            return $this->createLantaIssue($lat, $lon, $content);
        }

        public function createIssueAvailableWithoutSharedServices($phone, $description, $name, $address, $lat, $lon, $services)
        {
            $prev_issue = $this->getLastOpenedIssue($phone, 9006, time() - 24 * 60 * 60);
            if ($prev_issue !== false)
                return ['isNew' => false, 'issueId' => $prev_issue['issueId']];

            $params = $this->extractValuesForAvailableWithoutSharedServices($description);
            $name = $params['name'] ?? "";
            $address = $params['address'] ?? "";
            $services = $params['services'] ?? "";
            $content = [
                "issue" => [
                    "project" => "RTL",
                    "workflow" => "lanta",
                    "catalog" => "[9006] Подключение",
                    "subject" => "Подключение",
                    "assigned" => "callcenter",
                    "_cf_phone" => $phone,
                    "description" => "ФИО: $name\n"
                        . "Адрес, введенный пользователем: $address\n"
                        . "Список подключаемых услуг: $services\n"
                        . "Выполнить звонок клиенту и осуществить консультацию."
                ],
            ];
            return $this->createLantaIssue($lat, $lon, $content);
        }

        private function extractCameraId($input_string) {
            $pattern = '/\(id\s*=\s*(\d+)\)/';
            if (preg_match($pattern, $input_string, $matches)) {
                return intval($matches[1]);
            }
            return 0;
        }

        private function extractValuesForConfirmAddress($input_string) {
            // old
            // $pattern = '/ФИО:\s*(?<name>.*\S|)\s*(?<phone>Телефон\s*)?Адрес, введённый пользователем:\s*(?<address>.*\S|)\s/';

            // new
            $pattern = '/ФИО:\s*(?<name>.*?)\s*(?:\n|Телефон:\s*.*?)Адрес, введённый пользователем:\s*(?<address>.*?)\s*$/s';

            if (preg_match($pattern, $input_string, $matches)) {
                return $matches;
            }
            return false;
        }

        private function extractValuesForDeleteAddress($input_string) {
            // old
            // $pattern = '/ФИО:\s*(?<name>.*\S|)\s*(?<phone>Телефон\s*)?Адрес, введённый пользователем:\s*(?<address>.*\S|)\sПричина:\s*(?<reason>.*\S|)/s';

            // new
            $pattern = '/ФИО:\s*(?<name>.*?)\s*(?:\n|Телефон:\s*.*?)Адрес, введённый пользователем:\s*(?<address>.*?)\s*Причина:\s*(?<reason>.*?)\s*$/s';

            if (preg_match($pattern, $input_string, $matches)) {
                return $matches;
            }
            return false;
        }

        private function extractValuesForUnavailableServices($input_string) {
            // old
            // $pattern = '/ФИО:\s*(?<name>.*\S|)\s*(?<phone>Телефон\s*)?Адрес, введённый пользователем:\s*(?<address>.*\S|)\sСписок подключаемых услуг:\s*(?<services>.*\S|)/s';

            // new
            $pattern = '/ФИО:\s*(?<name>.*?)\s*(?:\n|Телефон:\s*.*?)Адрес, введённый пользователем:\s*(?<address>.*?)\s*Список подключаемых услуг:\s*(?<services>.*?)\s*$/s';

            if (preg_match($pattern, $input_string, $matches)) {
                return $matches;
            }
            return false;
        }

        private function extractValuesForAvailableWithSharedServices($input_string) {
            // old
            // $pattern = '/ФИО:\s*(?<name>.*\S|)\s*(?<phone>Телефон\s*)?Адрес, введённый пользователем:\s*(?<address>.*|)\n(Список подключаемых услуг:\s*)?\s*(?<services>.*)\nТребуется подтверждение адреса/s';

            // new
            $pattern = '/ФИО:\s*(?<name>.*?)\s*(?:\n|Телефон:\s*.*?)Адрес, введённый пользователем:\s*(?<address>.*?)\s*Список подключаемых услуг:\s*(?<services>.*?)\s*Требуется подтверждение адреса\s*/s';

            if (preg_match($pattern, $input_string, $matches)) {
                return $matches;
            }
            return false;
        }

        private function extractValuesForAvailableWithoutSharedServices($input_string) {
            // old
            // $pattern = '/ФИО:\s*(?<name>.*\S|)\s*(?<phone>Телефон\s*)?Адрес, введённый пользователем:\s*(?<address>.*\S|)\sПодключение услуг\(и\):\s*(?<services>[^\n]*\n)/s';

            // new
            $pattern = '/ФИО:\s*(?<name>.*?)\s*(?:\n|Телефон:\s*.*?)Адрес, введённый пользователем:\s*(?<address>.*?)\s*Подключение услуг\(и\):\s*(?<services>.*?)\s*$/s';

            if (preg_match($pattern, $input_string, $matches)) {
                return $matches;
            }
            return false;
        }

        /**
         * @param $lat
         * @param $lon
         * @param array $content
         * @return mixed
         */
        private function createLantaIssue($lat, $lon, array $content)
        {
            if (isset($lat) && isset($lon)) {
                $content["_cf_geo"] = ["type" => "Point", "coordinates" => [floatval($lon), floatval($lat)]];
            }
            $issue = json_decode(file_get_contents($this->tt_url . "/issue", false, stream_context_create([
                "http" => [
                    "method" => "POST",
                    "header" => [
                        "Content-Type: application/json; charset=utf-8",
                        "Accept: application/json; charset=utf-8",
                        "Authorization: Bearer $this->tt_token",
                    ],
                    "content" => json_encode($content),
                ],
            ])), true);

            if (isset($issue) && isset($issue['id']))
                return ['isNew' => true, 'issueId' => $issue['id']];
            else
                return ['isNew' => false, 'issueId' => null];
        }

        private function getLastOpenedIssue($phone, $catalog, $timestamp)
        {
            $content = [
                "project" => "RTL",
                "query" => [
                    "catalog" => ['$regex' => "^\\[$catalog\\].*"],
                    "_cf_phone" => $phone,
                    "created" => ['$gt' => $timestamp],
                    "status" => "Открыта"
                ],
                "sortBy" => ["created" => -1],
                "limit" => 1,
                "fields" => ["issueId", "created"]
            ];

            $result = json_decode(file_get_contents($this->tt_url . "/issues", false, stream_context_create([
                "http" => [
                    "method" => "POST",
                    "header" => [
                        "Content-Type: application/json; charset=utf-8",
                        "Accept: application/json; charset=utf-8",
                        "Authorization: Bearer $this->tt_token",
                    ],
                    "content" => json_encode($content),
                ],
            ])), true);

            return $result['issues']['issues'][0] ?? false;
        }
    }
}
