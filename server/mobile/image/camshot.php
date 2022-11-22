<?php
    $mongo = loadBackend('plog');
    $img = $mongo->getEventImage($param);

    if ($img)
    {
        header('Content-Type: image/jpeg');
        echo $img;
        exit;
    }
