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
//                  case "extension":
//                  case "ip":
                    default:
                        return $this->config["backends"]["sip"]["servers"][0];
                }
            }

            /**
             * @inheritDoc
             */
            public function stun($extension)
            {
                if (@$this->config["backends"]["sip"]["stuns"]) {
                    return $this->config["backends"]["sip"]["stuns"][rand(0, count($this->config["backends"]["sip"]["stuns"]) - 1)];
                } else {
                    return false;
                }
            }
        }
    }
