<?php

use Selpol\Middleware\FrontendMiddleware;
use Selpol\Middleware\InternalMiddleware;
use Selpol\Middleware\MobileMiddleware;
use Selpol\Router\RouterBuilder;

return static function (RouterBuilder $builder) {
    $builder->group('/internal', static function (RouterBuilder $builder) {
        $builder->middleware(InternalMiddleware::class);

        $builder->group('/actions', static function (RouterBuilder $builder) {
            $builder->get('/getSyslogConfig', path('controller/internal/actions/getSyslogConfig.php'), 'file');

            $builder->post('/callFinished', path('controller/internal/actions/callFinished.php'), 'file');
            $builder->post('/motionDetection', path('controller/internal/actions/motionDetection.php'), 'file');
            $builder->post('/openDoor', path('controller/internal/actions/openDoor.php'), 'file');
            $builder->post('/setRabbitGates', path('controller/internal/actions/setRabbitGates.php'), 'file');
        });

        $builder->group('/frs', static function (RouterBuilder $builder) {
            $builder->post('/callback', path('controller/internal/actions/callback.php'), 'file');

            $builder->get('/camshot/{id}', path('controller/internal/actions/camshot.php'), 'file');
        });
    });

    $builder->group('/mobile', static function (RouterBuilder $builder) {
        $builder->middleware(MobileMiddleware::class);
    });

    $builder->group('/frontend', static function (RouterBuilder $builder) {
        $builder->middleware(FrontendMiddleware::class);
    });
};