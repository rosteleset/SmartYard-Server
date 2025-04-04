<?php

    /**
     * backends customFields namespace
     */

    namespace backends\customFields {

        use backends\backend;

        /**
         * base customFields class
         */

        abstract class customFields extends backend {

            /**
             * @param string applyTo
             * @param integer id
             *
             * @return mixed
             */

            abstract function getValues($applyTo, $id);

            /**
             * @param string applyTo
             * @param integer id
             * @param mixed $set
             *
             * @return mixed
             */

            abstract function modifyValues($applyTo, $id, $set);

            /**
             * @param string applyTo
             * @param integer id
             *
             * @return mixed
             */

            abstract function deleteValues($applyTo, $id);

            /**
             * @param string applyTo
             * @param string customField
             * @param string value
             *
             * @return mixed
             */

            abstract function searchForValue($applyTo, $field, $value);

            /**
             * @param string applyTo
             * @param integer id
             * @param mixed set
             *
             * @return mixed
             */

            abstract function getFields($applyTo);
        }
    }
