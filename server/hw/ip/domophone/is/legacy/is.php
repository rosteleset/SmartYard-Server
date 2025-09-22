<?php

namespace hw\ip\domophone\is\legacy;

trait is
{
    public function configureApartment(
        int   $apartment,
        int   $code = 0,
        array $sipNumbers = [],
        bool  $cmsEnabled = true,
        array $cmsLevels = [],
    ): void
    {
        $this->refreshApartmentList();

        if (in_array($apartment, $this->apartments)) {
            $method = 'PUT';
            $endpoint = "/$apartment";
            $this->deleteOpenCode($apartment);
        } else {
            $method = 'POST';
            $endpoint = '';
        }

        $payload = [
            'panelCode' => $apartment,
            'callsEnabled' => [
                'handset' => $cmsEnabled,
                'sip' => (bool)$sipNumbers,
            ],
            'soundOpenTh' => null, // inheritance from general settings
            'typeSound' => 3, // inheritance from general settings
            // 'sipAccounts' => array_map('strval', $sipNumbers), FIXME: doesn't work well
        ];

        $resistanceParams = $this->getApartmentResistanceParams($cmsLevels);
        if ($resistanceParams !== null) {
            $payload['resistances'] = $resistanceParams;
        }

        $this->apiCall('/panelCode' . $endpoint, $method, $payload);
        $this->apartments[] = $apartment;

        if ($code) {
            $this->addOpenCode($code, $apartment);
        }
    }
}
