<?php

    namespace cli {

        class autoconfig {

            function __construct(&$globalCli) {
                $globalCli["#"]["autoconfigure"]["autoconfigure-device"] = [
                    "params" => [
                        [
                            "id" => [
                                "value" => "integer",
                                "placeholder" => "device id",
                            ],
                            "first-time" => [
                                "optional" => true,
                            ],
                        ],
                    ],
                    "value" => "string",
                    "placeholder" => "device type",
                    "description" => "Autoconfigure device",
                    "exec" => [ $this, "exec" ],
                ];
            }

            function exec($args) {
                $device_type = $args["--autoconfigure-device"];
                $device_id = $args["--id"];

                $first_time = array_key_exists("--first-time", $args);

                if (checkInt($device_id)) {
                    try {
                        autoconfigure_device($device_type, $device_id, $first_time);
                    } catch (Exception $e) {
                        $script_result = 'fail';
                        die("!!! FAILED: " . $e->getMessage() . " !!!\n\n");
                    }
                }

                exit(0);
            }
        }
    }