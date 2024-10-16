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
     * Mapping of CMS models to their corresponding ID.
     *
     * @var array<string, int>
     */
    protected const CMS_MODEL_ID = [
        'BK-4' => 50,
        'BK-10' => 51,
        'BK-100' => 52,
        'COM-80U' => 61,
        'COM-100U' => 3, // other
        'COM-160U' => 63,
        'COM-220U' => 65,
        'FACTORIAL 8x8' => 0, // other
        'KKM-100S2' => 13,
        'KKM-105' => 11,
        'KKM-108' => 12,
        'KM100-7.2' => 30,
        'KMG-100' => 20,
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
        $cmsModel = array_search($cmsTypeId, self::CMS_MODEL_ID);

        return $cmsTypeId !== null && $cmsModel !== false ? $cmsModel : '';
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
