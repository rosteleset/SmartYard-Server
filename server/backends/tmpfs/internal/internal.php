<?php

    /**
     * backends tmpfs namespace
     */

    namespace backends\tmpfs {

        class internal extends tmpfs {

            /**
             * @inheritDoc
             */

            public function putFile($uuid, $stream) {
                $id = md5((string)$uuid);

                $path = @$this->config["backends"]["tmpfs"]["path"] ?: "/tmp/tmpfs";
                $path_rights = octdec(@(int)$this->config["backends"]["tmpfs"]["path_rights"] ?: 777);
                $file_rights = octdec(@(int)$this->config["backends"]["tmpfs"]["file_rights"] ?: 777);

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

                $s = 0;

                while (!feof($stream)) {
                    $s += fwrite($file, fread($stream, 1024 * 1024));
                }

                fclose($file);
                fclose($stream);

                chmod($path, $file_rights);

                return $s;
            }

            /**
             * @inheritDoc
             */

            public function getFile($uuid) {
                $id = md5((string)$uuid);

                $path = @$this->config["backends"]["tmpfs"]["path"] ?: "/tmp/tmpfs";

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
                $id = md5((string)$uuid);

                $path = @$this->config["backends"]["tmpfs"]["path"] ?: "/tmp/tmpfs";

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

            public function cleanup() {
                $c = 0;

                $path = @$this->config["backends"]["tmpfs"]["path"] ?: "/tmp/tmpfs";
                $max_age = @$this->config["backends"]["tmpfs"]["max_age"] ?: "1month";
                $threshold = strtotime("-" . $max_age);

                if (file_exists($path) && $threshold) {
                    $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
                    foreach ($iterator as $info) {
                        if ($info->isFile() && $threshold >= $info->getMTime()) {
                            unlink($info->getPath() . "/" . $info->getFilename());
                        }
                    }
                }

                return $c;
            }

            /**
             * @inheritDoc
             */

            public function cron($part) {
                if ($part == "daily") {
                    $this->cleanup();

                    return true;
                }

                return true;
            }
        }
    }
