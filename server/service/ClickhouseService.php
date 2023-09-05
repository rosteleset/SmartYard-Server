<?php

namespace Selpol\Service;

use Exception;

class ClickhouseService
{
    private string $host;
    private int $port;

    private string $username;
    private string $password;
    private string $database;

    function __construct(string $host, int $port, string $username, string $password, string $database = 'default')
    {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->database = $database;
    }

    function select($query)
    {
        $curl = curl_init();
        $headers = [];

        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: text/plain; charset=UTF-8',
            "X-ClickHouse-User: {$this->username}",
            "X-ClickHouse-Key: {$this->password}",
        ]);

        curl_setopt($curl, CURLOPT_HEADERFUNCTION,
            function ($curl, $header) use (&$headers) {
                $len = strlen($header);
                $header = explode(':', $header, 2);
                if (count($header) < 2)
                    return $len;

                $headers[strtolower(trim($header[0]))][] = trim($header[1]);

                return $len;
            }
        );

        curl_setopt($curl, CURLOPT_POSTFIELDS, trim($query) . " FORMAT JSON");
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, "http://{$this->host}:{$this->port}");
        curl_setopt($curl, CURLOPT_POST, true);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        curl_setopt($curl, CURLOPT_VERBOSE, false);

        try {
            $raw = curl_exec($curl);
            $data = @json_decode($raw, true)['data'];
        } catch (Exception $e) {
            logger('clickhouseService')->error($e);

            return false;
        }
        curl_close($curl);

        if (@$headers['x-clickhouseService-exception-code']) {
            logger('clickhouseService')->error(trim($raw));

            return false;
        }

        return $data;
    }

    function insert($table, $data): bool|string
    {
        $curl = curl_init();
        $headers = [];

        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: text/plain; charset=UTF-8',
            "X-ClickHouse-User: {$this->username}",
            "X-ClickHouse-Key: {$this->password}",
        ]);

        curl_setopt($curl, CURLOPT_HEADERFUNCTION,
            function ($curl, $header) use (&$headers) {
                $len = strlen($header);
                $header = explode(':', $header, 2);
                if (count($header) < 2)
                    return $len;

                $headers[strtolower(trim($header[0]))][] = trim($header[1]);

                return $len;
            }
        );

        $_data = "";

        foreach ($data as $line) {
            $_data .= json_encode($line) . "\n";
        }

        curl_setopt($curl, CURLOPT_POSTFIELDS, $_data);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, "http://{$this->host}:{$this->port}/?query=" . urlencode("INSERT INTO {$this->database}.$table FORMAT JSONEachRow"));
        curl_setopt($curl, CURLOPT_POST, true);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        curl_setopt($curl, CURLOPT_VERBOSE, false);

        try {
            $error = curl_exec($curl);
        } catch (Exception $e) {
            logger('clickhouse-service')->error('Error send command' . PHP_EOL . $e);

            return false;
        }
        curl_close($curl);

        if (@$headers['x-clickhouseService-exception-code']) {
            return $error;
        } else {
            return true;
        }
    }
}