<?php

namespace hw\ip\domophone\rubetek;

use hw\ip\domophone\domophone;

/**
 * Abstract class representing a Rubetek domophone.
 */
abstract class rubetek extends domophone
{

    use \hw\ip\common\rubetek\rubetek {
        transformDbConfig as protected commonTransformDbConfig;
    }

    use legacy\rubetek {
        getUnlocked as protected getUnlockedLegacy;
        setUnlocked as protected setUnlockedLegacy;
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
                ]
            ]);
        }
    }

    public function configureApartment(
        int   $apartment,
        int   $code = 0,
        array $sipNumbers = [],
        bool  $cmsEnabled = true,
        array $cmsLevels = []
    ): void
    {
        $this->loadDialplans();

        $dialplan = $this->dialplans[$apartment] ?? ['id' => "$apartment", 'analog_number' => ''];

        $this->updateDialplan(
            id: $dialplan['id'],
            sipNumber: $sipNumbers[0],
            analogNumber: $dialplan['analog_number'],
            callType: $cmsEnabled ? RubetekConst::SIP_ANALOG : RubetekConst::SIP,
            doorAccess: [],
            accessCodes: $code !== 0 ? ["$code"] : [],
        );
    }

    public function configureEncoding(): void
    {
        // Multiple calls to work correctly
        $videoSettings = $this->apiCall('/settings/video');

        $videoSettings['channel1']['fps'] = '30fps';
        $videoSettings['channel1']['bitrate'] = '1Mbps';
        $videoSettings['channel1']['resolution'] = '1280x720';
        $this->apiCall('/settings/video', 'PATCH', $videoSettings);

        $videoSettings['channel2']['fps'] = '15fps';
        $videoSettings['channel2']['bitrate'] = '0.5Mbps';
        $videoSettings['channel2']['resolution'] = '720x480';
        $this->apiCall('/settings/video', 'PATCH', $videoSettings);

        $videoSettings['channel3']['fps'] = '15fps';
        $videoSettings['channel3']['bitrate'] = '0.5Mbps';
        $videoSettings['channel3']['resolution'] = '640x480';
        $this->apiCall('/settings/video', 'PATCH', $videoSettings);

        $videoSettings['use_for_sip'] = 'channel1';
        $videoSettings['use_for_webrtc'] = 'channel1';
        $videoSettings['snapshot_size'] = '640x360'; // Requesting a low resolution image is faster
        $this->apiCall('/settings/video', 'PATCH', $videoSettings);
    }

    public function configureGate(array $links = []): void
    {
        $this->apiCall('/apart_ranges', 'DELETE');

        foreach ($links as $link) {
            $this->apiCall('/apart_ranges', 'POST', [
                'house' => "{$link['prefix']}",
                'address' => $link['address'],
                'start_number' => $link['firstFlat'],
                'end_number' => $link['lastFlat'],
                'call_number' => 'XXXXYYYY',
                'call_type' => RubetekConst::SIP,
                'door_access' => [],
            ]);
        }
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
                'call_type' => RubetekConst::SIP_ANALOG,
                'door_access' => [],
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
        int    $stunPort = 3478
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
                    $this->deleteDialplan($apartment);
                } else {
                    $this->updateDialplan(
                        id: $dialplan['id'],
                        sipNumber: '',
                        analogNumber: $analogNumber,
                        callType: RubetekConst::SIP_ANALOG,
                        doorAccess: [],
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
        $audioSettings = $this->getConfig()['audio'];
        return [
            $audioSettings['sip']['volume'],
            $audioSettings['sip']['mic_sensitivity'],
            $audioSettings['analog']['volume'],
            $audioSettings['analog']['mic_sensitivity'],
            $audioSettings['notify_speaker_volume'],
        ];
    }

    public function getCmsLevels(): array
    {
        $analogSettings = $this->apiCall('/settings/analog');
        return [
            $analogSettings['analog_line_voltage_idle'],
            $analogSettings['analog_line_voltage_lifted'],
            $analogSettings['analog_line_voltage_button_pressed'],
            $analogSettings['digi_line_voltage_lifted'],
        ];
    }

    public function getLineDiagnostics(int $apartment): float
    {
        $handsetStatus = $this->apiCall("/analog_handset_status/$apartment") ?? [];
        $voltageRaw = $handsetStatus['voltage'] ?? '';
        return (float)filter_var($voltageRaw, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }

    public function getRfids(): array
    {
        return array_column($this->apiCall('/rfids'), 'rfid', 'rfid');
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

    public function setAudioLevels(array $levels): void
    {
        if (count($levels) === 5) {
            $audioSettings = $this->getConfig()['audio'];

            $audioSettings['sip']['volume'] = $levels[0];
            $audioSettings['sip']['mic_sensitivity'] = $levels[1];
            $audioSettings['analog']['volume'] = $levels[2];
            $audioSettings['analog']['mic_sensitivity'] = $levels[3];
            $audioSettings['notify_speaker_volume'] = $levels[4];

            $this->apiCall('/configuration', 'PATCH', ['audio' => $audioSettings]);
        }
    }

    public function setCallTimeout(int $timeout): void
    {
        $callSettings = $this->getConfig()['call'];
        $callSettings['dial_out_time'] = $timeout;
        $this->apiCall('/settings/call', 'PATCH', $callSettings);
    }

    public function setCmsLevels(array $levels): void
    {
        if (count($levels) === 4) {
            $analogSettings = $this->apiCall('/settings/analog');

            $analogSettings['analog_line_voltage_idle'] = $levels[0];
            $analogSettings['analog_line_voltage_lifted'] = $levels[1];
            $analogSettings['analog_line_voltage_button_pressed'] = $levels[2];
            $analogSettings['digi_line_voltage_lifted'] = $levels[3];

            $this->apiCall('/configuration', 'PATCH', ['analog' => $analogSettings]);
        }
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
            'door_access' => [],
        ]);
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

    public function setLanguage(string $language = 'ru'): void
    {
        // Empty implementation
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
            'door_access' => [],
            'backlight_period' => 3,
        ]);
    }

    public function setTalkTimeout(int $timeout): void
    {
        $callSettings = $this->getConfig()['call'];
        $callSettings['max_call_time'] = $timeout;
        $this->apiCall('/settings/call', 'PATCH', $callSettings);
    }

    public function setTickerText(string $text = ''): void
    {
        $displaySettings = $this->getConfig()['display'];
        $displaySettings['welcome_display'] = 1;
        $displaySettings['text'] = $text . ' '; // Space is needed, otherwise the text will stick together
        $displaySettings['changeLineTimeout'] = 5; // Seconds
        $displaySettings['changeSymbolTimeout'] = 5; // Milliseconds
        $this->apiCall('/settings/display', 'PATCH', $displaySettings);
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

    public function setUnlocked(bool $unlocked = true): void
    {
        if ($this->isLegacyVersion()) {
            $this->setUnlockedLegacy($unlocked);
            return;
        }

        if ($unlocked) {
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
            ]);
        } else {
            $this->apiCall('/free_passage/stop', 'POST');
        }

        sleep(3); // Wait for the relay to switch
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

    /**
     * Clears the matrix by removing analog numbers from dialplans or deleting dialplans without SIP numbers.
     *
     * @return void
     */
    protected function clearMatrix(): void
    {
        $this->loadDialplans();

        foreach ($this->dialplans as $dialplan) {
            if (empty($dialplan['sip_number'])) {
                // Delete dialplan without SIP number
                $this->deleteDialplan($dialplan['id']);
            } else {
                // Otherwise, remove the analog number from the dialplan
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
            'type' => 26,
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
                'sip_number' => $sipNumbers,
                'call_type' => $callType,
                'access_codes' => $codes,
            ] = $dialplan;

            if (
                $apartmentNumber === RubetekConst::CONCIERGE_ID ||
                $apartmentNumber === RubetekConst::SOS_ID ||
                !$sipNumbers
            ) {
                continue;
            }

            $apartments[$apartmentNumber] = [
                'apartment' => $apartmentNumber,
                // The $codes variable contains a list of codes for old fw
                // and a list of codes and their validity time for fw >= 2024.10
                'code' => $codes[0]['code'] ?? $codes[0] ?? 0,
                'sipNumbers' => [$sipNumbers],
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
            'out_code' => $codeCms
        ] = $this->getConfig()['dtmf'];

        return [
            'code1' => $code1,
            'code2' => $code2,
            'code3' => $code3,
            'codeCms' => $codeCms,
        ];
    }

    protected function getGateConfig(): array
    {
        $links = [];
        $apartRanges = $this->apiCall('/apart_ranges');

        foreach ($apartRanges as $link) {
            [
                'house' => $prefix,
                'address' => $address,
                'start_number' => $firstFlat,
                'end_number' => $lastFlat,
            ] = $link;

            $links[] = [
                'address' => $address,
                'prefix' => $prefix,
                'firstFlat' => $firstFlat,
                'lastFlat' => $lastFlat,
            ];
        }

        return $links;
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

    protected function getTickerText(): string
    {
        return trim($this->getConfig()['display']['text']) ?? '';
    }

    protected function getUnlocked(): bool
    {
        if ($this->isLegacyVersion()) {
            return $this->getUnlockedLegacy();
        }

        return $this->apiCall('/operating_mode')['free_passage_mode'] ?? true;
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

        $filteredDialplans = array_filter(
            $rawDialplans,
            static fn($value) => $value['id'] !== RubetekConst::CONCIERGE_ID && $value['id'] !== RubetekConst::SOS_ID,
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

        $displaySettings = $this->getConfig()['display'];
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
        array  $accessCodes
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
