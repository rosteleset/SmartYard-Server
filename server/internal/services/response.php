<?php
    
    //internal api response
    namespace internal\services;

    class Response
    {
        /**
         * API response test
         */
        public static function res($httpResCode = 200, $status = false, $message = false)
        {
            header("Content-Type: application/json; charset=UTF-8");
            http_response_code($httpResCode);
            $response = [
                "status" => $status,
                "message" => $message
            ];

            echo json_encode($response);
        }
    }
