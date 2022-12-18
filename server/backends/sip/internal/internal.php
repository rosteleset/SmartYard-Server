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
                if ($by == "all") {
                    return $this->config["backends"]["sip"]["servers"];
                } else {
                    return $this->config["backends"]["sip"]["servers"][0];
                }
            }
        }
    }
