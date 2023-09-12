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
            $cam_id = 900000000 + $this->extractCameraId($description);
            $issue = json_decode(file_get_contents($this->tt_url, false, stream_context_create([
                "http" => [
                    // создание заявки - через POST
                    "method" => "POST",
                    "header" => [
                        "Content-Type: application/json; charset=utf-8",
                        "Accept: application/json; charset=utf-8",
                        // постоянный токен "абонента"
                        // для tt.lanta.me не трогаем
                        // но нужно вынести в настройки
                        "Authorization: Bearer $this->tt_token",
                    ],
                    "content" => json_encode([
                        "issue" => [
                            // для tt.lanta.me не трогаем
                            // но нужно вынести в настройки
                            "project" => "RTL",

                            // для tt.lanta.me не трогаем
                            // но нужно вынести в настройки
                            "workflow" => "lanta",

                            // для tt.lanta.me не трогаем
                            // но нужно вынести в настройки
                            "catalog" => "[9002] Запрос на получение видеофрагмента",

                            // для tt.lanta.me не трогаем
                            // но нужно вынести в настройки
                            "subject" => "Запрос на получение видеофрагмента",

                            // для tt.lanta.me не трогаем
                            // но нужно вынести в настройки
                            "assigned" => "cctv",

                            // для tt.lanta.me не трогаем
                            // но что-то с этим делать надо, сильно зависит от workflow
                            // 900000000 + cameraId (передавать строкой)
                            //"_cf_object_id" => "900001044",
                            "_cf_object_id" => strval($cam_id),

                            // номер телефона (11 цифр, начиная с 8ки)
                            // для tt.lanta.me не трогаем
                            // но что-то с этим делать надо, сильно зависит от workflow
                            "_cf_phone" => $phone,
                            //"_cf_phone" => "79051200829",

                            // все что угодно
                            "description" => $description,
                        ],
                    ]),
                ],
            ])), true);

            return $issue['id'];
        }

        private function extractCameraId($input_string) {
            $pattern = '/\(id\s*=\s*(\d+)\)/';
            if (preg_match($pattern, $input_string, $matches)) {
                return intval($matches[1]);
            }
            return 0;
        }
    }
}
