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
                        $dvr_files_path = @$config["backends"]["dvr_exports"]["dvr_files_path"] ?: false;
                        if ( $dvr_files_path && substr($dvr_files_path, -1) != '/' ) $dvr_files_path = $dvr_files_path . '/';
        
                        $dvr_files_location_prefix = @$config["backends"]["dvr_exports"]["dvr_files_location_prefix"] ?: false;
                        if ( $dvr_files_location_prefix && substr($dvr_files_location_prefix, -1) != '/' ) $dvr_files_location_prefix = $dvr_files_location_prefix . '/';
                    
                        $cameras = loadBackend("cameras");
                        $cam = $cameras->getCamera($task['cameraId']);
        
                        if (!$cam) {
                            echo "Camera with id = " . $task['cameraId'] . " was not found\n";
                            exit(0);
        
                        }
                        $request_url = loadBackend("dvr")->getUrlOfRecord($cam, $task['start'], $task['finish']);
                        
                        $this->db->modify("update camera_records set state = 1 where record_id = $recordId");
                        
                        echo "Record download task with id = $recordId was started\n";
                        echo "Fetching record form {$request_url} to ". $dvr_files_path . $task['filename']  . "\n";
                        
                        $files = loadBackend("files");
                        $file = fopen($request_url, "r");
                        $fileId = $files->addFile($task['filename'], $file, [
                            // "contentType" => "image/jpeg",
                            "camId" => $task['cameraId'],
                            "expire" => time() + 3 * 86400 // file storing period - 3 days 
                        ]);
                            
                        if ($file ) {
                            $this->db->modify("update camera_records set state = 2 where record_id = $recordId");
                            echo "Record download task with id = $recordId was successfully finished!\n";
                            fclose($file);
                            print_r($files->getFile($fileId)["fileInfo"]);
                            echo "\n\n";
                            return 0;
                        } else {

                            $this->db->modify("update camera_records set state = 3 where record_id = $recordId");
                            echo "Record download task with id = $recordId was finished with error code = $code!\n";
                            return 1;
                        }
                    } else {
                        echo "Task with id = $recordId was not found\n";
                        return 1;
                    }
                    
                    
                } catch (Exception $e) {
                    echo "Record download task with id = $recordId was failed to start\n";
                    return 1;
                }
            }
        }
    }
