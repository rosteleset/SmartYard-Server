<?php

/**
 * backends tasks namespace
 */

namespace backends\tasks {

    use backends\backend;

    /**
     * base sip class
     */
    abstract class tasks extends backend
    {
        /**
         * Получить текущее состояние задач
         * @return array
         */
        public abstract function getStatus(): array;
    }
}
