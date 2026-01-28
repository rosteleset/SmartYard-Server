<?php

    /**
     * backends notes namespace
     */

    namespace backends\notes {

        use backends\backend;

        /**
         * base notes class
         */

        abstract class notes extends backend {

            /**
             * @return mixed
             */

            abstract public function getNotes();

            /**
             * @param string $subject
             * @param string $body
             * @param integer $type
             * @param string $category
             * @param integer $remind
             * @param string $icon
             * @param string $font
             * @param string $color
             * @param integer $fyeo
             *
             * @return mixed
             */

            abstract public function addNote($subject, $body, $type, $category, $remind, $icon, $font, $color, $fyeo);

            /**
             * @param integer $id
             * @param string $subject
             * @param string $body
             * @param integer $type
             * @param string $category
             * @param integer $remind
             * @param string $icon
             * @param string $font
             * @param string $color
             * @param integer $fyeo
             *
             * @return mixed
             */

            // or

            /**
             * @param integer $id
             * @param integer $line
             * @param integer $checked
             * @return mixed
             */

            // abstract public function modifyNote($id, ...);

            /**
             * @param integer $id
             * @return mixed
             */

            abstract public function deleteNote($id);

            /**
             * @param array newOrder
             */

            abstract public function reorder($newOrder);
        }
    }