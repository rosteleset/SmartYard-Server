<?php

    /**
     * backends cs namespace
     */

    namespace backends\cs {

        use backends\backend;

        /**
         * base cs class
         */

        abstract class cs extends backend {

            /**
             * @param $sheet
             * @param $date
             * @return mixed
             */

            public function getCS($sheet, $date, $extended = false) {
                $files = loadBackend("files");

                if (!$files) {
                    return false;
                }

                $css = $files->searchFiles([
                    "metadata.type" => "csheet",
                    "metadata.sheet" => $sheet,
                    "metadata.date" => $date,
                ]);

                $cs = "{\n\t\"sheet\": \"$sheet\",\n\t\"date\": \"$date\"\n}";

                foreach ($css as $s) {
                    $cs = $files->streamToContents($files->getFileStream($s["id"])) ? : $cs;
                    break;
                }

                if ($extended) {
                    $cells = $this->redis->keys("cell_" . md5($sheet) . "_" . md5($date) . "_*");
                    if ($cells) {
                        $cells = $this->redis->mget($cells);

                        foreach ($cells as &$cell) {
                            $cell = json_decode($cell, true);
                        }
                    }
                    return [
                        "sheet" => json_decode($cs),
                        "cells" => $cells ? $cells : false,
                    ];
                } else {
                    return $cs;
                }
            }

            /**
             * @param $sheet
             * @param $date
             * @param $data
             * @return boolean
             */

            public function putCS($sheet, $date, $data) {
                $files = loadBackend("files");
                $mqtt = loadBackend("mqtt");

                if (!$files) {
                    return false;
                }

                $css = $files->searchFiles([
                    "metadata.type" => "csheet",
                    "metadata.sheet" => $sheet,
                    "metadata.date" => $date,
                ]);

                foreach ($css as $s) {
                    $cs = $files->deleteFile($s["id"]);
                }

                try {
                    $data_parsed = json_decode($data, true);
                } catch (\Exception $e) {
                    $data_parsed = false;
                }

                if ($data_parsed) {
                    $data_parsed["sheet"] = $sheet;
                    $data_parsed["date"] = $date;
                    $data = json_encode($data_parsed);
                }

                $success = $files->addFile($date . "_" . $sheet . ".json", $files->contentsToStream($data), [
                    "type" => "csheet",
                    "sheet" => $sheet,
                    "date" => $date,
                    "by" => $this->login,
                ]);

                if ($mqtt) {
                    $success = $success && $mqtt->broadcast("sheet/changed", [
                        "sheet" => $sheet,
                        "date" => $date
                    ]);
                }

                return $success;
            }

            /**
             * @param $date
             * @return boolean
             */

            public function deleteCS($sheet, $date) {
                $files = loadBackend("files");
                $mqtt = loadBackend("mqtt");

                if (!$files) {
                    return false;
                }

                $css = $files->searchFiles([
                    "metadata.type" => "csheet",
                    "metadata.sheet" => $sheet,
                    "metadata.date" => $date,
                ]);

                foreach ($css as $s) {
                    $cs = $files->deleteFile($s["id"]);
                }

                if ($mqtt) {
                    return $mqtt->broadcast("sheet/changed", [
                        "sheet" => $sheet,
                        "date" => $date
                    ]);
                } else {
                    return true;
                }
            }

            /**
             * @return false|array
             */

            public function getCSes() {
                $files = loadBackend("files");

                if (!$files) {
                    return false;
                }

                return $files->searchFiles([
                    "metadata.type" => "csheet",
                ]);
            }

            /**
             * @param $action
             * @param $sheet
             * @param $date
             * @param $col
             * @param $row
             * @param $uid
             * @param $expire
             * @param $sid
             * @param $step
             * @param $comment
             */

            public function setCell($action, $sheet, $date, $col, $row, $uid, $expire = 0, $sid = "", $step = 0, $comment = "") {
                $mqtt = loadBackend("mqtt");

                $expire = (int)($expire ? : 60);

                switch ($action) {
                    case "claim":
                    case "release":
                    case "release-force":
                        $keys = $this->redis->keys("cell_{$sheet}_{$date}_*");

                        foreach ($keys as $key) {
                            $cell = json_decode($this->redis->get($key), true);
                            $payload = explode("_", $key);
                            if (
                                ($action == "claim" && $cell["login"] == $this->login && $cell["mode"] == "claimed" && $cell["sheet"] == $sheet && $cell["date"] == $date && $cell["col"] == $col && $cell["row"] == $row) ||
                                ($action == "release" && $cell["login"] == $this->login && $cell["mode"] == "claimed" && $cell["sheet"] == $sheet && $cell["date"] == $date && $cell["col"] == $col && $cell["row"] == $row) ||
                                ($action == "release" && $cell["login"] == $this->login && $cell["mode"] == "reserved" && (int)$cell["uid"] == (int)$uid && $cell["sheet"] == $sheet && $cell["date"] == $date && $cell["col"] == $col && $cell["row"] == $row) ||
                                ($action == "release-force" && $cell["mode"] == "reserved" && (int)$cell["uid"] == (int)$uid && $cell["sheet"] == $sheet && $cell["date"] == $date && $cell["col"] == $col && $cell["row"] == $row)
                            ) {
                                $this->redis->delete($key);

                                if ($mqtt) {
                                    $mqtt->broadcast("cs/cell", [
                                        "action" => "released",
                                        "sheet" => $payload[1],
                                        "date" => $payload[2],
                                        "col" => $payload[3],
                                        "row" => $payload[4],
                                        "uid" => $payload[5],
                                    ]);
                                }
                            }
                        }

                        if ($action == "claim" && $col && $row && $uid) {
                            $this->redis->setex("cell_{$sheet}_{$date}_{$col}_{$row}_{$uid}", $expire, json_encode([
                                "login" => $this->login,
                                "mode" => "claimed",
                                "step" => $step,
                                "sheet" => $sheet,
                                "date" => $date,
                                "col" => $col,
                                "row" => $row,
                                "uid" => $uid,
                                "expire" => $expire,
                                "claimed" => time(),
                            ]));

                            if ($mqtt) {
                                $mqtt->broadcast("cs/cell", [
                                    "action" => "claimed",
                                    "step" => $step,
                                    "sheet" => $sheet,
                                    "date" => $date,
                                    "col" => $col,
                                    "row" => $row,
                                    "uid" => $uid,
                                    "login" => $this->login,
                                    "sid" => $sid,
                                ]);
                            }
                        }

                        break;

                    case "reserve":
                        try {
                            $cell = json_decode($this->redis->get($this->redis->keys("cell_*_" . $uid)[0]), true);
                        } catch (\Exception $e) {
                            $cell = false;
                        }

                        if ($cell && $cell["login"] == $this->login && $col && $row && $uid) {
                            $this->redis->setex("cell_{$sheet}_{$date}_{$col}_{$row}_{$uid}", $expire, json_encode([
                                "login" => $this->login,
                                "mode" => "reserved",
                                "sheet" => $sheet,
                                "date" => $date,
                                "col" => $col,
                                "row" => $row,
                                "uid" => $uid,
                                "expire" => $expire,
                                "reserved" => time(),
                                "comment" => $comment,
                            ]));

                            if ($mqtt) {
                                $mqtt->broadcast("cs/cell", [
                                    "action" => "reserved",
                                    "sheet" => $sheet,
                                    "date" => $date,
                                    "col" => $col,
                                    "row" => $row,
                                    "uid" => $uid,
                                    "login" => $this->login,
                                    "sid" => $sid,
                                    "comment" => $comment,
                                ]);
                            }
                        }

                        break;
                }

                return true;
            }

            /**
             * @param $sheet
             * @param $date
             * @param $col
             * @param $row
             */

            public function getCellByXYZ($sheet, $date, $col, $row) {

            }

            /**
             * @param $uid
             */

            public function getCellByUID($uid) {

            }

            /**
             * @param $sheet
             * @param $date
             */

            public function cells($sheet, $date) {

            }

            /**
             * @inheritDoc
             */

            public function cleanup() {
                $ttl = @(int)$this->config["backends"]["cs"]["ttl"] ?: 3;

                $sheets = $this->getCSes();

                $n = 0;

                foreach ($sheets as $sheet) {
                    if ($sheet["metadata"]["date"] < date("Y-m-d", strtotime("-$ttl day"))) {
                        $this->deleteCS($sheet["metadata"]["sheet"], $sheet["metadata"]["date"]);
                        $n++;
                    }
                }

                return $n;
            }

            /**
             * @inheritDoc
             */

            function cron($part) {
                if ($part === "daily") {
                    $this->cleanup();
                }

                return true;
            }
        }
    }