<?php

use Selpol\Controller\Internal\ActionController as InternalActionController;
use Selpol\Controller\Internal\FrsController as InternalFrsController;
use Selpol\Controller\mobile\AddressController;
use Selpol\Controller\mobile\ArchiveController;
use Selpol\Controller\mobile\CallController;
use Selpol\Controller\mobile\CameraController;
use Selpol\Controller\mobile\FrsController;
use Selpol\Controller\mobile\InboxController;
use Selpol\Controller\mobile\IntercomController;
use Selpol\Controller\mobile\PlogController;
use Selpol\Controller\mobile\SubscriberController;
use Selpol\Controller\mobile\UserController;
use Selpol\Middleware\InternalMiddleware;
use Selpol\Middleware\JwtMiddleware;
use Selpol\Middleware\MobileMiddleware;
use Selpol\Router\RouterBuilder;

return static function (RouterBuilder $builder) {
    $builder->group('/internal', static function (RouterBuilder $builder) {
        $builder->include(InternalMiddleware::class);

        $builder->group('/actions', static function (RouterBuilder $builder) {
            $builder->get('/getSyslogConfig', [InternalActionController::class, 'getSyslogConfig']);

            $builder->get('/callFinished', [InternalActionController::class, 'callFinished']);
            $builder->get('/motionDetection', [InternalActionController::class, 'motionDetection']);
            $builder->get('/openDoor', [InternalActionController::class, 'openDoor']);
            $builder->get('/setRabbitGates', [InternalActionController::class, 'setRabbitGates']);
        });

        $builder->group('/frs', static function (RouterBuilder $builder) {
            $builder->post('/callback', [InternalFrsController::class, 'callback']);
            $builder->post('/camshot/{id}', [InternalFrsController::class, 'camshot']);
        });
    });

    $builder->group('/mobile', static function (RouterBuilder $builder) {
        $builder->include(JwtMiddleware::class);
        $builder->include(MobileMiddleware::class);

        $builder->group('/address', static function (RouterBuilder $builder) {
            $builder->post('/getAddressList', [AddressController::class, 'getAddressList']);
            $builder->post('/registerQR', [AddressController::class, 'registerQR'], excludes: [MobileMiddleware::class]);

            $builder->post('/intercom', [IntercomController::class, 'intercom']);
            $builder->post('/openDoor', [IntercomController::class, 'openDoor']);
            $builder->post('/resetCode', [IntercomController::class, 'resetCode']);

            $builder->get('/plog', [PlogController::class, 'index']);
            $builder->get('/plogCamshot/{uuid}', [PlogController::class, 'camshot'], excludes: [JwtMiddleware::class, MobileMiddleware::class]);
            $builder->get('/plogDays', [PlogController::class, 'days']);
        });

        $builder->group('/cctv', static function (RouterBuilder $builder) {
            $builder->post('/all', [CameraController::class, 'all']);
            $builder->post('/events', [CameraController::class, 'events']);

            $builder->post('/recPrepare', [ArchiveController::class, 'prepare']);
            $builder->post('/download/{uuid}', [ArchiveController::class, 'download'], excludes: [JwtMiddleware::class, MobileMiddleware::class]);
        });

        $builder->group('/call', static function (RouterBuilder $builder) {
            $builder->get('/camshot/{hash}', [CallController::class, 'camshot']);
            $builder->get('/live/{hash}', [CallController::class, 'live']);
        });

        $builder->group('/frs', static function (RouterBuilder $builder) {
            $builder->get('/{flatId}', [FrsController::class, 'index']);
            $builder->post('/{eventId}', [FrsController::class, 'store']);
            $builder->delete('/', [FrsController::class, 'delete']);
        });

        $builder->group('/inbox', static function (RouterBuilder $builder) {
            $builder->post('/read', [InboxController::class, 'read']);
            $builder->post('/unread', [InboxController::class, 'unread']);
        });

        $builder->group('/subscribers', static function (RouterBuilder $builder) {
            $builder->get('/{flatId}', [SubscriberController::class, 'index']);
            $builder->post('/{flatId}', [SubscriberController::class, 'store']);
            $builder->delete('/{flatId}', [SubscriberController::class, 'delete']);
        });

        $builder->group('/user', static function (RouterBuilder $builder) {
            $builder->post('/ping', [UserController::class, 'ping']);
            $builder->post('/registerPushToken', [UserController::class, 'registerPushToken']);
            $builder->post('/sendName', [UserController::class, 'sendName']);
        });
    });
};