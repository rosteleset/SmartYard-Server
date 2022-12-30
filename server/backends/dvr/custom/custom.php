<?php
    namespace backends\dvr
    {
        require_once __DIR__ . "/../internal/internal.php";
    
        class custom extends internal
        {
            
            /**
             * @inheritDoc
             */
            public function getDVRTokenForCam($cam, $subscriberId)
            {

                $result = $cam['comment'] ?: '';


                return $result;
            }

            
        }
    }
