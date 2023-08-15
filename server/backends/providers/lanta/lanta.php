<?php

    /**
     * backends providers namespace
     */

    namespace backends\providers
    {

        use http\Message;

        /**
         * LanTa's variant of flash calls and sms sending
         */
        class lanta extends providers
        {

            function putSecrets($section, $secrets) {
                $curl = curl_init();

                curl_setopt($curl, CURLOPT_HTTPHEADER, [ 'Content-Type: application_request/json' ]);
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($secrets));
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($curl, CURLOPT_URL, $this->config["backends"]["providers"]["api"] . "?action=secrets&secrets=" . $section . "&secret=" . $this->config["backends"]["providers"]["secret"]);

                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_TIMEOUT, 5);
                curl_setopt($curl, CURLOPT_VERBOSE, false);

                curl_exec($curl);
                curl_close($curl);
            }

            public function updateTokens()
            {
                $providers = $this->getProviders();

                $common = [];
                $Sms = [];
                $provs = [];

                foreach ($providers as $p) {
                    if ((int)$p["hidden"]) continue;
                    if ($p["tokenCommon"]) $common[] = $p["tokenCommon"];
                    if ($p["tokenSms"]) $Sms[] = $p["tokenSms"];
                    $provs[] = [
                        "id" => $p["id"],
                        "name" => $p["name"],
                        "baseUrl" => $p["baseUrl"],
                    ];
                }

                $this->putSecrets("Common", $common);
                $this->putSecrets("Sms", $Sms);

                try {
                    file_put_contents($this->config["backends"]["providers"]["providers.json"], json_encode([
                        "code" => 200,
                        "name" => "OK",
                        "message" => "GOOD",
                        "data" => $provs,
                    ]));
                } catch (\Exception $e) {
                    setLastError($e->getMessage());
                    return false;
                }

                return true;
            }

            /**
             * @inheritDoc
             */
            public function getJson()
            {
                try {
                    if (file_exists($this->config["backends"]["providers"]["providers.json"])) {
                        return file_get_contents($this->config["backends"]["providers"]["providers.json"]);
                    } else {
                        return "";
                    }
                } catch (\Exception $e) {
                    return false;
                }
            }

            /**
             * @inheritDoc
             */
            public function putJson($text)
            {
                try {
                    json_decode($text);
                    $error = json_last_error();
                    if ($error === JSON_ERROR_NONE) {
                        file_put_contents($this->config["backends"]["providers"]["providers.json"], $text);
                    } else {
                        setLastError("Json error: $error");
                        return false;
                    }
                } catch (\Exception $e) {
                    setLastError($e->getMessage());
                    return false;
                }

                return true;
            }

            /**
             * @inheritDoc
             */
            public function getProviders()
            {
                return $this->db->get("select * from providers order by name", false, [
                    "provider_id" => "providerId",
                    "id" => "id",
                    "name" => "name",
                    "base_url" => "baseUrl",
                    "logo" => "logo",
                    "token_common" => "tokenCommon",
                    "token_sms" => "tokenSms",
                    "hidden" => "hidden",
                ]);
            }

            /**
             * @inheritDoc
             */
            public function addProvider($id, $name, $baseUrl, $logo, $tokenCommon, $tokenSms, $hidden)
            {
                if (!checkInt($hidden)) {
                    return false;
                }

                if (!trim($name) || !trim(baseUrl) || !trim($tokenCommon)) {
                    return false;
                }

                $r = $this->db->insert("insert into providers (id, name, base_url, logo, token_common, token_sms, hidden) values (:id, :name, :base_url, :logo, :token_common, :token_sms, :hidden)", [
                    "id" => $id,
                    "name" => $name,
                    "base_url" => $baseUrl,
                    "logo" => $logo,
                    "token_common" => $tokenCommon,
                    "token_sms" => $tokenSms,
                    "hidden" => $hidden,
                ]);

                if ($this->updateTokens()) {
                    return $r;
                } else {
                    $this->deleteProvider($r);
                    return false;
                }
            }

            /**
             * @inheritDoc
             */
            public function modifyProvider($providerId, $id, $name, $baseUrl, $logo, $tokenCommon, $tokenSms, $hidden)
            {
                if (!checkInt($providerId)) {
                    return false;
                }

                if (!checkInt($hidden)) {
                    return false;
                }

                if (!trim($name) || !trim(baseUrl) || !trim($tokenCommon)) {
                    return false;
                }

                $r = $this->db->modify("update providers set id = :id, name = :name, base_url = :base_url, logo = :logo, token_common = :token_common, token_sms = :token_sms, hidden = :hidden where provider_id = $providerId", [
                    "id" => $id,
                    "name" => $name,
                    "base_url" => $baseUrl,
                    "logo" => $logo,
                    "token_common" => $tokenCommon,
                    "token_sms" => $tokenSms,
                    "hidden" => $hidden,
                ]);

                if ($this->updateTokens()) {
                    return $r;
                } else {
                    return false;
                }
            }

            /**
             * @inheritDoc
             */
            public function deleteProvider($providerId)
            {
                if (!checkInt($providerId)) {
                    return false;
                }

                $r = $this->db->modify("delete from providers where provider_id = $providerId");

                if ($this->updateTokens()) {
                    return $r;
                } else {
                    return false;
                }
            }
        }
    }
