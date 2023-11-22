<?php

namespace utils\SmartConfigurator\DbConfigCollector;

use backends\addresses\addresses;
use backends\households\households;
use backends\sip\sip;
use DateTime;
use utils\SmartConfigurator\ConfigurationBuilder\DomophoneConfigurationBuilder;

class DomophoneDbConfigCollector implements IDbConfigCollector
{

    /**
     * @var array The application configuration.
     */
    private array $appConfig;

    /**
     * @var array The domophone data.
     */
    private array $domophoneData;

    /**
     * @var DomophoneConfigurationBuilder The builder used to construct domophone configuration.
     */
    private DomophoneConfigurationBuilder $builder;

    /**
     * @var households Households backend object.
     */
    private households $householdsBackend;

    /**
     * @var array Array of entrances associated with domophone.
     */
    private $entrances;

    /**
     * @var array Main entrance associated with domophone.
     */
    private $mainEntrance;

    /**
     * @var mixed
     */
    private $entranceIsShared;

    /**
     * Construct a new DomophoneDbConfigCollector instance.
     *
     * @param array $appConfig The application configuration.
     * @param array $domophoneData The domophone data.
     * @param households|false $householdsBackend Households backend object.
     */
    public function __construct(array $appConfig, array $domophoneData, households $householdsBackend)
    {
        $this->appConfig = $appConfig;
        $this->domophoneData = $domophoneData;
        $this->householdsBackend = $householdsBackend;

        $this->entrances = $householdsBackend->getEntrances('domophoneId', [
            'domophoneId' => $domophoneData['domophoneId'],
            'output' => '0',
        ]) ?? [];

        $this->mainEntrance = $this->entrances[0] ?? [];
        $this->entranceIsShared = $this->mainEntrance['shared'] ?? false;
        $this->builder = new DomophoneConfigurationBuilder();
    }

    public function collectConfig(): array
    {
        $this
            ->addDtmf()
            ->addEventServer()
            ->addNtp()
            ->addSip()
            ->addUnlocked()
        ;

        if ($this->mainEntrance) { // If the domophone is linked to the entrance
            $this
                ->addApartmentsRfidsAndGates()
                ->addCmsLevels()
                ->addCmsModel()
                ->addMatrix()
                ->addTickerText()
            ;
        }

        return $this->builder->getConfig();
    }

    /**
     * Add apartments, RFID keys and gate links to the domophone configuration.
     *
     * @return self
     * @todo: ugly but it works
     */
    private function addApartmentsRfidsAndGates(): self
    {
        /** @var addresses $addresses */
        $addresses = loadBackend('addresses');

        $domophoneId = $this->domophoneData['domophoneId'];
        $offset = 0; // For shared domophones that must contain apartments from several houses
        $gateLinks = [];

        foreach ($this->entrances as $entrance) {
            $flatsRaw = $this->householdsBackend->getFlats('houseId', $entrance['houseId']);
            $flats = array_column($flatsRaw, null, 'flat');
            ksort($flats);

            if (!$flats) {
                continue;
            }

            // Find the first and last apartments
            $flatNumbers = array_column($flats, 'flat');
            $firstFlat = min($flatNumbers);
            $lastFlat = max($flatNumbers);

            // Collect gate link
            $gateLinks[$entrance['prefix']] = [
                'prefix' => $entrance['prefix'],
                'address' => $addresses->getHouse($entrance['houseId'])['houseFull'],
                'firstFlat' => $firstFlat,
                'lastFlat' => $lastFlat,
            ];

            foreach ($flats as $flat) {
                $flatEntrances = array_filter($flat['entrances'], function ($entrance) use ($domophoneId) {
                    return $entrance['domophoneId'] == $domophoneId;
                });

                if ($flatEntrances) {
                    $apartment = $flat['flat'];

                    $rawCmsLevels = $this->mainEntrance['cmsLevels'];

                    $cmsLevels = !empty($rawCmsLevels)
                        ? array_map('intval', explode(',', $rawCmsLevels))
                        : [];

                    $apartmentLevels = $cmsLevels;

                    foreach ($flatEntrances as $flatEntrance) {
                        if (!empty($flatEntrance['apartmentLevels'])) {
                            $apartmentLevels = array_map('intval', explode(',', $flatEntrance['apartmentLevels']));
                        }

                        if ($flatEntrance['apartment'] != $apartment) {
                            $apartment = $flatEntrance['apartment'];
                        }
                    }

                    $apartment += $offset;

                    $this->builder->addApartment(
                        $apartment,
                        $flat['openCode'] ?: 0,
                        $this->entranceIsShared ? [$apartment] : [sprintf('1%09d', $flat['flatId'])],
                        !$this->entranceIsShared && $flat['cmsEnabled'],
                        $apartmentLevels,
                    );

                    // Getting RFID keys for flat
                    $keys = $this->householdsBackend->getKeys('flatId', $flat['flatId']);
                    foreach ($keys as $key) {
                        $this->builder->addRfid($key['rfId']);
                    }
                }

                if ($flat['flat'] == $lastFlat) {
                    $offset += $flat['flat'];
                }
            }
        }

        // Add gate links if the entrance is shared
        if ($this->entranceIsShared) {
            foreach ($gateLinks as $gateLink) {
                $this->builder->addGateLink(...$gateLink);
            }
        }

        return $this;
    }

    /**
     * Add global CMS levels settings to the domophone configuration.
     *
     * @return self
     */
    private function addCmsLevels(): self
    {
        $rawCmsLevels = $this->mainEntrance['cmsLevels'];
        $cmsLevels = !empty($rawCmsLevels)
            ? array_map('intval', explode(',', $rawCmsLevels))
            : [];

        $this->builder->addCmsLevels($cmsLevels);
        return $this;
    }

    /**
     * Add the CMS model to the domophone configuration.
     *
     * @return self
     */
    private function addCmsModel(): self
    {
        $configs = loadBackend('configs');
        $cmses = $configs->getCMSes();
        $cmsFile = $this->mainEntrance['cms'];
        $this->builder->addCmsModel($cmses[$cmsFile]['model'] ?? '');
        return $this;
    }

    /**
     * Add DTMF codes to the domophone configuration.
     *
     * @return self
     */
    private function addDtmf(): self
    {
        $this->builder->addDtmf($this->domophoneData['dtmf'], 2, 3, 1);
        return $this;
    }

    /**
     * Add the event server information to the domophone configuration.
     *
     * @return self
     */
    private function addEventServer(): self
    {
        $url = $this->appConfig['syslog_servers'][$this->domophoneData['json']['eventServer']][0];
        $this->builder->addEventServer($url);
        return $this;
    }

    /**
     * Add the CMS matrix to the domophone configuration.
     *
     * @return self
     */
    private function addMatrix(): self
    {
        $mainEntranceId = $this->mainEntrance['entranceId'];
        $matrix = $this->householdsBackend->getCms($mainEntranceId);

        foreach ($matrix as $cell) {
            $this->builder->addMatrix(...array_values($cell));
        }

        return $this;
    }

    /**
     * Add NTP settings to the domophone configuration.
     *
     * @return self
     */
    private function addNtp(): self
    {
        $ntp = parse_url_ext($this->appConfig['ntp_servers'][0]);
        $server = $ntp['host'];
        $port = $ntp['port'] ?? 123;
        $timezone = $this->findTimezone();

        $this->builder->addNtp($server, $port, $timezone);
        return $this;
    }

    /**
     * Add SIP parameters to the domophone configuration.
     *
     * @return self
     */
    private function addSip(): self
    {
        /** @var sip $sip */
        $sip = loadBackend("sip");

        [
            'server' => $server,
            'domophoneId' => $domophoneId,
            'credentials' => $password,
            'nat' => $natEnabled
        ] = $this->domophoneData;

        $port = 5060;
        $login = sprintf("1%05d", $domophoneId);

        $stun = parse_url_ext($sip->stun(null));
        $stunServer = $stun['host'];
        $stunPort = $stun['port'] ?? 3478;

        $this->builder->addSip(
            $server,
            $port,
            $login,
            $password,
            $natEnabled,
            $stunServer,
            $stunPort
        );

        return $this;
    }

    /**
     * Add the ticker text to the domophone configuration.
     *
     * @return void
     */
    private function addTickerText(): void
    {
        $this->builder->addTickerText($this->mainEntrance['callerId']);
    }

    /**
     * Add unlocked status to the domophone configuration.
     *
     * @return void
     */
    private function addUnlocked(): void
    {
        $this->builder->addUnlocked($this->domophoneData['locksAreOpen']);
    }

    /**
     * Find and return the timezone for the current configuration.
     *
     * This method attempts to find the timezone from various levels of the address hierarchy,
     * starting from the city and falling back to the area and region if necessary.
     * If no timezone is found, it defaults to the system timezone.
     *
     * @return string The timezone identifier.
     */
    private function findTimezone(): string
    {
        /** @var Addresses $addresses */
        $addresses = loadBackend('addresses');

        $house = $addresses->getHouse($this->mainEntrance['houseId'] ?? null);
        $street = $addresses->getStreet($house['streetId'] ?? null);
        $settlement = $addresses->getSettlement($house['settlementId'] ?? null);
        $city = $addresses->getCity($settlement['cityId'] ?? $street['cityId'] ?? null);
        $area = $addresses->getArea($settlement['areaId'] ?? $city['areaId'] ?? null);
        $region = $addresses->getRegion($area['regionId'] ?? $city['regionId'] ?? null);

        $timezone = $city['timezone'] ?? $area['timezone'] ?? $region['timezone'] ?? null;

        if (!$timezone) {
            $date = new DateTime();
            $tz = $date->getTimezone();
            $timezone = $tz->getName();
        }

        return $timezone;
    }
}
