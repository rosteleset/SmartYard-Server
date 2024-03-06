<?php

    $lastError = false;

    function getLastError() {
        global $lastError;

        return $lastError;
    }

    function setLastError($error) {
        global $lastError;

        error_log($error);

        $lastError = $error;
    }