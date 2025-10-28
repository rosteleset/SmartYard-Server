<?php

namespace hw\ip\domophone\is;

/**
 * Class representing a Sokol ISCom X1 (rev.2) intercom.
 */
class iscomx1 extends is
{
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
}
