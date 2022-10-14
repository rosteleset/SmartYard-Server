<?php

/**
 * backends events namespace
 */

namespace backends\events
{
    use backends\backend;
    use DateTimeInterface;

    /**
     * base events class
     */

    abstract class events extends backend
    {
        /**
         * Получить список дней с событиями
         * @param int $flat_id идентификатор квартиры
         * @param array $filter_events фильтр типов событий
         * @return false|array
         */
        abstract public function getEventsDays(int $flat_id, $filter_events);

        /**
         * Получить детальный список событий
         * @param int $flat_id идентификатор квартиры
         * @param string $day день событий
         * @return false|array
         */
        abstract public function getDetailEventsByDay(int $flat_id, string $date);
    }
}
