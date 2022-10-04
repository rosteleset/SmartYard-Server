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

            public function updateTokens() {

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
                    "token" => "token",
                    "allow_sms" => "allowSms",
                    "allow_flash_call" => "allowFlashCall",
                    "allow_outgoing_call" => "allowOutgoingCall",
                ]);
            }

            /**
             * @inheritDoc
             */
            public function createProvider($id, $name, $baseUrl, $logo, $token, $allowSms, $allowFlashCall, $allowOutgoingCall)
            {
                return $this->db->insert("insert into providers (id, name, base_url, logo, token, allow_sms, allow_flash_call, allow_outgoing_call) values (:id, :name, :base_url, :logo, :token, :allow_sms, :allow_flash_call, :allow_outgoing_call)", [
                    "id" => $id,
                    "name" => $name,
                    "base_url" => $baseUrl,
                    "logo" => $logo,
                    "token" => $token,
                    "allow_sms" => $allowSms,
                    "allow_flash_call" => $allowFlashCall,
                    "allow_outgoing_call" => $allowOutgoingCall
                ]);
            }

            /**
             * @inheritDoc
             */
            public function modifyProvider($providerId, $id, $name, $baseUrl, $logo, $token, $allowSms, $allowFlashCall, $allowOutgoingCall)
            {
                if (!checkInt($providerId)) {
                    return false;
                }

                return $this->db->modify("update providers set id = :id, name = :name, base_url = :base_url, logo = :logo, token = :token, allow_sms = :allow_sms, allow_flash_call = :allow_flash_call, allow_outgoing_call = :allow_outgoing_call where provider_id = $providerId");
            }

            /**
             * @inheritDoc
             */
            public function deleteProvider($providerId)
            {
                if (!checkInt($providerId)) {
                    return false;
                }

                return $this->db->modify("delete from providers where provider_id = $providerId");
            }
        }
    }
