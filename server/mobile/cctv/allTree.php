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
     * @apiSuccess {String="map","list"} [-.type] тип представления группы (по умолчанию list)
     * @apiSuccess {Object[]} [-.childGroups] массив вложенных групп с такой же структурой
     * @apiSuccess {Object[]} [-.cameras] массив камер со структурой из метода all
     * @apiSuccess {Number} [-.cameras.pathOrder] порядок камеры внутри группы
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
    $camera_index = 0;
    foreach ($ret as $cam) {
        $cam["__allTreeIndex"] = $camera_index++;
        if (isset($cam["path"])) {
            $paths[] = $households->getPath($cam['path'], true);
            $path_to_cameras[$cam['path']][] = $cam;
        } else {
            $data["cameras"][] = $cam;
        }
    }

    function sortCamerasByPathOrder(&$cameras): void
    {
        if (!is_array($cameras)) {
            return;
        }

        $hasOrder = false;
        foreach ($cameras as $camera) {
            if (isset($camera["pathOrder"]) && $camera["pathOrder"] !== null && $camera["pathOrder"] !== "") {
                $hasOrder = true;
                break;
            }
        }

        if ($hasOrder) {
            usort($cameras, function ($a, $b) {
                $ao = (isset($a["pathOrder"]) && $a["pathOrder"] !== null && $a["pathOrder"] !== "") ? (int)$a["pathOrder"] : PHP_INT_MAX;
                $bo = (isset($b["pathOrder"]) && $b["pathOrder"] !== null && $b["pathOrder"] !== "") ? (int)$b["pathOrder"] : PHP_INT_MAX;

                if ($ao === $bo) {
                    return ($a["__allTreeIndex"] ?? 0) <=> ($b["__allTreeIndex"] ?? 0);
                }

                return $ao <=> $bo;
            });
        }

        foreach ($cameras as &$camera) {
            unset($camera["__allTreeIndex"]);
        }
    }

    if (isset($data["cameras"])) {
        sortCamerasByPathOrder($data["cameras"]);
    }

    $r = $households->mergePaths($paths);

    if (count($r) && count($r[0])) {
        function traverseTree($tree): array
        {
            global $path_to_cameras;
            $t = [
                "groupId" => $tree["id"],
                "groupName" => $tree["text"],
                "type" => in_array(@$tree["viewType"], [ "list", "map" ]) ? $tree["viewType"] : "list",
            ];
            if (isset($path_to_cameras[$tree["id"]])) {
                $t["cameras"] = $path_to_cameras[$tree["id"]];
                sortCamerasByPathOrder($t["cameras"]);
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
