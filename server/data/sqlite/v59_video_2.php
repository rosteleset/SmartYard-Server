<?php

function v59_video_2($db) {
    try {
        $rows = 0;

        $videos = $db->get("select house_domophone_id, video from houses_entrances");

        foreach ($videos as $video) {
            $db->modify("update houses_domophones set video = :video where house_domophone_id = " . $video["house_domophone_id"], [
                "video" => $video["video"],
            ]);
            $rows++;
        }

        echo "updated $rows rows\n";

        return true;
    } catch (\PDOException $e) {
        echo "Error executing query: " . $e->getMessage();
        return false;
    }
}
