<?php

namespace Selpol\Task\Tasks\Plog;

use backends\frs\frs;
use backends\plog\plog;
use Throwable;

class PlogOpenTask extends PlogTask
{
    /** @var int Тип события */
    public int $type;

    /** @var int Выход устройства */
    public int $door;

    /** @var int Дата события */
    public int $date;

    /** @var string Информация о событие */
    public string $detail;

    public int $retry = 0;

    public function __construct(int $id, int $type, int $door, int $date, string $detail)
    {
        parent::__construct('Событие открытие двери');

        $this->id = $id;

        $this->type = $type;
        $this->door = $door;
        $this->date = $date;
        $this->detail = $detail;
    }

    public function onTask(): bool
    {
        $this->retry++;

        $plog = backend('plog');

        $event_data = [];
        $event_id = false;
        $flat_list = [];

        $event_data[plog::COLUMN_DATE] = $this->date;
        $event_data[plog::COLUMN_EVENT] = $this->type;
        $event_data[plog::COLUMN_DOMOPHONE]['domophone_id'] = $this->id;
        $event_data[plog::COLUMN_DOMOPHONE]['domophone_output'] = $this->door;
        $event_data[plog::COLUMN_DOMOPHONE]['domophone_description'] = $this->getDomophoneDescription($event_data[plog::COLUMN_DOMOPHONE]['domophone_output']);
        $event_data[plog::COLUMN_EVENT_UUID] = guid_v4();

        if ($this->type == plog::EVENT_OPENED_BY_KEY) {
            $event_data[plog::COLUMN_OPENED] = 1;
            $rfid_key = $this->detail;
            $event_data[plog::COLUMN_RFID] = $rfid_key;
            $flat_list = $this->getFlatIdByRfid($rfid_key);

            if (count($flat_list) == 0)
                return false;
        }

        if ($this->type == plog::EVENT_OPENED_BY_CODE) {
            $event_data[plog::COLUMN_OPENED] = 1;
            $open_code = $this->detail;
            $event_data[plog::COLUMN_CODE] = $open_code;
            $flat_list = $this->getFlatIdByCode($open_code);

            if (count($flat_list) == 0)
                return false;
        }

        if ($this->type == plog::EVENT_OPENED_BY_APP) {
            $event_data[plog::COLUMN_OPENED] = 1;
            $user_phone = $this->detail;
            $event_data[plog::COLUMN_PHONES]['user_phone'] = $user_phone;
            $flat_list = $this->getFlatIdByUserPhone($user_phone);

            if (!$flat_list || count($flat_list) == 0)
                return false;
        }

        if ($this->type == plog::EVENT_OPENED_BY_FACE) {
            $event_data[plog::COLUMN_OPENED] = 1;

            $details = explode("|", $this->detail);

            $face_id = $details[0];
            $event_id = $details[1];

            $households = backend('households');

            $entrance = $households->getEntrances("domophoneId", ["domophoneId" => $this->id, "output" => $this->door])[0];

            $frs = backend("frs");

            if ($frs)
                $flat_list = $frs->getFlatsByFaceId($face_id, $entrance["entranceId"]);

            if (!$flat_list || count($flat_list) == 0)
                return false;
        }

        $image_data = $plog->getCamshot($this->id, $this->date, $event_id);

        if ($image_data) {
            if (isset($image_data[plog::COLUMN_IMAGE_UUID]))
                $event_data[plog::COLUMN_IMAGE_UUID] = $image_data[plog::COLUMN_IMAGE_UUID];

            $event_data[plog::COLUMN_PREVIEW] = $image_data[plog::COLUMN_PREVIEW];

            if (isset($image_data[plog::COLUMN_FACE])) {
                $event_data[plog::COLUMN_FACE] = $image_data[plog::COLUMN_FACE];

                if (isset($face_id))
                    $event_data[plog::COLUMN_FACE][frs::P_FACE_ID] = $face_id;
            }
        }

        $plog->writeEventData($event_data, $flat_list);

        return true;
    }

    public function onError(Throwable $throwable): void
    {
        logger('task')->debug('PlogOpenTask error' . PHP_EOL . $throwable);

        if ($this->retry < 3)
            task(new PlogOpenTask($this->id, $this->type, $this->door, $this->date, $this->detail))->low()->delay(300)->dispatch();
    }
}