<?php

    // API for internal usage
    require_once __DIR__ . "/internal/services/router.php";
    require_once __DIR__ . "/internal/routes/routes.php";
    require_once __DIR__ . "/internal/services/response.php";
    require_once __DIR__ . "/internal/actions/actions.php";
    require_once __DIR__ . "/internal/services/access.php";

    use internal\services\Router;

    Router::run();