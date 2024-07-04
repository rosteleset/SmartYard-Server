<?php

function v35_mult_2($db)
{
    try {

        $flatsSubscribers = $db->get("
            SELECT
                house_flat_id,
                house_subscriber_id,
                voip_enabled
            FROM
                houses_flats_subscribers
        ");

        $stmt = $db->prepare("
            INSERT INTO houses_flats_devices (
                house_flat_id,
                subscriber_device_id,
                voip_enabled
            ) VALUES (
                :house_flat_id,
                :subscriber_device_id,
                :voip_enabled
            )
        ");

        $createdRows = 0;

        foreach ($flatsSubscribers as $flatSubscriber) {
            $devices = $db->get("
                SELECT
                    subscriber_device_id
                FROM
                    houses_subscribers_devices
                WHERE
                    house_subscriber_id = :house_subscriber_id
            ", [':house_subscriber_id' => $flatSubscriber['house_subscriber_id']]);

            foreach ($devices as $device) {
                $stmt->execute([
                    ':house_flat_id' => $flatSubscriber['house_flat_id'],
                    ':subscriber_device_id' => $device['subscriber_device_id'],
                    ':voip_enabled' => $flatSubscriber['voip_enabled']
                ]);

                $createdRows += $stmt->rowCount();
            }
        }

        echo "created $createdRows rows";
        return true;
    } catch (PDOException $e) {
        echo "Error executing query: " . $e->getMessage();
        return false;
    }
}
