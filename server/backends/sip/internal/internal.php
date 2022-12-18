<?php

    /**
     * backends sip namespace
     */

    namespace backends\sip
    {
        class internal extends sip
        {

            /**
             * @inheritDoc
             */
            public function sipServer($by, $query)
            {
                switch ($by) {
                    case "all":
                        return $this->config["backends"]["sip"]["servers"];

                    case "":
                        break;

                    default:
                        return $this->config["backends"]["sip"]["servers"][0];
                }
            }
        }
    }
