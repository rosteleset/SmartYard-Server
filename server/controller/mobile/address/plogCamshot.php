<?php

$files = backend('files');
$uuid = $files->fromGUIDv4($param);

$bytes = $files->getFileBytes($uuid);

header('Content-Type: image/jpeg');

echo $bytes;

exit;