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
            public function getCS($sheet, $date)
            {
                $files = loadBackend("files");

                if (!$files) {
                    return false;
                }

                $css = $files->searchFiles([
                    "metadata.type" => "csheet",
                    "metadata.sheet" => $sheet,
                    "metadata.date" => $date,
                ]);

                $cs = "{}";

                foreach ($css as $s) {
                    $cs = $files->streamToContents($files->getFileStream($s["id"])) ? : "{}";
                }

                return $cs;
            }

            /**
             * @param $sheet
             * @param $date
             * @param $data
             * @return boolean
             */
            public function putCS($sheet, $date, $data)
            {
                $files = loadBackend("files");

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

                return $files->addFile($date . "_" . $sheet . ".json", $files->contentsToStream($data), [
                    "type" => "csheet",
                    "sheet" => $sheet,
                    "date" => $date,
                ]);
            }

            /**
             * @param $date
             * @return boolean
             */
            public function deleteCS($sheet, $date)
            {
                $files = loadBackend("files");

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

                return true;
            }

            /**
             * @return false|array
             */
            public function getCSes()
            {
                $files = loadBackend("files");

                if (!$files) {
                    return false;
                }

                return $files->searchFiles([
                    "metadata.type" => "csheet",
                ]);
            }
        }
    }