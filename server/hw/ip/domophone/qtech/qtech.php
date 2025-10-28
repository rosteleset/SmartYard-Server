<?php

namespace hw\ip\domophone\qtech;

use hw\Enum\HousePrefixField;
use hw\Interface\{
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
 * Abstract class representing a Qtech domophone.
 */
abstract class qtech extends domophone implements
    DisplayTextInterface,
    FreePassInterface,
    HousePrefixInterface,
    LanguageInterface
{
    use \hw\ip\common\qtech\qtech;

    /**
     * @var string The value indicating that the analog number is empty.
     */
    protected const EMPTY_ANALOG_REPLACE = '0';

    /**
     * @var array|null An array that holds dialplan information,
     * which may be null if not loaded.
     */
    protected ?array $dialplans = null;

    /**
     * @var array|null An array that holds personal access codes information,
     * which may be null if not loaded.
     */
    protected ?array $personalCodes = null;

    public function addRfid(string $code, int $apartment = 0): void
    {
        $this->apiCall('rfkey', 'add', [
            'name' => '',
            'code' => $code,
            'mon' => 1,
            'tue' => 1,
            'wed' => 1,
            'thur' => 1,
            'fri' => 1,
            'sat' => 1,
            'sun' => 1,
            'door_num' => 1,
            'door_wiegand_num' => 2,
            'device_name' => '',
        ]);
    }

    public function addRfids(array $rfids): void
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
        array $cmsLevels = [],
    ): void
    {
        $this->loadDialplans();
        $this->loadPersonalCodes();

        $dialplan = $this->dialplans[$apartment] ?? ['id' => null, 'replace1' => self::EMPTY_ANALOG_REPLACE];
        $personalCode = $this->personalCodes[$apartment] ?? ['id' => null];

        $this->updateDialplan(
            $dialplan['id'],
            $apartment,
            $dialplan['replace1'],
            $sipNumbers[0],
            $cmsEnabled ? 0 : 2,
        );

        if ($code === 0) {
            $this->deletePersonalCode($apartment);
        } else {
            $this->updatePersonalCode($personalCode['id'], $apartment, $code);
        }
    }

    public function configureEncoding(): void
    {
        // Works incorrectly when passing parameters in one call
        $this->setParams([
            'Config.DoorSetting.RTSP.Enable' => 1,
            'Config.DoorSetting.RTSP.Authroization' => 1,
            'Config.DoorSetting.RTSP.Audio' => 1,
            'Config.DoorSetting.RTSP.Video' => 1,
            'Config.DoorSetting.RTSP.Video2' => 1,
            'Config.DoorSetting.RTSP.Port' => 554,
            'Config.DoorSetting.RTSP.Codec' => 0, // H.264
        ]);

        $this->setParams([
            'Config.DoorSetting.RTSP.H264Resolution' => 5, // 720P
            'Config.DoorSetting.RTSP.H264FrameRate' => 15, // 15fps
            'Config.DoorSetting.RTSP.H264RateControl' => 1, // VBR
            'Config.DoorSetting.RTSP.H264BitRate' => 1024, // Bitrate
            'Config.DoorSetting.RTSP.H264VideoProfile' => 0, // Baseline profile
        ]);

        $this->setParams([
            'Config.DoorSetting.RTSP.H264Resolution2' => 3, // 480P
            'Config.DoorSetting.RTSP.H264FrameRate2' => 30, // 30fps
            'Config.DoorSetting.RTSP.H264RateControl2' => 1, // VBR
            'Config.DoorSetting.RTSP.H264BitRate2' => 512, // Bitrate
            'Config.DoorSetting.RTSP.H264VideoProfile2' => 0, // Baseline profile
        ]);
    }

    public function configureMatrix(array $matrix): void
    {
        $this->loadDialplans();
        $this->cleanMatrix();
        $nowCms = $this->getCmsModel();

        foreach ($matrix as $matrixCell) {
            ['hundreds' => $hundreds, 'tens' => $tens, 'units' => $units, 'apartment' => $apartment] = $matrixCell;

            if ($units === 10) {
                $units = 0;
                $tens += 1;

                if ($tens === 10) {
                    $tens = 0;
                    $hundreds += 1;
                }
            }

            $analogNumber = $hundreds * 100 + $tens * 10 + $units;

            if ($nowCms === 'ELTIS' && $analogNumber % 100 === 0) {
                $analogNumber += 100;
            }

            $analogReplace = str_pad($analogNumber, 2, '0', STR_PAD_LEFT);

            $dialplan = $this->dialplans[$apartment] ?? ['id' => null, 'replace2' => '', 'tags' => 0];

            $this->updateDialplan(
                $dialplan['id'],
                $apartment,
                $analogReplace,
                $dialplan['replace2'],
                $dialplan['tags'],
            );
        }
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
        $sipAccount = [
            'AccountID' => '0',
            'AccountActive' => '1',
            'DisplayLabel' => $login,
            'DisplayName' => $login,
            'RegisterName' => $login,
            'UserName' => $login,
            'Password' => $password,
        ];

        $sipServer = [
            'ServerIP' => $server,
            'Port' => (string)$port,
            'RegistrationPeriod' => '1800',
        ];

        $sipData = [
            'SipAccount' => $sipAccount,
            'SipServer1' => $sipServer,
        ];

        $this->apiCall('sip', 'set', $sipData);

        $this->setParams([
            'Config.Settings.GENERAL.DirectIP' => 0,
            'Config.Account1.SIP.ListenPortMin' => $port,
            'Config.Account1.SIP.ListenPortMax' => $port,
            'Config.Account1.STUN.Enable' => (int)$stunEnabled,
            'Config.Account1.STUN.Server' => $stunServer,
            'Config.Account1.STUN.Port' => $stunPort,
        ]);

        // Separate call, otherwise this param will not apply
        $this->setParams(['Config.Account1.AUTO_ANSWER.Enable' => 0]);
    }

    public function configureUserAccount(string $password): void
    {
        $this->setParams(['Config.Settings.WEB_LOGIN.Password02' => $password]);
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
                $analogReplace = $dialplan['replace1'];

                if ($analogReplace === self::EMPTY_ANALOG_REPLACE) {
                    $this->deleteDialplan($apartment);
                } else {
                    $this->updateDialplan($dialplan['id'], $apartment, $analogReplace, '', 0);
                }
            }

            $this->deletePersonalCode($apartment);
        }
    }

    public function deleteRfid(string $code = ''): void
    {
        if ($code) {
            $data = ['code' => $code];
        } else {
            $data = ['id' => '-1'];
        }

        $this->apiCall('rfkey', 'del', $data);
    }

    public function getAudioLevels(): array
    {
        $micVol = (int)$this->getParam('Config.Settings.HANDFREE.MicVol');
        $micVolMp = (int)$this->getParam('Config.Settings.HANDFREE.MicVolByMp');
        $spkVol = (int)$this->getParam('Config.Settings.HANDFREE.SpkVol');
        $kpdVol = (int)$this->getParam('Config.Settings.HANDFREE.KeypadVol');

        return [$micVol, $micVolMp, $spkVol, $kpdVol];
    }

    public function getDisplayText(): array
    {
        $text = $this->getParam('Config.Settings.OTHERS.GreetMsg') ?? '';
        return $text === '' ? [] : [$text];
    }

    public function getDisplayTextLinesCount(): int
    {
        return 1;
    }

    public function getHousePrefixSupportedFields(): array
    {
        return [HousePrefixField::FirstFlat, HousePrefixField::LastFlat];
    }

    public function getHousePrefixes(): array
    {
        $gateDialplans = $this->apiCall('dialreplacemp', 'get')['data'] ?? [];
        $prefixes = [];

        if (!$gateDialplans || $gateDialplans['num'] === 0) {
            return $prefixes;
        }

        unset($gateDialplans['num']);

        foreach ($gateDialplans as $gateDialplan) {
            $prefixes[] = new HousePrefix(
                number: $gateDialplan['prefix'],
                firstFlat: new FlatNumber($gateDialplan['start']),
                lastFlat: new FlatNumber($gateDialplan['end']),
            );
        }

        return $prefixes;
    }

    public function getLineDiagnostics(int $apartment): string
    {
        $this->loadDialplans();

        $analogReplace = $this->dialplans[$apartment]['replace1'] ?? null;
        $data = $this->apiCall('rs485', 'status', ['num' => "$analogReplace"])['data'];

        if (!$data['result']) {
            if ($data['line_err1']) {
                return 'short';
            } elseif ($data['line_err2']) {
                return 'unconnected';
            } elseif ($data['line_err3']) {
                return 'off-hook';
            }
        }

        return 'ok';
    }

    public function getRfids(): array
    {
        $rfidKeys = [];
        $rawKeys = $this->apiCall('rfkey', 'get')['data'] ?? [];

        foreach ($rawKeys as $key => $value) {
            if ($key === 'num') {
                continue;
            }

            $code = $value['code'];
            $rfidKeys[$code] = $code;
        }

        return $rfidKeys;
    }

    public function isFreePassEnabled(): bool
    {
        $relayA = $this->getParam('Config.DoorSetting.RELAY.RelayATrigAlways');
        $relayB = $this->getParam('Config.DoorSetting.RELAY.RelayBTrigAlways');
        $relayC = $this->getParam('Config.DoorSetting.RELAY.RelayCTrigAlways');

        return $relayA && $relayB && $relayC;
    }

    public function openLock(int $lockNumber = 0): void
    {
        $data = [
            'mode' => 0,
            'relay_num' => $lockNumber,
            'level' => 0,
            'delay' => 3,
        ];

        $this->apiCall('relay', 'trig', $data, 3);
    }

    public function prepare(): void
    {
        parent::prepare();
        $this->bindInputs();
        $this->enableDialplanOnly();
        $this->enableDisplayHeat();
        $this->enableFtp(false);
        $this->enablePnp(false);
        $this->setPersonalCodeLength();
        $this->configureRfidMode();
        $this->generateCodes();
    }

    public function setAudioLevels(array $levels): void
    {
        if (count($levels) === 4) {
            $this->setParams([
                'Config.Settings.HANDFREE.MicVol' => $levels[0],
                'Config.Settings.HANDFREE.MicVolByMp' => $levels[1],
                'Config.Settings.HANDFREE.SpkVol' => $levels[2],
                'Config.Settings.HANDFREE.KeypadVol' => $levels[3],
            ]);
        }
    }

    public function setCallTimeout(int $timeout): void
    {
        $this->setParams([
            'Config.Settings.CALLTIMEOUT.DialIn' => $timeout,
            'Config.Settings.CALLTIMEOUT.DialOut' => $timeout,
            'Config.Settings.CALLTIMEOUT.DialOut485' => $timeout,
        ]);
    }

    public function setCmsModel(string $model = ''): void
    {
        $modelMapping = [
            'BK-100' => 1,       // VIZIT
            'KMG-100' => 2,      // CYFRAL
            'KM100-7.1' => 3,    // ELTIS
            'KM100-7.5' => 3,    // ELTIS (same ID)
            'COM-100U' => 4,     // METAKOM
            'COM-220U' => 4,     // METAKOM (same ID)
            'QAD-100' => 5,      // Digital
        ];

        $id = $modelMapping[$model] ?? 0; // Default to 0 if not found

        $this->setParams([
            'Config.DoorSetting.GENERAL.Basip485' => $id,
            'Config.DoorSetting.GENERAL.Basip485OpenRelayA' => 1,
            'Config.DoorSetting.GENERAL.Basip485OpenRelayB' => 0,
            'Config.DoorSetting.GENERAL.Basip485OpenRelayC' => 0,
        ]);
    }

    public function setConciergeNumber(int $sipNumber): void
    {
        $this->setParams(['Config.Programable.SOFTKEY01.Param1' => $sipNumber]);
    }

    public function setDisplayText(array $textLines): void
    {
        $this->setParams([
            'Config.Settings.OTHERS.AccountStatusEnable' => 2,
            'Config.Settings.OTHERS.GreetMsg' => $textLines[0] ?? '',
            'Config.Settings.OTHERS.SendingMsg' => 'Вызываю...',
            'Config.Settings.OTHERS.TalkingMsg' => 'Говорите',
            'Config.Settings.OTHERS.OpenDoorSucMsg' => 'Дверь открыта!',
            'Config.Settings.OTHERS.OpenDoorFaiMsg' => 'Ошибка!',
            'Config.DoorSetting.GENERAL.DisplayNumber' => 1,
        ]);
    }

    public function setDtmfCodes(string $code1 = '1', string $code2 = '2', string $code3 = '3', string $codeCms = '1'): void
    {
        $this->setParams([
            'Config.DoorSetting.DTMF.Option' => 0,
            'Config.DoorSetting.DTMF.Code1' => $code1,
            'Config.DoorSetting.DTMF.Code2' => $code2,
            'Config.DoorSetting.DTMF.Code3' => $code3,
        ]);
    }

    public function setFreePassEnabled(bool $enabled): void
    {
        // Skip if the locks are currently already in the required state
        if ($enabled === $this->isFreePassEnabled()) {
            return;
        }

        $this->setParams([
            'Config.DoorSetting.RELAY.RelayATrigAlways' => (int)$enabled,
            'Config.DoorSetting.RELAY.RelayBTrigAlways' => (int)$enabled,
            'Config.DoorSetting.RELAY.RelayCTrigAlways' => (int)$enabled,
        ]);

        // Pull relays immediately
        $this->openLock();
        $this->openLock(1);
        $this->openLock(2);
    }

    public function setHousePrefixes(array $prefixes): void
    {
        $this->clearGateDialplan();

        if (!empty($prefixes)) {
            $this->setPanelMode('GATE');

            foreach ($prefixes as $prefix) {
                $this->apiCall('dialreplacemp', 'add', [
                    'prefix' => (string)$prefix->number,
                    'Start' => (string)$prefix->firstFlat->number,
                    'End' => (string)$prefix->lastFlat->number, // There will be an error if lastFlat === firstFlat
                    'Account' => 0,
                    'Address' => '', // There must be an empty string, otherwise the method will not work
                ]);
            }
        } else {
            $this->setPanelMode('NORMAL');
        }
    }

    public function setLanguage(string $language): void
    {
        $this->setParams(['Config.Settings.LANGUAGE.WebLang' => ($language === 'ru') ? 3 : 0]);
    }

    public function setPublicCode(int $code = 0): void
    {
        $this->setParams([
            'Config.DoorSetting.PASSWORD.PublicKeyEnable' => $code ? 1 : 0,
            'Config.DoorSetting.PASSWORD.PublicKey' => $code,

            // Disable code for relays B and C
            'Config.DoorSetting.PASSWORD.PublicKeyRelayB' => 0,
            'Config.DoorSetting.PASSWORD.PublicKeyRelayC' => 0,
        ]);
    }

    public function setSosNumber(int $sipNumber): void
    {
        $this->setParams(['Config.Features.SPEEDDIAL.Num01' => $sipNumber]);
    }

    public function setTalkTimeout(int $timeout): void
    {
        $timeout = round($timeout / 60);

        $this->setParams([
            'Config.Features.DOORPHONE.MaxCallTime' => $timeout,
            'Config.Features.DOORPHONE.Max485CallTime' => $timeout,
        ]);
    }

    public function setUnlockTime(int $time = 3): void
    {
        $this->setParams([
            'Config.DoorSetting.RELAY.RelayADelay' => $time,
            'Config.DoorSetting.RELAY.RelayBDelay' => $time,
            'Config.DoorSetting.RELAY.RelayCDelay' => $time,
        ]);
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $dbConfig['cmsLevels'] = [];
        $dbConfig['cmsModel'] = $this->getCmsVendorByModel($dbConfig['cmsModel']);
        $dbConfig['dtmf']['codeCms'] = '0';
        $dbConfig['ntp']['timezone'] = $this->getOffsetByTimezone($dbConfig['ntp']['timezone']);

        foreach ($dbConfig['apartments'] as &$apartment) {
            $apartment['cmsLevels'] = [];
        }

        return $dbConfig;
    }

    /**
     * Bind inputs to relays.
     *
     * This function is needed to bind discrete inputs to specified relays or SIP numbers
     *
     * - 0 = Disabled
     * - 1 = Relay A
     * - 2 = Relay B
     * - 3 = Relay C
     * - 4 = Call SOS
     * - 5 = Call for low-mobility groups
     *
     * @param int $inputA (Optional) What should be bound to input A (default is 1).
     * @param int $inputB (Optional) What should be bound to input B (default is 2).
     * @param int $inputC (Optional) What should be bound to input C (default is 1).
     *
     * @return void
     */
    protected function bindInputs(int $inputA = 1, int $inputB = 2, int $inputC = 1): void
    {
        $this->setParams([
            'Config.DoorSetting.INPUT.InputEnable' => 1,
            'Config.DoorSetting.INPUT.InputBEnable' => 1,
            'Config.DoorSetting.INPUT.InputCEnable' => 1,

            'Config.DoorSetting.INPUT.InputRelay' => $inputA,
            'Config.DoorSetting.INPUT.InputBRelay' => $inputB,
            'Config.DoorSetting.INPUT.InputCRelay' => $inputC,

            'Config.DoorSetting.INPUT.InputCTrigger' => 1, // Trigger on the falling edge (for motion sensors)
        ]);
    }

    /**
     * Clean the matrix by removing or updating dialplans.
     *
     * @return void
     */
    protected function cleanMatrix(): void
    {
        $this->loadDialplans();

        foreach ($this->dialplans as $dialplan) {
            if ($dialplan['replace2'] === '') {
                // If 'replace2' (SIP number) is empty, delete the dialplan
                $this->deleteDialplan($dialplan['prefix']);
            } else {
                // If 'replace2' (SIP number) is not empty, update the dialplan by setting the analog number to 0
                $this->updateDialplan(
                    $dialplan['id'],
                    $dialplan['prefix'],
                    self::EMPTY_ANALOG_REPLACE,
                    $dialplan['replace2'],
                    $dialplan['tags'],
                );
            }
        }
    }

    /**
     * Clear gate dialplan.
     *
     * @return void
     */
    protected function clearGateDialplan(): void
    {
        $this->apiCall('dialreplacemp', 'del', ['id' => '-1']);
    }

    /**
     * Configure the RFID reader mode.
     *
     * @param int $internalMode (Optional) The internal RFID reader mode. Default is 3 (8HN).
     * @param int $externalMode (Optional) The external RFID reader mode. Default is 3 (8HN).
     *
     * @return void
     */
    protected function configureRfidMode(int $internalMode = 3, int $externalMode = 3): void
    {
        $this->setParams([
            'Config.DoorSetting.GENERAL.ReverseMode' => 1,
            'Config.DoorSetting.RFCARDDISPLAY.RfidDisplayMode' => $internalMode,
            'Config.DoorSetting.RFCARDDISPLAY.WiegandDisplayMode' => $externalMode,
            'Config.DoorSetting.Card.CardMatchMode' => 1, // Partial match mode
            'Config.DoorSetting.Card.IDEnable' => 0,
        ]);
    }

    /**
     * Delete a dialplan based on the provided prefix (apartment).
     *
     * @param int $prefix (Optional) The prefix of the dialplan to be deleted.
     * If 0, then all dialplans will be deleted. Default is 0.
     *
     * @return void
     */
    protected function deleteDialplan(int $prefix = 0): void
    {
        $this->loadDialplans();

        if ($prefix === 0) {
            $this->apiCall('dialreplace', 'del', ['id' => '-1']);
            $this->dialplans = [];
        } elseif (isset($this->dialplans[$prefix])) {
            $dialplanId = $this->dialplans[$prefix]['id'];
            $this->apiCall('dialreplace', 'del', ['id' => "$dialplanId"]);
            unset($this->dialplans[$prefix]);
            $this->dialplans = $this->reindex($this->dialplans, $dialplanId);
        }
    }

    /**
     * Delete a personal access code based on the specified name (apartment).
     *
     * @param int $name (Optional) The name of the personal code that needs to be deleted.
     * If 0, then all codes will be deleted. Default is 0.
     *
     * @return void
     */
    protected function deletePersonalCode(int $name = 0): void
    {
        $this->loadPersonalCodes();

        if ($name === 0) {
            $this->apiCall('privatekey', 'del', ['id' => '-1']);
            $this->personalCodes = [];
        } elseif (isset($this->personalCodes[$name])) {
            $codeId = $this->personalCodes[$name]['id'];
            $this->apiCall('privatekey', 'del', ['id' => "$codeId"]);
            unset($this->personalCodes[$name]);
            $this->personalCodes = $this->reindex($this->personalCodes, $codeId);
        }
    }

    /**
     * Enable dialplan-only use.
     * If the called apartment isn't included to the dialplan, then the call is dropped immediately.
     *
     * @param bool $enabled (Optional) Whether to enable dialplan-only mode. Default is true.
     *
     * @return void
     */
    protected function enableDialplanOnly(bool $enabled = true): void
    {
        $this->setParams(['Config.DoorSetting.GENERAL.UseDialPlanOnly' => (int)$enabled]);
    }

    /**
     * Enable or disable display heating.
     *
     * @param bool $enabled (Optional) Whether to enable display heating. Default is true.
     * @param int $temperatureThreshold (Optional) The temperature threshold for heating. Default is 0°C.
     *
     * @return void
     */
    protected function enableDisplayHeat(bool $enabled = true, int $temperatureThreshold = 0): void
    {
        $this->setParams([
            'Config.DoorSetting.HEAT.Enable' => (int)$enabled,
            'Config.DoorSetting.HEAT.Threshold' => $temperatureThreshold,
        ]);
    }

    /**
     * Enable sending photos to FTP.
     *
     * @param bool $enabled (Optional) Whether to enable sending photos to FTP. Default is true.
     *
     * @return void
     */
    protected function enableFtp(bool $enabled = true): void
    {
        $this->setParams([
            // When opening the door
            'Config.DoorSetting.GENERAL.WebAndAPIEnable' => (int)$enabled,
            'Config.DoorSetting.GENERAL.AnalogHandsetEnable' => (int)$enabled,
            'Config.DoorSetting.GENERAL.SIPEquipmentEnable' => (int)$enabled,
        ]);
    }

    /**
     * Enable PNP.
     *
     * @param bool $enabled (Optional) Whether to enable PNP service. Default is true.
     *
     * @return void
     */
    protected function enablePnp(bool $enabled = true): void
    {
        $this->setParams(['Config.Autoprovision.PNP.Enable' => (int)$enabled]);
    }

    /**
     * Generate and set security access codes.
     * These codes are used to access the service menu from the front panel of the device.
     *
     * @return void
     */
    protected function generateCodes(): void
    {
        $projectKey = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $userSettingKey = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $systemSettingKey = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);

        $this->setParams([
            'Config.DoorSetting.PASSWORD.ProjectKey' => $projectKey,
            'Config.DoorSetting.PASSWORD.UserSettingKey' => $userSettingKey,
            'Config.DoorSetting.PASSWORD.SystemSettingKey' => $systemSettingKey,
        ]);
    }

    protected function getApartments(): array
    {
        $apartments = [];

        $this->loadDialplans();
        $this->loadPersonalCodes();

        foreach ($this->dialplans as $dialplan) {
            [
                'prefix' => $apartmentNumber,
                'replace2' => $sipNumber1,
                'replace3' => $sipNumber2,
                'replace4' => $sipNumber3,
                'replace5' => $sipNumber4,
                'tags' => $tags,
            ] = $dialplan;

            if ($sipNumber1) {
                $apartments[$apartmentNumber] = [
                    'apartment' => $apartmentNumber,
                    'code' => $this->personalCodes[$apartmentNumber]['code'] ?? 0,
                    'sipNumbers' => [$sipNumber1, $sipNumber2, $sipNumber3, $sipNumber4],
                    'cmsEnabled' => !in_array($tags, [2, 3]),
                    'cmsLevels' => [],
                ];
            }
        }

        return $apartments;
    }

    protected function getCmsModel(): string
    {
        $cmsModelId = $this->getParam('Config.DoorSetting.GENERAL.Basip485');

        $modelMapping = [
            '1' => 'VIZIT',
            '2' => 'CYFRAL',
            '3' => 'ELTIS',
            '4' => 'METAKOM',
            '5' => 'DIGITAL',
        ];

        return $modelMapping[$cmsModelId] ?? '';
    }

    /**
     * Get the CMS vendor name based on the given CMS model.
     *
     * @param string $cmsModel The CMS model for which the vendor name is needed.
     *
     * @return string The vendor name corresponding to the CMS model, or an empty string if not found.
     */
    protected function getCmsVendorByModel(string $cmsModel): string
    {
        $modelMapping = [
            'BK-100' => 'VIZIT',
            'KMG-100' => 'CYFRAL',
            'KM100-7.1' => 'ELTIS',
            'KM100-7.5' => 'ELTIS',
            'COM-100U' => 'METAKOM',
            'COM-220U' => 'METAKOM',
            'QAD-100' => 'DIGITAL',
        ];

        return $modelMapping[$cmsModel] ?? '';
    }

    protected function getDtmfConfig(): array
    {
        return [
            'code1' => $this->getParam('Config.DoorSetting.DTMF.Code1'),
            'code2' => $this->getParam('Config.DoorSetting.DTMF.Code2'),
            'code3' => $this->getParam('Config.DoorSetting.DTMF.Code3'),
            'codeCms' => '0',
        ];
    }

    protected function getMatrix(): array
    {
        $this->loadDialplans();
        $nowCms = $this->getCmsModel();
        $matrix = [];

        foreach ($this->dialplans as $dialplan) {
            $analogReplace = $dialplan['replace1'];

            if ($analogReplace === self::EMPTY_ANALOG_REPLACE) {
                continue;
            }

            [$hundreds, $tens, $units] = array_map('intval', str_split(str_pad($analogReplace, 3, '0', STR_PAD_LEFT)));

            if ($nowCms === 'ELTIS' && $hundreds > 0 && $tens === 0 && $units === 0) {
                $hundreds -= 1;
            }

            if (($nowCms === 'METAKOM' || $nowCms === 'DIGITAL') && $units === 0) {
                $units = 10;
                $tens -= 1;

                if ($tens < 0) {
                    $tens = 9;
                    $hundreds -= 1;
                }
            }

            if ($nowCms === 'DIGITAL' && $hundreds !== 0) {
                $tens += $hundreds * 10;
                $hundreds = 0;
            }

            $matrix[$hundreds . $tens . $units] = [
                'hundreds' => $hundreds,
                'tens' => $tens,
                'units' => $units,
                'apartment' => $dialplan['prefix'],
            ];
        }

        return $matrix;
    }

    /**
     * Get the next available ID based on the maximum ID in the given data array.
     *
     * @param array $data The data array to search for the maximum ID.
     *
     * @return int The next available ID.
     */
    protected function getNextId(array $data): int
    {
        $maxId = 0;

        foreach ($data as $entry) {
            if (isset($entry['id']) && $entry['id'] > $maxId) {
                $maxId = $entry['id'];
            }
        }

        return $maxId + 1;
    }

    protected function getSipConfig(): array
    {
        return [
            'server' => $this->getParam('Config.Account1.SIP.Server'),
            'port' => $this->getParam('Config.Account1.SIP.Port'),
            'login' => $this->getParam('Config.Account1.GENERAL.UserName'),
            'password' => $this->getParam('Config.Account1.GENERAL.Pwd'),
            'stunEnabled' => (bool)$this->getParam('Config.Account1.STUN.Enable'),
            'stunServer' => $this->getParam('Config.Account1.STUN.Server'),
            'stunPort' => $this->getParam('Config.Account1.STUN.Port'),
        ];
    }

    /**
     * Load and cache dialplans from the API if they haven't been loaded already.
     *
     * @return void
     */
    protected function loadDialplans(): void
    {
        if ($this->dialplans === null) {
            $rawDialplans = $this->apiCall('dialreplace', 'get')['data'] ?? [];
            unset($rawDialplans['num']);
            $this->dialplans = array_column($rawDialplans, null, 'prefix');
        }
    }

    /**
     * Load and cache personal codes from the API if they haven't been loaded already.
     *
     * @return void
     */
    protected function loadPersonalCodes(): void
    {
        if ($this->personalCodes === null) {
            $rawCodes = $this->apiCall('privatekey', 'get')['data'] ?? [];
            unset($rawCodes['num']);
            $this->personalCodes = array_column($rawCodes, null, 'name');
        }
    }

    /**
     * Reindex the data array after an element with a specific ID is removed.
     *
     * @param array $data The original data array.
     * @param int $deletedId The ID of the deleted element.
     *
     * @return array New data array.
     */
    protected function reindex(array $data, int $deletedId): array
    {
        return array_map(function ($newData) use ($deletedId) {
            if ($newData['id'] > $deletedId) {
                $newData['id'] -= 1;
            }
            return $newData;
        }, $data);
    }

    /**
     * Set panel mode.
     *
     * @param string $mode (Optional) The panel mode to set. Use 'GATE' to set the system in gate mode.
     * Any other value will put the panel into NORMAL mode.
     *
     * @return void
     */
    protected function setPanelMode(string $mode = ''): void
    {
        $this->setParams(['Config.DoorSetting.GENERAL.Basip485DeviceMode' => ($mode === 'GATE') ? 0 : 1]);
    }

    /**
     * Set personal code length.
     *
     * @param int $length (Optional) The length of personal access codes. Default is 5.
     *
     * @return void
     */
    protected function setPersonalCodeLength(int $length = 5): void
    {
        $this->setParams(['Config.DoorSetting.PrivateKey.Length' => $length]);
    }

    /**
     * Update or add a dialplan with the provided parameters.
     *
     * @param int|null $id The ID of the dialplan (if updating an existing dialplan), or null if adding a new dialplan.
     * @param string $prefix The prefix (apartment number) of the dialplan.
     * @param string $replace1 The first replacement value (analog number).
     * @param string $replace2 The second replacement value (SIP number).
     * @param int $tags Dialplan tags.
     *
     * @return void
     */
    protected function updateDialplan(
        ?int   $id,
        string $prefix,
        string $replace1,
        string $replace2,
        int    $tags,
    ): void
    {
        $this->loadDialplans();

        $data = [
            'line' => 0,
            'prefix' => $prefix,
            'Replace1' => $replace1,
            'DelayTime1' => '0',
            'Replace2' => $replace2,
            'DelayTime2' => '0',
            'Replace3' => '',
            'DelayTime3' => '0',
            'Replace4' => '',
            'DelayTime4' => '0',
            'Replace5' => '',
            'DelayTime5' => '0',
            'tags' => $tags,
        ];

        if ($id !== null) {
            $data['id'] = $id;
        }

        $this->apiCall('dialreplace', $id !== null ? 'set' : 'add', $data);

        if (!isset($data['id'])) {
            $data['id'] = $this->getNextId($this->dialplans);
        }

        $this->dialplans[$prefix] = array_change_key_case($data);
    }

    /**
     * Update or add a personal code with the provided parameters.
     *
     * @param int|null $id The ID of the personal code (if updating an existing code), or null if adding a new code.
     * @param int $name Name associated with the access code (usually apartment number).
     * @param int $code The access code to configure.
     * @param bool $enabled (Optional) Whether the access code is enabled. Default is true.
     *
     * @return void
     */
    protected function updatePersonalCode(?int $id, int $name, int $code, bool $enabled = true): void
    {
        $this->loadPersonalCodes();

        $data = [
            'name' => "$name",
            'code' => "$code",
            'mon' => (int)$enabled,
            'tue' => (int)$enabled,
            'wed' => (int)$enabled,
            'thur' => (int)$enabled,
            'fri' => (int)$enabled,
            'sat' => (int)$enabled,
            'sun' => (int)$enabled,
            'door_num' => 1,
            'time_start' => '00:00',
            'time_end' => '23:59',
            'device_name' => "$name",
        ];

        if ($id !== null) {
            $data['id'] = $id;
        }

        $this->apiCall('privatekey', $id !== null ? 'set' : 'add', $data);

        if (!isset($data['id'])) {
            $data['id'] = $this->getNextId($this->personalCodes);
        }

        $this->personalCodes[$name] = $data;
    }
}
