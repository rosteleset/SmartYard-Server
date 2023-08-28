<?php

namespace Selpol\Task\Tasks\Plog;

use Selpol\Task\Task;

abstract class PlogTask extends Task
{
    /** @var int Идентификатор устройства */
    public int $id;

    protected function getDomophoneDescription($domophone_output)
    {
        $households = loadBackend('households');

        $result = $households->getEntrances('domophoneId', ['domophoneId' => $this->id, 'output' => $domophone_output]);

        if ($result && $result[0])
            return $result[0]['entrance'];

        return false;
    }

    protected function getFlatIdByRfid($rfid): array
    {
        $households = loadBackend('households');

        $flats1 = array_map('self::getFlatId', $households->getFlats('rfId', ['rfId' => $rfid]));
        $flats2 = array_map('self::getFlatId', $households->getFlats('domophoneId', $this->id));

        return array_intersect($flats1, $flats2);
    }

    protected function getFlatIdByCode($code): array
    {
        $households = loadBackend('households');

        $flats1 = array_map('self::getFlatId', $households->getFlats('openCode', ['openCode' => $code]));
        $flats2 = array_map('self::getFlatId', $households->getFlats('domophoneId', $this->id));

        return array_intersect($flats1, $flats2);
    }

    protected function getFlatIdByUserPhone($user_phone): bool|array
    {
        $households = loadBackend('households');

        $result = $households->getSubscribers('mobile', $user_phone);

        if ($result && $result[0]) {
            $flats1 = array_map('self::getFlatId', $households->getFlats('subscriberId', ['id' => $user_phone]));
            $flats2 = array_map('self::getFlatId', $households->getFlats('domophoneId', $this->id));

            return array_intersect($flats1, $flats2);
        }

        return false;
    }

    protected function getFlatIdByPrefixAndNumber($prefix, $flat_number)
    {
        $households = loadBackend('households');
        $result = $households->getFlats('flatIdByPrefix', ['prefix' => $prefix, 'flatNumber' => $flat_number, 'domophoneId' => $this->id]);

        if ($result && $result[0])
            return $result[0]['flatId'];

        return false;
    }

    protected function getFlatIdByNumber($flat_number)
    {
        $households = loadBackend('households');
        $result = $households->getFlats('apartment', ['domophoneId' => $this->id, 'flatNumber' => $flat_number]);

        if ($result && $result[0])
            return $result[0]['flatId'];

        return false;
    }

    protected function getFlatIdByDomophoneId()
    {
        $households = loadBackend('households');
        $result = $households->getFlats('domophoneId', $this->id);

        // Only if one apartment is linked
        if ($result && count($result) === 1 && $result[0])
            return $result[0]['flatId'];

        return false;
    }

    protected function getEntranceCount($flat_id)
    {
        $households = loadBackend('households');
        $result = $households->getEntrances('flatId', $flat_id);

        if ($result)
            return count($result);

        return 0;
    }

    protected function getFlatId($item)
    {
        return $item['flatId'];
    }
}