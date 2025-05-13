<?php

// works with the second version of issues

namespace backends\issue_adapter {
    class teledom extends issue_adapter {
        const ISSUE_REQUEST_CALLBACK = 'requestCallback';
        const ISSUE_REQUEST_FRAGMENT = 'requestFragment';
        const ISSUE_REMOVE_ADDRESS = 'removeAddress';
        const ISSUE_CONNECT_SERVICES_NO_COMMON = 'connectServicesNoCommon';
        const ISSUE_CONNECT_SERVICES_HAS_COMMON = 'connectServicesHasCommon';
        const ISSUE_CONNECT_SERVICES_NO_NETWORK = 'connectServicesNoNetwork';
        const ISSUE_REQUEST_QR_CODE_OFFICE = 'requestQRCodeOffice';
        const ISSUE_REQUEST_QR_CODE_COURIER = 'requestQRCodeCourier';
        const ISSUE_REQUEST_CREDENTIALS = 'requestCredentials';

        const PARAM_TYPE = 'type';
        const PARAM_USER_NAME = 'userName';
        const PARAM_INPUT_ADDRESS = 'inputAddress';
        const PARAM_SERVICES = 'services';
        const PARAM_COMMENTS = 'comments';
        const PARAM_CAMERA_ID = 'cameraId';
        const PARAM_CAMERA_NAME = 'cameraName';
        const PARAM_FRAGMENT_DATE = 'fragmentDate';
        const PARAM_FRAGMENT_TIME = 'fragmentTime';
        const PARAM_FRAGMENT_DURATION = 'fragmentDuration';

        const F_PROJECT = 'project';
        const F_WORKFLOW = 'workflow';
        const F_CATALOG = 'catalog';
        const F_SUBJECT = 'subject';
        const F_ASSIGNED = 'assigned';
        const F_DESCRIPTION = 'description';
        const F_STATUS = 'status';
        const CF_PHONE = '_cf_phone';
        const F_ISSUE_ID = 'issueId';
        const F_CREATED = 'created';
        const F_IS_NEW = 'isNew';
        const CF_CAMERA_ID = '_cf_camera_id';
        const CF_QR_DELIVERY = '_cf_qr_delivery';
        const CF_GEO = '_cf_geo';
        const CF_ADDRESS = '_cf_address';

        const ACTION_CLOSE = "Закрыть";
        const ACTION_CHANGE_QR_DELIVERY = "Сменить способ доставки";
        const QR_DELIVERY_OFFICE = 'Самовывоз';
        const QR_DELIVERY_COURIER = 'Курьер';

        const API_CLOSE = "close";
        const API_CHANGE_QR_DELIVERY_TYPE = 'changeQRDeliveryType';
        const API_OFFICE = 'office';
        const API_COURIER = 'courier';

        const API_QR_DELIVERY_TYPE = 'deliveryType';
        const MAP_ACTION_API_TT = [
            self::API_CLOSE => self::ACTION_CLOSE,
            self::API_CHANGE_QR_DELIVERY_TYPE => self::ACTION_CHANGE_QR_DELIVERY,
            self::API_OFFICE => self::QR_DELIVERY_OFFICE,
            self::API_COURIER => self::QR_DELIVERY_COURIER
        ];

        private function getGeo($address): ?array
        {
            $geocoder = loadBackend('geocoder');
            $queryResult = @$geocoder->suggestions($address)[0];
            $result = null;
            if (isset($queryResult)) {
                $lat = $queryResult['data']['geo_lat'];
                $lon = $queryResult['data']['geo_lon'];
                $result = [
                    "type" => "Point",
                    "coordinates" => [floatval(str_replace(',', '.', $lon)),
                        floatval(str_replace(',', '.', $lat))]];
            }

            return $result;
        }

        private function queryIssues($content)
        {
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

            return $result['issues']['issues'] ?? null;
        }

        private function getLastOpenedIssue($phone, $data)
        {
            $anti_spam_interval = $this->config['backends']['issue_adapter']['anti_spam_interval'];
            if (!isset($anti_spam_interval) || $anti_spam_interval <= 0)
                return false;

            if (!isset($data[self::PARAM_TYPE]))
                return false;

            $issueType = $data[self::PARAM_TYPE];
            $project = $this->config['backends']['issue_adapter'][$issueType][self::F_PROJECT];
            $workflow = $this->config['backends']['issue_adapter'][$issueType][self::F_WORKFLOW];
            $catalog = $this->config['backends']['issue_adapter'][$issueType][self::F_CATALOG];
            $address = $data[self::PARAM_INPUT_ADDRESS] ?? null;
            $content = [
                self::F_PROJECT => $project,
                self::F_WORKFLOW => $workflow,
                'query' => [
                    self::F_CATALOG => $catalog,
                    self::CF_PHONE => $phone,
                    self::F_STATUS => 'Открыта'
                ],
                'sortBy' => [self::F_CREATED => -1],
                'limit' => 1,
                'fields' => [self::F_ISSUE_ID, self::F_CREATED]
            ];
            $content['query'][self::F_CREATED] = ['$gt' => time() - $anti_spam_interval];
            if (isset($address)) {
                $content['query'][self::F_DESCRIPTION] = ['$regex' => "\\Q$address\\E"];
            }

            $result = $this->queryIssues($content);
            return $result[0] ?? false;
        }

        public function createIssue($phone, $data): bool|array
        {
            $prev_issue = $this->getLastOpenedIssue($phone, $data);
            if ($prev_issue !== false)
                return [self::F_IS_NEW => false, self::F_ISSUE_ID => $prev_issue[self::F_ISSUE_ID]];

            $issueType = $data[self::PARAM_TYPE];
            if (!isset($data[self::PARAM_TYPE]))
                return false;

            $project = $this->config['backends']['issue_adapter'][$issueType][self::F_PROJECT];
            $workflow = $this->config['backends']['issue_adapter'][$issueType][self::F_WORKFLOW];
            $catalog = $this->config['backends']['issue_adapter'][$issueType][self::F_CATALOG];
            $subject = $this->config['backends']['issue_adapter'][$issueType][self::F_SUBJECT];
            $assigned = $this->config['backends']['issue_adapter'][$issueType][self::F_ASSIGNED];
            $issue = [
                self::F_PROJECT => $project,
                self::F_WORKFLOW => $workflow,
                self::F_CATALOG => $catalog,
                self::F_SUBJECT => $subject,
                self::F_ASSIGNED => $assigned,
                self::CF_PHONE => $phone,
            ];
            if (isset($data[self::PARAM_INPUT_ADDRESS])) {
                $cf_geo = $this->getGeo($data[self::PARAM_INPUT_ADDRESS]);
                if (isset($cf_geo)) {
                    $issue[self::CF_GEO] = $cf_geo;
                }
                $issue[self::CF_ADDRESS] = $data[self::PARAM_INPUT_ADDRESS];
            }
            switch ($issueType) {
                case self::ISSUE_REQUEST_CALLBACK:
                    $issue[self::F_DESCRIPTION] = "ФИО: {$data[self::PARAM_USER_NAME]}\n"
                        . "Выполнить звонок клиенту по запросу с приложения.";
                    break;

                case self::ISSUE_REQUEST_FRAGMENT:
                    $issue[self::CF_CAMERA_ID] = strval($data[self::PARAM_CAMERA_ID]);
                    $issue[self::F_DESCRIPTION] = "ФИО: {$data[self::PARAM_USER_NAME]}\n"
                        . "Обработать запрос на добавление видеофрагмента из архива видовой видеокамеры {$data[self::PARAM_CAMERA_NAME]}"
                        . " (id = {$data[self::PARAM_CAMERA_ID]}) по парамертам:\n"
                        . "дата - {$data[self::PARAM_FRAGMENT_DATE]};\n"
                        . "время - {$data[self::PARAM_FRAGMENT_TIME]};\n"
                        . "продолжительность фрагмента - {$data[self::PARAM_FRAGMENT_DURATION]} минут.\n"
                        . "Комментарий пользователя: {$data[self::PARAM_COMMENTS]}.";
                    break;

                case self::ISSUE_REMOVE_ADDRESS:
                    $issue[self::F_DESCRIPTION] = "ФИО: {$data[self::PARAM_USER_NAME]}\n"
                        . "Адрес, введённый пользователем: {$data[self::PARAM_INPUT_ADDRESS]}.\n"
                        . "Удаление адреса из приложения. Причина: {$data[self::PARAM_COMMENTS]}.";
                    break;

                case self::ISSUE_CONNECT_SERVICES_NO_COMMON:
                    $issue[self::F_DESCRIPTION] = "ФИО: {$data[self::PARAM_USER_NAME]}\n"
                        . "Адрес, введённый пользователем: {$data[self::PARAM_INPUT_ADDRESS]}.\n"
                        . "Подключение услуг(и): {$data[self::PARAM_SERVICES]}.\n"
                        . "Выполнить звонок клиенту и осуществить консультацию.";
                    break;

                case self::ISSUE_CONNECT_SERVICES_HAS_COMMON:
                    $issue[self::F_DESCRIPTION] = "ФИО: {$data[self::PARAM_USER_NAME]}\n"
                        . "Адрес, введённый пользователем: {$data[self::PARAM_INPUT_ADDRESS]}.\n"
                        . "Подключение услуг(и): {$data[self::PARAM_SERVICES]}.\n"
                        . "Требуется подтверждение адреса и подключение выбранных услуг.";
                    break;

                case self::ISSUE_CONNECT_SERVICES_NO_NETWORK:
                    $issue[self::F_DESCRIPTION] = "ФИО: {$data[self::PARAM_USER_NAME]}\n"
                        . "Адрес, введённый пользователем: {$data[self::PARAM_INPUT_ADDRESS]}.\n"
                        . "Подключение услуг(и): {$data[self::PARAM_SERVICES]}.";
                    break;

                case self::ISSUE_REQUEST_QR_CODE_OFFICE:
                    $issue[self::CF_QR_DELIVERY] = $this->config['backends']['issue_adapter'][$issueType][self::CF_QR_DELIVERY];
                    $issue[self::F_DESCRIPTION] = "ФИО: {$data[self::PARAM_USER_NAME]}\n"
                        . "Адрес, введённый пользователем: {$data[self::PARAM_INPUT_ADDRESS]}.\n"
                        . "Клиент подойдет в офис для получения подтверждения.";
                    break;

                case self::ISSUE_REQUEST_QR_CODE_COURIER:
                    $issue[self::CF_QR_DELIVERY] = $this->config['backends']['issue_adapter'][$issueType][self::CF_QR_DELIVERY];
                    $issue[self::F_DESCRIPTION] = "ФИО: {$data[self::PARAM_USER_NAME]}\n"
                        . "Адрес, введённый пользователем: {$data[self::PARAM_INPUT_ADDRESS]}.\n"
                        . "Подготовить конверт с QR-кодом. Далее заявку отправить курьеру.";
                    break;

                case self::ISSUE_REQUEST_CREDENTIALS:
                    $issue[self::F_DESCRIPTION] = "ФИО: {$data[self::PARAM_USER_NAME]}\n"
                        . "Выполнить звонок клиенту для напоминания номера договора и пароля от личного кабинета.";
                    break;

                default:
                    return false;
            }

            $issue = json_decode(file_get_contents($this->tt_url . "/issue", false, stream_context_create([
                "http" => [
                    "method" => "POST",
                    "header" => [
                        "Content-Type: application/json; charset=utf-8",
                        "Accept: application/json; charset=utf-8",
                        "Authorization: Bearer $this->tt_token",
                    ],
                    "content" => json_encode([
                        "issue" => $issue,
                    ]),
                ],
            ])), true);

            if (empty($issue['id']))
                return ['isNew' => false, 'issueId' => null];
            else
                return ['isNew' => true, 'issueId' => $issue['id']];
        }

        public function listConnectIssues($phone): bool|array
        {
            $issueType = self::ISSUE_REQUEST_QR_CODE_COURIER;
            $project = $this->config['backends']['issue_adapter'][$issueType][self::F_PROJECT];
            $workflow = $this->config['backends']['issue_adapter'][$issueType][self::F_WORKFLOW];

            $content = [
                self::F_PROJECT => $project,
                self::F_WORKFLOW => $workflow,
                'query' => [
                    self::CF_PHONE => $phone,
                    self::F_STATUS => 'Открыта',
                    self::CF_QR_DELIVERY => ['$exists' => true],
                ],
                'sortBy' => [self::F_CREATED => 1],
                'limit' => 100,
                'fields' => [self::F_ISSUE_ID, self::CF_ADDRESS, self::CF_QR_DELIVERY]
            ];
            $result = $this->queryIssues($content);

            if (!isset($result))
                return false;

            $issues = [];
            foreach ($result as $issue) {
                $r = [];
                $r['key'] = $issue[self::F_ISSUE_ID];
                $r['address'] = $issue[self::CF_ADDRESS];
                $r['courier'] = strcasecmp($issue[self::CF_QR_DELIVERY], self::QR_DELIVERY_COURIER) == 0 ? "t" : "f";
                $issues[] = $r;
            }
            return $issues;
        }

        public function commentIssue($issueId, $comment)
        {
            $content = [
                "issueId" => $issueId,
                "comment" => $comment,
                "commentPrivate" => false,
                "type" => false
            ];

            $result = json_decode(file_get_contents($this->tt_url . "/comment", false, stream_context_create([
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

            return $result[0] ?? false;
        }

        public function actionIssue($data)
        {
            $issueId = @$data['key'];
            $action = @$data['action'];
            $content = [
                "action" => self::MAP_ACTION_API_TT[$action]
            ];
            if ($action === self::API_CHANGE_QR_DELIVERY_TYPE) {
                $delivery_type = @$data[self::API_QR_DELIVERY_TYPE];
                $content["set"][self::CF_QR_DELIVERY] = self::MAP_ACTION_API_TT[$delivery_type];
            }

            $result = json_decode(file_get_contents($this->tt_url . "/action/" . $issueId, false, stream_context_create([
                "http" => [
                    "method" => "PUT",
                    "header" => [
                        "Content-Type: application/json; charset=utf-8",
                        "Accept: application/json; charset=utf-8",
                        "Authorization: Bearer $this->tt_token",
                    ],
                    "content" => json_encode($content),
                ],
            ])), true);

            return $result[0] ?? false;
        }
    }
}
