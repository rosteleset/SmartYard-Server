<?php

    /**
     * backends plog namespace
     */

    namespace backends\plog
    {

        use backends\backend;

        /**
         * base plog class
         */
        abstract class plog extends backend
        {
            //типы событий
            const EVENT_UNANSWERED_CALL = 1;
            const EVENT_ANSWERED_CALL = 2;
            const EVENT_OPENED_BY_KEY = 3;
            const EVENT_OPENED_BY_APP = 4;
            const EVENT_OPENED_BY_FACE = 5;
            const EVENT_OPENED_BY_CODE = 6;
            const EVENT_OPENED_GATES_BY_CALL = 7;

            //колонки событий
            const COLUMN_DATE = 'date';
            const COLUMN_EVENT_UUID = 'event_uuid';
            const COLUMN_HIDDEN = 'hidden';
            const COLUMN_IMAGE_UUID = 'image_uuid';
            const COLUMN_FLAT_ID = 'flat_id';
            const COLUMN_DOMOPHONE = 'domophone';
            const COLUMN_EVENT = 'event';
            const COLUMN_OPENED = 'opened';
            const COLUMN_FACE = 'face';
            const COLUMN_RFID = 'rfid';
            const COLUMN_CODE = 'code';
            const COLUMN_PHONES = 'phones';
            const COLUMN_PREVIEW = 'preview';

            //типы доступа к журналу событий
            const ACCESS_DENIED = 0;
            const ACCESS_ALL = 1;
            const ACCESS_OWNER_ONLY = 2;
            const ACCESS_RESTRICTED_BY_ADMIN = 3;

            //preview type
            const PREVIEW_NONE = 0;
            const PREVIEW_DVR = 1;
            const PREVIEW_FRS = 2;

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
             * @param string $date день событий
             * @return false|array
             */
            abstract public function getDetailEventsByDay(int $flat_id, string $date);

            /**
             * Get event's detail by UUID
             * @param string $uuid
             * @return false|array
             */
            abstract public function getEventDetails(string $uuid);

            /**
             * Записать данные событий в базу
             * @param array $event_data данные событий
             * @param array $flat_list список идентификаторов квартир
             */
            abstract public function writeEventData($event_data, $flat_list = []);

            /**
             * Получить кадр события с устройства или от FRS на дату (по идентификатору события)
             * @param int $domophone_id идентификатор устройства
             * @param false|string $date дата и время события
             * @param false|int $event_id идентификатор события FRS
             * @return false|object
             */
            abstract public function getCamshot(int $domophone_id, $date, $event_id = false);

            /**
             * Добавить данные открытия двери для последующего формирования события
             * @param int $date timestamp события
             * @param string $ip адрес устройства
             * @param int $event_type тип события
             * @param int $door "выход" устройства
             * @param string $detail детали события в зависимости от типа
             */
            abstract public function addDoorOpenData($date, $ip, $event_type, $door, $detail);

            /**
             * Добавить данные открытия двери для последующего формирования события
             * @param int $date timestamp события
             * @param int $domophone_id идентификатор устройства
             * @param int $event_type тип события
             * @param int $door "выход" устройства
             * @param string $detail детали события в зависимости от типа
             */
            abstract public function addDoorOpenDataById($date, $domophone_id, $event_type, $door, $detail);

            /**
             * @param int $date timestamp события
             * @param string $ip адрес устройства
             * @param (int | null) $call_id идентификатор звонка (beward only)
             */
            abstract public function addCallDoneData($date, $ip, $call_id);
        }
    }
