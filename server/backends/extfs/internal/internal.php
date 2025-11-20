<?php

    /**
     * backends extfs namespace
     */

    namespace backends\extfs {

        class internal extends extfs {

            /**
             * @inheritDoc
             */

            public function addFile($uuid, $stream) {
                $id = (string)$uuid;

                $path = @$this->config["backends"]["path"] ?: "/tmp/extfs";
                $path_rights = octdec(@(int)$this->config["backends"]["extfs"]["path_rights"] ?: 777);
                $file_rights = octdec(@(int)$this->config["backends"]["extfs"]["file_rights"] ?: 777);

                if ($path[strlen($path) - 1] != "/") {
                    $path .= "/";
                }

                $path .= $id[0] . "/";
                $path .= $id[1] . "/";

                if (!file_exists($path)) {
                    mkdir($path, $path_rights, true);
                }

                $path .= $id;

                $file = fopen($path, "w");

                while (!feof($stream)) {
                    fwrite($file, fread($stream, 1024 * 1024));
                }

                fclose($file);
                fclose($stream);

                chmod($path, $file_rights);

                return true;
            }

            /**
             * @inheritDoc
             */

            public function getFile($uuid) {
                $id = (string)$uuid;

                $path = @$this->config["backends"]["extfs"]["path"] ?: "/tmp/extfs";

                if ($path[strlen($path) - 1] != "/") {
                    $path .= "/";
                }

                $path .= $id[0] . "/";
                $path .= $id[1] . "/";

                $path .= $id;

                if (file_exists($path)) {
                    return fopen($path, "r");
                } else {
                    return false;
                }
            }

            /**
             * @inheritDoc
             */

            public function deleteFile($uuid) {
                $id = (string)$uuid;

                $path = @$this->config["backends"]["extfs"]["path"] ?: "/tmp/extfs";

                if ($path[strlen($path) - 1] != "/") {
                    $path .= "/";
                }

                $path .= $id[0] . "/";
                $path .= $id[1] . "/";

                $path .= $id;

                if (file_exists($path)) {
                    return unlink($path);
                } else {
                    return false;
                }

            }

            /**
             * @inheritDoc
             */

            public function cron($part) {
                if ($part == "daily") {
                    return true;
                }

                return true;
            }
        }
    }
