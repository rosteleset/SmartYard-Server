<?php

    /**
    * backends frs namespace
    */

    namespace backends\frs {

        use backends\backend;

        /**
         * base frs class
         */

        abstract class frs extends backend {
            //FRS params names
            const P_CODE = "code";
            const P_DATA = "data";
            const P_STREAM_ID = "streamId";
            const P_URL = "url";
            const P_FACE_IDS = "faces";
            const P_CALLBACK_URL = "callback";
            const P_START = "start";
            const P_DATE = "date";
            const P_EVENT_ID = "eventId";
            const P_EVENT_UUID = "uuid";
            const P_SCREENSHOT = "screenshot";
            const P_FACE_LEFT = "left";
            const P_FACE_TOP = "top";
            const P_FACE_WIDTH = "width";
            const P_FACE_HEIGHT = "height";
            const P_FACE_ID = "faceId";
            const P_FACE_IMAGE = "faceImage";
            const P_PARAMS = "params";
            const P_PARAM_NAME = "paramName";
            const P_PARAM_VALUE = "paramValue";
            const P_QUALITY = "quality";
            const P_DATE_START = "dateStart";
            const P_DATE_END = "dateEnd";
            const P_MESSAGE = "message";
            const P_CONFIG = "config";
            const P_AUTH_TOKEN = "authToken";
            const P_HAS_SPECIAL = "hasSpecial";
            const P_PLATES = "plates";
            const P_SCREENSHOT_URL = "screenshotUrl";
            const P_VEHICLES = "vehicles";
            const P_VEHICLE_BOX = "vehicleBox";
            const P_PLATE_KEY_POINTS = "plateKeyPoints";
            const P_PLATE_NUMBER = "plateNumber";
            const P_BOX = "box";
            const P_KPTS = "kpts";
            const P_NUMBER = "number";

            //FRS method names
            const M_ADD_STREAM = "addStream";
            const M_BEST_QUALITY = "bestQuality";
            const M_MOTION_DETECTION = "motionDetection";
            const M_REGISTER_FACE = "registerFace";
            const M_REMOVE_FACES = "removeFaces";
            const M_LIST_STREAMS = "listStreams";
            const M_LIST_ALL_FACES = "listAllFaces";
            const M_DELETE_FACES = "deleteFaces";
            const M_REMOVE_STREAM = "removeStream";
            const M_ADD_FACES = "addFaces";
            const M_START_WORKFLOW = "startWorkflow";
            const M_STOP_WORKFLOW = "stopWorkflow";
            const M_GET_EVENT_DATA = "getEventData";
            const M_SET_STREAM_DEFAULT_CONFIG = "setStreamDefaultConfig";
            const M_GET_STREAM_DEFAULT_CONFIG = "getStreamDefaultConfig";

            //response codes
            const R_CODE_OK = 200;

            //internal params names
            const CAMERA_ID = "cameraId";
            const CAMERA_URL = "url";
            const CAMERA_CREDENTIALS = "credentials";
            const CAMERA_FRS = "frs";
            const FRS_BASE_URL = "url";
            const FRS_STREAMS = "streams";
            const FRS_ALL_FACES = "allFaces";
            const FRS_FACES = "faces";
            const API_TYPE = "api";
            const API_FRS = "frs";
            const API_LPRS = "lprs";

            //other
            const PDO_SINGLIFY = "singlify";
            const PDO_FIELDLIFY = "fieldlify";

            const FLAG_CAN_LIKE = "canLike";
            const FLAG_CAN_DISLIKE = "canDislike";
            const FLAG_LIKED = "liked";

            const C_SCREENSHOT_URL = "screenshot-url";
            const C_CALLBACK_URL = "callback-url";
            const C_WORK_AREA = "work-area";

            //FRS API methods calls

            /**
             * @return mixed
             */

            abstract public function servers();

            abstract public function getServerByUrl($base_url);

            /**
             * Call API method
             * @param string $base_url base URL FRS
             * @param string $method API method name
             * @param array|null $params call parameters
             *
             * @return false|object
             */

            abstract public function apiCallFrs($base_url, $method, $params);

            /**
             * Add video stream to FRS
             * @param object $cam camera object
             * @param array $faces array of faceId
             * @param array $params array of setup parameters for video stream
             *
             * @return object
             */

            abstract public function addStreamFrs($cam, array $faces = [], array $params = []);

            /**
             * Call API method bestQuality by date
             * @param object $cam camera object
             * @param int $date host event's timestamp
             * @param string $event_uuid host event's UUID
             *
             * @return object
             */

            abstract public function bestQualityByDateFrs($cam, $date, string $event_uuid = "");

            /**
             * Call API method bestQuality by FRS event's identifier
             * @param object $cam camera object
             * @param int $event_id FRS event's identifier
             * @param string $event_uuid host event's UUID
             *
             * @return object
             */

            abstract public function bestQualityByEventIdFrs($cam, $event_id, string $event_uuid = "");

            /**
             * Register face by host's event data
             * @param object $cam camera object
             * @param string $event_uuid host event's UUID
             * @param int $left X-coordinate of face's square region
             * @param int $top Y-coordinate of face's square region
             * @param int $width face's region width
             * @param int $height face's region height
             *
             * @return object
             */

            abstract public function registerFaceFrs($cam, $event_uuid, $left = 0, $top = 0, $width = 0, $height = 0);

            /**
             * Detach faces from video stream
             * @param object $cam camera object
             * @param array $faces array of face identifiers (face_id)
             *
             * @return object
             */

            abstract public function removeFacesFrs($cam, array $faces);

            /**
             * Motion Detection
             * @param object $cam camera object
             * @param bool $is_start starts or stops motion detection
             *
             * @return object
             */

            abstract public function motionDetectionFrs($cam, bool $is_start);

            //RBT methods

            /**
             * Attach face_id to flat and subscriber
             * @param int $face_id
             * @param int $flat_id
             * @param int $house_subscriber_id
             *
             * @return bool
             */

            abstract public function attachFaceIdFrs($face_id, $flat_id, $house_subscriber_id): bool;

            /**
             * Detach face_id from all subscriber's flats
             * @param int $face_id
             * @param int $house_subscriber_id
             *
             * @return bool
             */

            abstract public function detachFaceIdFrs($face_id, $house_subscriber_id): bool;

            /**
             * Detach face_id from flat (all subscribers)
             * @param int $face_id
             * @param int $flat_id
             *
             * @return bool
             */

            abstract public function detachFaceIdFromFlatFrs($face_id, $flat_id): bool;

            /**
             * @param $face_id
             * @param $entrance_id
             *
             * @return array returns a list of flat identifiers
             */

            abstract public function getFlatsByFaceIdFrs($face_id, $entrance_id): array;

            /**
             * Check liked flag
             * @param int $flat_id
             * @param int $subscriber_id
             * @param int $face_id
             * @param string $event_uuid
             * @param bool $is_owner
             *
             * @return bool
             */

            abstract public function isLikedFlagFrs($flat_id, $subscriber_id, $face_id, $event_uuid, $is_owner): bool;

            /**
             * List all liked faces in the flat by subscriber or all faces in the flat for owner
             * @param int $flat_id
             * @param int $subscriber_id
             * @param bool $is_owner
             *
             * @return array
             */

            abstract public function listFacesFrs($flat_id, $subscriber_id, $is_owner = false): array;

            /**
             * Get registered face_id by event's UUID
             * @param int $event_uuid
             *
             * @return false|int
             */

            abstract public function getRegisteredFaceIdFrs($event_uuid);

            //LPRS API methods calls

            /**
             * Call API method
             * @param string $base_url base URL LPRS
             * @param string $method API method name
             * @param array|null $params call parameters
             *
             * @return false|object
             */

            abstract public function apiCallLprs($base_url, $method, $params);
        }
    }
