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

    public function configureSip(
        string $login,
        string $password,
        string $server,
        int    $port = 5060,
        bool   $stunEnabled = false,
        string $stunServer = '',
        int    $stunPort = 3478,
    ): void
    {
        $this->apiCall('/sip/settings', 'PUT', [
            'videoEnable' => true,
            'remote' => [
                'username' => $login,
                'password' => $password,
                'domain' => $server,
                'port' => $port,
            ],
        ]);
    }

    public function deleteApartment(int $apartment = 0): void
    {
        if ($apartment === 0) {
            $this->apiCall('/panelCode/clear', 'DELETE');
            $this->apiCall('/openCode/clear', 'DELETE');
            $this->apartments = [];
        } else {
            $this->apiCall("/panelCode/$apartment", 'DELETE');
            $this->deleteOpenCode($apartment);
            $this->apartments = array_diff($this->apartments, [$apartment]);
        }
    }

    public function getLineDiagnostics(int $apartment): int
    {
        $res = $this->apiCall("/panelCode/$apartment/resist");

        if (!$res || isset($res['errors'])) {
            return 0;
        }

        return $res['resist'];
    }

    /**
     * Get all apartments as they are presented in the panel.
     *
     * @return array An array of raw apartments.
     */
    protected function getRawApartments(): array
    {
        return $this->apiCall('/panelCode');
    }
}
