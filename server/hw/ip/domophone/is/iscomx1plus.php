<?php

namespace hw\ip\domophone\is;

use hw\ip\domophone\is\entities\CmsMatrix;

/**
 * Class representing a Sokol ISCom X1 Plus (rev.5) intercom.
 */
class iscomx1plus extends is
{

    /**
     * Mapping of CMS models to their corresponding type ID and capacity.
     *
     * @var array<string, array{type: int, capacity: int, columns: int, rows: int}>
     */
    protected const CMS_MODEL_DATA = [
        'BK-4' => ['type' => 50, 'capacity' => 4, 'columns' => 1, 'rows' => 4],
        'BK-10' => ['type' => 51, 'capacity' => 10, 'columns' => 1, 'rows' => 10],
        'BK-100' => ['type' => 52, 'capacity' => 100, 'columns' => 10, 'rows' => 10],
        'COM-80U' => ['type' => 61, 'capacity' => 80, 'columns' => 8, 'rows' => 10],
        'COM-100U' => ['type' => 3, 'capacity' => 100, 'columns' => 10, 'rows' => 10], // other
        'COM-160U' => ['type' => 63, 'capacity' => 160, 'columns' => 16, 'rows' => 10],
        'COM-220U' => ['type' => 65, 'capacity' => 220, 'columns' => 22, 'rows' => 10],
        'FACTORIAL 8x8' => ['type' => 0, 'capacity' => 64, 'columns' => 8, 'rows' => 8], // other
        'KKM-100S2' => ['type' => 13, 'capacity' => 100, 'columns' => 10, 'rows' => 10],
        'KKM-105' => ['type' => 11, 'capacity' => 100, 'columns' => 10, 'rows' => 10],
        'KKM-108' => ['type' => 12, 'capacity' => 100, 'columns' => 10, 'rows' => 10],
        'KM100-7.2' => ['type' => 30, 'capacity' => 100, 'columns' => 10, 'rows' => 10],
        'KMG-100' => ['type' => 20, 'capacity' => 100, 'columns' => 10, 'rows' => 10],
        'QAD-100' => ['type' => 40, 'capacity' => 260, 'columns' => 10, 'rows' => 26], // wrong, col <=> row
    ];

    /**
     * Array of CMS types that needs to increment/decrement units.
     */
    protected const CMS_TYPES_WRONG_UNITS = [61, 3, 63, 65];

    /**
     * First matrix number.
     */
    protected const MATRIX_FIRST_NUMBER = 1;

    /**
     * Last matrix number.
     */
    protected const MATRIX_LAST_NUMBER = 4;

    /**
     * @var array<int, CmsMatrix|null> An indexed array of CmsMatrix objects or null values.
     */
    protected array $cmsMatrices = [1 => null, 2 => null, 3 => null, 4 => null];

    /**
     * Generates a matrix of specified dimensions filled with `null` values.
     *
     * @param int $columns The number of columns in the matrix.
     * @param int $rows The number of rows in each column.
     * @return array A 2D array (matrix) filled with `null` values.
     */
    protected static function getNullMatrix(int $columns, int $rows): array
    {
        return array_fill(0, $columns, array_fill(0, $rows, null));
    }

    /**
     * Retrieves CMS model data by type.
     *
     * @param int $type The type identifier of the CMS model.
     * @return array{name: string, capacity: int, columns: int, rows: int}|null
     *         The CMS model data array with the 'name' key if a match is found, or null if no match.
     */
    protected static function getCmsDataByType(int $type): ?array
    {
        foreach (self::CMS_MODEL_DATA as $key => $data) {
            if ($data['type'] === $type) {
                return array_merge(['name' => $key], $data);
            }
        }

        return null;
    }

    public function configureMatrix(array $matrix): void
    {
        if ($this->isLegacyVersion()) {
            $this->configureMatrixLegacy($matrix);
            return;
        }

        // Set matrices cells to null
        foreach ($this->cmsMatrices as $cmsMatrixObject) {
            // Skip if matrix is disabled
            if ($cmsMatrixObject->type === null) {
                continue;
            }

            $cmsData = self::getCmsDataByType($cmsMatrixObject->type);
            $cmsMatrixObject->matrix = self::getNullMatrix($cmsData['columns'], $cmsData['rows']);
        }

        foreach ($matrix as $matrixCell) {
            [
                'hundreds' => $hundreds,
                'tens' => $tens,
                'units' => $units,
                'apartment' => $apartment
            ] = $matrixCell;

            $number = $hundreds + 1;
            $cmsMatrixObject = $this->getCmsMatrixObject($number);

            if ($cmsMatrixObject === null) {
                continue;
            }

            if (in_array($cmsMatrixObject->type, self::CMS_TYPES_WRONG_UNITS)) {
                $units--;
            }

            $cmsMatrixObject->matrix[$tens][$units] = $apartment;
        }
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

    public function setCmsModel(string $model = ''): void
    {
        if ($this->isLegacyVersion()) {
            $this->setCmsModelLegacy($model);
            return;
        }

        for ($number = self::MATRIX_FIRST_NUMBER; $number <= self::MATRIX_LAST_NUMBER; $number++) {
            $cmsMatrixObject = $this->getCmsMatrixObject($number);

            $cmsMatrixObject->type = self::CMS_MODEL_DATA[$model]['type'];
            $cmsMatrixObject->capacity = self::CMS_MODEL_DATA[$model]['capacity'];

            $this->configureMatrix($this->getMatrix());
        }
    }

    public function setTickerText(string $text = ''): void
    {
        $this->apiCall('/panelDisplay/settings', 'PUT', [
            'strDisplay' => $text !== '',
            'speed' => 300, // ms
            'imgStr' => $text,
        ]);
    }

    public function syncData(): void
    {
        parent::syncData();
        $this->uploadCmsMatrices();
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
     * Retrieves a CmsMatrix object based on the provided number.
     *
     * This method retrieves a `CmsMatrix` object from the `$cmsMatrices` array, or makes an API call
     * to fetch the matrix data if it has not been initialized yet.
     *
     * @param int $number The index of the CmsMatrix to retrieve. Must be between 1 and 4.
     * @return CmsMatrix|null The CmsMatrix object corresponding to the given number
     * or null if the number is out of range.
     */
    protected function getCmsMatrixObject(int $number = 1): ?CmsMatrix
    {
        if ($number < self::MATRIX_FIRST_NUMBER || $number > self::MATRIX_LAST_NUMBER) {
            return null;
        }

        if ($this->cmsMatrices[$number] === null) {
            $response = $this->apiCall("/v1/switch/$number");
            $this->cmsMatrices[$number] = CmsMatrix::fromArray($response);
        }

        return $this->cmsMatrices[$number];
    }

    protected function getCmsModel(): string
    {
        if ($this->isLegacyVersion()) {
            return $this->getCmsModelLegacy();
        }

        // We assume that all matrices have the same CMS model, so we take the first one
        $cmsTypeId = $this->getCmsMatrixObject()->type;
        return self::getCmsDataByType($cmsTypeId)['name'] ?? '';
    }

    protected function getMatrix(): array
    {
        if ($this->isLegacyVersion()) {
            return $this->getMatrixLegacy();
        }

        $matrix = [];

        for ($number = self::MATRIX_FIRST_NUMBER; $number <= self::MATRIX_LAST_NUMBER; $number++) {
            $cmsMatrixObject = $this->getCmsMatrixObject($number);

            if ($cmsMatrixObject->matrix === null) {
                continue;
            }

            $hundreds = $number - 1;

            foreach ($cmsMatrixObject->matrix as $tens => $column) {
                foreach ($column as $units => $apartment) {
                    if ($apartment === null) {
                        continue;
                    }

                    if (in_array($this->getCmsMatrixObject($number)->type, self::CMS_TYPES_WRONG_UNITS)) {
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

    /**
     * Uploads all non-null CmsMatrix objects via API.
     *
     * Iterates over `$cmsMatrices` and sends each non-null matrix object to the intercom
     * with data converted to an array.
     *
     * @return void
     */
    protected function uploadCmsMatrices(): void
    {
        foreach ($this->cmsMatrices as $number => $cmsMatrixObject) {
            if ($cmsMatrixObject === null) {
                continue;
            }

            $this->apiCall("/v1/switch/$number", 'PUT', $cmsMatrixObject->toArray());
        }
    }
}
