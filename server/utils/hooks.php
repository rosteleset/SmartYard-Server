<?php

    /**
     * scans for available "pre" hooks and run it before main method called
     *
     * can return true for continue executing main method,
     * false for error generation and array for skipping
     * main method and return data to client immediately
     *
     * @param array $params all params passed to api method
     * @return bool|array
     *
     */
    function hook_pre($params) {
        return true;
    }

    /**
     * scans for avaialable "post" hooks and run it after main method called
     *
     * @param array $params
     * @param array $result
     * @return array
     */

    function hook_post($params, $result) {
        return $result;
    }
