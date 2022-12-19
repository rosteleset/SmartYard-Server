<?php

    /**
     * backends frs namespace
     */

    namespace backends\frs
    {
        class internal extends frs
        {

            /**
             * @inheritDoc
             */
            public function servers()
            {
                return $this->config["backends"]["frs"]["servers"];
            }
        }
    }
