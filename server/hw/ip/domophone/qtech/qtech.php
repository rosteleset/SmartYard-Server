<?php

namespace hw\ip\domophone\qtech;

use hw\ip\domophone\domophone;

/**
 * Abstract class representing a Qtech domophone.
 */
abstract class qtech extends domophone
{

    use \hw\ip\common\qtech\qtech;

    public function addRfid(string $code, int $apartment = 0)
    {
        $data = [
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
        ];
        $this->apiCall('rfkey', 'add', $data);
    }

    public function configureApartment(
        int   $apartment,
        int   $code = 0,
        array $sipNumbers = [],
        bool  $cmsEnabled = true,
        array $cmsLevels = []
    )
    {
        $this->configureDialplan($apartment, null, $sipNumbers, $cmsEnabled);
        $this->configureApartmentCode($apartment, $code);
    }

    public function configureApartmentCMS(int $cms, int $dozen, int $unit, int $apartment)
    {
        $analogReplace = $cms * 100 + $dozen * 10 + $unit;
        $this->configureDialplan($apartment, $analogReplace);
    }

    public function configureEncoding()
    {
        // Works incorrectly when passing parameters in one call
        $mainParams = $this->paramsToString([
            'Config.DoorSetting.RTSP.Enable' => 1,
            'Config.DoorSetting.RTSP.Authroization' => 1,
            'Config.DoorSetting.RTSP.Audio' => 1,
            'Config.DoorSetting.RTSP.Video' => 1,
            'Config.DoorSetting.RTSP.Video2' => 1,
            'Config.DoorSetting.RTSP.Port' => 554,
            'Config.DoorSetting.RTSP.Codec' => 0, // H.264
        ]);

        $firstStream = $this->paramsToString([
            'Config.DoorSetting.RTSP.H264Resolution' => 5, // 720P
            'Config.DoorSetting.RTSP.H264FrameRate' => 15, // 15fps
            'Config.DoorSetting.RTSP.H264RateControl' => 1, // VBR
            'Config.DoorSetting.RTSP.H264BitRate' => 1024, // Bitrate
            'Config.DoorSetting.RTSP.H264VideoProfile' => 0, // Baseline profile
        ]);

        $secondStream = $this->paramsToString([
            'Config.DoorSetting.RTSP.H264Resolution2' => 3, // 480P
            'Config.DoorSetting.RTSP.H264FrameRate2' => 30, // 30fps
            'Config.DoorSetting.RTSP.H264RateControl2' => 1, // VBR
            'Config.DoorSetting.RTSP.H264BitRate2' => 512, // Bitrate
            'Config.DoorSetting.RTSP.H264VideoProfile2' => 0, // Baseline profile
        ]);

        $this->setParams($mainParams);
        $this->setParams($firstStream);
        $this->setParams($secondStream);
    }

    public function configureGate(array $links = [])
    {
        if (count($links)) {
            $this->setPanelMode('GATE');
            $this->clearGateDialplan();

            for ($i = 0; $i < count($links); $i++) {
                $data = [
                    'prefix' => (string)$links[$i]['prefix'],
                    'Start' => (string)$links[$i]['begin'],
                    'End' => (string)$links[$i]['end'],
                    'Account' => 1,
                    'Address' => '',
                ];
                $this->apiCall('dialreplacemp', 'add', $data);
            }
        } else {
            $this->setPanelMode('NORMAL');
        }
    }

    public function configureMatrix(array $matrix)
    {
        // TODO: Implement configureMatrix() method.
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

        $params = $this->paramsToString([
            'Config.Account1.STUN.Enable' => $stunEnabled,
            'Config.Account1.STUN.Server' => $stunServer,
            'Config.Account1.STUN.Port' => $stunPort,
            'Config.Account1.AUTO_ANSWER.Enable' => 0,
        ]);

        $this->apiCall('sip', 'set', $sipData);
        $this->setParams($params);
    }

    public function configureUserAccount(string $password)
    {
        $params = $this->paramsToString([
            'Config.Settings.WEB_LOGIN.Password02' => $password,
        ]);
        $this->setParams($params);
    }

    public function deleteApartment(int $apartment = 0)
    {
        $this->removePersonalCode($apartment);
        $this->removeApartmentDialplan($apartment);
    }

    public function deleteRfid(string $code = '')
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
        $micVol = $this->getParam('Config.Settings.HANDFREE.MicVol');
        $micVolMp = $this->getParam('Config.Settings.HANDFREE.MicVolByMp');
        $spkVol = $this->getParam('Config.Settings.HANDFREE.SpkVol');
        $kpdVol = $this->getParam('Config.Settings.HANDFREE.KeypadVol');

        return [$micVol, $micVolMp, $spkVol, $kpdVol];
    }

    public function getCmsLevels(): array
    {
        return [];
    }

    public function getLineDiagnostics(int $apartment): string
    {
        $analogReplace = @$this->getApartmentDialplan($apartment)['replace1'];
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
        $rawKeys = @$this->apiCall('rfkey', 'get')['data'];

        if ($rawKeys) {
            array_pop($rawKeys);
            foreach ($rawKeys as $value) {
                $rfidKeys[] = $value['code'];
            }
        }

        return $rfidKeys;
    }

    public function openLock(int $lockNumber = 0)
    {
        $data = [
            'mode' => 0,
            'relay_num' => $lockNumber,
            'level' => 0,
            'delay' => 3,
        ];

        $this->apiCall('relay', 'trig', $data);
    }

    public function prepare()
    {
        parent::prepare();
        $this->bindInputs();
        $this->enableDialplanOnly();
        $this->enableDisplayHeat();
        $this->enableFtp(false);
        $this->enableInternalFrs(false);
        $this->enablePnp(false);
        $this->setPersonalCodeLength();
        $this->configureRfidMode();
    }

    public function setAudioLevels(array $levels)
    {
        $params = $this->paramsToString([
            'Config.Settings.HANDFREE.MicVol' => @$levels[0] ?: 8,
            'Config.Settings.HANDFREE.MicVolByMp' => @$levels[1] ?: 1,
            'Config.Settings.HANDFREE.SpkVol' => @$levels[2] ?: 8,
            'Config.Settings.HANDFREE.KeypadVol' => @$levels[3] ?: 8,
        ]);
        $this->setParams($params);
    }

    public function setCallTimeout(int $timeout)
    {
        $params = $this->paramsToString([
            'Config.Settings.CALLTIMEOUT.DialIn' => $timeout,
            'Config.Settings.CALLTIMEOUT.DialOut' => $timeout,
            'Config.Settings.CALLTIMEOUT.DialOut485' => $timeout,
        ]);
        $this->setParams($params);
    }

    public function setCmsLevels(array $levels)
    {
        // Empty implementation
    }

    public function setCmsModel(string $model = '')
    {
        switch ($model) {
            case 'BK-100':
                $id = 1; // VIZIT
                break;
            case 'KMG-100':
                $id = 2; // CYFRAL
                break;
            case 'KM100-7.1':
            case 'KM100-7.5':
                $id = 3; // ELTIS
                break;
            case 'COM-100U':
            case 'COM-220U':
                $id = 4; // METAKOM
                break;
            case 'QAD-100':
                $id = 5; // Digital
                break;
            default:
                $id = 0; // Disabled
        }

        $params = $this->paramsToString([
            'Config.DoorSetting.GENERAL.Basip485' => $id,
            'Config.DoorSetting.GENERAL.Basip485OpenRelayA' => 1,
            'Config.DoorSetting.GENERAL.Basip485OpenRelayB' => 0,
            'Config.DoorSetting.GENERAL.Basip485OpenRelayC' => 0,
        ]);
        $this->setParams($params);
    }

    public function setConciergeNumber(int $sipNumber)
    {
        $params = $this->paramsToString([
            'Config.Programable.SOFTKEY01.Param1' => $sipNumber,
        ]);
        $this->setParams($params);
    }

    public function setDtmfCodes(string $code1 = '1', string $code2 = '2', string $code3 = '3', string $codeCms = '1')
    {
        $params = $this->paramsToString([
            'Config.DoorSetting.DTMF.Option' => 0,
            'Config.DoorSetting.DTMF.Code1' => $code1,
            'Config.DoorSetting.DTMF.Code2' => $code2,
            'Config.DoorSetting.DTMF.Code3' => $code3,
        ]);
        $this->setParams($params);
    }

    public function setLanguage(string $language = 'ru')
    {
        switch ($language) {
            case 'ru':
                $webLang = 3;
                break;
            default:
                $webLang = 0;
                break;
        }

        $params = $this->paramsToString([
            'Config.Settings.LANGUAGE.WebLang' => $webLang,
        ]);
        $this->setParams($params);
    }

    public function setPublicCode(int $code = 0)
    {
        $params = $this->paramsToString([
            'Config.DoorSetting.PASSWORD.PublicKeyEnable' => $code ? 1 : 0,
            'Config.DoorSetting.PASSWORD.PublicKey' => $code,

            // Отключение кода для реле B и C
            'Config.DoorSetting.PASSWORD.PublicKeyRelayB' => 0,
            'Config.DoorSetting.PASSWORD.PublicKeyRelayC' => 0,
        ]);
        $this->setParams($params);
    }

    public function setSosNumber(int $sipNumber)
    {
        $params = $this->paramsToString([
            'Config.Features.SPEEDDIAL.Num01' => $sipNumber,
        ]);
        $this->setParams($params);
    }

    public function setTalkTimeout(int $timeout)
    {
        $timeout = round($timeout / 60);

        $params = $this->paramsToString([
            'Config.Features.DOORPHONE.MaxCallTime' => $timeout,
            'Config.Features.DOORPHONE.Max485CallTime' => $timeout,
        ]);
        $this->setParams($params);
    }

    public function setTickerText(string $text = '')
    {
        $params = $this->paramsToString([
            'Config.Settings.OTHERS.AccountStatusEnable' => 2,
            'Config.Settings.OTHERS.GreetMsg' => $text,
            'Config.Settings.OTHERS.SendingMsg' => 'Вызываю...',
            'Config.Settings.OTHERS.TalkingMsg' => 'Говорите',
            'Config.Settings.OTHERS.OpenDoorSucMsg' => 'Дверь открыта!',
            'Config.Settings.OTHERS.OpenDoorFaiMsg' => 'Ошибка!',
            'Config.DoorSetting.GENERAL.DisplayNumber' => 1,
        ]);
        $this->setParams($params);
    }

    public function setUnlockTime(int $time = 3)
    {
        $params = $this->paramsToString([
            'Config.DoorSetting.RELAY.RelayADelay' => $time,
            'Config.DoorSetting.RELAY.RelayBDelay' => $time,
            'Config.DoorSetting.RELAY.RelayCDelay' => $time,
        ]);
        $this->setParams($params);
    }

    public function setUnlocked(bool $unlocked = true)
    {
        $params = $this->paramsToString([
            'Config.DoorSetting.RELAY.RelayATrigAlways' => (int)$unlocked,
            'Config.DoorSetting.RELAY.RelayBTrigAlways' => (int)$unlocked,
            'Config.DoorSetting.RELAY.RelayCTrigAlways' => (int)$unlocked,
        ]);
        $this->setParams($params);

        // Pull relays immediately
        $this->openLock();
        $this->openLock(1);
        $this->openLock(2);
    }

    public function transformDbConfig(array $dbConfig): array
    {
        return $dbConfig;
    }

    /** Привязать входы к реле (0:отключен, 1:A, 2:B, 3:C, 4:SOS, 5:МГН) */
    protected function bindInputs(int $inpA = 1, int $inpB = 2, int $inpC = 1)
    {
        $params = $this->paramsToString([
            'Config.DoorSetting.INPUT.InputEnable' => 1,
            'Config.DoorSetting.INPUT.InputBEnable' => 1,
            'Config.DoorSetting.INPUT.InputCEnable' => 1,

            'Config.DoorSetting.INPUT.InputRelay' => $inpA,
            'Config.DoorSetting.INPUT.InputBRelay' => $inpB,
            'Config.DoorSetting.INPUT.InputCRelay' => $inpC,

            'Config.DoorSetting.INPUT.InputCTrigger' => 1, // Высокий триггер для АСТРЫ-5
        ]);
        $this->setParams($params);
    }

    /** Clear gate dialplan */
    protected function clearGateDialplan()
    {
        $this->apiCall('dialreplacemp', 'del', ['id' => "-1"]);
    }

    /** Configure personal access code for apartment */
    protected function configureApartmentCode(int $apartment, int $code, bool $enabled = true)
    {
        $data = [
            'name' => "$apartment",
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
            'device_name' => "$apartment",
        ];

        $code = $this->getApartmentCode($apartment);
        if ($code) { // Edit existing code
            $data['id'] = $code['id'];
            $this->apiCall('privatekey', 'set', $data);
        } else { // Add new code
            $this->apiCall('privatekey', 'add', $data);
        }
    }

    /** Configure dialplan */
    protected function configureDialplan(
        int   $apartment,
        int   $analogReplace = null,
        array $numbers = null,
        bool  $cmsEnabled = null
    )
    {
        if ($analogReplace >= 0 && $analogReplace < 10 && $analogReplace !== null) {
            $analogReplace = "0$analogReplace";
        }

        $existingDialplan = $this->getApartmentDialplan($apartment);

        $data = [];

        if ($existingDialplan) {
            $data['id'] = $existingDialplan['id'];
            $action = 'set';
            $analogReplace = ($analogReplace !== null) ? $analogReplace : $existingDialplan['replace1'];
            $numbers = ($numbers !== null) ? $numbers : [
                $existingDialplan['replace2'],
                $existingDialplan['replace3'],
                $existingDialplan['replace4'],
                $existingDialplan['replace5'],
            ];
            $cmsEnabled = ($cmsEnabled !== null) ? $cmsEnabled : !($existingDialplan['tags']);
        } else {
            $action = 'add';
        }

        $data['line'] = 1;
        $data['prefix'] = "$apartment";
        $data['Replace1'] = "$analogReplace";
        $data['DelayTime1'] = '0';
        $data['Replace2'] = @"$numbers[0]";
        $data['DelayTime2'] = '0';
        $data['Replace3'] = @"$numbers[1]";
        $data['DelayTime3'] = '0';
        $data['Replace4'] = @"$numbers[2]";
        $data['DelayTime4'] = '0';
        $data['Replace5'] = @"$numbers[3]";
        $data['DelayTime5'] = '0';
        $data['tags'] = $cmsEnabled ? 0 : 2;

        $this->apiCall('dialreplace', $action, $data);
    }

    /** Configure RFID mode */
    protected function configureRfidMode(int $intMode = 4, int $extMode = 3)
    {
        $params = $this->paramsToString([
            'Config.DoorSetting.RFCARDDISPLAY.RfidDisplayMode' => $intMode,
            'Config.DoorSetting.RFCARDDISPLAY.WiegandDisplayMode' => $extMode,
            'Config.DoorSetting.Card.CardMatchMode' => 1, // Частичный режим поиска для Wiegand
            'Config.DoorSetting.Card.IDEnable' => 0, // ID карта
        ]);
        $this->setParams($params);
    }

    /**
     * Enable dialplan-only use.
     * If the called apartment isn't included to the dialplan, then the call is dropped immediately.
     */
    protected function enableDialplanOnly(bool $enabled = true)
    {
        $params = $this->paramsToString([
            'Config.DoorSetting.GENERAL.UseDialPlanOnly' => (int)$enabled,
        ]);
        $this->setParams($params);
    }

    /** Enable display heating */
    protected function enableDisplayHeat(bool $enabled = true)
    {
        $params = $this->paramsToString([
            'Config.DoorSetting.HEAT.Enable' => (int)$enabled,
            'Config.DoorSetting.HEAT.Threshold' => 0,
        ]);
        $this->setParams($params);
    }

    /** Enable sending photos to FTP */
    protected function enableFtp(bool $enabled = true)
    {
        $params = $this->paramsToString([
            // When opening the door
            'Config.DoorSetting.GENERAL.WebAndAPIEnable' => (int)$enabled,
            'Config.DoorSetting.GENERAL.AnalogHandsetEnable' => (int)$enabled,
            'Config.DoorSetting.GENERAL.SIPEquipmentEnable' => (int)$enabled,
        ]);
        $this->setParams($params);
    }

    /** Enable built-in FRS */
    protected function enableInternalFrs(bool $enabled = true)
    {
        $params = $this->paramsToString([
            'Config.DoorSetting.FACEDETECT.Enable' => (int)$enabled,
        ]);
        $this->setParams($params);
    }

    /** Enable PNP */
    protected function enablePnp(bool $enabled = true)
    {
        $params = $this->paramsToString([
            'Config.Autoprovision.PNP.Enable' => (int)$enabled,
        ]);
        $this->setParams($params);
    }

    /** Get apartment personal code */
    protected function getApartmentCode(int $apartment)
    {
        return @$this->getPersonalCodes()["$apartment"];
    }

    /** Get apartment dialplan */
    protected function getApartmentDialplan(int $apartment)
    {
        return @$this->getDialplan()["$apartment"];
    }

    protected function getApartments(): array
    {
        // TODO: Implement getApartments() method.
        return [];
    }

    protected function getCmsModel(): string
    {
        // TODO: Implement getCmsModel() method.
        return '';
    }

    /** Get dialplan */
    protected function getDialplan(): array
    {
        $rawDialplans = @$this->apiCall('dialreplace', 'get')['data'];
        unset($rawDialplans['num']);

        $dialplans = [];

        if ($rawDialplans) {
            foreach ($rawDialplans as $value) {
                $dialplans[$value['prefix']] = [
                    'id' => $value['id'],
                    'replace1' => $value['replace1'],
                    'replace2' => $value['replace2'],
                    'replace3' => $value['replace3'],
                    'replace4' => $value['replace4'],
                    'replace5' => $value['replace5'],
                    'tags' => $value['tags'],
                ];
            }
        }

        return $dialplans;
    }

    protected function getDtmfConfig(): array
    {
        // TODO: Implement getDtmfConfig() method.
        return [];
    }

    protected function getGateConfig(): array
    {
        // TODO: Implement getGateConfig() method.
        return [];
    }

    protected function getMatrix(): array
    {
        // TODO: Implement getMatrix() method.
        return [];
    }

    /** Get parameter from config section */
    protected function getParam(string $path)
    {
        $req = $this->apiCall('config', 'get', ['config_key' => $path]);
        return $req['data'][$path];
    }

    /** Get personal codes */
    protected function getPersonalCodes(bool $codesOnly = false): array
    {
        $rawCodes = $this->apiCall('privatekey', 'get')['data'];
        unset($rawCodes['num']);

        $codes = [];

        foreach ($rawCodes as $value) {
            if ($codesOnly) {
                $codes[] = $value['code'];
            } else {
                $codes[$value['name']] = [
                    'id' => $value['id'],
                    'code' => $value['code'],
                ];
            }
        }

        return $codes;
    }

    protected function getSipConfig(): array
    {
        // TODO: Implement getSipConfig() method.
        return [];
    }

    protected function getTickerText(): string
    {
        // TODO: Implement getTickerText() method.
        return '';
    }

    protected function getUnlocked(): bool
    {
        // TODO: Implement getUnlocked() method.
        return false;
    }

    /** Remove apartment from dialplan */
    protected function removeApartmentDialplan(int $apartment = 0)
    {
        $dialplanId = -1;

        if ($apartment !== 0) {
            $dialplanId = $this->getApartmentDialplan($apartment)['id'];
        }

        $this->apiCall('dialreplace', 'del', ['id' => "$dialplanId"]);
    }

    /** Remove apartment personal code */
    protected function removePersonalCode(int $apartment = 0)
    {
        $codeId = -1;

        if ($apartment !== 0) {
            $codeId = @$this->getApartmentCode($apartment)['id'];
        }

        $this->apiCall('privatekey', 'del', ['id' => "$codeId"]);
    }

    /** Set panel mode */
    protected function setPanelMode($mode = '')
    {
        $params = $this->paramsToString([
            'Config.DoorSetting.GENERAL.Basip485DeviceMode' => ($mode === 'GATE') ? 0 : 1,
        ]);
        $this->setParams($params);
    }

    /** Set personal code length */
    protected function setPersonalCodeLength(int $length = 5)
    {
        $params = $this->paramsToString([
            'Config.DoorSetting.PrivateKey.Length' => $length,
        ]);
        $this->setParams($params);
    }
}
