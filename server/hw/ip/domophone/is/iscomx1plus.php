<?php

namespace hw\ip\domophone\is;

use hw\ip\domophone\is\entities\CmsMatrix;
use InvalidArgumentException;

/**
 * Class representing a Sokol ISCom X1 Plus (rev.5) intercom.
 */
class iscomx1plus extends is
{

    /**
     * Mapping of CMS models to their corresponding type ID and capacity.
     *
     * @var array<string, array{id: int, capacity: int}>
     */
    protected const CMS_MODEL_DATA = [
        'BK-4' => ['type' => 50, 'capacity' => 4],
        'BK-10' => ['type' => 51, 'capacity' => 10],
        'BK-100' => ['type' => 52, 'capacity' => 100],
        'COM-80U' => ['type' => 61, 'capacity' => 80],
        'COM-100U' => ['type' => 3, 'capacity' => 100], // other
        'COM-160U' => ['type' => 63, 'capacity' => 160],
        'COM-220U' => ['type' => 65, 'capacity' => 220],
        'FACTORIAL 8x8' => ['type' => 0, 'capacity' => 64], // other
        'KKM-100S2' => ['type' => 13, 'capacity' => 100],
        'KKM-105' => ['type' => 11, 'capacity' => 100],
        'KKM-108' => ['type' => 12, 'capacity' => 100],
        'KM100-7.2' => ['type' => 30, 'capacity' => 100],
        'KMG-100' => ['type' => 20, 'capacity' => 100],
        'QAD-100' => ['type' => 40, 'capacity' => 260],
    ];

    /**
     * An array that holds either null (for uninitialized matrices) or CmsMatrix objects.
     *
     * @var array<int, CmsMatrix|null> An indexed array of CmsMatrix objects or null values.
     */
    protected array $cmsMatrices = [1 => null, 2 => null, 3 => null, 4 => null];

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

        for ($number = 1; $number <= 4; $number++) {
            $cmsMatrixObject = $this->getCmsMatrixObject($number);
            $cmsMatrixObject->type = self::CMS_MODEL_DATA[$model]['type'];
            $cmsMatrixObject->capacity = self::CMS_MODEL_DATA[$model]['capacity'];
            // TODO: set full matrix
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
     * @return CmsMatrix The CmsMatrix object corresponding to the given number.
     * @throws InvalidArgumentException If the provided number is less than 1 or greater than 4.
     */
    protected function getCmsMatrixObject(int $number = 1): CmsMatrix
    {
        if ($number < 1 || $number > 4) {
            throw new InvalidArgumentException('Number must be between 1 and 4.');
        }

        if ($this->cmsMatrices[$number] === null) {
            $response = $this->apiCall("/v1/switch/$number");
            $this->cmsMatrices[$number] = new CmsMatrix(
                $response['type'] ?? null,
                $response['matrices'][0]['matrix'] ?? null,
                $response['matrices'][0]['capacity'] ?? null
            );
        }

        return $this->cmsMatrices[$number];
    }

    protected function getCmsModel(): string
    {
        if ($this->isLegacyVersion()) {
            return $this->getCmsModelLegacy();
        }

        $cmsTypeId = $this->getCmsMatrixObject()->type;

        foreach (self::CMS_MODEL_DATA as $cmsModel => $cmsData) {
            if ($cmsData['id'] === $cmsTypeId) {
                return $cmsModel;
            }
        }

        return '';
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
