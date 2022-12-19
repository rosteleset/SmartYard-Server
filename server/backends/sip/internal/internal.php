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
            public function server($by, $query = false)
            {
                switch ($by) {
                    case "all":
                        return $this->config["backends"]["sip"]["servers"];

                    default:
                        return $this->config["backends"]["sip"]["servers"][0];
                }
            }
        }
    }
