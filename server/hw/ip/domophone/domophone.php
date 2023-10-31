<?php

namespace hw\ip\domophone;

use hw\ip\ip;
use utils\SmartConfigurator\ConfigurationBuilder\DomophoneConfigurationBuilder;

/**
 * Abstract class representing a domophone.
 */
abstract class domophone extends ip
{

    /**
     * @return void
     * @deprecated Left for compatibility with the old configurator.
     */
    public function clean(
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
        $stunPort
    )
    {
        $this->setUnlocked();
        $this->configureEventServer($syslogServer, $syslogPort);
        $this->setUnlockTime(5);
        $this->setPublicCode();
        $this->setCallTimeout(45);
        $this->setTalkTimeout(90);
        $this->setLanguage();
        $this->setAudioLevels($audioLevels);
        $this->setCmsLevels($cmsLevels);
        $this->configureNtp($ntpServer, $ntpPort);
        $this->configureSip($sipUsername, $this->password, $sipServer, $sipPort, $nat, $stunServer, $stunPort);
        $this->setDtmfCodes($mainDoorDtmf);
        $this->deleteRfid();
        $this->deleteApartment();
        $this->setConciergeNumber(9999);
        $this->setSosNumber(112);
        $this->setCmsModel($cmsModel);
        $this->configureGate();
    }

    final public function getCurrentConfig(): array
    {
        $builder = new DomophoneConfigurationBuilder();

        $builder
            ->addDtmf(...$this->getDtmfConfig())
            ->addEventServer(...$this->getEventServerConfig())
            ->addSip(...$this->getSipConfig())
            ->addUnlocked($this->getUnlocked())
            ->addCmsLevels($this->getCmsLevels())
            ->addCmsModel($this->getCmsModel())
            ->addNtp(...$this->getNtpConfig())
            ->addTickerText($this->getTickerText())
        ;

        foreach ($this->getApartments() as $apartment) {
            $builder->addApartment(...$apartment);
        }

        foreach ($this->getRfids() as $rfid) {
            $builder->addRfid($rfid);
        }

        foreach ($this->getGateConfig() as $link) {
            $builder->addGateLink(...$link);
        }

        foreach ($this->getMatrix() as $matrixCell) {
            $builder->addMatrix(...$matrixCell);
        }

        return $builder->getConfig();
    }

    public function prepare()
    {
        $this->configureEncoding();
        $this->setConciergeNumber(9999);
        $this->setSosNumber(112);
        $this->setCallTimeout(45);
        $this->setTalkTimeout(90);
        $this->setUnlockTime(5);
        $this->setPublicCode();
    }

    /**
     * Get apartment configuration.
     *
     * @return array[] All apartments configured on the device.
     */
    abstract protected function getApartments(): array;

    /**
     * Get audio levels.
     *
     * @return int[] All audio levels
     * such as speaker/microphone volume, sensitivity, etc.
     * that are configured on the device.
     */
    abstract protected function getAudioLevels(): array;

    /**
     * Get global CMS levels.
     *
     * @return string[] All CMS levels configured on the device.
     * Usually, here's off-hook and door open levels.
     */
    abstract protected function getCmsLevels(): array;

    /**
     * Get CMS model.
     *
     * @return string CMS model configured on the device.
     */
    abstract protected function getCmsModel(): string;

    /**
     * Get DTMF codes configuration.
     *
     * @return array An array containing DTMF codes configured on the device.
     */
    abstract protected function getDtmfConfig(): array;

    /**
     * Get gate configuration.
     *
     * @return array[] An array with gate links configured on the device.
     */
    abstract protected function getGateConfig(): array;

    /**
     * Get CMS matrix.
     *
     * @return array[] An array representing the apartment allocation matrix configured on the device.
     */
    abstract protected function getMatrix(): array;

    /**
     * Get RFID keys.
     *
     * @return string[] An array containing RFID keys, stored on the device.
     */
    abstract protected function getRfids(): array;

    /**
     * Get SIP configuration.
     *
     * @return array An array containing SIP account params configured on the device.
     */
    abstract protected function getSipConfig(): array;

    /**
     * Get the text of the ticker.
     *
     * @return string Ticker text configured on the device.
     */
    abstract protected function getTickerText(): string;

    /**
     * Get lock state.
     *
     * @return bool True if the domophone locks are now unlocked, false otherwise.
     */
    abstract protected function getUnlocked(): bool;

    /**
     * Add the RFID key.
     *
     * @param string $code RFID code to be added.
     * @param int $apartment (Optional) Apartment associated with the RFID key.
     * Default is 0, indicating the RFID key not tied to a specific apartment.
     *
     * @return void
     */
    abstract public function addRfid(string $code, int $apartment = 0);

    /**
     * Add RFID keys.
     *
     * @param string[] $rfids An array of RFIDs to be added.
     *
     * @return void
     */
    abstract public function addRfids(array $rfids);

    /**
     * Configure an apartment.
     *
     * @param int $apartment Apartment number to configure.
     * @param int $code (Optional) Personal access code associated with the apartment.
     * Default is 0 which means the code is disabled.
     * @param array $sipNumbers (Optional) An array of SIP numbers associated with the apartment.
     * Default is an empty array.
     * @param bool $cmsEnabled (Optional) Specifies whether the CMS headset is enabled for the apartment.
     * Default is true.
     * @param array $cmsLevels (Optional) An array of CMS levels associated with the apartment.
     * These are the so-called individual levels. Default is an empty array,
     * which tells the device to use global levels.
     *
     * @return void
     */
    abstract public function configureApartment(
        int   $apartment,
        int   $code = 0,
        array $sipNumbers = [],
        bool  $cmsEnabled = true,
        array $cmsLevels = []
    );

    /**
     * Configure audio and video streams encoding.
     *
     * @return void
     * @todo It should be in the camera class, but the camera doesn't have a "first time" field
     */
    abstract public function configureEncoding();

    /**
     * Configure gate mode.
     *
     * @param array $links (Optional) An array containing necessary links for gate mode.
     * If an array is empty, then gate mode is disabled. Default is empty.
     *
     * @return void
     */
    abstract public function configureGate(array $links = []);

    /**
     * Configure CMS matrix.
     *
     * @param array $matrix An array containing CMS matrix.
     *
     * @return void
     */
    abstract public function configureMatrix(array $matrix);

    /**
     * Configure SIP account.
     *
     * @param string $login Login for the SIP account.
     * @param string $password Password for the SIP account.
     * @param string $server SIP server address.
     * @param int $port (Optional) SIP server port. Default is 5060.
     * @param bool $stunEnabled (Optional) If true, STUN is active. Default is false.
     * @param string $stunServer (Optional) STUN server address. If the string is empty, then STUN is disabled.
     * Default is an empty string.
     * @param int $stunPort (Optional) Port number for STUN. Default is 3478.
     *
     * @return void
     */
    abstract public function configureSip(
        string $login,
        string $password,
        string $server,
        int    $port = 5060,
        bool   $stunEnabled = false,
        string $stunServer = '',
        int    $stunPort = 3478
    );

    /**
     * Configure user account.
     *
     * @param string $password Password for a user account.
     *
     * @return void
     */
    abstract public function configureUserAccount(string $password);

    /**
     * Delete apartment.
     *
     * @param int $apartment (Optional) Delete the apartment from the domophone.
     * If 0 is passed, then all apartments will be deleted.
     * Default is 0.
     *
     * @return void
     */
    abstract public function deleteApartment(int $apartment = 0);

    /**
     * Delete RFID key.
     *
     * @param string $code (Optional) Delete the RFID key from the domophone.
     * If is an empty string passed, then all RFID keys will be deleted.
     * Default is an empty string.
     *
     * @return void
     */
    abstract public function deleteRfid(string $code = '');

    /**
     * Get line diagnostics for the specified apartment.
     *
     * @param int $apartment Apartment number for diagnostics.
     *
     * @return int|string Electrical parameters of the line or verbal description.
     */
    abstract public function getLineDiagnostics(int $apartment);

    /**
     * Open lock.
     *
     * @param int $lockNumber (Optional) The lock number.
     * Starts with 0 (main lock). Default is 0.
     *
     * @return void
     */
    abstract public function openLock(int $lockNumber = 0);

    /**
     * Set audio levels.
     *
     * @param int[] $levels An array containing audio levels
     * such as speaker/microphone volume, sensitivity, etc.
     * Array elements must be in the same order they were received from the device.
     *
     * @return void
     *
     * @see getAudioLevels()
     */
    abstract public function setAudioLevels(array $levels);

    /**
     * Set call timeout.
     *
     * @param int $timeout Call timeout in seconds.
     * When this time expires, the call will automatically end.
     *
     * @return void
     */
    abstract public function setCallTimeout(int $timeout);

    /**
     * Set global CMS levels.
     *
     * @param string[] $levels An array containing global CMS levels.
     * Array elements must be in the same order they were received from device.
     *
     * @return void
     *
     * @see getCmsLevels()
     */
    abstract public function setCmsLevels(array $levels);

    /**
     * Set CMS model.
     *
     * @param string $model (Optional) CMS model in text form.
     * Look at the *.json CMS models in the "model" field.
     * If an empty string is passed, then the domophone will use the default CMS or the CMS will be disabled.
     * Default is an empty string.
     *
     * @return void
     */
    abstract public function setCmsModel(string $model = '');

    /**
     * Set SIP number for concierge button.
     *
     * @param int $sipNumber The number that will be called when the "Concierge" button is pressed.
     *
     * @return void
     */
    abstract public function setConciergeNumber(int $sipNumber);

    /**
     * Set DTMF codes to open locks.
     *
     * @param string $code1 (Optional) DTMF code that opens the main lock. Default is "1".
     * @param string $code2 (Optional) DTMF code that opens the second lock. Default is "2".
     * @param string $code3 (Optional) DTMF code that opens the third lock. Default is "3".
     * @param string $codeCms (Optional) DTMF code that is sent to the caller
     * when the open button on the CMS headset is pressed.
     * A typical use is to open a gate using a CMS headset.
     * Default is "1".
     *
     * @return void
     */
    abstract public function setDtmfCodes(
        string $code1 = '1',
        string $code2 = '2',
        string $code3 = '3',
        string $codeCms = '1'
    );

    /**
     * Set the language used on the device.
     *
     * @param string $language (Optional) The language used by the device in the ISO 639-1 format.
     * The language will be applied to the WEB interface text, sound files, etc.
     * Default is "ru".
     *
     * @return void
     */
    abstract public function setLanguage(string $language = 'ru');

    /**
     * Set a public access code.
     *
     * @param int $code (Optional) Public access code.
     * Default is 0 which means the code is disabled.
     *
     * @return void
     */
    abstract public function setPublicCode(int $code = 0);

    /**
     * Set SIP number for SOS button.
     *
     * @param int $sipNumber The number that will be called when the "SOS" button is pressed.
     *
     * @return void
     */
    abstract public function setSosNumber(int $sipNumber);

    /**
     * Set talk timeout.
     *
     * @param int $timeout Talk timeout in seconds.
     * When this time expires, the talk will automatically end.
     *
     * @return void
     */
    abstract public function setTalkTimeout(int $timeout);

    /**
     * Set ticker text.
     *
     * @param string $text (Optional) Text displayed on device ticker.
     * Default is an empty string.
     *
     * @return void
     */
    abstract public function setTickerText(string $text = '');

    /**
     * Set opening times for locks.
     *
     * @param int $time (Optional) Opening time in seconds after receiving the command to open the lock.
     * Default is 3.
     *
     * @return void
     * @see openLock()
     */
    abstract public function setUnlockTime(int $time = 3);

    /**
     * Set the lock state to always locked/unlocked.
     *
     * @param bool $unlocked (Optional) If true, then the locks will be in the open state. Closed if false.
     *
     * @return void
     */
    abstract public function setUnlocked(bool $unlocked = true);
}
