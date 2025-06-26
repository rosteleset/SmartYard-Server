<?php

    /**
     * @api {get} /api/tt/prints/:printId get print's part
     *
     * @apiVersion 1.0.0
     *
     * @apiName getPrint
     * @apiGroup tt
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {String} printId
     * @apiQuery {String} mode
     *
     * @apiSuccess {Mixed} $mode
     */

    /**
     * @api {post} /api/tt/prints/:printId add print
     *
     * @apiVersion 1.0.0
     *
     * @apiName addPrint
     * @apiGroup tt
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {String} [printId]
     * @apiBody {String} mode
     *
     * @apiSuccess {Mixed} $mode
     */

    /**
     * @api {post} /api/tt/prints/:printId modify print
     *
     * @apiVersion 1.0.0
     *
     * @apiName modifyPrint
     * @apiGroup tt
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {String} printId
     * @apiBody {String} mode
     *
     * @apiSuccess {Mixed} $mode
     */

    /**
     * @api {post} /api/tt/prints/:printId delete print
     *
     * @apiVersion 1.0.0
     *
     * @apiName deletePrint
     * @apiGroup tt
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {String} printId
     * @apiBody {String} mode
     *
     * @apiSuccess {Mixed} $mode
     */

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * prints method
         */

        class prints extends api {

            public static function GET($params) {
                $tt = loadBackend("tt");
                $success = false;

                $mode = @$params["mode"];
                switch (@$params["mode"]) {
                    case "data":
                        $success = $tt->printGetData($params["_id"]);
                        break;

                    case "formatter":
                        $success = $tt->printGetFormatter($params["_id"]);
                        break;

                    case "template":
                        $template = $tt->printGetTemplate($params["_id"]);

                        if ($template) {
                            header("Content-Disposition: attachment; filename=" . urlencode($template["name"]));
                            header('Cache-Control: public, must-revalidate, max-age=0');
                            header('Pragma: no-cache');
                            header('Content-Length:' . $template["size"]);
                            header('Content-Transfer-Encoding: binary');

                            echo $template["body"];

                            exit();
                        }
                        break;

                    default:
                        $mode = "prints";
                        $success = $tt->getPrints();
                        break;
                }

                return api::ANSWER($success, ($success !== false) ? $mode : "notAcceptable");
            }

            public static function POST($params) {
                $tt = loadBackend("tt");
                $success = false;

                switch (@$params["mode"]) {
                    case "exec":
                        $success = $tt->printExec($params["_id"], $params["data"]);
                        break;

                    default:
                        $success = $tt->addPrint($params["formName"], $params["extension"], $params["description"]);
                        break;
                }

                return api::ANSWER($success);
            }

            public static function PUT($params) {
                $tt = loadBackend("tt");
                $success = false;

                switch (@$params["mode"]) {
                    case "data":
                        $success = $tt->printSetData($params["_id"], $params["data"]);
                        break;

                    case "formatter":
                        $success = $tt->printSetFormatter($params["_id"], $params["formatter"]);
                        break;

                    case "template":
                        $success = $tt->printSetTemplate($params["_id"], $params["name"], $params["body"]);
                        break;

                    default:
                        $success = $tt->modifyPrint($params["_id"], $params["formName"], $params["extension"], $params["description"]);
                        break;
                }

                return api::ANSWER($success);
            }

            public static function DELETE($params) {
                $tt = loadBackend("tt");
                $success = false;

                switch (@$params["mode"]) {
                    case "template":
                        $success = $tt->printDeleteTemplate($params["_id"]);
                        break;

                    default:
                        $success = $tt->deletePrint($params["_id"]);
                        break;
                }

                return api::ANSWER($success);
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "GET" => "#same(tt,issue,GET)",
                        "POST" => "#same(tt,project,POST)",
                        "PUT" => "#same(tt,project,PUT)",
                        "DELETE" => "#same(tt,project,DELETE)",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
