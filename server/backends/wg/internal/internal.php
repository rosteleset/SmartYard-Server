<?php

    /**
     * backends wg namespace
     */

    namespace backends\wg {

        class internal extends wg {

            /**
             * generate base64 public key from base64 private key
             */

            private function pubKey($privateKeyBase64) {
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
                    $groups = loadBackend("groups");
                    $users = loadBackend("users");

                    if ($groups && $users) {
                        $allUsers = $users->getUsers();
                        $allGroups = $groups->getGroups();

                        $primaryGroups = [];
                        $groupExists = [];
                        foreach ($allUsers as $user) {
                            $primaryGroups[$user["login"]] = $user["primaryGroupAcronym"];
                            @$groupExists[$user["primaryGroupAcronym"]]++;
                        }

                        foreach ($allGroups as $g) {
                            $peers = json_decode(file_get_contents($this->config["backends"]["wg"]["api"] . "/api/downloadAllPeers/" . $g["acronym"], false, stream_context_create([
                                "http" => [
                                    "method" => "GET",
                                    "header" => [
                                        "Content-Type: application/json; charset=utf-8",
                                        "Accept: application/json; charset=utf-8",
                                        "wg-dashboard-apikey: " . $this->config["backends"]["wg"]["key"],
                                    ],
                                ],
                            ])), true);

                            $counts = [];
                            $logins = [];

                            if ($peers["status"]) {
                                foreach ($peers["data"] as $p) {
                                    @$counts[$p["fileName"]]++;
                                    $logins[$p["fileName"]] = $this->pubKey(parse_ini_string($p["file"], true, INI_SCANNER_RAW)["Interface"]["PrivateKey"]);
                                }

                                // remove doubles
                                foreach ($counts as $l => $c) {
                                    if ($c > 1) {
                                        file_get_contents($this->config["backends"]["wg"]["api"] . "/api/deletePeers/" . $g["acronym"], false, stream_context_create([
                                            "http" => [
                                                "method" => "POST",
                                                "header" => [
                                                    "Content-Type: application/json; charset=utf-8",
                                                    "Accept: application/json; charset=utf-8",
                                                    "wg-dashboard-apikey: " . $this->config["backends"]["wg"]["key"],
                                                ],
                                                "content" => json_encode([
                                                    "peers" => [
                                                        $logins[$l],
                                                    ],
                                                ]),
                                            ],
                                        ]));
                                    }
                                }

                                // remove if not in group
                                foreach ($logins as $l => $k) {
                                    if (@$primaryGroups[$l] != $g["acronym"]) {
                                        file_get_contents($this->config["backends"]["wg"]["api"] . "/api/deletePeers/" . $g["acronym"], false, stream_context_create([
                                            "http" => [
                                                "method" => "POST",
                                                "header" => [
                                                    "Content-Type: application/json; charset=utf-8",
                                                    "Accept: application/json; charset=utf-8",
                                                    "wg-dashboard-apikey: " . $this->config["backends"]["wg"]["key"],
                                                ],
                                                "content" => json_encode([
                                                    "peers" => [
                                                        $logins[$l],
                                                    ],
                                                ]),
                                            ],
                                        ]));
                                    }
                                }

                                // create peers
                                foreach ($primaryGroups as $l => $p) {
                                    if ($p == $g["acronym"] && !@$logins[$l]) {
                                        file_get_contents($this->config["backends"]["wg"]["api"] . "/api/addPeers/" . $g["acronym"], false, stream_context_create([
                                            "http" => [
                                                "method" => "POST",
                                                "header" => [
                                                    "Content-Type: application/json; charset=utf-8",
                                                    "Accept: application/json; charset=utf-8",
                                                    "wg-dashboard-apikey: " . $this->config["backends"]["wg"]["key"],
                                                ],
                                                "content" => json_encode([
                                                    "name" => $l,
                                                ]),
                                            ],
                                        ]));
                                    }
                                }
                            } else {
                                if (@$groupExists[$g["acronym"]]) {
                                    echo $g["acronym"] . ": " . $peers["message"] . "\n";
                                }
                            }
                        }
                    }
                }

                return true;
            }
        }
    }
