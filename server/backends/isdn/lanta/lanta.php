<?php

/**
 * backends isdn namespace
 */

namespace backends\isdn {

    /**
     * LanTa's variant of flash calls and sms sending
     */
    class lanta extends isdn
    {
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

            $result = trim(file_get_contents($this->config["backends"]["isdn"]['endpoint'] . "/isdn_api.php?action=push&secret=" . $this->config["backends"]["isdn"]["secret"] . "&" . $query));

            if (strtolower(explode(":", $result)[0]) !== "ok") {
                error_log("isdn push send error:\n query = $query\n result = $result\n");

                if (strtolower($result) === "err:broken") {
                    backend("households")->dismissToken($push["token"]);
                }
            }

            return $result;
        }

        /**
         * @inheritDoc
         */
        function sendCode($id)
        {
            return trim(file_get_contents($this->config["backends"]["isdn"]['endpoint'] . "/isdn_api.php?action=sendCode&mobile=$id&secret=" . $this->config["backends"]["isdn"]["sms_secret"]));
        }

        /**
         * @inheritDoc
         */
        function confirmNumbers()
        {
            return [
                "88002220374" // or can use number "+74752429949", if you are ouside of Russian Federation
            ];
        }

        /**
         * @inheritDoc
         */
        function checkIncoming($id)
        {
            return trim(file_get_contents($this->config["backends"]["isdn"]['endpoint'] . "/isdn_api.php?action=checkIncoming&mobile=$id&secret=" . $this->config["backends"]["isdn"]["common_secret"]));
        }

        function message($push)
        {
            return $this->push($push);
        }
    }
}
