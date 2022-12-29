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
                // Custom realisation
                return 'your custom token';
            }

            
        }
    }
