<?php

namespace Selpol\Controller\mobile;

use backends\plog\plog;
use Selpol\Controller\Controller;
use Selpol\Http\Response;
use Selpol\Validator\Filter;
use Selpol\Validator\Rule;
use Selpol\Validator\ValidatorMessage;

class CameraController extends Controller
{
    public function all(): Response
    {
        $user = $this->getSubscriber();

        $body = $this->request->getParsedBody();

        $house_id = (int)@$body['houseId'];
        $households = backend("households");
        $cameras = backend("cameras");

        $houses = [];

        foreach ($user['flats'] as $flat) {
            if ($flat['addressHouseId'] != $house_id)
                continue;

            $houseId = $flat['addressHouseId'];

            if (array_key_exists($houseId, $houses)) {
                $house = &$houses[$houseId];

            } else {
                $houses[$houseId] = [];
                $house = &$houses[$houseId];
                $house['houseId'] = strval($houseId);

                $house['cameras'] = $households->getCameras("houseId", $houseId);
                $house['doors'] = [];
            }

            $house['cameras'] = array_merge($house['cameras'], $households->getCameras("flatId", $flat['flatId']));

            $flatDetail = $households->getFlat($flat['flatId']);

            foreach ($flatDetail['entrances'] as $entrance) {
                if (array_key_exists($entrance['entranceId'], $house['doors'])) {
                    continue;
                }

                $e = $households->getEntrance($entrance['entranceId']);
                $door = [];

                if ($e['cameraId']) {
                    $cam = $cameras->getCamera($e["cameraId"]);
                    $house['cameras'][] = $cam;
                }

                $house['doors'][$entrance['entranceId']] = $door;
            }
        }

        $result = [];

        foreach ($houses as $house_key => $h) {
            $houses[$house_key]['doors'] = array_values($h['doors']);

            unset($houses[$house_key]['cameras']);

            foreach ($h['cameras'] as $camera) {
                if ($camera['cameraId'] === null)
                    continue;

                $dvr = backend("dvr")->getDVRServerByStream($camera['dvrStream']);

                $result[] = [
                    "id" => $camera['cameraId'],
                    "name" => $camera['name'],
                    "lat" => strval($camera['lat']),
                    "url" => $camera['dvrStream'],
                    "token" => backend("dvr")->getDVRTokenForCam($camera, $user['subscriberId']),
                    "lon" => strval($camera['lon']),
                    "serverType" => $dvr['type']
                ];
            }
        }

        if (count($result))
            return $this->rbtResponse(200, $result);

        return $this->rbtResponse();
    }

    public function events()
    {
        $user = $this->getSubscriber();

        $body = $this->request->getParsedBody();

        $validate = validate($body, [
            'cameraId' => [Rule::required(), Rule::int(), Rule::min(0), Rule::max(), Rule::nonNullable()],
            'date' => [Filter::default(1), Rule::int(), Rule::min(0), Rule::max(14), Rule::nonNullable()]
        ]);

        if ($validate instanceof ValidatorMessage)
            return $this->rbtResponse(400, message: $validate);

        $households = backend("households");
        $plog = backend("plog");

        $domophoneId = $households->getDomophoneIdByEntranceCameraId($validate['cameraId']);

        if (is_null($domophoneId))
            return $this->rbtResponse(404);

        $flats = array_filter(
            array_map(static fn(array $item) => ['id' => $item['flatId'], 'owner' => $item['role'] == 0], $user['flats']),
            static function (array $flat) use ($households) {
                $plog = $households->getFlatPlog($flat['id']);

                return is_null($plog) || $plog == plog::ACCESS_ALL || $plog == plog::ACCESS_OWNER_ONLY && $flat['owner'];
            }
        );

        $flatsId = array_map(static fn(array $item) => $item['id'], $flats);

        if (count($flatsId) == 0)
            return $this->rbtResponse(404);

        $events = $plog->getEventsByFlatsAndDomophone($flatsId, $domophoneId, $validate['date']);

        if ($events)
            return $this->rbtResponse(200, array_map(static fn(array $item) => $item['date'], $events));

        return $this->rbtResponse(200, []);
    }
}