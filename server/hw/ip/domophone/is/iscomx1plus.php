<?php

namespace hw\ip\domophone\is;

use hw\Interface\DisplayTextInterface;

/**
 * Class representing a Sokol ISCom X1 Plus (rev.5) intercom.
 */
class iscomx1plus extends is implements DisplayTextInterface
{
    public function getDisplayText(): array
    {
        $text = $this->apiCall('/panelDisplay/settings')['imgStr'] ?? '';
        return $text === '' ? [] : [$text];
    }

    public function getDisplayTextLinesCount(): int
    {
        return 1;
    }

    public function prepare(): void
    {
        parent::prepare();
        $this->setServiceCode();
    }

    public function setCmsLevels(array $levels): void
    {
        if (count($levels) === 2) {
            $this->apiCall('/levels', 'PUT', [
                'resistances' => [
                    'quiescent' => $levels[0],
                    'answer' => $levels[1],
                ],
            ]);
        }
    }

    public function setDisplayText(array $textLines): void
    {
        $this->apiCall('/panelDisplay/settings', 'PUT', [
            'strDisplay' => isset($textLines[0]) && $textLines[0] !== '',
            'speed' => 300, // ms
            'imgStr' => $textLines[0] ?? '',
        ]);
    }

    protected function getApartmentCmsParams(?int $answer, ?int $quiescent): array
    {
        return [$quiescent, $answer];
    }

    protected function getApartmentResistanceParams(array $cmsLevels): ?array
    {
        if (count($cmsLevels) === 2) {
            return [
                'quiescent' => (float)$cmsLevels[0],
                'answer' => (float)$cmsLevels[1],
            ];
        }

        return null;
    }

    /**
     * Set service code.
     * This code is used to access the service menu from the front panel of the device.
     *
     * @param int $code (Optional) The service code to be set. If set to 0, the service code will be disabled.
     * Otherwise, it will be enabled with the provided code. 0 by default.
     *
     * @return void
     */
    protected function setServiceCode(int $code = 0): void
    {
        $enabled = $code !== 0;
        $pass = $enabled ? $code : 123456;

        $this->apiCall('/serviceCode/settings', 'PUT', [
            'enabled' => $enabled,
            'pass' => $pass,
        ]);
    }

    protected const CMS_MODELS = [
        'BK-100' => ['id' => 2, 'name' => 'Визит', 'eCount' => 10, 'dCount' => 10, 'switchCount' => 1],
        'KMG-100' => ['id' => 20, 'name' => 'Цифрал', 'eCount' => 10, 'dCount' => 10, 'switchCount' => 1],
        'KKM-100S2' => ['id' => 13, 'name' => 'Бевард', 'eCount' => 10, 'dCount' => 10, 'switchCount' => 1],
        'KM100-7.1' => ['id' => 4, 'name' => 'Элтис', 'eCount' => 10, 'dCount' => 10, 'switchCount' => 1],
        'KM100-7.5' => ['id' => 4, 'name' => 'Элтис', 'eCount' => 10, 'dCount' => 10, 'switchCount' => 1],
        'COM-100U' => ['id' => 3, 'name' => 'Метаком', 'eCount' => 10, 'dCount' => 10, 'switchCount' => 1],
        'COM-220U' => ['id' => 65, 'name' => 'Метаком COM 220 U', 'eCount' => 10, 'dCount' => 22, 'switchCount' => 1],
        'FACTORIAL 8x8' => ['id' => 0, 'name' => 'Факториал', 'eCount' => 8, 'dCount' => 8, 'switchCount' => 1],
        'DP-K2D' => ['id' => 40, 'name' => 'ДП-К2Д', 'eCount' => 26, 'dCount' => 10, 'switchCount' => 1],
//        'Метаком COM 80' => ['id' => 60, 'name' => 'Метаком COM 80', 'eCount' => 10, 'dCount' => 8, 'switchCount' => 1],
//        'Метаком COM 80 U' => ['id' => 61, 'name' => 'Метаком COM 80 U', 'eCount' => 10, 'dCount' => 8, 'switchCount' => 1],
//        'Метаком COM 80 UD' => ['id' => 62, 'name' => 'Метаком COM 80 UD', 'eCount' => 10, 'dCount' => 8, 'switchCount' => 1],
//        'Мeтаком COM 160 U' => ['id' => 63, 'name' => 'Мeтаком COM 160 U', 'eCount' => 10, 'dCount' => 16, 'switchCount' => 1],
//        'Метаком COM 160 UD' => ['id' => 64, 'name' => 'Метаком COM 160 UD', 'eCount' => 10, 'dCount' => 16, 'switchCount' => 1],
//        'Метаком COM 220 UD' => ['id' => 66, 'name' => 'Метаком COM 220 UD', 'eCount' => 10, 'dCount' => 22, 'switchCount' => 1]
    ];

    protected function getMatrixNow(): array
    {
        $matrices = [];
        for ($i = 0; $i <= 2; $i++) {
            $idMatrix = $i + 1;
            $columns = $this->apiCall('/v1/switch/' . $idMatrix)['matrices'];
            $matrices[$idMatrix] = $columns;
        }
        return $matrices;
    }

    public function setCmsModel(string $model = ''): void
    {
        echo PHP_EOL . 'setCmsModel rev5' . PHP_EOL;
        $nowMatrix = $this->getMatrixNow();
        $dataModel = self::CMS_MODELS[$model];
        $id_model = $dataModel['id'];
        foreach ($nowMatrix as $idMatrix => $matrixCell) {
            $requestData = [
                'type' => $id_model,
                'matrices' => $matrixCell
            ];
            $this->apiCall("/v1/switch/$idMatrix", 'PUT', $requestData);
        }
    }

    protected function disfigureMatrix(array $matrix): array
    {
        [$id, , $columns, $rows] = self::CMS_PARAMS[$this->getCmsModel()];
        $wrongCmses = ['METAKOM', 'FACTORIAL'];

        if (!in_array($id, $wrongCmses)) {
            return $matrix;
        }

        foreach ($matrix as $key => &$item) {
            if ($item['units'] === $rows) {
                $item['units'] = 0;
                $item['tens'] += 1;

                if ($item['tens'] === $columns) {
                    $item['tens'] = 0;
                    $item['units'] = 0;
                }

                $newKey = $item['hundreds'] . $item['tens'] . $item['units'];
                $matrix[$newKey] = $item;
                unset($matrix[$key]);
            }
        }

        return $matrix;
    }

    public function configureMatrix(array $matrix): void
    {
        echo PHP_EOL . 'configureMatrix rev5' . PHP_EOL;
        $this->refreshApartmentList();
        $matrix = $this->disfigureMatrix($matrix);
        $dataModel = self::CMS_MODELS[$this->getCmsModel()];
        $id = $dataModel['id'];
        $capacity = $dataModel['dCount'] * $dataModel['eCount'];
        $params = [];
        foreach ($matrix as $matrixCell) {
            $hundreds = $matrixCell['hundreds'];
            $apartment = $matrixCell['apartment'];
            $tens = $matrixCell['tens'];
            $units = $matrixCell['units'];
            if (!isset($params[$hundreds])){
                $params[$hundreds] = array_fill(0, $capacity, 0);
            }
            $params[$hundreds][($tens.$units)*1] = $apartment;
        }
        foreach ($params as $key => $items) {
            // Подготавливаем данные запроса
            $idMatrix = $key + 1;
            $requestData = [
                'type' => $id,
                'matrices' => [
                    [
                        'id' => $idMatrix,
                        'matrix' => [$items],
                        'capacity' => $capacity
                    ]
                ]
            ];
            $this->apiCall("/v1/switch/$idMatrix", 'PUT', $requestData);
        }
        $this->removeUnwantedApartments();
    }

    protected function restoreMatrix(array $matrix): array
    {
        [$id, , $columns, $rows] = self::CMS_PARAMS[$this->getCmsModel()];
        $wrongCmses = ['METAKOM', 'FACTORIAL'];

        if (!in_array($id, $wrongCmses)) {
            return $matrix;
        }

        foreach ($matrix as $key => &$item) {
            if ($item['units'] === 0) {
                $item['units'] = $rows;

                if ($item['tens'] !== 0) {
                    $item['tens'] -= 1;
                } else {
                    $item['tens'] = $columns - 1;
                }

                $newKey = $item['hundreds'] . $item['tens'] . $item['units'];
                $matrix[$newKey] = $item;
                unset($matrix[$key]);
            }
        }

        return $matrix;
    }

    protected function getCmsModel(): string
    {
        $idModelMap = [
            0 => 'FACTORIAL 8x8',
            20 => 'KMG-100',
            2 => 'BK-100',
            3 => 'COM-100U',
            65 => 'COM-220U',
            4 => 'KM100-7.1',
            13 => 'KKM-100S2',
            40 => 'DP-K2D',
        ];
        $cmsModelId = $this->apiCall('/v1/switch/1')['type'];
        return  $idModelMap[$cmsModelId] ?? '';
    }

    protected function getMatrix(): array
    {
        $matrix = [];

        for ($i = 0; $i <= 2; $i++) {
            $columns = $this->apiCall('/v1/switch/' . ($i + 1))['matrices'];
            if (isset($columns['matrix'], $columns)) {
                foreach ($columns['matrix'] as $tens => $column) {
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
        }

        return $this->restoreMatrix($matrix);
    }

}
