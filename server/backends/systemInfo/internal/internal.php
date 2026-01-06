<?php

    /**
     * backends systemInfo namespace
     */

    /**
     * based on Zxce3's Server Dashboard
     * https://dev.to/zxce3/building-a-server-dashboard-with-a-single-php-file-2738
     */

    /**
     * Server Dashboard - Simplified System Information Display
     * This file contains functions to retrieve system information and display it in a server dashboard.
     * The functions include getting basic server info, CPU info, memory usage, disk usage, uptime, load average,
     * network interfaces, and process list.
     *
     * @author Zxce3
     * @version 2.0
     */


    namespace backends\systemInfo {

        /**
         * internal systemInfo class
         */

        class internal extends systemInfo {

            /**
             * @inheritDoc
             */

            public function systemInfo() {

                function formatBytes($bytes, $precision = 2) {
                    $units = [ 'B', 'KB', 'MB', 'GB', 'TB' ];
                    $bytes = max($bytes, 0);
                    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
                    $pow = min($pow, count($units) - 1);
                    $bytes /= pow(1024, $pow);
                    return round($bytes, $precision) . ' ' . $units[$pow];
                }

                function getBasicInfo() {
                    return [
                        'Hostname' => gethostname(),
                        'OS' => php_uname('s') . ' ' . php_uname('r'),
                        'PHP Version' => phpversion(),
                        'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
                    ];
                }

                function getCPUInfo() {
                    if (!file_exists('/proc/cpuinfo') || !file_exists('/proc/stat')) return ['CPU Info' => 'Not available'];
                    $cpu_info = file_get_contents('/proc/cpuinfo');
                    preg_match('/model name\s+:\s+(.+)$/m', $cpu_info, $model);
                    preg_match_all('/^processor\s+:\s+\d+$/m', $cpu_info, $cores);
                    $stat_info = file_get_contents('/proc/stat');
                    preg_match('/cpu\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/', $stat_info, $cpu_usage);
                    $total_time = array_sum(array_slice($cpu_usage, 1));
                    $idle_time = $cpu_usage[4];
                    $usage_percentage = round((($total_time - $idle_time) / $total_time) * 100, 2);
                    return [
                        'Model' => $model[1] ?? 'Unknown',
                        'Cores' => count($cores[0]),
                        'CPU Usage' => $usage_percentage . '%'
                    ];
                }

                function getMemoryInfo() {
                    if (!file_exists('/proc/meminfo')) return ['Memory Info' => 'Not available'];
                    $meminfo = file_get_contents('/proc/meminfo');
                    preg_match('/MemTotal:\s+(\d+)/', $meminfo, $total);
                    preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $available);
                    $total = isset($total[1]) ? (int)$total[1] * 1024 : 0;
                    $available = isset($available[1]) ? (int)$available[1] * 1024 : 0;
                    $used = $total - $available;
                    return [
                        'Total' => formatBytes($total),
                        'Used' => formatBytes($used),
                        'Available' => formatBytes($available),
                        'Usage' => round(($used / $total) * 100, 2) . '%'
                    ];
                }

                function getDiskInfo() {
                    $disks = [];
                    $df = shell_exec('df -B1');
                    if (!$df) return ['Disk Info' => 'Not available'];
                    foreach (explode("\n", $df) as $line) {
                        if (preg_match('/^\/dev\//', $line)) {
                            $parts = preg_split('/\s+/', $line);
                            if (count($parts) >= 6) {
                                $mount = $parts[5];
                                $disks[$mount] = [
                                    'Device' => $parts[0],
                                    'Total' => formatBytes((int)$parts[1]),
                                    'Used' => formatBytes((int)$parts[2]),
                                    'Available' => formatBytes((int)$parts[3]),
                                    'Usage' => $parts[4]
                                ];
                            }
                        }
                    }
                    return $disks;
                }

                function getUptime() {
                    if (!file_exists('/proc/uptime')) return 'Not available';
                    $uptime = (int)file_get_contents('/proc/uptime');
                    $days = floor($uptime / 86400);
                    $hours = floor(($uptime % 86400) / 3600);
                    $minutes = floor(($uptime % 3600) / 60);
                    return sprintf("%d days, %d hours, %d minutes", $days, $hours, $minutes);
                }

                function getLoadAverage() {
                    $load = sys_getloadavg();
                    return [
                        '1min' => number_format($load[0], 2),
                        '5min' => number_format($load[1], 2),
                        '15min' => number_format($load[2], 2)
                    ];
                }

                function getNetworkInfo() {
                    $interfaces = [];
                    $ifconfig = shell_exec('ifconfig -a');
                    if (!$ifconfig) return ['Network Info' => 'Not available'];
                    preg_match_all('/^(\S+): flags/m', $ifconfig, $matches);
                    foreach ($matches[1] as $interface) {
                        preg_match("/$interface:.*?inet (\d+\.\d+\.\d+\.\d+)/s", $ifconfig, $ip);
                        preg_match("/$interface:.*?ether ([\da-f:]+)/s", $ifconfig, $mac);
                        preg_match("/$interface:.*?RX packets.*?bytes (\d+)/s", $ifconfig, $rx);
                        preg_match("/$interface:.*?TX packets.*?bytes (\d+)/s", $ifconfig, $tx);
                        $interfaces[$interface] = [
                            'IP Address' => $ip[1] ?? 'Not available',
                            'MAC Address' => $mac[1] ?? 'Not available',
                            'RX Data' => isset($rx[1]) ? formatBytes($rx[1]) : 'Not available',
                            'TX Data' => isset($tx[1]) ? formatBytes($tx[1]) : 'Not available'
                        ];
                    }
                    return $interfaces;
                }

                function getProcessList() {
                    $processes = [];
                    $ps = shell_exec('ps aux --sort=-%cpu');
                    if (!$ps) return ['Process List' => 'Not available'];
                    $lines = explode("\n", $ps);
                    array_shift($lines); // Remove header line
                    foreach ($lines as $line) {
                        if (trim($line) === '') continue;
                        $columns = preg_split('/\s+/', $line, 11);
                        if (count($columns) >= 11) {
                            $processes[] = [
                                'User' => $columns[0],
                                'PID' => $columns[1],
                                'CPU' => $columns[2],
                                'Memory' => $columns[3],
                                'Command' => $columns[10]
                            ];
                        }
                    }
                    return $processes;
                }

                return [
                    'basic' => getBasicInfo(),
                    'cpu' => getCPUInfo(),
                    'memory' => getMemoryInfo(),
                    'disk' => getDiskInfo(),
                    'uptime' => getUptime(),
                    'load' => getLoadAverage(),
                    'network' => getNetworkInfo(),
                    'processes' => getProcessList(),
                    'timestamp' => date('Y-m-d H:i:s T')
                ];
            }
        }
    }
