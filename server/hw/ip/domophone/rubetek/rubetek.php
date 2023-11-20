<?php

namespace hw\ip\domophone\rubetek;

use hw\ip\domophone\domophone;

/**
 * Abstract class representing a Rubetek domophone.
 */
abstract class rubetek extends domophone
{

    use \hw\ip\common\rubetek\rubetek;

    protected string $defaultWebPassword = 'Rubetek34';

    public function _setUnlockTime(int $time)
    {
        $this->apiCall('/settings/door_left_open_timeout', 'PATCH', ['timeout' => $time]);
    }

    public function addRfid(string $code, int $apartment = 0)
    {
        $this->apiCall('/rfids', 'POST', [
            'rfid' => $code,
            'door_access' => [1, 5] // 1 - Relay A, internal reader; 5 - Relay B, external reader
        ]);
    }

    public function addRfids(array $rfids)
    {
        foreach ($rfids as $rfid) {
            $this->addRfid($rfid);
        }
    }

    public function configureApartment(
        int   $apartment,
        int   $code = 0,
        array $sipNumbers = [],
        bool  $cmsEnabled = true,
        array $cmsLevels = []
    )
    {
        $this->apiCall('/apartments', 'POST', [
            'id' => "$apartment",
            'sip_number' => (string)($sipNumbers[0] ?? $apartment),
            'call_type' => $cmsEnabled ? 'sip_0_analog' : 'sip',
            'door_access' => [1],
            'access_codes' => $code ? ["$code"] : [],
        ]);
    }

    public function configureEncoding()
    {
        // Multiple calls to work correctly
        $videoSettings = $this->apiCall('/settings/video');

        $videoSettings['channel1']['fps'] = '15fps';
        $videoSettings['channel1']['bitrate'] = '1Mbps';
        $videoSettings['channel1']['resolution'] = '1280x720';
        $this->apiCall('/settings/video', 'PATCH', $videoSettings);

        $videoSettings['channel2']['fps'] = '15fps';
        $videoSettings['channel2']['bitrate'] = '0.5Mbps';
        $videoSettings['channel2']['resolution'] = '720x480';
        $this->apiCall('/settings/video', 'PATCH', $videoSettings);

        $videoSettings['channel3']['fps'] = '25fps';
        $videoSettings['channel3']['bitrate'] = '0.5Mbps';
        $videoSettings['channel3']['resolution'] = '640x480';
        $this->apiCall('/settings/video', 'PATCH', $videoSettings);

        $videoSettings['use_for_sip'] = 'channel1';
        $videoSettings['use_for_webrtc'] = 'channel1';
        $videoSettings['snapshot_size'] = '1280x720';
        $this->apiCall('/settings/video', 'PATCH', $videoSettings);
    }

    public function configureGate(array $links = [])
    {
        $this->apiCall('/apart_ranges', 'DELETE');

        foreach ($links as $link) {
            $this->apiCall('/apart_ranges', 'POST', [
                'house' => "{$link['prefix']}",
                'address' => $link['address'],
                'start_number' => $link['firstFlat'],
                'end_number' => $link['lastFlat'],
                'call_number' => 'XXXXYYYY',
                'call_type' => 'sip',
                'door_access' => [1],
            ]);
        }
    }

    public function configureMatrix(array $matrix)
    {
        // Delete all analog replaces
        $apartments = $this->apiCall('/apartments');

        foreach ($apartments as $apartment) {
            ['id' => $id, 'sip_number' => $sipNumber] = $apartment;

            if (!$sipNumber) { // Delete apartment from dialplan if it doesn't have a SIP number
                $this->apiCall("/apartments/$id", 'DELETE');
            } else { // Otherwise, remove the analog number from the apartment
                $this->apiCall('/apartments', 'POST', [
                    'id' => $id,
                    'analog_number' => '',
                ]);
            }
        }

        foreach ($matrix as $matrixCell) {
            [
                'cms' => $cmsNumber,
                'dozen' => $dozen,
                'unit' => $unit,
                'apartment' => $apartment,
            ] = $matrixCell;

            $this->apiCall('/apartments', 'POST', [
                'id' => "$apartment",
                'analog_number' => (string)($cmsNumber * 100 + $dozen * 10 + $unit),
                'door_access' => [1],
            ]);
        }
    }

    public function configureSip(
        string $login,
        string $password,
        string $server,
        int    $port = 5060,
        bool   $stunEnabled = false,
        string $stunServer = '',
        int    $stunPort = 3478
    )
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

    public function configureUserAccount(string $password)
    {
        $this->apiCall('/settings/account', 'POST', [
            'account' => 'user',
            'password' => $password,
            'role' => 'operator',
        ]);
    }

    public function deleteApartment(int $apartment = 0)
    {
        if ($apartment !== 0) {
            $this->apiCall("/apartments/$apartment", 'DELETE');
        } else {
            foreach ($this->getApartmentsIDs() as $apartment) { // TODO: too slow
                $this->apiCall("/apartments/$apartment", 'DELETE');
            }
        }
    }

    public function deleteRfid(string $code = '')
    {
        if ($code) {
            $this->apiCall("/rfids/$code", 'DELETE');
        } else {
            // Until better times...
            // $rfids_chunks = array_chunk($this->get_rfids(), 900);
            // foreach ($rfids_chunks as $rfids_chunk) {
            // $this->api_call('/rfids_apartment', 'DELETE', [ 'rfids' => $rfids_chunk ]);
            // }

            foreach ($this->getRfids() as $rfid) { // TODO: too slow
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

    public function getLineDiagnostics(int $apartment)
    {
        // TODO: wait for new firmware
    }

    public function getRfids(): array
    {
        return array_column($this->apiCall('/rfids'), 'rfid', 'rfid');
    }

    public function openLock(int $lockNumber = 0)
    {
        $doors = $this->getDoors();
        $open = $doors[$lockNumber]['open'] ?? false;

        if (!$open) {
            $lockNumber += 1;
            $this->apiCall("/doors/$lockNumber/open", 'POST');
        }
    }

    public function prepare()
    {
        parent::prepare();
        $this->setAdminPin(false);
        $this->configureInternalReader();
        $this->configureExternalReader();
    }

    public function setAudioLevels(array $levels)
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

    public function setCallTimeout(int $timeout)
    {
        $callSettings = $this->getConfig()['call'];
        $callSettings['dial_out_time'] = $timeout;
        $this->apiCall('/settings/call', 'PATCH', $callSettings);
    }

    public function setCmsLevels(array $levels)
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

    public function setCmsModel(string $model = '')
    {
        switch ($model) {
            case 'FE-12D':
                $mode = 'digital';
                break;
            default:
                $mode = 'analog';
                // TODO: API for configuring the CMS model
                break;
        }

        $analogSettings = $this->apiCall('/settings/analog');
        $analogSettings['mode'] = $mode;
        $this->apiCall('/configuration', 'PATCH', ['analog' => $analogSettings]);
    }

    public function setConciergeNumber(int $sipNumber)
    {
        $this->apiCall('/settings/concierge', 'PATCH', [
            'enabled' => true,
            'dial_number' => "$sipNumber",
            'analog_dial_number' => '',
            'call_type' => 'sip',
            'door_access' => [1],
        ]);
    }

    public function setDtmfCodes(string $code1 = '1', string $code2 = '2', string $code3 = '3', string $codeCms = '1')
    {
        $this->apiCall('/settings/dtmf', 'PATCH', [
            'code_length' => 1,
            'code1' => $code1,
            'code2' => $code2,
            'code3' => $code3,
            'out_code' => $codeCms,
            'out_mode' => 'SIP-INFO',
        ]);
    }

    public function setLanguage(string $language = 'ru')
    {
        // Empty implementation
    }

    public function setPublicCode(int $code = 0)
    {
        // Empty implementation
    }

    public function setSosNumber(int $sipNumber)
    {
        $this->apiCall('/settings/sos', 'PATCH', [
            'enabled' => true,
            'dial_number' => "$sipNumber",
            'analog_dial_number' => '',
            'call_type' => 'sip',
            'backlight_period' => 3,
        ]);
    }

    public function setTalkTimeout(int $timeout)
    {
        $callSettings = $this->getConfig()['call'];
        $callSettings['max_call_time'] = $timeout;
        $this->apiCall('/settings/call', 'PATCH', $callSettings);
    }

    public function setTickerText(string $text = '')
    {
        $displaySettings = $this->getConfig()['display'];
        $displaySettings['welcome_display'] = 1;
        $displaySettings['text'] = $text;
        $this->apiCall('/configuration', 'PATCH', ['display' => $displaySettings]);
    }

    public function setUnlockTime(int $time = 3)
    {
        // TODO: causes a side effect: always closes the relay
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

    public function setUnlocked(bool $unlocked = true)
    {
        // TODO: if unlocked, the locks will close after reboot
        $doors = $this->getDoors();

        foreach ($doors as $door) {
            $id = $door['id'];
            $this->apiCall("/doors/$id", 'PATCH', [
                'id' => $id,
                'open' => $unlocked,
            ]);
        }
    }

    public function syncData()
    {
        // Empty implementation
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $stunEnabled = $dbConfig['sip']['stunEnabled'];
        if (!$stunEnabled) {
            $dbConfig['sip']['stunServer'] = '';
            $dbConfig['sip']['stunPort'] = 3478;
        }

        return $dbConfig;
    }

    /**
     * Configure external reader mode.
     *
     * @return void
     */
    protected function configureExternalReader()
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
    protected function configureInternalReader()
    {
        $this->apiCall('/settings/nfc_reader', 'PATCH', [
            'period_reading_ms' => 2000,
            'disable_sl3' => true,
            'code_length' => 4,
            'reverse_data_order' => true,
        ]);
    }

    protected function getApartments(): array
    {
        $apartments = [];
        $rawApartments = $this->apiCall('/apartments');

        foreach ($rawApartments as $rawApartment) {
            [
                'id' => $apartmentNumber,
                'sip_number' => $sipNumbers,
                'call_type' => $callType,
                'access_codes' => $codes,
            ] = $rawApartment;

            if ($apartmentNumber === 'CONCIERGE' || $apartmentNumber === 'SOS' || !$sipNumbers) {
                continue;
            }

            $apartments[$apartmentNumber] = [
                'apartment' => $apartmentNumber,
                'code' => $codes[0] ?? 0,
                'sipNumbers' => [$sipNumbers],
                'cmsEnabled' => $callType === 'sip_0_analog',
                'cmsLevels' => [],
            ];
        }

        return $apartments;
    }

    /**
     * Get all apartment IDs (apartment numbers).
     *
     * @return array An array containing apartment IDs.
     */
    protected function getApartmentsIDs(): array
    {
        return array_column($this->apiCall('/apartments'), 'id');
    }

    protected function getCmsModel(): string
    {
        // TODO: Implement getCmsModel() method.
        return '';
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
        $analogReplaces = array_filter(array_column($this->apiCall('/apartments'), 'analog_number', 'id'), 'strlen');

        foreach ($analogReplaces as $apartmentNumber => $analogReplace) {
            [$cms, $dozen, $unit] = str_split(str_pad($analogReplace, 3, '0', STR_PAD_LEFT));

            $matrix[$cms . $dozen . $unit] = [
                'hundreds' => $cms,
                'tens' => $dozen,
                'units' => $unit,
                'apartment' => $apartmentNumber,
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
        return $this->getConfig()['display']['text'];
    }

    protected function getUnlocked(): bool
    {
        return $this->getDoors()[0]['open'];
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
    protected function setAdminPin(bool $enabled = true)
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
}
