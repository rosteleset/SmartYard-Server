<?php

declare(strict_types=1);

use SesameWare\RbtAgent\Controller;
use SesameWare\RbtAgent\ControllerIdentity;
use SesameWare\RbtAgent\Database;
use SesameWare\RbtAgent\DesiredStateService;
use SesameWare\RbtAgent\GatewayCommandRunner;
use SesameWare\RbtAgent\GatewayKeyStore;
use SesameWare\RbtAgent\GatewayReconciler;
use SesameWare\RbtAgent\JsonLog;

require_once __DIR__ . '/lib/Protocol.php';
require_once __DIR__ . '/lib/ManagedAuthorization.php';
require_once __DIR__ . '/lib/ControllerIdentity.php';
require_once __DIR__ . '/lib/Database.php';
require_once __DIR__ . '/lib/JsonLog.php';
require_once __DIR__ . '/lib/IPv4.php';
require_once __DIR__ . '/lib/DesiredStateService.php';
require_once __DIR__ . '/lib/GatewayCommandRunner.php';
require_once __DIR__ . '/lib/GatewayKeyStore.php';
require_once __DIR__ . '/lib/GatewayReconciler.php';
require_once __DIR__ . '/lib/Controller.php';

function rbtAgentConfigPath(): string
{
    return getenv('RBT_AGENT_RBT_CONFIG') ?: dirname(__DIR__) . '/config/config.json';
}

function rbtAgentIdentityPath(): string
{
    return getenv('RBT_AGENT_IDENTITY') ?: '/var/lib/rbt-agent-controller/controller-identity.json';
}

function rbtAgentLogPath(): string
{
    return getenv('RBT_AGENT_LOG') ?: '/var/log/rbt-agent-controller/events.jsonl';
}

function rbtAgentGatewayStatePath(): string
{
    return getenv('RBT_AGENT_GATEWAY_STATE') ?: '/var/lib/rbt-agent-controller/gateway';
}

function rbtAgentGatewayPublicKeyPath(): string
{
    return getenv('RBT_AGENT_GATEWAY_PUBLIC_KEYS') ?: '/var/lib/rbt-agent-controller/gateway-public';
}

function rbtAgentGatewayLogPath(): string
{
    return getenv('RBT_AGENT_GATEWAY_LOG') ?: '/var/log/rbt-overlay-gateway/events.jsonl';
}

function rbtAgentLog(): JsonLog
{
    static $log = null;
    if ($log === null) {
        $log = new JsonLog(rbtAgentLogPath());
    }
    return $log;
}

function rbtAgentGatewayLog(): JsonLog
{
    static $log = null;
    if ($log === null) {
        $log = new JsonLog(rbtAgentGatewayLogPath());
    }
    return $log;
}

/** @return array<string,mixed> */
function rbtAgentConfig(): array
{
    static $config = null;
    if ($config === null) {
        $config = Database::loadRBTConfig(rbtAgentConfigPath());
    }
    return $config;
}

function rbtAgentController(): Controller
{
    static $controller = null;
    if ($controller === null) {
        $controller = new Controller(
            Database::connect(rbtAgentConfig()),
            ControllerIdentity::loadOrCreate(rbtAgentIdentityPath()),
            rbtAgentLog(),
        );
    }
    return $controller;
}

function rbtAgentDesiredStateService(): DesiredStateService
{
    static $service = null;
    if ($service === null) {
        $service = new DesiredStateService(
            Database::connect(rbtAgentConfig()),
            ControllerIdentity::loadOrCreate(rbtAgentIdentityPath()),
            rbtAgentLog(),
        );
    }
    return $service;
}

function rbtAgentGatewayReconciler(): GatewayReconciler
{
    static $reconciler = null;
    if ($reconciler === null) {
        $runner = new GatewayCommandRunner();
        $stateDirectory = rbtAgentGatewayStatePath();
        $reconciler = new GatewayReconciler(
            Database::connect(rbtAgentConfig()),
            rbtAgentGatewayLog(),
            $stateDirectory,
            $runner,
            rbtAgentGatewayKeyStore(),
        );
    }
    return $reconciler;
}

function rbtAgentGatewayKeyStore(): GatewayKeyStore
{
    static $store = null;
    if ($store === null) {
        $store = new GatewayKeyStore(
            rbtAgentGatewayStatePath() . '/keys',
            new GatewayCommandRunner(),
            rbtAgentGatewayPublicKeyPath(),
        );
    }
    return $store;
}
