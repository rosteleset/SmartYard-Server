<?php

    /**
     * @api {post} /cctv/allTree получить дерево камер
     * @apiVersion 1.0.0
     * @apiDescription ***почти готов***
     *
     * @apiGroup CCTV
     *
     * @apiBody {Number} [houseId] идентификатор дома
     *
     * @apiHeader {String} authorization токен авторизации
     *
     * @apiSuccess {Object} - корневая группа камер
     * @apiSuccess {Number} [-.groupId] уникальный идентификатор группы
     * @apiSuccess {String} [-.groupName] наименование группы
     * @apiSuccess {String="map","list"} [-.type] тип представления группы (по умолчанию map)
     * @apiSuccess {Object[]} [-.childGroups] массив вложенных групп с такой же структурой
     * @apiSuccess {Object[]} [-.cameras] массив камер со структурой из метода all
     */


    auth();

    $house_id = (int)@$postdata['houseId'];
    $households = loadBackend("households");

    require_once __DIR__ . "/helpers/listCameras.php";
    // at this point variable $ret contains camera data

    $data["groupId"] = 0;
    $data["type"] = "list";
    $paths = [];
    $path_to_cameras = [];
    foreach ($ret as $cam) {
        if (isset($cam["path"])) {
            $paths[] = $households->getPath($cam['path'], true);
            $path_to_cameras[$cam['path']][] = $cam;
        } else {
            $data["cameras"][] = $cam;
        }
    }
    $r = $households->mergePaths($paths);

    if (count($r) && count($r[0])) {
        function traverseTree($tree): array
        {
            global $path_to_cameras;
            $t = [
                "groupId" => $tree["id"],
                "groupName" => $tree["text"],
                "type" => "list",
            ];
            if (isset($path_to_cameras[$tree["id"]])) {
                $t["cameras"] = $path_to_cameras[$tree["id"]];
            }
            if (isset($tree["children"]) && $tree["children"] !== false)
                if (count($tree["children"])) {
                    foreach ($tree["children"] as $child) {
                        $t["childGroups"][] = traverseTree($child);
                    }
                }
            return $t;
        }
        foreach ($r[0] as $item) {
            $data["childGroups"][] = traverseTree($item);
        }

        // remove unnecessary items
        function removeUnnecessaryItems(&$data): void
        {
            if (isset($data["childGroups"]) && count($data["childGroups"])) {
                foreach ($data["childGroups"] as $key => &$item) {
                    removeUnnecessaryItems($item);
                    if ((!isset($item["cameras"]) || count($item["cameras"]) == 0) && (!isset($item["childGroups"]) || count($item["childGroups"]) == 0)) {
                        unset($data["childGroups"][$key]);
                    }
                }
            }
        }
        removeUnnecessaryItems($data);
    }

    if (count($data)) {
        response(200, $data);
    } else {
        response();
    }
