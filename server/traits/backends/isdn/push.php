<?php

    /**
     * backends isdn namespace
     */

    namespace backends\isdn
    {

        /**
         * teledome trait (common part)
         */

        trait push
        {
            /**
             * @inheritDoc
             */
            function push($push)
            {
                $query = "";
                foreach ($push as $param => $value) {
                    if ($param != "action" && $param != "secret") {
                        $query = $query . $param . "=" . urlencode($value) . "&";
                    }
                    if ($param == "action") {
                        $query = $query . "pushAction=" . urlencode($value) . "&";
                    }
                }
                if ($query) {
                    $query = substr($query, 0, -1);
                }

                $result = trim(file_get_contents("https://isdn.lanta.me/isdn_api.php?action=push&secret=" . $this->config["backends"]["isdn"]["common_secret"] . "&" . $query));

                if (strtolower(explode(":", $result)[0]) !== "ok") {
                    error_log("isdn push send error:\n query = $query\n result = $result\n");

                    if (strtolower($result) === "err:broken") {
                        loadBackend("households")->dismissToken($push["token"]);
                    }
                }
                

                return $result;
            }
        }
    }