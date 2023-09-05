<?php

namespace Selpol\Kernel\Runner;

use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Selpol\Cache\FileCache;
use Selpol\Container\ContainerBuilder;
use Selpol\Kernel\Kernel;
use Selpol\Kernel\KernelRunner;
use Selpol\Logger\EchoLogger;
use Selpol\Logger\GroupLogger;
use Selpol\Router\RouterBuilder;
use Selpol\Service\DatabaseService;
use Selpol\Task\Tasks\IntercomConfigureTask;
use Selpol\Task\Tasks\ReindexTask;
use Throwable;

class CliRunner implements KernelRunner
{
    private array $argv;

    private LoggerInterface $logger;

    public function __construct(array $argv, ?LoggerInterface $logger = null)
    {
        $this->argv = $argv;

        $this->logger = $logger ?? new GroupLogger([new EchoLogger(), logger('cli')]);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     */
    function __invoke(Kernel $kernel): int
    {
        $arguments = $this->getArguments();

        if ($this->isCommand($arguments, '--init-db')) $this->initDb();
        else if ($this->isCommand($arguments, '--cleanup')) $this->cleanup();
        else if ($this->isCommand($arguments, '--reindex')) $this->reindex();
        else if ($this->isCommand($arguments, '--clear-cache')) $this->clearCache();
        else if ($this->isCommand($arguments, '--admin-password', true)) $this->adminPassword($arguments['--admin-password']);
        else if ($this->isCommand($arguments, '--cron', true)) $this->cron($arguments);
        else if ($this->isCommand($arguments, '--install-crontabs')) $this->installCron();
        else if ($this->isCommand($arguments, '--uninstall-crontabs')) $this->uninstallCron();
        else if ($this->isCommand($arguments, '--clear-kernel')) $this->clearKernel();
        else if ($this->isCommand($arguments, '--container-kernel')) $this->containerKernel();
        else if ($this->isCommand($arguments, '--router-kernel')) $this->routerKernel();
        else if ($this->isCommand($arguments, '--optimize-kernel')) $this->optimizeKernel();
        else if ($this->isCommand($arguments, '--check-backends')) $this->checkBackends();
        else if ($this->isCommand($arguments, '--intercom-configure-task', true, 2)) $this->intercomConfigureTask($arguments);
        else echo $this->help();

        return 0;
    }

    public function onFailed(Throwable $throwable, bool $fatal): int
    {
        echo $throwable->getMessage();

        $this->logger->error($throwable, ['fatal' => $fatal]);

        return 0;
    }

    private function getArguments(): array
    {
        $args = [];

        for ($i = 1; $i < count($this->argv); $i++) {
            $a = explode('=', $this->argv[$i]);

            $args[$a[0]] = @$a[1];
        }

        return $args;
    }

    private function isCommand(array $arguments, string $command, bool $isset = false, int $max = 1): bool
    {
        return (count($arguments) <= $max) && array_key_exists($command, $arguments) && ($isset ? isset($arguments[$command]) : !isset($arguments[$command]));
    }

    /**
     * @throws Exception
     * @throws ContainerExceptionInterface
     */
    private function initDb(): void
    {
        require_once path('sql/install.php');

        init_db();

        $n = clear_cache(true);

        echo "$n cache entries cleared\n\n";

        task(new ReindexTask())->sync();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function cleanup(): void
    {
        $backends = config('backends');

        foreach ($backends as $backend => $_) {
            $b = backend($backend);

            if ($b) {
                $n = $b->cleanup();

                echo "$backend: $n items cleaned\n";
            } else echo "$backend: not found\n";
        }
    }

    /**
     * @throws Exception
     */
    private function reindex(): void
    {
        $n = clear_cache(true);
        echo "$n cache entries cleared\n";

        task(new ReindexTask())->sync();
    }

    private function clearCache(): void
    {
        $n = clear_cache(true);

        echo "$n cache entries cleared\n";
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function adminPassword(string $password): void
    {
        $db = container(DatabaseService::class);

        try {
            $db->exec("insert into core_users (uid, login, password) values (0, 'admin', 'admin')");
        } catch (Exception) {
        }

        try {
            $sth = $db->prepare("update core_users set password = :password, login = 'admin', enabled = 1 where uid = 0");
            $sth->execute([":password" => password_hash($password, PASSWORD_DEFAULT)]);

            $this->logger->debug('Update admin password');

            echo "admin account updated\n";
        } catch (Exception) {
            echo "admin account update failed\n";
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function cron(array $arguments): void
    {
        $parts = ["minutely", "5min", "hourly", "daily", "monthly"];
        $part = false;

        foreach ($parts as $p)
            if (in_array($p, $arguments)) {
                $part = $p;

                break;
            }

        if ($part) {
            $start = microtime(true) * 1000;
            $cronBackends = config('backends');

            $this->logger->debug('Processing cron', ['part' => $part, 'backends' => array_keys($cronBackends)]);

            foreach ($cronBackends as $backend_name => $cfg) {
                $backend = backend($backend_name);

                if ($backend) {
                    try {
                        if ($backend->cron($part))
                            $this->logger->debug('Success', ['backend' => $backend_name, 'part' => $part]);
                        else
                            $this->logger->error('Fail', ['backend' => $backend_name, 'part' => $part]);
                    } catch (Exception $e) {
                        $this->logger->error('Error cron' . PHP_EOL . $e, ['backend' => $backend_name, 'part' => $part]);
                    }
                } else $this->logger->error('Backend not found', ['backend' => $backend_name, 'part' => $part]);
            }

            $this->logger->debug('Cron done', ['ellapsed_ms' => microtime(true) * 1000 - $start]);
        } else echo $this->help();
    }

    private function installCron(): void
    {
        $crontab = [];

        exec("crontab -l", $crontab);

        $clean = [];
        $skip = false;

        $cli = PHP_BINARY . " " . __FILE__ . " --cron";

        $lines = 0;

        foreach ($crontab as $line) {
            if ($line === "## RBT crons start, dont touch!!!")
                $skip = true;

            if (!$skip)
                $clean[] = $line;

            if ($line === "## RBT crons end, dont touch!!!")
                $skip = false;
        }

        $clean = explode("\n", trim(implode("\n", $clean)));

        $clean[] = "";

        $clean[] = "## RBT crons start, dont touch!!!";
        $lines++;
        $clean[] = "*/1 * * * * $cli=minutely";
        $lines++;
        $clean[] = "*/5 * * * * $cli=5min";
        $lines++;
        $clean[] = "1 */1 * * * $cli=hourly";
        $lines++;
        $clean[] = "1 1 */1 * * $cli=daily";
        $lines++;
        $clean[] = "1 1 1 */1 * $cli=monthly";
        $lines++;
        $clean[] = "## RBT crons end, dont touch!!!";
        $lines++;

        file_put_contents(sys_get_temp_dir() . "/rbt_crontab", trim(implode("\n", $clean)));

        system("crontab " . sys_get_temp_dir() . "/rbt_crontab");

        echo "$lines crontabs lines added\n";

        $this->logger->debug('Install crontabs', ['lines' => $lines]);
    }

    private function uninstallCron(): void
    {
        $crontab = [];

        exec("crontab -l", $crontab);

        $clean = [];
        $skip = false;

        $lines = 0;

        foreach ($crontab as $line) {
            if ($line === "## RBT crons start, dont touch!!!")
                $skip = true;

            if (!$skip) $clean[] = $line;
            else $lines++;

            if ($line === "## RBT crons end, dont touch!!!")
                $skip = false;
        }

        $clean = explode("\n", trim(implode("\n", $clean)));

        file_put_contents(sys_get_temp_dir() . "/rbt_crontab", trim(implode("\n", $clean)));

        system("crontab " . sys_get_temp_dir() . "/rbt_crontab");

        echo "$lines crontabs lines removed\n";

        $this->logger->debug('Uninstall crontabs', ['lines' => $lines]);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function clearKernel(): void
    {
        $cache = container(FileCache::class);

        $cache->clear();

        $this->logger->debug('Kernel cleared');
    }

    private function containerKernel(): void
    {
        if (file_exists(path('config/container.php'))) {
            $callback = require path('config/container.php');
            $builder = new ContainerBuilder();
            $callback($builder);

            $factories = $builder->getFactories();

            $headers = ['TYPE', 'ID', 'FACTORY'];
            $result = [];

            foreach ($factories as $id => $factory)
                $result[] = ['TYPE' => $factory[0] ? 'SINGLETON' : 'FACTORY', 'ID' => $id, 'FACTORY' => $factory[1] ?: ''];

            $this->logger->debug('CONTAINER TABLE:');
            $this->logger->debug($this->table($headers, $result));
        }
    }

    private function routerKernel(): void
    {
        if (file_exists(path('config/router.php'))) {
            $callback = require path('config/router.php');
            $builder = new RouterBuilder();
            $callback($builder);

            $routes = $builder->collect();

            var_dump($routes);
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     */
    private function optimizeKernel(): void
    {
        $cache = container(FileCache::class);

        $cache->set('env', load_env());
        $cache->set('config', load_config());

        if (file_exists(path('config/container.php'))) {
            $callback = require path('config/container.php');
            $builder = new ContainerBuilder();
            $callback($builder);

            $cache->set('container', $builder->getFactories());
        }

        if (file_exists(path('config/router.php'))) {
            $callback = require path('config/router.php');
            $builder = new RouterBuilder();
            $callback($builder);

            $cache->set('router', $builder->collect());
        }

        $this->logger->debug('Kernel optimized');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function checkBackends(): void
    {
        $backends = config('backends');

        $all_ok = true;

        foreach ($backends as $backend => $null) {
            $t = backend($backend);

            if (!$t) {
                echo "loading $backend failed\n";

                $all_ok = false;
            } else {
                try {
                    if (!$t->check()) {
                        echo "error checking backend $backend\n";

                        $all_ok = false;
                    }
                } catch (Exception $e) {
                    print_r($e);

                    $all_ok = false;
                }
            }
        }

        if ($all_ok)
            echo "everything is all right\n";
    }

    /**
     * @throws Exception
     */
    private function intercomConfigureTask(array $arguments): void
    {
        $id = $arguments['--intercom-configure-task'];
        $first = array_key_exists('--first', $arguments);

        task(new IntercomConfigureTask($id, $first))->sync();
    }

    private function help(): string
    {
        return "initialization:
            [--init-db]
            [--admin-password=<password>]
            [--reindex]
            [--clear-cache]
            [--cleanup]

        kernel:
            [--clear-kernel]
            [--container-kernel]
            [--router-kernel]
            [--optimize-kernel]

        tests:
            [--check-backends]

        cron:
            [--cron=<minutely|5min|hourly|daily|monthly>]
            [--install-crontabs]
            [--uninstall-crontabs]

        intercom:
            [--intercom-configure-task=<id> [--first]]
        \n";
    }

    /**
     * @param string[] $headers
     * @param array $values
     * @return string
     */
    private function table(array $headers, array $values): string
    {
        $mask = array_reduce($headers, static function (string $previous, string $header) use ($values) {
                $max = strlen($header);

                foreach ($values as $value) {
                    if (strlen($value[$header]) > $max)
                        $max = strlen($value[$header]);
                }

                return $previous . ' | %' . $max . '.' . $max . 's';
            }, '') . ' | ';

        $result = sprintf($mask, ...$headers);
        $result .= PHP_EOL . str_repeat('-', strlen($result)) . PHP_EOL;

        foreach ($values as $value)
            $result .= sprintf($mask, ...array_map(static fn(string $header) => $value[$header], $headers)) . PHP_EOL;

        return $result;
    }
}