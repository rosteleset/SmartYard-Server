<?php

    function is_executable_pathenv($filename) {
        if (is_executable($filename)) {
            return true;
        }
        if ($filename !== basename($filename)) {
            return false;
        }
        $paths = explode(PATH_SEPARATOR, getenv("PATH"));
        foreach ($paths as $path) {
            if (is_executable($path . DIRECTORY_SEPARATOR . $filename)) {
                return true;
            }
        }
        return false;
    }


