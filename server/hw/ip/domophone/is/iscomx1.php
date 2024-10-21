<?php

namespace hw\ip\domophone\is;

/**
 * Class representing a Sokol ISCom X1 (rev.2) intercom.
 */
class iscomx1 extends is
{

    public function configureMatrix(array $matrix): void
    {
        if ($this->isLegacyVersion()) {
            $this->configureMatrixLegacy($matrix);
            return;
        }

        $this->refreshApartmentList();
        $params = [0 => [], 1 => [], 2 => [], 3 => []];
        [, $capacity, $columns, $rows] = self::CMS_MODEL_TO_PARAMS[$this->getCmsModel()];

        $cmsModelId = $this->getCmsModelId();
        $zeroMatrix = array_fill(0, $columns, array_fill(0, $rows, 0));

        foreach ($matrix as $matrixCell) {
            [
                'hundreds' => $hundreds,
                'tens' => $tens,
                'units' => $units,
                'apartment' => $apartment
            ] = $matrixCell;

            if ($cmsModelId === 'METAKOM') {
                $units--;
            }

            $params[$hundreds][$tens][$units] = $apartment;
        }

        foreach ($params as $hundreds => $param) {
            $fullMatrix = array_replace_recursive($zeroMatrix, $param);

            $this->apiCall('/switch/matrix/' . ($hundreds + 1), 'PUT', [
                'capacity' => $capacity,
                'matrix' => $fullMatrix,
            ]);
        }

        $this->removeUnwantedApartments(); // FIXME: too slow, do something!
    }

    public function prepare(): void
    {
        parent::prepare();
        $this->enableEchoCancellation(false);
    }

    public function setCmsLevels(array $levels): void
    {
        if (count($levels) === 4) {
            $this->apiCall('/levels', 'PUT', [
                'resistances' => [
                    'break' => $levels[0],
                    'error' => $levels[1],
                    'quiescent' => $levels[2],
                    'answer' => $levels[3],
                ],
            ]);
        }
    }

    public function setCmsModel(string $model = ''): void
    {
        if ($this->isLegacyVersion()) {
            $this->setCmsModelLegacy($model);
            return;
        }

        $this->apiCall('/switch/settings', 'PUT', ['modelId' => self::CMS_MODEL_TO_PARAMS[$model][0]]);
    }

    public function setTickerText(string $text = ''): void
    {
        // Empty implementation
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $parentDbConfig = parent::transformDbConfig($dbConfig);
        $parentDbConfig['tickerText'] = '';

        if (!$this->isLegacyVersion()) {
            $parentDbConfig['cmsModel'] = self::CMS_MODEL_TO_PARAMS[$dbConfig['cmsModel']][0];
        }

        return $parentDbConfig;
    }

    protected function getApartmentCmsParams(?int $answer, ?int $quiescent): array
    {
        return [$answer, $quiescent];
    }

    protected function getApartmentResistanceParams(array $cmsLevels): ?array
    {
        $countLevels = count($cmsLevels);

        if ($countLevels === 4) { // From global levels
            return [
                'answer' => $cmsLevels[2],
                'quiescent' => $cmsLevels[3],
            ];
        }

        if ($countLevels === 2) { // From individual levels
            return [
                'answer' => $cmsLevels[0],
                'quiescent' => $cmsLevels[1],
            ];
        }

        return null;
    }

    protected function getCmsModel(): string
    {
        if ($this->isLegacyVersion()) {
            return $this->getCmsModelLegacy();
        }

        return $this->apiCall('/switch/settings')['modelId'] ?? '';
    }

    protected function getMatrix(): array
    {
        if ($this->isLegacyVersion()) {
            return $this->getMatrixLegacy();
        }

        $matrix = [];

        for ($hundreds = 0; $hundreds <= 3; $hundreds++) {
            $columns = $this->apiCall('/switch/matrix/' . ($hundreds + 1))['matrix'] ?? [];

            foreach ($columns as $tens => $column) {
                foreach ($column as $units => $apartment) {
                    if ($apartment === null) {
                        continue;
                    }

                    if ($this->getCmsModelId() === 'METAKOM') {
                        $units++;
                    }

                    $matrix[$hundreds . $tens . $units] = [
                        'hundreds' => $hundreds,
                        'tens' => $tens,
                        'units' => $units,
                        'apartment' => $apartment,
                    ];
                }
            }
        }

        return $matrix;
    }
}
