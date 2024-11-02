<?php

namespace hw\ip\domophone\ufanet;

/**
 * Class representing a Secret Top intercom.
 */
class secretTop extends ufanet
{

    public function configureApartment(
        int   $apartment,
        int   $code = 0,
        array $sipNumbers = [],
        bool  $cmsEnabled = true,
        array $cmsLevels = []
    ): void
    {
        $this->loadDialplans();

        $this->dialplans[$apartment] = [
            'sip_number' => "$sipNumbers[0]" ?? '',
            'sip' => true,
            'analog' => $cmsEnabled,
            'map' => $this->dialplans[$apartment]['map'] ?? 0,
        ];
    }

    public function configureGate(array $links = []): void
    {
        if (empty($links)) {
            return;
        }

        $this->apiCall('/api/v1/configuration', 'PATCH', [
            'commutator' => [
                'type' => 'GATE',
                'mode' => 1,
            ],
        ]);
    }

    public function deleteApartment(int $apartment = 0): void
    {
        $this->loadDialplans();

        ['map' => $analogReplace, 'analog' => $cmsEnabled] = $this->dialplans[$apartment];

        if ($analogReplace !== 0) {
            $this->dialplans[$apartment] = [
                'sip_number' => '',
                'sip' => false,
                'analog' => $cmsEnabled,
                'map' => $analogReplace,
            ];
        } else {
            unset($this->dialplans[$apartment]);
        }
    }

    public function prepare(): void
    {
        parent::prepare();
        $this->setDisplayLocalization();
    }

    public function setCmsModel(string $model = ''): void
    {
        $this->apiCall('/api/v1/configuration', 'PATCH', ['commutator' => self::CMS_PARAMS[$model] ?? []]);
    }

    public function setConciergeNumber(int $sipNumber): void
    {
        $this->loadDialplans();

        $this->dialplans[self::BUTTON_CONCIERGE] = [
            'sip_number' => "$sipNumber",
            'analog' => false,
            'sip' => true,
            'map' => 0,
        ];
    }

    public function setSosNumber(int $sipNumber): void
    {
        $this->loadDialplans();

        $this->dialplans[self::BUTTON_SOS] = [
            'sip_number' => "$sipNumber",
            'analog' => false,
            'sip' => true,
            'map' => 0,
        ];
    }

    public function setTickerText(string $text = ''): void
    {
        $this->apiCall('/api/v1/configuration', 'PATCH', ['display' => ['labels' => [$text, '', '']]]);
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $dbConfig = parent::transformDbConfig($dbConfig);

        if ($dbConfig['cmsModel'] !== '') {
            $cmsType = self::CMS_PARAMS[$dbConfig['cmsModel']]['type'];
            if (in_array($cmsType, ['METAKOM', 'ELTIS'])) {
                $dbConfig['cmsModel'] = $cmsType;
            }
        }

        return $dbConfig;
    }

    protected function getApartments(): array
    {
        $this->loadDialplans();

        $apartments = [];

        foreach ($this->dialplans as $apartmentNumber => $dialplan) {
            $isServiceNumber = in_array($apartmentNumber, [
                self::BUTTON_SOS,
                self::BUTTON_CONCIERGE,
                self::BUTTON_GATE,
                self::BUTTON_FACE,
            ]);

            if ($isServiceNumber || $dialplan['sip'] === false) {
                continue;
            }

            $apartments[$apartmentNumber] = [
                'apartment' => $apartmentNumber,
                'code' => 0,
                'sipNumbers' => [$dialplan['sip_number']],
                'cmsEnabled' => $dialplan['analog'],
                'cmsLevels' => [],
            ];
        }

        return $apartments;
    }

    protected function getCmsModel(): string
    {
        ['type' => $rawType, 'mode' => $mode] = $this->apiCall('/api/v1/configuration')['commutator'];

        return match ($rawType) {
            'DIGITAL' => 'QAD-100',
            'CYFRAL' => 'KMG-100',
            'FACTORIAL' => 'FACTORIAL 8x8',
            'VIZIT' => match ($mode) {
                2 => 'BK-100',
                3 => 'BK-400',
                default => $rawType,
            },
            default => $rawType,
        };
    }

    protected function getTickerText(): string
    {
        return $this->apiCall('/api/v1/configuration')['display']['labels'][0] ?? '';
    }

    /**
     * Set the display text for service messages.
     *
     * @return void
     */
    protected function setDisplayLocalization(): void
    {
        $this->apiCall('/api/v1/configuration', 'PATCH', [
            'display' => [
                'localization' => [
                    'ENTER_APARTMENT' => 'НАБЕРИТЕ НОМЕР КВАРТИРЫ',
                    'ENTER_PREFIX' => 'НАБЕРИТЕ ПРЕФИКС',
                    'CALL' => 'ИДЁТ ВЫЗОВ',
                    'CALL_GATE' => 'ЗАНЯТО',
                    'CONNECT' => 'ГОВОРИТЕ',
                    'OPEN' => 'ОТКРЫТО',
                    'FAIL_NO_CLIENT' => 'НЕВЕРНЫЙ НОМЕР КВАРТИРЫ',
                    'FAIL_NO_APP_AND_FLAT' => 'АБОНЕНТ НЕДОСТУПЕН',
                    'FAIL_LONG_SPEAK' => 'ВРЕМЯ ВЫШЛО',
                    'FAIL_NO_ANSWER' => 'НЕ ОТВЕЧАЕТ',
                    'FAIL_UNKNOWN' => 'ОШИБКА',
                    'FAIL_BLACK_LIST' => 'АБОНЕНТ ЗАБЛОКИРОВАН',
                    'FAIL_LINE_BUSY' => 'ЛИНИЯ ЗАНЯТА',
                    'KEY_DUPLICATE_ERROR' => 'ДУБЛИКАТ КЛЮЧА ЗАБЛОКИРОВАН',
                    'KEY_READ_ERROR' => 'ОШИБКА ЧТЕНИЯ КЛЮЧА',
                    'KEY_BROKEN_ERROR' => 'КЛЮЧ ВЫШЕЛ ИЗ СТРОЯ',
                    'KEY_UNSUPPORTED_ERROR' => 'КЛЮЧ НЕ ПОДДЕРЖИВАЕТСЯ'
                ],
            ],
        ]);
    }
}
