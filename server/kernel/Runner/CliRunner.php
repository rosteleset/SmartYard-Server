<?php

namespace Selpol\Kernel\Runner;

use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Selpol\Kernel\Kernel;
use Selpol\Kernel\KernelRunner;
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

        $this->logger = $logger ?? logger('cli');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
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
        else if ($this->isCommand($arguments, '--check-backends')) $this->checkBackends();
        else if ($this->isCommand($arguments, '--clear-config')) $this->clearConfig();
        else if ($this->isCommand($arguments, '--intercom-configure-task', true, 2)) $this->intercomConfigureTask($arguments);
        else echo $this->help();

        return 0;
    }

    public function onFailed(Throwable $throwable): int
    {
        echo $throwable->getMessage();

        $this->logger->error($throwable);

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
    private function initDb()
    {
        require_once path('sql/install.php');

        init_db();

        $n = clear_cache(true);

        echo "$n cache entries cleared\n\n";

        task(new ReindexTask())->sync();
    }

    private function cleanup()
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
    private function reindex()
    {
        $n = clear_cache(true);
        echo "$n cache entries cleared\n";

        task(new ReindexTask())->sync();
    }

    private function clearCache()
    {
        $n = clear_cache(true);

        echo "$n cache entries cleared\n";
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function adminPassword(string $password)
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

    private function cron(array $arguments)
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

    private function installCron()
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

    private function uninstallCron()
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

    private function checkBackends()
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

    private function clearConfig()
    {
        if (file_exists(path('var/cache/env.php')))
            unlink(path('var/cache/env.php'));

        if (file_exists(path('var/cache/config.php')))
            unlink(path('var/cache/config.php'));

        env();
        config();

        $this->logger->debug('Clear config and env cache');
    }

    /**
     * @throws Exception
     */
    private function intercomConfigureTask(array $arguments)
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
}