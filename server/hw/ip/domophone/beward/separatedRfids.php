<?php

namespace hw\ip\domophone\beward;

/**
 * Trait that implements RFID key management for models with separate key tables.
 */
trait separatedRfids
{

    public function addRfid(string $code, int $apartment = 0)
    {
        $this->apiCall('cgi-bin/mifare_cgi', ['action' => 'add', 'Key' => $code, 'Type' => 1]);
        $this->apiCall('cgi-bin/extrfid_cgi', ['action' => 'add', 'Key' => $code, 'Type' => 1]);
    }

    public function addRfids(array $rfids)
    {
        foreach ($rfids as $rfid) {
            $this->addRfid($rfid);
        }
    }

    public function deleteRfid(string $code = '')
    {
        if ($code) {
            $this->apiCall('cgi-bin/mifare_cgi', ['action' => 'delete', 'Key' => $code]);
            $this->apiCall('cgi-bin/extrfid_cgi', ['action' => 'delete', 'Key' => $code]);
        } else {
            $this->apiCall('cgi-bin/mifare_cgi', ['action' => 'delete', 'Apartment' => 0]);
            $this->apiCall('cgi-bin/extrfid_cgi', ['action' => 'delete', 'Apartment' => 0]);

            foreach ($this->getRfids() as $rfid) {
                $this->deleteRfid($rfid);
            }
        }
    }

    public function getRfids(): array
    {
        $rfids = [];
        $raw_rfids = $this->parseParamValue(
            $this->apiCall('cgi-bin/mifare_cgi', ['action' => 'list'])
        );

        foreach ($raw_rfids as $key => $value) {
            if (strpos($key, 'Key') !== false) {
                $rfids[$value] = $value;
            }
        }

        return $rfids;
    }
}
