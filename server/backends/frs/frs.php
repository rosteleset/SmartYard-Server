<?php

    /**
    * backends frs namespace
    */

    namespace backends\frs
    {

        use backends\backend;

        /**
         * base frs class
         */
        abstract class frs extends backend
        {
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

            //other
            const PDO_SINGLIFY = "singlify";
            const PDO_FIELDLIFY = "fieldlify";

            const FLAG_CAN_LIKE = "canLike";
            const FLAG_CAN_DISLIKE = "canDislike";
            const FLAG_LIKED = "liked";

            //FRS API methods calls

            /**
             * @return mixed
             */
            abstract public function servers();

            /**
             * Call API method
             * @param string $base_url base URL FRS
             * @param string $method API method name
             * @param obect $params call parameters
             * @return false|object
             */
            abstract public function apiCall($base_url, $method, $params);

            /**
             * Add video stream to FRS
             * @param object $cam camera object
             * @param array $faces array of faceId
             * @param array $params array of setup parameters for video stream
             * @return object
             */
            abstract public function addStream($cam, array $faces = [], array $params = []);

            /**
             * Call API method bestQuality by date
             * @param object $cam camera object
             * @param int $date host event's timestamp
             * @param string $event_uuid host event's UUID
             * @return object
             */
            abstract public function bestQualityByDate($cam, $date, string $event_uuid = "");

            /**
             * Call API method bestQuality by FRS event's identifier
             * @param object $cam camera object
             * @param int $event_id FRS event's identifier
             * @param string $event_uuid host event's UUID
             * @return object
             */
            abstract public function bestQualityByEventId($cam, $event_id, string $event_uuid = "");

            /**
             * Register face by host's event data
             * @param object $cam camera object
             * @param string $event_uuid host event's UUID
             * @param int $left X-coordinate of face's square region
             * @param int $top Y-coordinate of face's square region
             * @param int $width face's region width
             * @param int $height face's region height
             * @return object
             */
            abstract public function registerFace($cam, $event_uuid, $left = 0, $top = 0, $width = 0, $height = 0);

            /**
             * Detach faces from video stream
             * @param object $cam camera object
             * @param array $faces array of face identifiers (face_id)
             * @return object
             */
            abstract public function removeFaces($cam, array $faces);

            /**
             * Motion Detection
             * @param object $cam camera object
             * @param bool $is_start starts or stops motion detection
             * @return object
             */
            abstract public function motionDetection($cam, bool $is_start);

            //RBT methods

            /**
             * Attach face_id to flat and subscriber
             * @param int $face_id
             * @param int $flat_id
             * @param int $house_subscriber_id
             * @return bool
             */
            abstract public function attachFaceId($face_id, $flat_id, $house_subscriber_id);

            /**
             * Detach face_id from all subscriber's flats
             * @param int $face_id
             * @param int $house_subscriber_id
             * @return false|int
             */
            abstract public function detachFaceId($face_id, $house_subscriber_id);

            /**
             * Detach face_id from flat (all subscribers)
             * @param int $face_id
             * @param int $flat_id
             * @return false|int
             */
            abstract public function detachFaceIdFromFlat($face_id, $flat_id);

            /**
             * @param $face_id
             * @param $entrance_id
             * @return array returns a list of flat identifiers
             */
            abstract public function getFlatsByFaceId($face_id, $entrance_id);

            /**
             * Check liked flag
             * @param int $flat_id
             * @param int $subscriber_id
             * @param int $face_id
             * @param string $event_uuid
             * @param bool $is_owner
             * @return bool
             */
            abstract public function isLikedFlag($flat_id, $subscriber_id, $face_id, $event_uuid, $is_owner);

            /**
             * List all liked faces in the flat by subscriber or all faces in the flat for owner
             * @param int $flat_id
             * @param int $subscriber_id
             * @param bool $is_owner
             * @return array
             */
            abstract public function listFaces($flat_id, $subscriber_id, $is_owner = false);

            /**
             * Get registered face_id by event's UUID
             * @param int $event_uuid
             * @return false|int
             */
            abstract public function getRegisteredFaceId($event_uuid);
        }
    }
