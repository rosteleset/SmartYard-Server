<?php

namespace hw\SmartConfigurator\DbConfigCollector;

use backends\{
    addresses\addresses,
    configs\configs,
    households\households,
    sip\sip,
};
use hw\hw;
use hw\Interface\{
    CmsLevelsInterface,
    DisplayTextInterface,
    FreePassInterface,
    GateModeInterface,
    HousePrefixInterface,
};
use hw\SmartConfigurator\ConfigurationBuilder\DomophoneConfigurationBuilder;
use hw\ValueObject\{
    FlatNumber,
    HousePrefix,
};

/**
 * Class responsible for collecting intercom configuration data from the database.
 */
class DomophoneDbConfigCollector implements DbConfigCollectorInterface
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
     * @var hw
     */
    private hw $device;

    /**
     * Construct a new DomophoneDbConfigCollector instance.
     *
     * @param array $appConfig The application configuration.
     * @param array $domophoneData The domophone data.
     * @param households $householdsBackend Households backend object.
     * @param hw $device Device instance.
     */
    public function __construct(array $appConfig, array $domophoneData, households $householdsBackend, hw $device)
    {
        $this->appConfig = $appConfig;
        $this->domophoneData = $domophoneData;
        $this->device = $device;

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
        if ($this->device instanceof DisplayTextInterface) {
            $this->addDisplayText();
        }

        if ($this->device instanceof FreePassInterface) {
            $this->addFreePassEnabled();
        }

        $this
            ->addApartmentsAndHousePrefixes()
            ->addDtmf()
            ->addEventServer()
            ->addNtp()
            ->addSip()
        ;

        // If the domophone is linked to the entrance
        if ($this->mainEntrance) {
            if ($this->device instanceof CmsLevelsInterface) {
                $this->addCmsLevels();
            }

            $this
                ->addCmsModel()
                ->addMatrix()
                ->addRfids()
            ;
        }

        return $this->builder->getConfig();
    }

    /**
     * Add apartments and house prefixes to the domophone configuration.
     *
     * @return self
     * @todo: ugly but it works
     */
    private function addApartmentsAndHousePrefixes(): self
    {
        /** @var addresses $addresses */
        $addresses = loadBackend('addresses');

        /** @var households $households */
        $households = loadBackend('households');

        $domophoneId = $this->domophoneData['domophoneId'];
        $offset = 0; // For shared domophones that must contain apartments from several houses
        $housePrefixes = [];

        foreach ($this->entrances as $entrance) {
            $flatsRaw = $households->getFlats('houseId', $entrance['houseId']);
            $flats = array_column($flatsRaw, null, 'flat');
            ksort($flats);

            if (!$flats) {
                continue;
            }

            // Find the first and last apartments
            $flatNumbers = array_column($flats, 'flat');
            $firstFlat = min($flatNumbers);
            $lastFlat = max($flatNumbers);

            // Collect house prefixes
            $prefixNumber = $entrance['prefix'] ?? 0;
            if ($prefixNumber > 0) {
                $housePrefixes[] = new HousePrefix(
                    number: $prefixNumber,
                    address: $addresses->getHouse($entrance['houseId'])['houseFull'],
                    firstFlat: new FlatNumber($firstFlat),
                    lastFlat: new FlatNumber($lastFlat),
                );
            }

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
                }

                if ($flat['flat'] == $lastFlat) {
                    $offset += $flat['flat'];
                }
            }
        }

        // TODO: move to a separate method
        if ($this->device instanceof GateModeInterface) {
            $this->builder->addGateModeEnabled(!empty($housePrefixes));
        }

        // TODO: move to a separate method
        if ($this->device instanceof HousePrefixInterface) {
            $this->builder->addHousePrefixes($housePrefixes);
        }

        return $this;
    }

    /**
     * Add global CMS levels settings to the domophone configuration.
     *
     * @return void
     */
    private function addCmsLevels(): void
    {
        $rawCmsLevels = $this->mainEntrance['cmsLevels'];
        $cmsLevels = !empty($rawCmsLevels)
            ? array_map('intval', explode(',', $rawCmsLevels))
            : [];

        $this->builder->addCmsLevels($cmsLevels);
    }

    /**
     * Add the CMS model to the domophone configuration.
     *
     * @return self
     */
    private function addCmsModel(): self
    {
        /** @var configs $configs */
        $configs = loadBackend('configs');

        $cmses = $configs->getCMSes();
        $cmsFile = $this->mainEntrance['cms'];
        $this->builder->addCmsModel($cmses[$cmsFile]['model'] ?? '');
        return $this;
    }

    /**
     * Adds display text lines to the intercom configuration.
     *
     * @return void
     */
    private function addDisplayText(): void
    {
        $display = $this->domophoneData['display'] ?? '';
        $callerId = $this->mainEntrance['callerId'] ?? '';

        $lines = match (true) {
            trim($display) !== '' => explode("\n", $display),
            trim($callerId) !== '' => [$callerId],
            default => [],
        };

        $this->builder->addDisplayText($lines);
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
        $url = $this->appConfig['syslog_servers'][$this->domophoneData['json']['eventServer']][0] ?? '';
        $this->builder->addEventServer($url);
        return $this;
    }

    /**
     * Add the free pass mode status to the intercom configuration.
     *
     * @return void
     */
    private function addFreePassEnabled(): void
    {
        $this->builder->addFreePassEnabled($this->domophoneData['locksAreOpen']);
    }

    /**
     * Add the CMS matrix to the domophone configuration.
     *
     * @return self
     */
    private function addMatrix(): self
    {
        /** @var households $households */
        $households = loadBackend('households');

        $mainEntranceId = $this->mainEntrance['entranceId'];
        $matrix = $households->getCms($mainEntranceId);

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
     * Add RFID keys to the domophone configuration.
     *
     * @return void
     */
    private function addRfids(): void
    {
        /** @var households $households */
        $households = loadBackend('households');

        $keys = $households->getKeys('domophoneId', $this->domophoneData['domophoneId']);
        foreach ($keys as $key) {
            $this->builder->addRfid($key['rfId']);
        }
    }

    /**
     * Add SIP parameters to the domophone configuration.
     *
     * @return void
     */
    private function addSip(): void
    {
        /** @var sip $sip */
        $sip = loadBackend("sip");

        [
            'server' => $server,
            'domophoneId' => $domophoneId,
            'credentials' => $password,
            'nat' => $natEnabled,
        ] = $this->domophoneData;

        $port = $sip->server('ip', $server)['sip_udp_port'] ?? 5060;
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
            $stunPort,
        );
    }

    /**
     * Find and return the timezone for the current configuration.
     *
     * This method attempts to find the timezone from various levels of the address hierarchy,
     * starting from the city and falling back to the area and region if necessary.
     * If the timezone is not set, then "Europe/Moscow" will be used.
     *
     * @return string The timezone identifier.
     */
    private function findTimezone(): string
    {
        /** @var addresses $addresses */
        $addresses = loadBackend('addresses');

        $house = $addresses->getHouse($this->mainEntrance['houseId'] ?? null);
        $street = $addresses->getStreet($house['streetId'] ?? null);
        $settlement = $addresses->getSettlement($house['settlementId'] ?? null);
        $city = $addresses->getCity($settlement['cityId'] ?? $street['cityId'] ?? null);
        $area = $addresses->getArea($settlement['areaId'] ?? $city['areaId'] ?? null);
        $region = $addresses->getRegion($area['regionId'] ?? $city['regionId'] ?? null);

        $timezone = $city['timezone'] ?? $area['timezone'] ?? $region['timezone'] ?? null;

        if ($timezone === null) {
            $timezone = 'Europe/Moscow';
        }

        return $timezone;
    }
}
