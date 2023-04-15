<?php

    /**
     * backends dvr_exports namespace
     */

    namespace backends\dvr_exports
    {
        class mongo extends dvr_exports
        {
//
            /**
             * @inheritDoc
             */
            public function addDownloadRecord($cameraId, $subscriberId, $start, $finish)
            {
                $dvr_files_ttl = @$this->config["backends"]["dvr_exports"]["dvr_files_ttl"] ?: 259200; // 3 days

                if (!checkInt($cameraId) || !checkInt($subscriberId) || !checkInt($start) || !checkInt($finish)) {
                    return false;
                }

                $filename = GUIDv4() . '.mp4';
                
                return $this->db->insert("insert into camera_records (camera_id, subscriber_id, start, finish, filename, expire, state) values (:camera_id, :subscriber_id, :start, :finish, :filename, :expire, :state)", [
                    "camera_id" => (int)$cameraId,
                    "subscriber_id" => (int)$subscriberId,
                    "start" => (int)$start,
                    "finish" => (int)$finish,
                    "filename" => $filename,
                    "expire" => time() + $dvr_files_ttl,
                    "state" => 0 //0 = created, 1 = in progress, 2 = completed, 3 = error
                ]);
            }

            /**
             * @inheritDoc
             */
            public function checkDownloadRecord($cameraId, $subscriberId, $start, $finish)
            {
                if (!checkInt($cameraId) || !checkInt($subscriberId) || !checkInt($start) || !checkInt($finish)) {
                    return false;
                }
                return $this->db->get(
                    "select record_id from camera_records where camera_id = :camera_id and subscriber_id = :subscriber_id AND start = :start AND finish = :finish",
                    [
                        ":camera_id" => (int)$cameraId,
                        ":subscriber_id" => (int)$subscriberId,
                        ":start" => (int)$start,
                        ":finish" => (int)$finish
                    ],
                    [
                        "record_id" => "id",
                    ],
                    [
                        "singlify"
                    ]
                );
            }

            /**
             * @inheritDoc
             */
            public function runDownloadRecordTask($recordId)
            {
                $config = $this->config;
                // TODO: добавить удаление старых заданий на скачивание.

                try {
                    $task = $this->db->get(
                        "select camera_id, subscriber_id, start, finish, filename, expire, state from camera_records where record_id = :record_id AND state = 0",
                        [
                            ":record_id" => $recordId,
                        ],
                        [
                            "camera_id" => "cameraId",
                            "subscriber_id" => "subscriberId",
                            "start" => "start",
                            "finish" => "finish",
                            "filename" => "filename",
                            "expire" => "expire",
                            "state" => "state" //0 = created, 1 = in progress, 2 = completed, 3 = error
                        ],
                        [
                            "singlify"
                        ]
                    );
                    if ($task) {
                        $cameras = loadBackend("cameras");
                        $cam = $cameras->getCamera($task['cameraId']);
        
                        if (!$cam) {
                            echo "Camera with id = " . $task['cameraId'] . " was not found\n";
                           return false;
        
                        }
                        $request_url = loadBackend("dvr")->getUrlOfRecord($cam, $task['subscriberId'], $task['start'], $task['finish']);
                        
                        $this->db->modify("update camera_records set state = 1 where record_id = $recordId");
                        
                        echo "Record download task with id = $recordId was started\n";
                        echo "Fetching record form {$request_url} to " . $task['filename']  . "\n";
                        
                        $files = loadBackend("files");
                        $arrContextOptions=array(
                            "ssl"=>array(
                                "verify_peer"=>false,
                                "verify_peer_name"=>false,
                            ),
                        );
                        $file = fopen($request_url, "r", false, stream_context_create($arrContextOptions));
                        $fileId = $files->addFile($task['filename'], $file, [
                            "camId" => $task['cameraId'],
                            "start" => $task['start'],
                            "finish" => $task['finish'],
                            "subscriberId" => $task['subscriberId'],
                            "expire" => $task['expire']  
                        ]);
                            
                        if ($file ) {
                            $this->db->modify("update camera_records set state = 2 where record_id = $recordId");
                            echo "Record download task with id = $recordId was successfully finished!\n";
                            fclose($file);
                            // print_r($files->getFile($fileId)["fileInfo"]);
                            // echo "\n\n";
                            return $fileId;
                        } else {

                            $this->db->modify("update camera_records set state = 3 where record_id = $recordId");
                            echo "Record download task with id = $recordId was finished with error code = $code!\n";
                            return false;
                        }
                    } else {
                        echo "Task with id = $recordId was not found\n";
                        return false;
                    }
                    
                    
                } catch (Exception $e) {
                    echo "Record download task with id = $recordId was failed to start\n";
                    return false;
                }
            }
        }
    }
