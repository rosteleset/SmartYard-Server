<?php

/**
 * backends geocoder namespace
 */

namespace backends\geocoder {

    /**
     * datata geocoder
     */

    class dadata extends geocoder {

        /**
         * @inheritDoc
         */
        public function suggestions($search) {
            if ($search) {
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_HTTPHEADER, [
                    "Content-Type: application/json",
                    "Accept: application/json",
                    "Authorization: Token {$this->config["backends"]["geocoder"]["token"]}"
                ]);
                curl_setopt($curl, CURLOPT_POST, 1);
                if (@$this->config["backends"]["geocoder"]["locations"]) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode([ "query" => $search, "locations" => $this->config["backends"]["geocoder"]["locations"] ]));
                } else {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode([ "query" => $search ]));
                }
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($curl, CURLOPT_URL, "https://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/address");
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl, CURLOPT_TIMEOUT, 30);
                curl_setopt($curl, CURLOPT_VERBOSE, 0);

                $result_raw = curl_exec($curl);
                $result_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                $result = json_decode($result_raw, true);
                curl_close($curl);

                if ($result_code >= 200 && $result_code < 400) {
                    for ($i = 0; $i < count($result["suggestions"]); $i++) {
                        if ((int)$result["suggestions"][$i]["data"]["fias_level"] === 8 || ((int)$result["suggestions"][$i]["data"]["fias_level"] === -1 && $result["suggestions"][$i]["data"]["house"])) {
                            $this->redis->setex("house_" . $result["suggestions"][$i]["data"]["house_fias_id"], 7 * 24 * 60 * 60, json_encode($result["suggestions"][$i]));
                        }
                    }
                    return $result["suggestions"];
                } else {
                    return false;
                }
            } else {
                return [];
            }
        }
    }
}
