<?php

namespace hw\ip\domophone\rubetek;

use hw\Enum\HousePrefixField;
use hw\Interface\{
    CmsLevelsInterface,
    DbConfigUpdaterInterface,
    DisplayTextInterface,
    FreePassInterface,
    HousePrefixInterface,
    LanguageInterface,
};
use hw\ip\domophone\domophone;
use hw\ValueObject\{
    FlatNumber,
    HousePrefix,
};

/**
 * Abstract class representing a Rubetek domophone.
 */
abstract class rubetek extends domophone implements
    CmsLevelsInterface,
    DbConfigUpdaterInterface,
    DisplayTextInterface,
    FreePassInterface,
    HousePrefixInterface,
    LanguageInterface
{
    use \hw\ip\common\rubetek\rubetek {
        transformDbConfig as protected commonTransformDbConfig;
    }

    /**
     * @var array|null $dialplans An array that holds dialplan information,
     * which may be null if not loaded.
     */
    protected ?array $dialplans = null;

    public function addRfid(string $code, int $apartment = 0): void
    {
        $this->apiCall('/rfids', 'POST', [
            'rfid' => $code,
            'door_access' => [
                RubetekConst::RELAY_1_INTERNAL,
                RubetekConst::RELAY_2_EXTERNAL,
            ],
        ]);
    }

    public function addRfids(array $rfids): void
    {
        $rfidChunks = array_chunk($rfids, 400); // Cannot add more than 400 records in one request

        foreach ($rfidChunks as $rfidChunk) {
            $this->apiCall('/rfids_pack', 'POST', [
                'rfids' => $rfidChunk,
                'door_access' => [
                    RubetekConst::RELAY_1_INTERNAL,
                    RubetekConst::RELAY_2_EXTERNAL,
                ],
            ]);
        }
    }

    public function configureApartment(
        int   $apartment,
        int   $code = 0,
        array $sipNumbers = [],
        bool  $cmsEnabled = true,
        array $cmsLevels = [],
    ): void
    {
        $this->loadDialplans();

        $dialplan = $this->dialplans[$apartment] ?? ['id' => "$apartment", 'analog_number' => ''];

        $this->updateDialplan(
            id: $dialplan['id'],
            sipNumber: $sipNumbers[0] ?? '',
            analogNumber: $dialplan['analog_number'],
            callType: $cmsEnabled ? RubetekConst::SIP_ANALOG : RubetekConst::SIP,
            doorAccess: [RubetekConst::RELAY_1_INTERNAL],
            accessCodes: $code !== 0 ? ["$code"] : [],
        );
    }

    public function configureEncoding(): void
    {
        $videoSettings = $this->apiCall('/settings/video');

        // 1st stream
        $videoSettings['channel1']['fps'] = '15fps';
        $videoSettings['channel1']['bitrate'] = '2Mbps';
        $videoSettings['channel1']['resolution'] = '1280x720';

        // 2nd stream
        $videoSettings['channel2']['fps'] = '15fps';
        $videoSettings['channel2']['bitrate'] = '0.5Mbps';
        $videoSettings['channel2']['resolution'] = '720x480';

        // 3rd stream
        $videoSettings['channel3']['fps'] = '15fps';
        $videoSettings['channel3']['bitrate'] = '0.5Mbps';
        $videoSettings['channel3']['resolution'] = '640x480';

        // Image (day)
        $videoSettings['day_time']['wdr_enabled'] = true;
        $videoSettings['day_time']['light_compensation_mode'] = 1; // BLC (Back Light Compensation)
        $videoSettings['day_time']['contrast'] = 70;
        $videoSettings['day_time']['saturation'] = 60;

        // Image (night)
        $videoSettings['night_time']['wdr_enabled'] = true;
        $videoSettings['night_time']['light_compensation_mode'] = 2; // HLC (High Light Compensation)
        $videoSettings['night_time']['contrast'] = 70;
        $videoSettings['night_time']['saturation'] = 60;

        // Other settings
        $videoSettings['use_for_sip'] = 'channel1';
        $videoSettings['use_for_webrtc'] = 'channel1';
        $videoSettings['snapshot_size'] = '1280x720';

        $this->apiCall('/settings/video', 'PATCH', $videoSettings);
    }

    public function configureMatrix(array $matrix): void
    {
        $this->clearMatrix();

        $minAnalogNumber = 500;
        $maxAnalogNumber = 1;

        foreach ($matrix as $matrixCell) {
            [
                'hundreds' => $hundreds,
                'tens' => $tens,
                'units' => $units,
                'apartment' => $apartment,
            ] = $matrixCell;

            $analogNumber = $hundreds * 100 + $tens * 10 + $units;

            if ($analogNumber % 100 === 0) {
                $analogNumber += 100;
            }

            $minAnalogNumber = min($analogNumber, $minAnalogNumber);
            $maxAnalogNumber = max($analogNumber, $maxAnalogNumber);

            $dialplan = $this->dialplans[$apartment] ?? [
                'id' => "$apartment",
                'sip_number' => '',
                'call_type' => RubetekConst::ANALOG,
                'door_access' => [RubetekConst::RELAY_1_INTERNAL],
                'access_codes' => [],
            ];

            $this->updateDialplan(
                id: $dialplan['id'],
                sipNumber: $dialplan['sip_number'],
                analogNumber: $analogNumber,
                callType: $dialplan['call_type'],
                doorAccess: $dialplan['door_access'],
                accessCodes: $dialplan['access_codes'],
            );
        }

        $analogSettings = $this->apiCall('/settings/analog');
        // FIXME: currently doesn't work correctly if first_location_id isn't 1
        $analogSettings['first_location_id'] = 1; // $minAnalogNumber;
        $analogSettings['last_location_id'] = $maxAnalogNumber;
        $this->apiCall('/configuration', 'PATCH', ['analog' => $analogSettings]);
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
        $params = [
            'Acc1Login' => $login,
            'Acc1Password' => $password,
            'Acc1SipServer' => $server,
            'Acc1SipServerPort' => $port,
            'Acc1SipTransport' => 'tcp',
            'Acc1RegInterval' => 1200,
            'RegTimeout' => 5,
            'RegCycleInterval' => 60,
            'RegAttemptsInCycle' => 5,
            'Acc1NATType' => $stunEnabled ? 'stun' : '',
            'Acc1StunServer' => $stunEnabled ? $stunServer : '',
            'Acc1StunPort' => $stunPort,
        ];

        $endpoint = '/sip?' . http_build_query($params);
        $this->apiCall($endpoint, 'PATCH');

        $this->apiCall('/settings/incoming_call', 'PATCH', [
            'enable_proxy_to_analog' => true,
            'own_number' => '',
        ]);
    }

    public function configureUserAccount(string $password): void
    {
        $this->apiCall('/settings/account', 'POST', [
            'account' => 'user',
            'password' => $password,
            'role' => 'operator',
        ]);

        // Without sleep() the following calls can respond "access is forbidden" or "account not found"
        sleep(5);
    }

    public function deleteApartment(int $apartment = 0): void
    {
        $this->loadDialplans();

        if ($apartment === 0) {
            foreach ($this->dialplans as $apartment => $dialplan) {
                $this->deleteApartment($apartment);
            }
        } else {
            $dialplan = $this->dialplans[$apartment] ?? null;

            if ($dialplan) {
                $analogNumber = $dialplan['analog_number'];

                if ($analogNumber === '') {
                    // Delete apartment-only dialplan
                    $this->deleteDialplan($apartment);
                } else {
                    // Otherwise, delete apartment params
                    $this->updateDialplan(
                        id: $dialplan['id'],
                        sipNumber: '',
                        analogNumber: $analogNumber,
                        callType: RubetekConst::ANALOG,
                        doorAccess: [RubetekConst::RELAY_1_INTERNAL],
                        accessCodes: [],
                    );
                }
            }
        }
    }

    public function deleteRfid(string $code = ''): void
    {
        if ($code) {
            $this->apiCall("/rfids/$code", 'DELETE');
        } else {
            foreach ($this->getRfids() as $rfid) {
                $this->deleteRfid($rfid);
            }
        }
    }

    public function getAudioLevels(): array
    {
        $audioSettings = $this->getConfiguration()['audio'];

        return [
            $audioSettings['system']['volume'] ?? 15,
            $audioSettings['system']['mic_sensitivity'] ?? 10,
            $audioSettings['sip']['sip_volume'] ?? 13,
            $audioSettings['sip']['sip_mic_sensitivity'] ?? 15,
            $audioSettings['sip']['sip_incoming_volume'] ?? 13,
            $audioSettings['analog']['analog_volume'] ?? 14,
            $audioSettings['analog']['analog_mic_sensitivity'] ?? 12,
            $audioSettings['analog']['proxy_handset_speaker_volume'] ?? 15,
            $audioSettings['webrtc']['webrtc_mic_sensitivity'] ?? 15,
            $audioSettings['notify_speaker_volume'] ?? 10,
            $audioSettings['rtsp']['mic_sensitivity'] ?? 15,
        ];
    }

    public function getCmsLevels(): array
    {
        $analogSettings = $this->apiCall('/settings/analog');

        return [
            $analogSettings['analog_line_voltage_idle'] ?? 3.6,
            $analogSettings['analog_line_voltage_lifted'] ?? 7.8,
            $analogSettings['analog_line_voltage_button_pressed'] ?? 9.6,
            $analogSettings['digi_line_voltage_lifted'] ?? 6.6,
        ];
    }

    public function getDisplayText(): array
    {
        $displaySettings = $this->getConfiguration()['display'];

        // Three line text
        if (isset($displaySettings['text1'])) {
            return array_filter([
                $displaySettings['text1'],
                $displaySettings['text2'],
                $displaySettings['text3'],
            ]);
        }

        $text = trim($displaySettings['text'] ?? '');
        return $text === '' ? [] : [$text];
    }

    public function getDisplayTextLinesCount(): int
    {
        return 3;
    }

    public function getHousePrefixSupportedFields(): array
    {
        return [HousePrefixField::Address, HousePrefixField::FirstFlat, HousePrefixField::LastFlat];
    }

    public function getHousePrefixes(): array
    {
        $apartRanges = $this->apiCall('/apart_ranges') ?? [];
        $prefixes = [];

        foreach ($apartRanges as $apartRange) {
            $prefixes[] = new HousePrefix(
                number: $apartRange['house'],
                address: $apartRange['address'],
                firstFlat: new FlatNumber($apartRange['start_number']),
                lastFlat: new FlatNumber($apartRange['end_number']),
            );
        }

        return $prefixes;
    }

    public function getLineDiagnostics(int $apartment): float
    {
        $this->loadDialplans();
        $analogNumber = $this->dialplans[$apartment]['analog_number'] ?? null;

        if ($analogNumber === null) {
            return 0;
        }

        $lineVoltage = $this->apiCall("/settings/analog/line_voltage/start/$analogNumber") ?? [];
        $voltageRaw = $lineVoltage['voltage'] ?? '';
        return (float)filter_var($voltageRaw, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }

    public function getRfids(): array
    {
        return array_column($this->apiCall('/rfids'), 'rfid', 'rfid');
    }

    public function isFreePassEnabled(): bool
    {
        return $this->apiCall('/operating_mode')['free_passage_mode'] ?? true;
    }

    public function openLock(int $lockNumber = 0): void
    {
        $lockNumber += 1;
        $this->apiCall("/doors/$lockNumber/open", 'POST', [], 3);
    }

    public function prepare(): void
    {
        parent::prepare();
        $this->configureBasicSettings();
        $this->setAdminPin(false);
        $this->configureInternalReader();
        $this->configureExternalReader();
    }

    public function setAudioLevels(array $levels = []): void
    {
        $audioSettings = $this->getConfiguration()['audio'];

        $audioSettings['system']['volume'] = $levels[0] ?? 15;
        $audioSettings['system']['mic_sensitivity'] = $levels[1] ?? 10;
        $audioSettings['sip']['sip_volume'] = $levels[2] ?? 13;
        $audioSettings['sip']['sip_mic_sensitivity'] = $levels[3] ?? 15;
        $audioSettings['sip']['sip_incoming_volume'] = $levels[4] ?? 13;
        $audioSettings['analog']['analog_volume'] = $levels[5] ?? 14;
        $audioSettings['analog']['analog_mic_sensitivity'] = $levels[6] ?? 12;
        $audioSettings['analog']['proxy_handset_speaker_volume'] = $levels[7] ?? 15;
        $audioSettings['webrtc']['webrtc_mic_sensitivity'] = $levels[8] ?? 15;
        $audioSettings['notify_speaker_volume'] = $levels[9] ?? 10;
        $audioSettings['rtsp']['mic_sensitivity'] = $levels[10] ?? 15;

        $this->apiCall('/configuration', 'PATCH', ['audio' => $audioSettings]);
    }

    public function setCallTimeout(int $timeout): void
    {
        $callSettings = $this->getConfiguration()['call'];
        $callSettings['dial_out_time'] = $timeout;
        $this->apiCall('/settings/call', 'PATCH', $callSettings);
    }

    public function setCmsLevels(array $levels): void
    {
        $analogSettings = $this->apiCall('/settings/analog');

        $analogSettings['analog_line_voltage_idle'] = $levels[0] ?? 3.6;
        $analogSettings['analog_line_voltage_lifted'] = $levels[1] ?? 7.8;
        $analogSettings['analog_line_voltage_button_pressed'] = $levels[2] ?? 9.6;
        $analogSettings['digi_line_voltage_lifted'] = $levels[3] ?? 6.6;

        $this->apiCall('/configuration', 'PATCH', ['analog' => $analogSettings]);
    }

    public function setCmsModel(string $model = ''): void
    {
        if ($model === 'DIGITAL') {
            $mode = 'digital';
            $type = 'custom';
        } else {
            $mode = 'analog';
            $type = RubetekConst::CMS_MODEL_MAP[$model] ?? 'custom';
        }

        $analogSettings = $this->apiCall('/settings/analog');
        $analogSettings['mode'] = $mode;
        $analogSettings['kkmtype'] = $type;

        if ($this->isLegacyVersion()) {
            $this->apiCall('/configuration', 'PATCH', ['analog' => $analogSettings]);
        } else {
            // The analog settings payload requires analog and digital matrices.
            $analogSettings['kkm_addressing'] = $this->getAnalogAddressingTemplate();
            $analogSettings['digital_addressing'] = $this->getDigitalAddressingTemplate();
            $this->apiCall('/settings/analog', 'PATCH', $analogSettings);
        }
    }

    public function setConciergeNumber(int $sipNumber): void
    {
        $this->apiCall('/settings/concierge', 'PATCH', [
            'enabled' => true,
            'dial_number' => "$sipNumber",
            'analog_dial_number' => '',
            'call_type' => RubetekConst::SIP,
            'door_access' => [RubetekConst::RELAY_1_INTERNAL],
        ]);
    }

    public function setDisplayText(array $textLines): void
    {
        $displaySettings = $this->getConfiguration()['display'];
        $textLinesCount = count($textLines);

        if ($textLinesCount === 0) {
            $displaySettings['welcome_display'] = 4; // Current date and time
            $displaySettings['text'] = '';
        } elseif ($textLinesCount === 1) {
            $text = $textLines[0] ?? '';
            $displaySettings['welcome_display'] = 1; // Long text in one line
            $displaySettings['text'] = $text . ' '; // Space is needed, otherwise the text will stick together
            $displaySettings['changeLineTimeout'] = 5; // Seconds
            $displaySettings['changeSymbolTimeout'] = 5; // Milliseconds
        } else {
            $displaySettings['welcome_display'] = 6; // Three line text
            $displaySettings['text1'] = $textLines[0];
            $displaySettings['text2'] = $textLines[1];
            $displaySettings['text3'] = $textLines[2] ?? '';
        }

        $this->apiCall('/settings/display', 'PATCH', $displaySettings);
    }

    public function setDtmfCodes(
        string $code1 = '1',
        string $code2 = '2',
        string $code3 = '3',
        string $codeCms = '1',
    ): void
    {
        $this->apiCall('/settings/dtmf', 'PATCH', [
            'code_length' => 1,
            'code1' => $code1,
            'code2' => $code2,
            'code3' => $code3,
            'out_mode' => 'SIP-INFO',
            'internal_dtmf_enabled' => false,
            'out_code' => $codeCms,
        ]);
    }

    public function setFreePassEnabled(bool $enabled): void
    {
        if ($enabled) {
            $this->apiCall('/free_passage/start', 'POST', [
                'door_access' => [1, 2],
                'mon' => true,
                'tue' => true,
                'wed' => true,
                'thu' => true,
                'fri' => true,
                'sat' => true,
                'sun' => true,
                'selectDate' => false,
                'selectTime' => false,
                'startDate' => '04.10.2024',
                'endDate' => '04.10.2025',
                'startTime' => '00:00',
                'endTime' => '23:59',
                'showMessageText' => true, // Display message about free passage
            ]);
        } else {
            $this->apiCall('/free_passage/stop', 'POST');
        }

        sleep(3); // Wait for the relay to switch
    }

    public function setHousePrefixes(array $prefixes): void
    {
        $this->apiCall('/apart_ranges', 'DELETE');

        foreach ($prefixes as $prefix) {
            $this->apiCall('/apart_ranges', 'POST', [
                'house' => (string)$prefix->number,
                'address' => $prefix->address,
                'start_number' => $prefix->firstFlat->number,
                'end_number' => $prefix->lastFlat->number,
                'call_number' => 'XXXXYYYY',
                'call_type' => RubetekConst::SIP,
                'door_access' => [RubetekConst::RELAY_1_INTERNAL],
            ]);
        }
    }

    public function setLanguage(string $language): void
    {
        // TODO: Implement setLanguage() method.
    }

    public function setPublicCode(int $code = 0): void
    {
        // Empty implementation
    }

    public function setSosNumber(int $sipNumber): void
    {
        $this->apiCall('/settings/sos', 'PATCH', [
            'enabled' => true,
            'dial_number' => "$sipNumber",
            'analog_dial_number' => '',
            'call_type' => RubetekConst::SIP,
            'door_access' => [RubetekConst::RELAY_1_INTERNAL],
            'backlight_period' => 3,
        ]);
    }

    public function setTalkTimeout(int $timeout): void
    {
        $callSettings = $this->getConfiguration()['call'];
        $callSettings['max_call_time'] = $timeout;
        $this->apiCall('/settings/call', 'PATCH', $callSettings);
    }

    public function setUnlockTime(int $time = 3): void
    {
        // Closes the relay if the door is currently open by API, RFID, personal access code, etc.
        $doors = $this->getDoors();

        foreach ($doors as $door) {
            $id = $door['id'];
            $inverted = $this->apiCall("/doors/$id/param")['inverted'];

            $this->apiCall("/doors/$id/param", 'PATCH', [
                'id' => $id,
                'open_timeout' => $time,
                'inverted' => $inverted,
            ]);
        }
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $dbConfig = $this->commonTransformDbConfig($dbConfig);

        $stunEnabled = $dbConfig['sip']['stunEnabled'];
        if (!$stunEnabled) {
            $dbConfig['sip']['stunServer'] = '';
            $dbConfig['sip']['stunPort'] = 3478;
        }

        return $dbConfig;
    }

    public function updateDbConfig(array $dbConfig): array
    {
        /*
         * If the intercom is configured in the gate mode with prefixes,
         * then it is necessary to reset the SIP numbers for apartments.
         * Otherwise, entering the prefix will result in a call to the apartment
         * if the prefixes and apartment numbers intersect (for example, 1, 2, 3).
         * TODO: need to check if we can use an empty array of SIP numbers in gate mode for all devices
         */
        if (!empty($dbConfig['gateLinks'])) {
            foreach ($dbConfig['apartments'] as &$apartment) {
                $apartment['sipNumbers'] = [];
            }
        }

        return $dbConfig;
    }

    /**
     * Clears the matrix by removing analog numbers from dialplans or deleting dialplans without SIP numbers.
     *
     * @return void
     */
    protected function clearMatrix(): void
    {
        $this->loadDialplans();

        foreach ($this->dialplans as $dialplan) {
            if ($dialplan['call_type'] === RubetekConst::ANALOG) {
                // Delete matrix-only dialplan
                $this->deleteDialplan($dialplan['id']);
            } else {
                // Otherwise, remove the analog number from the full dialplan
                $this->updateDialplan(
                    id: $dialplan['id'],
                    sipNumber: $dialplan['sip_number'],
                    analogNumber: '',
                    callType: $dialplan['call_type'],
                    doorAccess: $dialplan['door_access'],
                    accessCodes: $dialplan['access_codes'],
                );
            }
        }
    }

    /**
     * Configure basic settings.
     *
     * @return void
     */
    protected function configureBasicSettings(): void
    {
        $this->apiCall('/configuration', 'PATCH', [
            'log_buffer_size' => 1024,
            'accelerometer_sensitivity' => 20,
            'onvif_enabled' => false,
            'call_end_type' => 0, // at the end of the call
            'keep_analog_calling' => false,
            'ignore_early_media' => true,
            'display_typed_number_time' => 15, // seconds
            'backlight_keypad_time' => 15, // seconds
            'light_sensor_mode_enabled' => false,
            'backlight_day_brightness' => 6, // 50% brightness
            'backlight_night_brightness' => 3, // 20% brightness
            'backlight_increase_brightness' => 3,
        ]);
    }

    /**
     * Configure external reader mode.
     *
     * @return void
     */
    protected function configureExternalReader(): void
    {
        $this->apiCall('/settings/wiegand', 'PATCH', [
            'type' => 34,
            'mute_notifications' => true,
            'reverse_data_order' => false,
        ]);
    }

    /**
     * Configure internal reader mode.
     *
     * @return void
     */
    protected function configureInternalReader(): void
    {
        $this->apiCall('/settings/nfc_reader', 'PATCH', [
            'period_reading_ms' => 2000,
            'disable_sl3' => true,
            'use_card_uid_length' => true,
            'code_length' => 4,
            'reverse_data_order' => true,
            'find_direct_and_reverse_orders' => false,
        ]);
    }

    /**
     * Delete a dialplan based on the provided ID (apartment number).
     *
     * @param int $id (Optional) The ID of the dialplan to be deleted.
     * If 0, then all dialplans will be deleted. Default is 0.
     *
     * @return void
     */
    protected function deleteDialplan(int $id = 0): void
    {
        $this->loadDialplans();

        if ($id === 0) {
            $this->apiCall('/apartments', 'DELETE', ['apartIds' => []]);
            $this->dialplans = [];
        } elseif (isset($this->dialplans[$id])) {
            $this->apiCall("/apartments/$id", 'DELETE');
            unset($this->dialplans[$id]);
        }
    }

    /**
     * Generates a default analog addressing template.
     *
     * @return array Analog addressing template.
     */
    protected function getAnalogAddressingTemplate(): array
    {
        $analogAddressing = [];

        for ($kkm = 1; $kkm <= 8; $kkm++) {
            $analogAddressing["kkm_$kkm"] = [];

            for ($e = 0; $e <= 9; $e++) {
                $analogAddressing["kkm_$kkm"]["e$e"] = [];

                for ($i = 0; $i < 10; $i++) {
                    $value = ($kkm - 1) * 100 + $i * 10 + $e;

                    if ($value % 100 === 0) {
                        $value += 100;
                    }

                    $analogAddressing["kkm_$kkm"]["e$e"][] = $value;
                }
            }
        }

        return $analogAddressing;
    }

    protected function getApartments(): array
    {
        $apartments = [];
        $this->loadDialplans();

        foreach ($this->dialplans as $dialplan) {
            [
                'id' => $apartmentNumber,
                'sip_number' => $sipNumber,
                'call_type' => $callType,
                'access_codes' => $codes,
            ] = $dialplan;

            // Skip matrix-only dialplan, this is not an apartment
            if ($callType === RubetekConst::ANALOG) {
                continue;
            }

            $apartments[$apartmentNumber] = [
                'apartment' => $apartmentNumber,
                // The $codes variable contains a list of codes for old fw
                // and a list of codes and their validity time for fw >= 2024.10
                'code' => $codes[0]['code'] ?? $codes[0] ?? 0,
                'sipNumbers' => [$sipNumber],
                'cmsEnabled' => $callType === RubetekConst::SIP_ANALOG,
                'cmsLevels' => [],
            ];
        }

        return $apartments;
    }

    protected function getCmsModel(): string
    {
        $cmsModelRaw = $this->apiCall('/settings/analog');

        if ($cmsModelRaw['mode'] === 'digital') {
            return 'DIGITAL';
        }

        return array_search($cmsModelRaw['kkmtype'], RubetekConst::CMS_MODEL_MAP) ?? '';
    }

    /**
     * Generates a default digital addressing template.
     *
     * @return array Digital addressing template.
     */
    protected function getDigitalAddressingTemplate(): array
    {
        $digitAddressing = [];

        for ($i = 1; $i <= 15; $i++) {
            $digitAddressing["$i"] = [];

            for ($j = 0; $j < 17; $j++) {
                $digitAddressing["$i"][] = $i + ($j * 15);
            }
        }

        return $digitAddressing;
    }

    /**
     * Get doors information.
     *
     * @return array Door IDs and locks status.
     */
    protected function getDoors(): array
    {
        return array_slice($this->apiCall('/doors'), 0, -1);
    }

    protected function getDtmfConfig(): array
    {
        [
            'code1' => $code1,
            'code2' => $code2,
            'code3' => $code3,
            'out_code' => $codeCms,
        ] = $this->getConfiguration()['dtmf'];

        return [
            'code1' => $code1,
            'code2' => $code2,
            'code3' => $code3,
            'codeCms' => $codeCms,
        ];
    }

    protected function getMatrix(): array
    {
        $matrix = [];
        $this->loadDialplans();

        foreach ($this->dialplans as $dialplan) {
            $analogNumber = $dialplan['analog_number'];

            if ($analogNumber === '') {
                continue;
            }

            if ($analogNumber % 100 === 0) {
                $analogNumber -= 100;
            }

            [$hundreds, $tens, $units] = str_split(str_pad($analogNumber, 3, '0', STR_PAD_LEFT));

            $matrix[$hundreds . $tens . $units] = [
                'hundreds' => $hundreds,
                'tens' => $tens,
                'units' => $units,
                'apartment' => $dialplan['id'],
            ];
        }

        return $matrix;
    }

    protected function getSipConfig(): array
    {
        [
            'Acc1Login' => $login,
            'Acc1Password' => $password,
            'Acc1SipServer' => $server,
            'Acc1SipServerPort' => $port,
            'Acc1NATType' => $stunEnabled,
            'Acc1StunServer' => $stunServer,
            'Acc1StunPort' => $stunPort,
        ] = $this->parseParamValue($this->apiCall('/sip'));

        return [
            'server' => $server,
            'port' => $port,
            'login' => $login,
            'password' => $password,
            'stunEnabled' => $stunEnabled,
            'stunServer' => $stunServer,
            'stunPort' => $stunPort,
        ];
    }

    /**
     * Load and cache dialplans from the API if they haven't been loaded already.
     *
     * @return void
     */
    protected function loadDialplans(): void
    {
        if ($this->dialplans !== null) {
            return;
        }

        $rawDialplans = $this->apiCall('/apartments');

        // Filter out service apartments
        $filteredDialplans = array_filter(
            $rawDialplans,
            static fn($item) => !in_array($item['id'], [RubetekConst::CONCIERGE_ID, RubetekConst::SOS_ID]),
        );

        $this->dialplans = array_column($filteredDialplans, null, 'id');
    }

    /**
     * Parse response string to array.
     *
     * @param string $res Response string.
     *
     * @return array Associative array with parsed parameters.
     */
    protected function parseParamValue(string $res): array
    {
        $ret = [];
        $res = explode("\n", trim($res));

        foreach ($res as $r) {
            $r = explode('=', trim($r));
            $ret[$r[0]] = @$r[1];
        }

        return $ret;
    }

    /**
     * Set random administrator pin code.
     *
     * @param bool $enabled (Optional) Is the admin pin enabled. Default is true.
     *
     * @return void
     */
    protected function setAdminPin(bool $enabled = true): void
    {
        if ($enabled) {
            $pin = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
        } else {
            $pin = '';
        }

        $displaySettings = $this->getConfiguration()['display'];
        $displaySettings['admin_password'] = $pin;
        $this->apiCall('/configuration', 'PATCH', ['display' => $displaySettings]);
    }

    /**
     * Update or add a dialplan with the provided parameters.
     *
     * @param string $id Apartment number.
     * @param string $sipNumber SIP number for an apartment.
     * @param string $analogNumber Analog number for an apartment.
     * @param string $callType Indicates where to call.
     * @param int[] $doorAccess List of numeric codes that control access to the relay.
     * @param string[] $accessCodes List of apartment access codes.
     *
     * @return void
     *
     * @see RubetekConst
     */
    protected function updateDialplan(
        string $id,
        string $sipNumber,
        string $analogNumber,
        string $callType,
        array  $doorAccess,
        array  $accessCodes,
    ): void
    {
        $this->loadDialplans();

        $data = [
            'id' => $id,
            'sip_number' => $sipNumber,
            'analog_number' => $analogNumber,
            'call_type' => $callType,
            'door_access' => $doorAccess,
            'access_codes' => $accessCodes,
        ];

        $this->apiCall('/apartments', 'POST', $data);
        $this->dialplans[$id] = $data;
    }
}
