<?php

// TODO: Old slow configurator, remove after testing the smart configurator
function autoconfigure_domophone($domophoneId, $firstTime = false)
{
    global $config;

    $households = loadBackend('households');
    $addresses = loadBackend('addresses');
    $configs = loadBackend('configs');
    $sip = loadBackend('sip');

    $domophone = $households->getDomophone($domophoneId);
    if (!$domophone) {
        echo 'Domophone not found' . PHP_EOL;
        exit(1);
    }

    $entrances = $households->getEntrances('domophoneId', ['domophoneId' => $domophoneId, 'output' => '0']);
    if (!$entrances) {
        echo 'This domophone is not linked with any entrance' . PHP_EOL;
        exit(1);
    }

    $asteriskServer = $sip->server('ip', $domophone['server']);
    $cmses = $configs->getCMSes();
    $tickerText = $entrances[0]['callerId'];

    try {
        $panel = loadDevice(
            'domophone',
            $domophone['model'],
            $domophone['url'],
            $domophone['credentials'],
            $firstTime
        );

        $cameraModel = strtolower($domophone['json']['vendor']);

        $camera = loadDevice(
            'camera',
            "$cameraModel.json",
            $domophone['url'],
            $domophone['credentials'],
        );
    } catch (Exception $e) {
        echo $e->getMessage() . "\n";
        exit(1);
    }

    if ($firstTime) {
        $panel->prepare();
        $camera->prepare();
    }

    $ntps = $config['ntp_servers'];
    $ntp = parse_url_ext($ntps[array_rand($ntps)]);
    $ntpServer = $ntp['host'];
    $ntpPort = $ntp['port'] ?? 123;

    $syslogs = $config['syslog_servers'][$domophone['json']['eventServer']];
    $syslog = parse_url_ext($syslogs[array_rand($syslogs)]);
    $syslogServer = $syslog['host'];
    $syslogPort = $syslog['port'] ?? 514;

    $sipUsername = sprintf('1%05d', $domophone['domophoneId']);
    $sipServer = $asteriskServer['ip'];
    $sipPort = $asteriskServer['sip_udp_port'] ?? 5060;

    $nat = (bool)$domophone['nat'];

    $stun = parse_url_ext($sip->stun(''));
    $stunServer = $stun['host'];
    $stunPort = $stun['port'] ?? 3478;

    $audioLevels = [];
    $mainDoorDtmf = $domophone['dtmf'];

    $cmsLevels = explode(',', $entrances[0]['cmsLevels']);
    $cmsModel = (string)@$cmses[$entrances[0]['cms']]['model'];

    $isShared = $entrances[0]['shared'];

    $panel->clean(
        $sipServer,
        $ntpServer,
        $syslogServer,
        $sipUsername,
        $sipPort,
        $ntpPort,
        $syslogPort,
        $mainDoorDtmf,
        $audioLevels,
        $cmsLevels,
        $cmsModel,
        $nat,
        $stunServer,
        $stunPort,
    );

    if (!$isShared) {
        $rawMatrix = $households->getCms($entrances[0]['entranceId']);

        $matrix = array_map(function ($rawMatrix) {
            return [
                'hundreds' => $rawMatrix['cms'],
                'tens' => $rawMatrix['dozen'],
                'units' => $rawMatrix['unit'],
                'apartment' => $rawMatrix['apartment'],
            ];
        }, $rawMatrix);

        $panel->configureMatrix($matrix);
    }

    $links = [];
    $offset = 0;

    foreach ($entrances as $entrance) {
        $flatsRaw = $households->getFlats('houseId', $entrance['houseId']);
        $flats = array_column($flatsRaw, null, 'flat');
        ksort($flats);

        if (!$flats) {
            continue;
        }

        $begin = reset($flats)['flat'];
        $end = end($flats)['flat'];

        $links[] = [
            'address' => $addresses->getHouse($entrance['houseId'])['houseFull'],
            'prefix' => $entrance['prefix'],
            'firstFlat' => $begin,
            'lastFlat' => $end,
        ];

        foreach ($flats as $flat) {
            $flatEntrances = array_filter($flat['entrances'], function ($entrance) use ($domophoneId) {
                return $entrance['domophoneId'] == $domophoneId;
            });

            if ($flatEntrances) {
                $apartment = $flat['flat'];
                $apartmentLevels = $cmsLevels;

                foreach ($flatEntrances as $flatEntrance) {
                    if (isset($flatEntrance['apartmentLevels'])) {
                        $apartmentLevels = explode(',', $flatEntrance['apartmentLevels']);
                    }

                    if ($flatEntrance['apartment'] != $apartment) {
                        $apartment = $flatEntrance['apartment'];
                    }
                }

                $panel->configureApartment(
                    $apartment + $offset,
                    $flat['openCode'] ?: 0,
                    $isShared ? [] : [sprintf('1%09d', $flat['flatId'])],
                    $isShared ? false : $flat['cmsEnabled'],
                    $apartmentLevels,
                );

                $keys = $households->getKeys('flatId', $flat['flatId']);
                $keysToBeAdded = array_column($keys, 'rfId');
                $panel->addRfids($keysToBeAdded);
            }

            if ($flat['flat'] == $end) {
                $offset += $flat['flat'];
            }
        }
    }

    if ($isShared) {
        $panel->configureGate($links);
    }

    $panel->setTickerText($tickerText);
    $panel->syncData();
    $panel->setUnlocked($domophone['locksAreOpen']);

    $camera->configureMotionDetection();
    $camera->setOsdText($tickerText);

    $households->autoconfigDone($domophoneId);
}
