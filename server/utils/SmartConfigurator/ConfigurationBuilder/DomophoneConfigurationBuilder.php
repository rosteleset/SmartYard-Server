<?php

namespace utils\SmartConfigurator\ConfigurationBuilder;

/**
 * This class is responsible for building domophone configuration.
 */
class DomophoneConfigurationBuilder extends ConfigurationBuilder
{

    /**
     * Construct a new DomophoneConfigurationBuilder instance.
     */
    public function __construct()
    {
        $this->config = [
            'dtmf' => [],
            'eventServer' => [],
            'sip' => [],
            'unlocked' => true,
            'apartments' => [],
            'rfids' => [],
            'gateLinks' => [],
            'cmsLevels' => [],
            'cmsModel' => '',
            'matrix' => [],
            'ntp' => [],
            'tickerText' => '',
        ];
    }

    /**
     * Add an apartment parameters to the domophone configuration.
     *
     * @param int $apartment The apartment number.
     * @param int $code The apartment's personal access code.
     * @param array $sipNumbers An array of SIP numbers associated with the apartment.
     * @param bool $cmsEnabled Whether CMS is enabled for the apartment.
     * @param array $cmsLevels An array of individual CMS levels for the apartment.
     *
     * @return self
     */
    public function addApartment(
        int   $apartment,
        int   $code,
        array $sipNumbers,
        bool  $cmsEnabled,
        array $cmsLevels
    ): self
    {
        // Filter empty elements and cast to int
        $sipNumbers = array_map('intval', array_filter($sipNumbers));

        $this->config['apartments'][$apartment] = compact(
            'apartment',
            'code',
            'sipNumbers',
            'cmsEnabled',
            'cmsLevels'
        );

        return $this;
    }

    /**
     * Add global CMS levels to the domophone configuration.
     *
     * @param array $levels An array of CMS levels.
     *
     * @return self
     */
    public function addCmsLevels(array $levels): self
    {
        $this->config['cmsLevels'] = $levels;

        return $this;
    }

    /**
     * Add the CMS model to the domophone configuration.
     *
     * @param string $cmsModel The CMS model.
     *
     * @return self
     */
    public function addCmsModel(string $cmsModel): self
    {
        $this->config['cmsModel'] = $cmsModel;

        return $this;
    }

    /**
     * Add DTMF codes to the domophone configuration.
     *
     * @param string $code1 The first DTMF code (main lock).
     * @param string $code2 The second DTMF code (second lock).
     * @param string $code3 The third DTMF code (third lock).
     * @param string $codeCms The CMS-DTMF code.
     *
     * @return self
     */
    public function addDtmf(string $code1, string $code2, string $code3, string $codeCms): self
    {
        $this->config['dtmf'] = compact('code1', 'code2', 'code3', 'codeCms');

        return $this;
    }

    /**
     * Add a gate link to the domophone configuration.
     *
     * @param int $prefix The entrance prefix.
     * @param string $address The entrance address.
     * @param int $firstFlat The first flat of the entrance.
     * @param int $lastFlat The last flat of the entrance.
     *
     * @return self
     */
    public function addGateLink(int $prefix, string $address, int $firstFlat, int $lastFlat): self
    {
        $this->config['gateLinks'][$prefix] = compact('prefix', 'address', 'firstFlat', 'lastFlat');

        return $this;
    }

    /**
     * Add a matrix cell to the domophone configuration.
     *
     * @param int $hundreds The hundreds.
     * @param int $tens The tens.
     * @param int $units The units.
     * @param int $apartment The apartment associated with the matrix cell.
     *
     * @return self
     */
    public function addMatrix(int $hundreds, int $tens, int $units, int $apartment): self
    {
        $this->config['matrix'][$hundreds . $tens . $units] = compact('hundreds', 'tens', 'units', 'apartment');

        return $this;
    }

    /**
     * Add RFID code to the domophone configuration.
     *
     * @param string $rfidCode The RFID code.
     *
     * @return self
     */
    public function addRfid(string $rfidCode): self
    {
        $this->config['rfids'][$rfidCode] = $rfidCode;

        return $this;
    }

    /**
     * Add SIP parameters to the domophone configuration.
     *
     * @param string $server The SIP server's address.
     * @param int $port The SIP server's port number.
     * @param string $login The SIP login.
     * @param string $password The SIP password.
     * @param bool $stunEnabled Whether STUN is enabled.
     * @param string $stunServer The STUN server's address.
     * @param int $stunPort The STUN server's port number.
     *
     * @return self
     */
    public function addSip(
        string $server,
        int    $port,
        string $login,
        string $password,
        bool   $stunEnabled,
        string $stunServer,
        int    $stunPort
    ): self
    {
        $this->config['sip'] = compact(
            'server',
            'port',
            'login',
            'password',
            'stunEnabled',
            'stunServer',
            'stunPort'
        );

        return $this;
    }

    /**
     * Add ticker text to the domophone configuration.
     *
     * @param string $text The ticker text to display.
     *
     * @return self
     */
    public function addTickerText(string $text): self
    {
        $this->config['tickerText'] = $text;

        return $this;
    }

    /**
     * Add unlocked status to the domophone configuration.
     *
     * @param bool $unlocked Whether the domophone is unlocked.
     *
     * @return self
     */
    public function addUnlocked(bool $unlocked): self
    {
        $this->config['unlocked'] = $unlocked;

        return $this;
    }
}
