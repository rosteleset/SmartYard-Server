<?php

use Selpol\Controller\Internal\ActionController;
use Selpol\Controller\Internal\FrsController;
use Selpol\Middleware\FrontendMiddleware;
use Selpol\Middleware\InternalMiddleware;
use Selpol\Middleware\MobileMiddleware;
use Selpol\Router\RouterBuilder;

return static function (RouterBuilder $builder) {
    $builder->group('/internal', static function (RouterBuilder $builder) {
        $builder->middleware(InternalMiddleware::class);

        $builder->group('/actions', static function (RouterBuilder $builder) {
            $builder->get('/getSyslogConfig', ActionController::class, 'getSyslogConfig');

            $builder->get('/callFinished', ActionController::class, 'callFinished');
            $builder->get('/motionDetection', ActionController::class, 'motionDetection');
            $builder->get('/openDoor', ActionController::class, 'openDoor');
            $builder->get('/setRabbitGates', ActionController::class, 'setRabbitGates');
        });

        $builder->group('/frs', static function (RouterBuilder $builder) {
            $builder->post('/callback', FrsController::class, 'callback');
            $builder->post('/camshot/{id}', FrsController::class, 'camshot');
        });
    });

    $builder->group('/mobile', static function (RouterBuilder $builder) {
        $builder->middleware(MobileMiddleware::class);
    });

    $builder->group('/frontend', static function (RouterBuilder $builder) {
        $builder->middleware(FrontendMiddleware::class);
    });
};