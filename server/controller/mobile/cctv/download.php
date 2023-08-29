<?php

/**
 * @api {get} /cctv/download скачать файл
 * @apiVersion 1.0.0
 * @apiDescription ***почти готов***
 *
 * @apiGroup CCTV
 *
 * @apiParam {String} id идентификатор файла
 */
$contentType = "video/mp4";
$files = loadBackend("files");
$stream = $files->getFileStream($param);
$info = $files->getFileInfo($param);
$fileName = $info['filename'];

header("Content-type: $contentType");
header("Content-Disposition: attachment; filename=$fileName");

$begin  = 0;
$size = $info['length'];
$end  = $size - 1;

if (isset($_SERVER['HTTP_RANGE'])) {
    if (preg_match('/bytes=\h*(\d+)-(\d*)[\D.*]?/i', $_SERVER['HTTP_RANGE'], $matches)) {
        $begin  = intval($matches[1]);
        if (!empty($matches[2])) {
            $end  = intval($matches[2]);
        }
    }
    header('HTTP/1.1 206 Partial Content');
    header("Content-Range: bytes $begin-$end/$size");
} else {
    header('HTTP/1.1 200 OK');
}
$new_length = $end - $begin + 1;
header('Cache-Control: public, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Accept-Ranges: bytes');
header('Content-Length:' . $new_length);
header('Content-Transfer-Encoding: binary');

$chunksize = 1024*1024; //you may want to change this
$bytes_send = 0;
if ($stream) {
    if (isset($_SERVER['HTTP_RANGE'])) {
        fseek($stream, $begin);
    }

    while (!feof($stream) && (!connection_aborted()) && ($bytes_send < $new_length) ) {
        $buffer = fread($stream, $chunksize);
        echo($buffer);
        flush();
        $bytes_send += strlen($buffer);
    }
    fclose($stream);
} else {
    throw new \Exception('Error - can not open file.');
}

exit(0);