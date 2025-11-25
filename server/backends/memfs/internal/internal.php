<?php

    /**
     * backends memfs namespace
     */

    namespace backends\memfs {

        class internal extends memfs {

            /**
             * @inheritDoc
             */

            public function putFile($uuid, $content) {
                $id = md5((string)$uuid);

                $path = @$this->config["backends"]["memfs"]["path"] ?: "/tmp/memfs";
                $path_rights = octdec(@(int)$this->config["backends"]["memfs"]["path_rights"] ?: 777);
                $file_rights = octdec(@(int)$this->config["backends"]["memfs"]["file_rights"] ?: 777);

                if ($path[strlen($path) - 1] != "/") {
                    $path .= "/";
                }

                $path .= $id[0] . "/";
                $path .= $id[1] . "/";

                if (!file_exists($path)) {
                    mkdir($path, $path_rights, true);
                }

                $path .= $id;

                $res = file_put_contents($path, $content);

                chmod($path, $file_rights);

                return $res;
            }

            /**
             * @inheritDoc
             */

            public function getFile($uuid) {
                $id = md5((string)$uuid);

                $path = @$this->config["backends"]["memfs"]["path"] ?: "/tmp/memfs";

                if ($path[strlen($path) - 1] != "/") {
                    $path .= "/";
                }

                $path .= $id[0] . "/";
                $path .= $id[1] . "/";

                $path .= $id;

                if (file_exists($path)) {
                    return @file_get_contents($path);
                } else {
                    return false;
                }
            }

            /**
             * @inheritDoc
             */

            public function cleanup() {
                $c = 0;

                $path = @$this->config["backends"]["memfs"]["path"] ?: "/tmp/memfs";
                $max_age = @$this->config["backends"]["memfs"]["max_age"] ?: "5min";
                $threshold = strtotime("-" . $max_age);

                if (file_exists($path) && $threshold) {
                    $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
                    foreach ($iterator as $info) {
                        if ($info->isFile() && $threshold >= $info->getMTime()) {
                            unlink($info->getPath() . "/" . $info->getFilename());
                            $c++;
                        }
                    }
                }

                return $c;
            }

            /**
             * @inheritDoc
             */

            public function cron($part) {
                if ($part == "5min") {
                    $this->cleanup();

                    return true;
                }

                return true;
            }
        }
    }
