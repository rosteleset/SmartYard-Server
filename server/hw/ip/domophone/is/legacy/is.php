<?php

namespace hw\ip\domophone\is\legacy;

trait is
{

    /**
     * @deprecated
     */
    protected array $apartmentsLegacy = [];

    /**
     * @param int $apartment
     * @param int $code
     * @param array $sipNumbers
     * @param bool $cmsEnabled
     * @param array $cmsLevels
     * @return void
     *
     * @deprecated
     * @see configureApartment()
     */
    protected function configureApartmentLegacy(
        int   $apartment,
        int   $code = 0,
        array $sipNumbers = [],
        bool  $cmsEnabled = true,
        array $cmsLevels = []
    ): void
    {
        $this->refreshApartmentList();

        if (in_array($apartment, $this->apartmentsLegacy)) {
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
        $this->apartmentsLegacy[] = $apartment;

        if ($code) {
            $this->addOpenCode($code, $apartment);
        }
    }

    /**
     * @param array $matrix
     *
     * @return void
     *
     * @deprecated
     * @see configureMatrix()
     */
    protected function configureMatrixLegacy(array $matrix): void
    {
        $params = [];
        $this->refreshApartmentList();
        $matrix = $this->disfigureMatrix($matrix);

        foreach ($matrix as $matrixCell) {
            [
                // 'hundreds' => $hundreds,
                'tens' => $tens,
                'units' => $units,
                'apartment' => $apartment
            ] = $matrixCell;

            $params[$tens][$units] = $apartment;
        }

        [, $capacity, $columns, $rows] = self::CMS_MODEL_TO_PARAMS[$this->getCmsModel()];

        $zeroMatrix = array_fill(0, $columns, array_fill(0, $rows, 0));
        $fullMatrix = array_replace_recursive($zeroMatrix, $params);

        $this->apiCall('/switch/matrix/1', 'PUT', [
            'capacity' => $capacity,
            'matrix' => $fullMatrix,
        ]);

        $this->removeUnwantedApartments();
    }

    /**
     * @param int $apartment
     * @return void
     *
     * @deprecated
     * @see deleteApartment()
     */
    protected function deleteApartmentLegacy(int $apartment = 0): void
    {
        if ($apartment === 0) {
            $this->apiCall('/panelCode/clear', 'DELETE');
            $this->apiCall('/openCode/clear', 'DELETE');
            $this->apartmentsLegacy = [];
        } else {
            $this->apiCall("/panelCode/$apartment", 'DELETE');
            $this->deleteOpenCode($apartment);
            $this->apartmentsLegacy = array_diff($this->apartmentsLegacy, [$apartment]);
        }
    }

    /**
     * @return array
     *
     * @deprecated
     * @see getApartments()
     */
    protected function getApartmentsLegacy(): array
    {
        $rawApartments = $this->getRawApartments();

        if (!$rawApartments) {
            return [];
        }

        $openCodes = array_column($this->apiCall('/openCode'), 'code', 'panelCode');
        $apartments = [];

        foreach ($rawApartments as $apartment) {
            $apartmentNumber = $apartment['panelCode'];
            $code = $openCodes[$apartmentNumber] ?? 0;
            $cmsEnabled = $apartment['callsEnabled']['handset'];
            $cmsLevels = $this->getApartmentCmsParams(
                $apartment['resistances']['answer'],
                $apartment['resistances']['quiescent'],
            );
            $sipNumbers = $apartment['sipAccounts'] ?? [$apartmentNumber];

            $apartments[$apartmentNumber] = [
                'apartment' => $apartmentNumber,
                'code' => $code,
                'sipNumbers' => $sipNumbers,
                'cmsEnabled' => $cmsEnabled,
                'cmsLevels' => $cmsLevels,
            ];
        }

        return $apartments;
    }

    /**
     * @return string
     *
     * @deprecated
     * @see getCmsModel()
     */
    protected function getCmsModelLegacy(): string
    {
        $idModelMap = [
            'FACTORIAL' => [
                64 => 'FACTORIAL 8x8'
            ],
            'CYFRAL' => [
                100 => 'KMG-100'
            ],
            'VIZIT' => [
                100 => 'BK-100'
            ],
            'METAKOM' => [
                100 => 'COM-100U',
                220 => 'COM-220U',
            ],
            'ELTIS' => [
                100 => 'KM100-7.1'
            ],
        ];

        $cmsCapacity = $this->apiCall('/switch/matrix/1')['capacity'];
        $cmsModelId = $this->apiCall('/switch/settings')['modelId'];

        return $idModelMap[$cmsModelId][$cmsCapacity] ?? '';
    }

    /**
     * @return array
     *
     * @deprecated
     * @see getMatrix()
     */
    protected function getMatrixLegacy(): array
    {
        $matrix = [];

        for ($i = 0; $i <= 2; $i++) {
            $columns = $this->apiCall('/switch/matrix/' . ($i + 1))['matrix'];

            foreach ($columns as $tens => $column) {
                foreach ($column as $units => $apartment) {
                    if ($apartment !== null) {
                        $matrix[$i . $tens . $units] = [
                            'hundreds' => $i,
                            'tens' => $tens,
                            'units' => $units,
                            'apartment' => $apartment,
                        ];
                    }
                }
            }
        }

        return $this->restoreMatrix($matrix);
    }

    /**
     * @param string $model
     *
     * @return void
     *
     * @deprecated
     * @see setCmsModel()
     */
    protected function setCmsModelLegacy(string $model = ''): void
    {
        $id = self::CMS_MODEL_TO_PARAMS[$model][0];
        $nowMatrix = $this->getMatrix();

        $this->apiCall('/switch/settings', 'PUT', ['modelId' => $id]);

        $this->configureMatrix($nowMatrix);
    }
}
