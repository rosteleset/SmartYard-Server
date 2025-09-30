<?php

    /**
     * backends wg namespace
     */

    namespace backends\wg {

        class internal extends wg {

            /**
             * generate base64 public key from base64 private key
             */

            private function pubkey($privateKeyBase64) {
                // native support

                if (extension_loaded('sodium')) {
                    return sodium_bin2base64(sodium_crypto_box_publickey_from_secretkey(sodium_base642bin($privateKeyBase64, SODIUM_BASE64_VARIANT_ORIGINAL)), SODIUM_BASE64_VARIANT_ORIGINAL);
                }

                $privateKeyFile = tempnam(sys_get_temp_dir(), 'wg_priv_');
                file_put_contents($privateKeyFile, $privateKeyBase64);
                $publicKeyBase64 = shell_exec("wg pubkey < " . escapeshellarg($privateKeyFile));
                unlink($privateKeyFile);

                if ($publicKeyBase64 !== null) {
                    return $publicKeyBase64;
                }

                return false;
            }

            /**
             * @inheritDoc
             */

            public function cron($part) {
                if ($part == "5min") {
                    // list peers for connection
                    // curl --header "Content-Type: application/json" --header "wg-dashboard-apikey: YOUR API KEY" SERVER/api/downloadAllPeers/callcenter

                    // add peer
                    // curl --header "Content-Type: application/json" --header "wg-dashboard-apikey: YOUR API KEY" SERVER/api/addPeers/callcenter --data '{ "name": "name" }'

                    // remove peer
                    // curl --header "Content-Type: application/json" --header "wg-dashboard-apikey: YOUR API KEY" SERVER/api/deletePeers/callcenter --data '{ "peers": [ "public key" ] }'
                }

                return true;
            }
        }
    }
