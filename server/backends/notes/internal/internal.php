<?php

    /**
     * backends notes namespace
     */

    namespace backends\notes {

        /**
         * internal notes class
         */

        class internal extends notes {

            /**
             * @inheritDoc
             */
            public function getNotes()
            {

            }

            /**
             * @inheritDoc
             */
            public function addNote($subject, $body, $checks, $category, $remind, $icon, $font, $color)
            {

            }

            /**
             * @inheritDoc
             */
            public function modifyNote11($id, $subject, $body, $category, $remind, $icon, $font, $color, $x, $y, $z)
            {

            }

            /**
             * @inheritDoc
             */
            public function modifyNote4($id, $x, $y, $z)
            {

            }

            /**
             * @inheritDoc
             */
            public function deleteNote($id)
            {

            }

            public function __call($method, $arguments) {
                if($method == 'modifyNote') {
                    if(count($arguments) == 11) {
                       return call_user_func_array([ $this, 'modifyNote11' ], $arguments);
                    }
                    else if(count($arguments) == 4) {
                       return call_user_func_array([ $this, 'modifyNote4' ], $arguments);
                    }
                }
             }
        }
    }
