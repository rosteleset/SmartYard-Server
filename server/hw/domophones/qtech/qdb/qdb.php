<?php

    namespace hw\domophones {

        require_once __DIR__ . '/../../domophones.php';

        abstract class qdb extends domophones {

            public string $user = 'admin';

            protected string $def_pass = 'httpapi';
            protected string $api_prefix = '/api/';

            /** Сделать API-вызов */
            protected function api_call(string $target, string $action, array $data = null) {
                $req = $this->url . $this->api_prefix;

                $postfields = [
                    'target' => $target,
                    'action' => $action,
                    'session' => '',
                    'data' => $data,
                ];

                $ch = curl_init($req);

                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_USERPWD, "$this->user:$this->pass");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_VERBOSE, false);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postfields));

                $res = curl_exec($ch);
                curl_close($ch);

                return json_decode($res, true);
            }

            /** Привязать входы к реле (0:отключен, 1:A, 2:B, 3:C, 4:SOS, 5:МГН) */
            protected function bind_inputs(int $inp_A = 1, int $inp_B = 2, int $inp_C = 1) {
                $params = $this->params_to_str([
                    'Config.DoorSetting.INPUT.InputEnable' => 1,
                    'Config.DoorSetting.INPUT.InputBEnable' => 1,
                    'Config.DoorSetting.INPUT.InputCEnable' => 1,

                    'Config.DoorSetting.INPUT.InputRelay' => $inp_A,
                    'Config.DoorSetting.INPUT.InputBRelay' => $inp_B,
                    'Config.DoorSetting.INPUT.InputCRelay' => $inp_C,

                    'Config.DoorSetting.INPUT.InputCTrigger' => 1, // Высокий триггер для АСТРЫ-5
                ]);
                $this->set_params($params);
            }

            /** Очистить план набора для режима калитки */
            protected function clear_gate_dialplan() {
                $this->api_call('dialreplacemp', 'del', [ 'id' => "-1" ]);
            }

            /** Configure remote debug server */
            protected function configure_debug(string $server, int $port, bool $enabled = true) {
                $params = $this->params_to_str([
                    'Config.DoorSetting.REMOTEDEBUG.Enable' => $enabled,
                    'Config.DoorSetting.REMOTEDEBUG.IP' => $server,
                    'Config.DoorSetting.REMOTEDEBUG.Port' => $port,
                ]);
                $this->set_params($params);
            }

            /** Настроить план набора */
            protected function configure_dialplan(
                int $apartment,
                int $analog_replace = null,
                array $numbers = null,
                bool $cms_enabled = null
            ) {
                if ($analog_replace >= 0 && $analog_replace < 10 && $analog_replace !== null) {
                    $analog_replace = "0$analog_replace";
                }

                $existing_dialplan = $this->get_dialplan($apartment);

                $data = [];

                if ($existing_dialplan) {
                    $data['id'] = $existing_dialplan['id'];
                    $action = 'set';
                    $analog_replace = ($analog_replace !== null) ? $analog_replace : $existing_dialplan['replace1'];
                    $numbers = ($numbers !== null) ? $numbers : [
                        $existing_dialplan['replace2'],
                        $existing_dialplan['replace3'],
                        $existing_dialplan['replace4'],
                        $existing_dialplan['replace5'],
                    ];
                    $cms_enabled = ($cms_enabled !== null) ? $cms_enabled : !($existing_dialplan['tags']);
                } else {
                    $action = 'add';
                }

                $data['line'] = 1;
                $data['prefix'] = "$apartment";
                $data['Replace1'] = "$analog_replace";
                $data['DelayTime1'] = '0';
                $data['Replace2'] = @"$numbers[0]";
                $data['DelayTime2'] = '0';
                $data['Replace3'] = @"$numbers[1]";
                $data['DelayTime3'] = '0';
                $data['Replace4'] = @"$numbers[2]";
                $data['DelayTime4'] = '0';
                $data['Replace5'] = @"$numbers[3]";
                $data['DelayTime5'] = '0';
                $data['tags'] = $cms_enabled ? 0 : 2;

                $this->api_call('dialreplace', $action, $data);
            }

            /** Настроить персональный код для квартиры */
            protected function configure_private_code(int $apartment, int $code, bool $enabled = true) {
                $data = [
                    'name' => "$apartment",
                    'code' => "$code",
                    'mon' => (int) $enabled,
                    'tue' => (int) $enabled,
                    'wed' => (int) $enabled,
                    'thur' => (int) $enabled,
                    'fri' => (int) $enabled,
                    'sat' => (int) $enabled,
                    'sun' => (int) $enabled,
                    'door_num' => 1,
                    'time_start' => '00:00',
                    'time_end' => '23:59',
                    'device_name' => "$apartment",
                ];

                $code = $this->get_private_code($apartment);
                if ($code) { // Редактирование существующего кода
                    $data['id'] = $code['id'];
                    $this->api_call('privatekey', 'set', $data);
                } else { // Добавление нового кода
                    $this->api_call('privatekey', 'add', $data);
                }
            }

            /** Enable dialplan-only use.
             * If the called apartment isn't included to the dialplan, then the call is dropped immediately
             */
            protected function enable_dialplan_only(bool $enabled = true) {
                $params = $this->params_to_str([
                    'Config.DoorSetting.GENERAL.UseDialPlanOnly' => (int) $enabled,
                ]);
                $this->set_params($params);
            }

            /** Разрешить подогрев дисплея */
            protected function enable_display_heat(bool $enabled = true) {
                $params = $this->params_to_str([
                    'Config.DoorSetting.HEAT.Enable' => (int) $enabled,
                    'Config.DoorSetting.HEAT.Threshold' => 0,
                ]);
                $this->set_params($params);
            }

            /** Разрешить отправку фото на FTP */
            protected function enable_ftp(bool $enabled = true) {
                $params = $this->params_to_str([
                    // При открытии двери
                    'Config.DoorSetting.GENERAL.WebAndAPIEnable' => (int) $enabled,
                    'Config.DoorSetting.GENERAL.AnalogHandsetEnable' => (int) $enabled,
                    'Config.DoorSetting.GENERAL.SIPEquipmentEnable' => (int) $enabled,
                ]);
                $this->set_params($params);
            }

            /** Разрешить работу встроенной FRS */
            protected function enable_internal_frs(bool $enabled = true) {
                $params = $this->params_to_str([
                    'Config.DoorSetting.FACEDETECT.Enable' => (int) $enabled,
                ]);
                $this->set_params($params);
            }

            /** Разрешить PNP */
            protected function enable_pnp(bool $enabled = true) {
                $params = $this->params_to_str([
                    'Config.Autoprovision.PNP.Enable' => (int) $enabled,
                ]);
                $this->set_params($params);
            }

            /** Получить весь план набора */
            protected function get_all_dialplans(): array {
                $raw_dialplans = @$this->api_call('dialreplace', 'get')['data'];
                unset($raw_dialplans['num']);

                $dialplans = [];

                if ($raw_dialplans) {
                    foreach ($raw_dialplans as $value) {
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

            /** Получить все персональные коды */
            protected function get_all_private_codes(bool $only_codes = false): array {
                $raw_codes = $this->api_call('privatekey', 'get')['data'];
                unset($raw_codes['num']);

                $codes = [];

                foreach ($raw_codes as $value) {
                    if ($only_codes) {
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

            /** Получить план набора для квартиры */
            protected function get_dialplan(int $apartment) {
                return @$this->get_all_dialplans()["$apartment"];
            }

            /** Получить параметр из секции config */
            protected function get_param(string $path) {
                $req = $this->api_call('config', 'get', [ 'config_key' => $path ]);
                return $req['data'][$path];
            }

            /** Получить персональный код квартиры */
            protected function get_private_code(int $apartment) {
                return @$this->get_all_private_codes()["$apartment"];
            }

            /** Преобразовать массив с параметрами в строку */
            protected function params_to_str(array $arr): string {
                $str = '';

                foreach ($arr as $key => $value) {
                    $str .= "$key:$value;";
                }

                return $str;
            }

            /** Удалить квартиру из плана набора */
            protected function remove_dialplan(int $apartment = -1) {
                $dialplan_id = -1;

                if ($apartment !== -1) {
                    $dialplan_id = $this->get_dialplan($apartment)['id'];
                }

                $this->api_call('dialreplace', 'del', [ 'id' => "$dialplan_id" ]);
            }

            /** Удалить персональный код квартиры */
            protected function remove_private_code(int $apartment = -1) {
                $code_id = -1;

                if ($apartment !== -1) {
                    $code_id = @$this->get_private_code($apartment)['id'];
                }

                $this->api_call('privatekey', 'del', [ 'id' => "$code_id" ]);
            }

            /** Установить режим работы панели */
            protected function set_panel_mode($mode = '') {
                $params = $this->params_to_str([
                    'Config.DoorSetting.GENERAL.Basip485DeviceMode' => ($mode === 'GATE') ? 0 : 1,
                ]);
                $this->set_params($params);
            }

            /** Установить параметры в секции config */
            protected function set_params(string $params) {
                return $this->api_call('config', 'set', [ 'config_key_value' => $params ]);
            }

            /** Установить длину персонального кода */
            protected function set_private_code_length(int $length = 5) {
                $params = $this->params_to_str([
                    'Config.DoorSetting.PrivateKey.Length' => $length,
                ]);
                $this->set_params($params);
            }

            /** Установить режим отображения RFID-ключей */
            protected function set_rfid_mode(int $int_mode = 4, int $ext_mode = 3) {
                $params = $this->params_to_str([
                    'Config.DoorSetting.RFCARDDISPLAY.RfidDisplayMode' => $int_mode,
                    'Config.DoorSetting.RFCARDDISPLAY.WiegandDisplayMode' => $ext_mode,
                    'Config.DoorSetting.Card.CardMatchMode' => 1, // Частичный режим поиска для Wiegand
                    'Config.DoorSetting.Card.IDEnable' => 0, // ID карта
                ]);
                $this->set_params($params);
            }

            public function add_rfid(string $code, int $apartment = 0) {
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
                $this->api_call('rfkey', 'add', $data);
            }

            public function clear_apartment(int $apartment = -1) {
                $this->remove_private_code($apartment);
                $this->remove_dialplan($apartment);
            }

            public function clear_rfid(string $code = '') {
                if ($code) {
                    $data = [ 'code' => $code ];
                } else {
                    $data = [ 'id' => '-1' ];
                }

                $this->api_call('rfkey', 'del', $data);
            }

            public function configure_apartment(
                int $apartment,
                bool $private_code_enabled,
                bool $cms_handset_enabled,
                array $sip_numbers = [],
                int $private_code = 0,
                array $levels = []
            ) {
                $this->configure_dialplan($apartment, null, $sip_numbers, $cms_handset_enabled);
                $this->configure_private_code($apartment, $private_code, $private_code_enabled);
            }

            public function configure_cms(int $apartment, int $offset) {
                if ($apartment == 0) {
                    return;
                }

                $cms = intdiv($offset, 100);
                $offset++;

                if ($offset%100 == 0) {
                    $units = 0;
                    $dozens = 0;
                } else {
                    $dozens = $offset%100;
                    $units = $dozens%10;
                    $dozens = intdiv($dozens, 10);
                }

                $analog_replace = (int) ($cms . $dozens . $units);
                $this->configure_dialplan($apartment, $analog_replace);
            }

            public function configure_cms_raw(int $index, int $dozens, int $units, int $apartment, string $cms_model) {
                $analog_replace = $index * 100 + $dozens * 10 + $units;
                $this->configure_dialplan($apartment, $analog_replace);
            }

            public function configure_gate(array $links = []) {
                if (count($links)) {
                    $this->set_panel_mode('GATE');
                    $this->clear_gate_dialplan();

                    for ($i = 0; $i < count($links); $i++) {
                        $data = [
                            'prefix' => (string) $links[$i]['prefix'],
                            'Start' => (string) $links[$i]['begin'],
                            'End' => (string) $links[$i]['end'],
                            'Account' => 1,
                            'Address' => '',
                        ];
                        $this->api_call('dialreplacemp', 'add', $data);
                    }
                } else {
                    $this->set_panel_mode('NORMAL');
                }
            }

            public function configure_md(
                int $sensitivity = 4,
                int $left = 0,
                int $top = 0,
                int $width = 705,
                int $height = 576
            ) {
                $params = $this->params_to_str([
                    'Config.DoorSetting.MOTION_DETECT.MotionDectect' => 1,
                    'Config.DoorSetting.MOTION_DETECT.DetectDelay' => 3,
                    'Config.DoorSetting.MOTION_DETECT.MDTimeWeekDay' => '0123456',
                    'Config.DoorSetting.MOTION_DETECT.MDTimeStart' => '00:00',
                    'Config.DoorSetting.MOTION_DETECT.MDTimeEnd' => '23:59',
//                'Config.DoorSetting.MOTION_DETECT.AreaStartWidth' => $left,
//                'Config.DoorSetting.MOTION_DETECT.AreaEndWidth' => $width,
//                'Config.DoorSetting.MOTION_DETECT.AreaStartHeight' => $top,
//                'Config.DoorSetting.MOTION_DETECT.AreaEndHeight' => $height,
                    'Config.DoorSetting.MOTION_DETECT.DetectAccuracy' => $sensitivity,
                    'Config.DoorSetting.MOTION_DETECT.FTPEnable' => 1, // костыль для syslog
                ]);
                $this->set_params($params);
            }

            public function configure_ntp(string $server, int $port, string $timezone) {
                $params = $this->params_to_str([
                    'Config.Settings.SNTP.TimeZone' => '+03:00',
                    'Config.Settings.SNTP.Name' => 'Russia(Moscow)',
                    'Config.Settings.SNTP.NTPServer1' => $server,
                    'Config.Settings.SNTP.NTPServer2' => null,
                    'Config.Settings.SNTP.Interval' => 3600,
                    'Config.Settings.SNTP.Port' => $port,
                ]);
                $this->set_params($params);
            }

            public function configure_sip(
                string $login,
                string $password,
                string $server,
                int $port = 5060,
                bool $nat = false,
                string $stun_server = '',
                int $stun_port = 3478
            ) {
                $sip_account = [
                    'AccountID' => '0',
                    'AccountActive' => '1',
                    'DisplayLabel' => $login,
                    'DisplayName' => $login,
                    'RegisterName' => $login,
                    'UserName' => $login,
                    'Password' => $password,
                ];

                $sip_server = [
                    'ServerIP' => $server,
                    'Port' => (string) $port,
                    'RegistrationPeriod' => '1800',
                ];

                $sip_data = [
                    'SipAccount' => $sip_account,
                    'SipServer1' => $sip_server,
                ];

                $params = $this->params_to_str([
                    'Config.Account1.STUN.Enable' => (int) $nat,
                    'Config.Account1.STUN.Server' => $stun_server,
                    'Config.Account1.STUN.Port' => $stun_port,
                    'Config.Account1.AUTO_ANSWER.Enable' => 0,
                ]);

                $this->api_call('sip', 'set', $sip_data);
                $this->set_params($params);
            }

            public function configure_syslog(string $server, int $port) {
                $params = $this->params_to_str([
                    'Config.DoorSetting.SysLog.SysLogServer' => $server,
                    'Config.DoorSetting.SysLog.SysLogServerPort' => $port,
                    'Config.DoorSetting.SysLog.SysLogServerTransportType' => 0,
                    'Config.DoorSetting.SysLog.SysLogServerHeartBeat' => 5,
                ]);
                $this->set_params($params);
                $this->configure_debug($server, $port + 1000);
            }

            public function configure_user_account(string $password) {
                $params = $this->params_to_str([
                    'Config.Settings.WEB_LOGIN.Password02' => $password,
                ]);
                $this->set_params($params);
            }

            public function configure_video_encoding() {
                // При передаче параметров одним вызовом некорректно выставляет настройки
                $main_params = $this->params_to_str([
                    'Config.DoorSetting.RTSP.Enable' => 1,
                    'Config.DoorSetting.RTSP.Authroization' => 1,
                    'Config.DoorSetting.RTSP.Audio' => 1,
                    'Config.DoorSetting.RTSP.Video' => 1,
                    'Config.DoorSetting.RTSP.Video2' => 1,
                    'Config.DoorSetting.RTSP.Port' => 554,
                    'Config.DoorSetting.RTSP.Codec' => 0, // H.264
                ]);

                $first_stream = $this->params_to_str([
                    'Config.DoorSetting.RTSP.H264Resolution' => 5, // 720P
                    'Config.DoorSetting.RTSP.H264FrameRate' => 15, // 15fps
                    'Config.DoorSetting.RTSP.H264RateControl' => 1, // VBR
                    'Config.DoorSetting.RTSP.H264BitRate' => 1024, // Bitrate
                    'Config.DoorSetting.RTSP.H264VideoProfile' => 0, // Baseline profile
                ]);

                $second_stream = $this->params_to_str([
                    'Config.DoorSetting.RTSP.H264Resolution2' => 3, // 480P
                    'Config.DoorSetting.RTSP.H264FrameRate2' => 30, // 30fps
                    'Config.DoorSetting.RTSP.H264RateControl2' => 1, // VBR
                    'Config.DoorSetting.RTSP.H264BitRate2' => 512, // Bitrate
                    'Config.DoorSetting.RTSP.H264VideoProfile2' => 0, // Baseline profile
                ]);

                $this->set_params($main_params);
                $this->set_params($first_stream);
                $this->set_params($second_stream);
            }

            public function get_audio_levels(): array {
                $mic_vol = $this->get_param('Config.Settings.HANDFREE.MicVol');
                $mic_vol_mp = $this->get_param('Config.Settings.HANDFREE.MicVolByMp');
                $spk_vol = $this->get_param('Config.Settings.HANDFREE.SpkVol');
                $kpd_vol = $this->get_param('Config.Settings.HANDFREE.KeypadVol');

                return [$mic_vol, $mic_vol_mp, $spk_vol, $kpd_vol];
            }

            public function get_cms_allocation(): array {
                $cms_raw = [];
                $dialplans = $this->get_all_dialplans();

                for ($i = 0; $i <= 1; $i++) {
                    for ($u = 0; $u <= 9; $u++) {
                        for ($d = 0; $d <= 9; $d++) {
                            $apartment = (int) ($i . $d . $u);

                            if ($apartment % 100 == 0) {
                                $apartment += 100;
                            }

                            $cms_raw[$i][$u][$d] = array_key_exists($apartment, $dialplans) ? $apartment : 0;
                        }
                    }
                }

                return $cms_raw;
            }

            public function get_cms_levels(): array {
                return [];
            }

            public function get_rfids(): array {
                $rfid_keys = [];
                $raw_keys = @$this->api_call('rfkey', 'get')['data'];

                if ($raw_keys) {
                    array_pop($raw_keys);
                    foreach ($raw_keys as $value) {
                        $rfid_keys[] = $value['code'];
                    }
                }

                return $rfid_keys;
            }

            public function get_sysinfo(): array {
                $res = $this->api_call('firmware', 'status');

                $sysinfo['DeviceID'] = str_replace(':', '', $res['data']['mac']);
                $sysinfo['DeviceModel'] = $res['data']['model'];
                $sysinfo['HardwareVersion'] = $res['data']['hardware'];
                $sysinfo['SoftwareVersion'] = $res['data']['firmware'];

                return $sysinfo;
            }

            public function keep_doors_unlocked(bool $unlocked = true) {
                $params = $this->params_to_str([
                    'Config.DoorSetting.RELAY.RelayATrigAlways' => (int) $unlocked,
                    'Config.DoorSetting.RELAY.RelayBTrigAlways' => (int) $unlocked,
                    'Config.DoorSetting.RELAY.RelayCTrigAlways' => (int) $unlocked,
                ]);
                $this->set_params($params);

                // Передернуть реле чтобы сработало сразу
                $this->open_door();
                $this->open_door(1);
                $this->open_door(2);
            }

            public function line_diag(int $apartment): string {
                $analog_replace = @$this->get_dialplan($apartment)['replace1'];
                $data = $this->api_call('rs485', 'status', [ 'num' => "$analog_replace" ])['data'];

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

            public function open_door(int $door_number = 0) {
                $data = [
                    'mode' => 0,
                    'relay_num' => $door_number,
                    'level' => 0,
                    'delay' => 3,
                ];

                $this->api_call('relay', 'trig', $data);
            }

            public function set_admin_password(string $password) {
                $params = $this->params_to_str([
                    'Config.DoorSetting.APIFCGI.AuthMode' => 3,
                    'Config.Settings.WEB_LOGIN.Password' => $password, // WEB
                    'Config.DoorSetting.APIFCGI.Password' => $password, // API
                    'Config.DoorSetting.RTSP.Password' => $password, // RTSP
                ]);
                $this->set_params($params);
            }

            public function set_audio_levels(array $levels) {
                $params = $this->params_to_str([
                    'Config.Settings.HANDFREE.MicVol' => @$levels[0] ?: 8,
                    'Config.Settings.HANDFREE.MicVolByMp' => @$levels[1] ?: 1,
                    'Config.Settings.HANDFREE.SpkVol' => @$levels[2] ?: 8,
                    'Config.Settings.HANDFREE.KeypadVol' => @$levels[3] ?: 8,
                ]);
                $this->set_params($params);
            }

            public function set_call_timeout(int $timeout) {
                $params = $this->params_to_str([
                    'Config.Settings.CALLTIMEOUT.DialIn' => $timeout,
                    'Config.Settings.CALLTIMEOUT.DialOut' => $timeout,
                    'Config.Settings.CALLTIMEOUT.DialOut485' => $timeout,
                ]);
                $this->set_params($params);
            }

            public function set_cms_levels(array $levels) {
                // не используется
            }

            public function set_cms_model(string $model = '') {
                switch ($model) {
                    case 'BK-100M':
                        $id = 1; // ВИЗИТ
                        break;
                    case 'KMG-100':
                        $id = 2; // ЦИФРАЛ
                        break;
                    case 'KM100-7.1':
                    case 'KM100-7.5':
                        $id = 3; // ЭЛТИС
                        break;
                    case 'COM-100U':
                    case 'COM-220U':
                        $id = 4; // МЕТАКОМ
                        break;
                    case 'QAD-100':
                        $id = 5; // Цифровые
                        break;
                    default:
                        $id = 0; // Отключен
                }

                $params = $this->params_to_str([
                    'Config.DoorSetting.GENERAL.Basip485' => $id,
                    'Config.DoorSetting.GENERAL.Basip485OpenRelayA' => 1,
                    'Config.DoorSetting.GENERAL.Basip485OpenRelayB' => 0,
                    'Config.DoorSetting.GENERAL.Basip485OpenRelayC' => 0,
                ]);
                $this->set_params($params);
            }

            public function set_concierge_number(int $number) {
                $params = $this->params_to_str([
                    'Config.Programable.SOFTKEY01.Param1' => $number,
                ]);
                $this->set_params($params);
            }

            public function set_display_text(string $text = '') {
                $params = $this->params_to_str([
                    'Config.Settings.OTHERS.AccountStatusEnable' => 2,
                    'Config.Settings.OTHERS.GreetMsg' => $text,
                    'Config.Settings.OTHERS.SendingMsg' => 'Вызываю...',
                    'Config.Settings.OTHERS.TalkingMsg' => 'Говорите',
                    'Config.Settings.OTHERS.OpenDoorSucMsg' => 'Дверь открыта!',
                    'Config.Settings.OTHERS.OpenDoorFaiMsg' => 'Ошибка!',
                    'Config.DoorSetting.GENERAL.DisplayNumber' => 1,
                ]);
                $this->set_params($params);
            }

            public function set_public_code(int $code = 0) {
                $params = $this->params_to_str([
                    'Config.DoorSetting.PASSWORD.PublicKeyEnable' => $code ? 1 : 0,
                    'Config.DoorSetting.PASSWORD.PublicKey' => $code,

                    // Отключение кода для реле B и C
                    'Config.DoorSetting.PASSWORD.PublicKeyRelayB' => 0,
                    'Config.DoorSetting.PASSWORD.PublicKeyRelayC' => 0,
                ]);
                $this->set_params($params);
            }

            public function setDtmf(string $code1, string $code2, string $code3, string $codeOut) {
                $params = $this->params_to_str([
                    'Config.DoorSetting.DTMF.Option' => 0,
                    'Config.DoorSetting.DTMF.Code1' => $code1,
                    'Config.DoorSetting.DTMF.Code2' => $code2,
                    'Config.DoorSetting.DTMF.Code3' => $code3,
                ]);
                $this->set_params($params);
            }

            public function set_sos_number(int $number) {
                $params = $this->params_to_str([
                    'Config.Features.SPEEDDIAL.Num01' => $number,
                ]);
                $this->set_params($params);
            }

            public function set_talk_timeout(int $timeout) {
                $timeout = round($timeout / 60);

                $params = $this->params_to_str([
                    'Config.Features.DOORPHONE.MaxCallTime' => $timeout,
                    'Config.Features.DOORPHONE.Max485CallTime' => $timeout,
                ]);
                $this->set_params($params);
            }

            public function set_unlock_time(int $time) {
                $params = $this->params_to_str([
                    'Config.DoorSetting.RELAY.RelayADelay' => $time,
                    'Config.DoorSetting.RELAY.RelayBDelay' => $time,
                    'Config.DoorSetting.RELAY.RelayCDelay' => $time,
                ]);
                $this->set_params($params);
            }

            public function set_video_overlay(string $title = '') {
                $params = $this->params_to_str([
                    'Config.DoorSetting.GENERAL.VideoWaterMark2' => $title,
                ]);
                $this->set_params($params);
            }

            public function set_language(string $lang) {
                switch ($lang) {
                    case 'RU':
                        $web_lang = 3;
                        break;
                    default:
                        $web_lang = 0;
                        break;
                }

                $params = $this->params_to_str([
                    'Config.Settings.LANGUAGE.WebLang' => $web_lang,
                ]);
                $this->set_params($params);
            }

            public function write_config() {
                // не используется
            }

            public function reboot() {
                $this->api_call('remote', 'reboot');
            }

            public function reset() {
                $this->api_call('remote', 'reset_factory');
            }

            public function prepare() {
                parent::prepare();
                $this->bind_inputs();
                $this->enable_dialplan_only();
                $this->enable_display_heat();
                $this->enable_ftp(false);
                $this->enable_internal_frs(false);
                $this->enable_pnp(false);
                $this->set_private_code_length();
                $this->set_rfid_mode();
            }
        }
    }
