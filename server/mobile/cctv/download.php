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
$filename = $param;
$contentType = "application/octet-stream";
$files = loadBackend("files");
$stream = $files->getFileStream($fileName);
$info = $files->getFileInfo($fileName);

print_r($info);

/*header("Content-type: $contentType");
header("Content-Disposition: attachment; filename=$fileName");

$begin  = 0;
$size = strlen($body);
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

header('Cache-Control: public, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Accept-Ranges: bytes');
header('Content-Length:' . ($size - $begin));
header('Content-Transfer-Encoding: binary');

echo substr($body, $begin, $size - $begin);
*/
exit(0);