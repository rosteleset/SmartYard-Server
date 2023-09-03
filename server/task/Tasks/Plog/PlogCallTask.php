<?php

namespace Selpol\Task\Tasks\Plog;

use backends\plog\plog;
use Throwable;

class PlogCallTask extends PlogTask
{
    /** @var int Идентификатор устройства */
    public int $id;

    /** @var string IP-адресс устройства */
    public string $ip;

    /** @var int Дата события */
    public int $date;

    /** @var int|null Идентификатор звонка */
    public ?int $call;

    public int $retry = 0;

    public function __construct(int $id, string $ip, int $date, ?int $call)
    {
        parent::__construct('Событие звонка');

        $this->id = $id;
        $this->ip = $ip;

        $this->date = $date;
        $this->call = $call;
    }

    public function onTask(): bool
    {
        $this->retry++;

        $plog = backend('plog');

        $event_data = [];
        $event_data[plog::COLUMN_DATE] = $this->date;
        $event_data[plog::COLUMN_EVENT] = plog::EVENT_UNANSWERED_CALL;
        $event_data[plog::COLUMN_DOMOPHONE]['domophone_id'] = $this->id;
        $event_data[plog::COLUMN_DOMOPHONE]['domophone_output'] = 0;
        $event_data[plog::COLUMN_DOMOPHONE]['domophone_description'] = $this->getDomophoneDescription($event_data[plog::COLUMN_DOMOPHONE]['domophone_output']);
        $event_data[plog::COLUMN_EVENT_UUID] = guid_v4();

        $logs = $plog->getSyslog($this->ip, $this->date);

        $call_from_panel = 0;
        $call_start_found = false;

        $call_id = $this->call;

        if ($call_id == 0)
            $call_id = null;

        $flat_id = null;
        $prefix = null;
        $flat_number = null;

        foreach ($logs as $item) {
            $unit = $item['unit'];

            if ($unit == 'beward')
                $this->beward($event_data, $call_from_panel, $call_start_found, $call_id, $flat_id, $prefix, $flat_number, $item, $item['msg']);
            else if ($unit == 'is')
                $this->is($event_data, $call_from_panel, $call_start_found, $call_id, $flat_id, $prefix, $flat_number, $item, $item['msg']);
            else if ($unit == 'qtech')
                $this->qtech($event_data, $call_from_panel, $call_start_found, $call_id, $flat_id, $prefix, $flat_number, $item, $item['msg']);
            else if ($unit == 'akuvox')
                $this->akuvox($event_data, $call_from_panel, $call_start_found, $call_id, $flat_id, $prefix, $flat_number, $item, $item['msg']);

            if ($call_start_found || $call_from_panel < 0)
                break;
        }

        if ($call_from_panel < 0)
            return false;

        if ($flat_id != null) $event_data[plog::COLUMN_FLAT_ID] = $flat_id;
        else if ($prefix != null && $flat_number != null) $event_data[plog::COLUMN_FLAT_ID] = $this->getFlatIdByPrefixAndNumber($prefix, $flat_number);
        else if ($flat_number != null) $event_data[plog::COLUMN_FLAT_ID] = $this->getFlatIdByNumber($flat_number);
        else $event_data[plog::COLUMN_FLAT_ID] = $this->getFlatIdByDomophoneId();

        //не удалось получить flat_id - игнорируем звонок
        if (!isset($event_data[plog::COLUMN_FLAT_ID]))
            return false;

        if ($call_from_panel == 0) {
            //нет точных данных о том, что начало звонка было с этой панели
            //проверяем, мог ли звонок идти с другой панели
            $entrance_count = $this->getEntranceCount($event_data[plog::COLUMN_FLAT_ID]);

            if ($entrance_count > 1)
                //в квартиру можно позвонить с нескольких домофонов,
                //в данном случае считаем, что начальный звонок был с другого домофона - игнорируем звонок
                return false;
        }

        $image_data = $plog->getCamshot($this->id, $event_data[plog::COLUMN_DATE]);

        if ($image_data) {
            if (isset($image_data[plog::COLUMN_IMAGE_UUID]))
                $event_data[plog::COLUMN_IMAGE_UUID] = $image_data[plog::COLUMN_IMAGE_UUID];

            $event_data[plog::COLUMN_PREVIEW] = $image_data[plog::COLUMN_PREVIEW];
        }

        $plog->writeEventData($event_data);

        return true;
    }

    public function onError(Throwable $throwable): void
    {
        logger('task')->debug('PlogCallTask error' . PHP_EOL . $throwable);

        if ($this->retry < 3)
            task(new PlogCallTask($this->id, $this->ip, $this->date, $this->call))->low()->delay(300)->dispatch();
    }

    private function beward(array &$event_data, int &$call_from_panel, bool &$call_start_found, ?int $call_id, ?int $flat_id, ?string &$prefix, ?int &$flat_number, array $item, string $msg)
    {
        $patterns_call = [
            //pattern start  talk  open   call_from_panel
            ["Calling sip:", true, false, false, 1],
            ["Unable to call CMS apartment ", true, false, false, 0],
            ["CMS handset call started for apartment ", true, false, false, 0],
            ["SIP call | state ", false, false, false, 0],
            ["CMS handset talk started for apartment ", false, true, false, 0],
            ["SIP talk started for apartment ", false, true, false, 1],
            ["SIP call | CONFIRMED", false, true, false, 0],
            ["Opening door by CMS handset for apartment ", false, false, true, 0],
            ["Opening door by DTMF command", false, false, true, 0],
            ["All calls are done", false, false, false, 0],
            ["SIP call | DISCONNECTED", false, false, false, 0],
            ["SIP call | CALLING", false, false, false, 1],
            ["Incoming DTMF ", false, false, false, 1],
            ["Send DTMF ", false, false, false, -1],
        ];

        foreach ($patterns_call as [$pattern, $flag_start, $flag_talk_started, $flag_door_opened, $now_call_from_panel]) {
            unset($now_flat_id);
            unset($now_flat_number);
            unset($now_call_id);
            unset($now_sip_call_id);

            $parts = explode("|", $pattern);
            $matched = true;

            foreach ($parts as $p)
                $matched = $matched && (str_contains($msg, $p));

            if ($matched) {
                if ($now_call_from_panel > 0) $call_from_panel = 1;
                elseif ($now_call_from_panel < 0) {
                    $call_from_panel = -1;

                    break;
                }

                if (str_contains($msg, "[")) {
                    $p1 = strpos($msg, "[");
                    $p2 = strpos($msg, "]", $p1 + 1);

                    $now_call_id = intval(substr($msg, $p1 + 1, $p2 - $p1 - 1));
                }

                if (str_contains($pattern, "apartment")) {
                    //парсим номер квартиры
                    $p1 = strpos($msg, $pattern);
                    $p2 = strpos($msg, ".", $p1 + strlen($pattern));

                    if (!$p2) $p2 = strpos($msg, ",", $p1 + strlen($pattern));
                    if (!$p2) $p2 = strlen($msg);

                    $now_flat_number = intval(substr($msg, $p1 + strlen($pattern), $p2 - $p1 - strlen($pattern)));
                }

                if (str_contains($pattern, "Calling sip:")) {
                    $p1 = strpos($msg, $pattern);
                    $p2 = strpos($msg, "@", $p1 + strlen($pattern));

                    $sip = substr($msg, $p1 + strlen($pattern), $p2 - $p1 - strlen($pattern));
                    if ($sip[0] == "1") {
                        //звонок с панели, имеющей КМС, доп. панели или калитки без префикса
                        //парсим flat_id
                        $p1 = strpos($msg, $pattern);
                        $p2 = strpos($msg, "@", $p1 + strlen($pattern));
                        $now_flat_id = intval(substr($msg, $p1 + strlen($pattern) + 1, $p2 - $p1 - strlen($pattern) - 1));
                    } else {
                        //звонок с префиксом, первые четыре цифры - префикс с лидирующими нулями, остальные - номер квартиры (калитка)
                        $prefix = intval(substr($sip, 0, 4));
                        $now_flat_number = intval(substr($sip, 4));
                    }
                }

                if (str_contains($pattern, "SIP call ")) {
                    //парсим sip_call_id
                    $p1 = strpos($msg, $parts[0]);
                    $p2 = strpos($msg, " ", $p1 + strlen($parts[0]));

                    $now_sip_call_id = intval(substr($msg, $p1 + strlen($parts[0]), $p2 - $p1 - strlen($parts[0])));
                }

                $call_start_lost = isset($now_flat_id) && $flat_id != null && $now_flat_id != $flat_id
                    || isset($now_flat_number) && $flat_number != null && $now_flat_number != $flat_number
                    || isset($now_sip_call_id) && isset($sip_call_id) && $now_sip_call_id != $sip_call_id
                    || isset($now_call_id) && $call_id != null && $now_call_id != $call_id;

                if ($call_start_lost)
                    break;

                $event_data[plog::COLUMN_DATE] = $item['date'];

                if (isset($now_call_id) && $call_id == null) $call_id = $now_call_id;
                if (isset($now_sip_call_id) && !isset($sip_call_id)) $sip_call_id = $now_sip_call_id;
                if (isset($now_flat_number) && $flat_number == null) $flat_number = $now_flat_number;
                if (isset($now_flat_id) && $flat_id == null) $flat_id = $now_flat_id;
                if ($flag_talk_started) $event_data[plog::COLUMN_EVENT] = plog::EVENT_ANSWERED_CALL;
                if ($flag_door_opened) $event_data[plog::COLUMN_OPENED] = 1;

                if ($flag_start) {
                    $call_start_found = true;

                    break;
                }
            }
        }
    }

    private function is(array &$event_data, int &$call_from_panel, bool &$call_start_found, ?int $call_id, ?int $flat_id, ?string &$prefix, ?int &$flat_number, array $item, string $msg)
    {
        $patterns_call = [
            // pattern         start  talk  open   call_from_panel
            ["/Calling sip:\d+@.* through account/", true, false, false, 1],
            ["/CMS handset is not connected for apartment \d+, aborting CMS call/", true, false, false, 0],
            ["/CMS handset call started for apartment \d+/", true, false, false, 0],
            ["/CMS handset talk started for apartment \d+/", false, true, false, 0],
            ["/Baresip event CALL_RINGING/", true, false, false, 1],
            ["/Baresip event CALL_ESTABLISHED/", false, true, false, 0],
            ["/Opening door by CMS handset for apartment \d+/", false, false, true, 0],
            ["/Open from handset!/", false, false, true, 0],
            ["/Open main door by DTMF/", false, false, true, 1],
            ["/CMS handset call done for apartment \d+, handset is down/", false, false, false, 0],
            ["/SIP call done for apartment \d+, handset is down/", false, false, false, 1],
            ["/All calls are done for apartment \d+/", false, false, false, 1],

            // Incoming call patterns
            ["/Baresip event CALL_INCOMING/", false, false, false, -1],
            ["/Incoming call to sip:\d+@.* \(\d+\)/", false, false, false, -1],
        ];

        foreach ($patterns_call as [$pattern, $flag_start, $flag_talk_started, $flag_door_opened, $now_call_from_panel]) {
            unset($now_flat_id);
            unset($now_flat_number);
            unset($now_call_id);
            unset($now_sip_call_id);

            if (preg_match($pattern, $msg)) {
                // Check if call started from this panel
                if ($now_call_from_panel > 0) {
                    $call_from_panel = 1;
                } else if ($now_call_from_panel < 0) {
                    $call_from_panel = -1;

                    break;
                }

                // Get message parts
                $msg_parts = array_map('trim', preg_split("/[,@:]|\s(?=\d)/", $msg));

                // Get flat number and prefix
                if (isset($msg_parts[1])) {
                    $number = $msg_parts[1];

                    if (strlen($number) < 5) { // Apartment - ordinary panel
                        $now_flat_number = $number;
                    } else { // Gate panel - prefix and apartment
                        $prefix = substr($number, 0, 4);
                        $now_flat_number = substr($number, 4);
                    }
                }

                $call_start_lost = isset($now_flat_id) && $flat_id != null && $now_flat_id != $flat_id
                    || isset($now_flat_number) && $flat_number != null && $now_flat_number != $flat_number
                    || isset($now_sip_call_id) && isset($sip_call_id) && $now_sip_call_id != $sip_call_id
                    || isset($now_call_id) && $call_id != null && $now_call_id != $call_id;

                if ($call_start_lost)
                    break;

                $event_data[plog::COLUMN_DATE] = $item["date"];

                if (isset($now_call_id) && $call_id == null) $call_id = $now_call_id;
                if (isset($now_sip_call_id) && !isset($sip_call_id)) $sip_call_id = $now_sip_call_id;
                if (isset($now_flat_number) && $flat_number == null) $flat_number = $now_flat_number;
                if (isset($now_flat_id) && $flat_id == null) $flat_id = $now_flat_id;
                if ($flag_talk_started) $event_data[plog::COLUMN_EVENT] = plog::EVENT_ANSWERED_CALL;
                if ($flag_door_opened) $event_data[plog::COLUMN_OPENED] = 1;

                if ($flag_start) {
                    $call_start_found = true;

                    break;
                }
            }
        }
    }

    private function qtech(array &$event_data, int &$call_from_panel, bool &$call_start_found, ?int $call_id, ?int $flat_id, ?string &$prefix, ?int &$flat_number, array $item, string $msg)
    {
        $patterns_call = [
            // pattern         start  talk  open   call_from_panel
            ["/Prefix:\d+,Replace Number:\d+, Status:\d+/", true, false, false, 1],
            ["/Prefix:\d+,Analog Number:\d+, Status:\d+/", true, false, false, 1],
            ["/\d+:Call Established, Number:\d+/", false, true, false, 0],
            ["/\d+:Open Door By Intercom,Apartment No \d+/", false, false, true, 1],
            ["/\d+:\d+:Open Door By DTMF, DTMF Symbol \d+ ,Apartment No \d+/", false, false, true, 1],
        ];

        foreach ($patterns_call as [$pattern, $flag_start, $flag_talk_started, $flag_door_opened, $now_call_from_panel]) {
            unset($now_flat_id);
            unset($now_flat_number);
            unset($now_call_id);
            unset($now_sip_call_id);

            if (preg_match($pattern, $msg) !== 0) {
                // Check if call started from this panel
                if ($now_call_from_panel > 0)
                    $call_from_panel = 1;

                // Get message parts separated by ":" and ","
                $msg_parts = array_map("trim", preg_split("/[:,]/", $msg));

                // Get flat number, flat ID and prefix from call started events
                if ($msg_parts[0] === "Prefix") {
                    $number = $msg_parts[1]; // Caller (apartment or panel SIP number)
                    $replacing_number = $msg_parts[3]; // Call destination

                    if ($number <= 9999) { // Apartment - ordinary panel
                        $now_flat_number = $number;

                        if ($msg_parts[2] === "Replace Number") { // Get flat ID
                            $now_flat_id = substr($replacing_number, 1);
                        }
                    } else { // Panel SIP number - gate panel
                        $prefix = substr($replacing_number, 0, 4);
                        $now_flat_number = substr($replacing_number, 4);
                    }
                }

                // Get flat number, flat ID and prefix from call established events
                if ($msg_parts[1] === "Call Established") {
                    $number = $msg_parts[0]; // Call destination
                    $number_len = strlen($number);

                    if ($number_len === 10) { // Get flat ID
                        $now_flat_id = substr($number, 1);
                    } elseif ($number_len < 9 && $number_len > 4) { // Get prefix and flat number
                        $prefix = substr($number, 0, 4);
                        $now_flat_number = substr($number, 4);
                    } else { // Get flat number
                        $now_flat_number = $number;
                    }
                }

                // Get flat number from CMS door open event
                if ($msg_parts[1] === "Open Door By Intercom") {
                    $now_flat_number = $msg_parts[0];
                }

                // Get flat number from DTMF door open event
                if ($msg_parts[2] === "Open Door By DTMF") {
                    $number = $msg_parts[1];

                    if ($number <= 9999) { // Apartment - ordinary panel
                        $now_flat_number = $number;
                    }
                }

                $call_start_lost = isset($now_flat_id) && isset($flat_id) && $now_flat_id != $flat_id
                    || isset($now_flat_number) && isset($flat_number) && $now_flat_number != $flat_number
                    || isset($now_sip_call_id) && isset($sip_call_id) && $now_sip_call_id != $sip_call_id
                    || isset($now_call_id) && isset($call_id) && $now_call_id != $call_id;

                if ($call_start_lost)
                    break;

                $event_data[plog::COLUMN_DATE] = $item["date"];

                if (isset($now_call_id) && !isset($call_id)) $call_id = $now_call_id;
                if (isset($now_sip_call_id) && !isset($sip_call_id)) $sip_call_id = $now_sip_call_id;
                if (isset($now_flat_number) && !isset($flat_number)) $flat_number = $now_flat_number;
                if (isset($now_flat_id) && !isset($flat_id)) $flat_id = $now_flat_id;
                if ($flag_talk_started) $event_data[plog::COLUMN_EVENT] = plog::EVENT_ANSWERED_CALL;
                if ($flag_door_opened) $event_data[plog::COLUMN_OPENED] = 1;

                if ($flag_start) {
                    $call_start_found = true;

                    break;
                }
            }
        }
    }

    private function akuvox(array &$event_data, int &$call_from_panel, bool &$call_start_found, ?int $call_id, ?int $flat_id, ?string &$prefix, ?int &$flat_number, array $item, string $msg)
    {
        $patterns_call = [
            // pattern         start  talk  open   call_from_panel
            ["SIP_LOG:MSG_S2P_TRYING", true, false, false, 1],
            ["SIP_LOG:MSG_S2P_RINGBACK", true, false, false, 1],
            ["SIP_LOG:MSG_S2P_ESTABLISHED_CALL", false, true, false, 1],
            ["DTMF_LOG:Receive", false, false, true, 1],
            ["DTMF_LOG:From", false, false, true, 1],
            ["DTMF_LOG:Successful", false, false, true, 1],
            ["SIP_LOG:Call Finished", false, false, false, 1],
            ["SIP_LOG:Call Failed", false, false, false, 1],
        ];

        foreach ($patterns_call as [$pattern, $flag_start, $flag_talk_started, $flag_door_opened, $now_call_from_panel]) {
            unset($now_flat_id);
            unset($now_flat_number);
            unset($now_call_id);
            unset($now_sip_call_id);

            if (str_contains($msg, $pattern)) {
                // Get call ID
                if (str_contains($msg, 'SIP_LOG')) {
                    $now_call_id = explode('=', $msg)[1];
                }

                // Get flat ID
                if (str_contains($msg, 'DTMF_LOG:From')) {
                    $number = explode(' ', $msg)[1];
                    $now_flat_id = substr($number, 1);
                }

                $call_start_lost = isset($now_flat_id) && isset($flat_id) && $now_flat_id != $flat_id
                    || isset($now_call_id) && isset($call_id) && $now_call_id != $call_id;

                if ($call_start_lost)
                    break;

                $event_data[plog::COLUMN_DATE] = $item["date"];

                if (isset($now_call_id) && !isset($call_id)) $call_id = $now_call_id;
                if (isset($now_flat_id) && !isset($flat_id)) $flat_id = $now_flat_id;
                if ($flag_talk_started) $event_data[plog::COLUMN_EVENT] = plog::EVENT_ANSWERED_CALL;
                if ($flag_door_opened) $event_data[plog::COLUMN_OPENED] = 1;

                if ($flag_start) {
                    $call_start_found = true;

                    break;
                }
            }
        }
    }
}