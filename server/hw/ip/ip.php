<?php

namespace hw\ip;

use Exception;
use hw\hw;

/**
 * Abstract class representing an IP device.
 */
abstract class ip extends hw
{

    /**
     * @var string Login to access the device.
     */
    public string $login;

    /**
     * @var string Password to access the device.
     */
    public string $password;

    /**
     * @var string Default password for the device.
     */
    protected string $defaultPassword;

    /**
     * @var string Prefix for API routes.
     */
    protected string $apiPrefix;

    /**
     * @var string|null Caches the hardware version once fetched.
     */
    protected ?string $hardwareVersion = null;

    /**
     * @var string|null Caches the software version once fetched.
     */
    protected ?string $softwareVersion = null;

    /**
     * Construct a new instance of the IP device.
     *
     * @param string $url IP device URL.
     * @param string $password Password for authentication.
     * This may be a desired but not yet valid password.
     * In such a case, the password will be applied on the device if the $firstTime is set to true,
     * otherwise, there will be an exception.
     * @param bool $firstTime (Optional) Indicates if it's the first time using the device. Default is false.
     *
     * @throws Exception if the device is unavailable.
     */
    public function __construct(string $url, string $password, bool $firstTime = false)
    {
        parent::__construct($url);
        $this->initializeProperties();

        $this->password = $firstTime ? ($this->defaultPassword ?? $password) : $password;

        if (!$this->ping()) {
            throw new Exception("Device at $this->url is unavailable");
        }

        if ($firstTime) {
            $this->prepare();
            $this->setAdminPassword($password);
            $this->password = $password;
        }
    }

    public function ping(): bool
    {
        $errno = false;
        $errstr = '';

        $url = parse_url($this->url);
        if (!@$url['port']) {
            switch (@strtolower($url['scheme'])) {
                case 'http':
                    $url['port'] = 80;
                    break;
                case 'https':
                    $url['port'] = 443;
                    break;
                default:
                    $url['port'] = 22;
                    break;
            }
        }
        $fp = @stream_socket_client($url['host'] . ":" . $url['port'], $errno, $errstr, 1);

        if ($fp) {
            fclose($fp);

            if (@$this->getSysinfo()['DeviceID']) {
                return true;
            }

            return false;
        }

        return false;
    }

    /**
     * Retrieves the hardware version from system information, with caching.
     *
     * @return string|null The hardware version or null if not available.
     */
    protected function getHardwareVersion(): ?string
    {
        if ($this->hardwareVersion === null) {
            $this->hardwareVersion = $this->getSysinfo()['HardwareVersion'] ?? null;
        }

        return $this->hardwareVersion;
    }

    /**
     * Retrieves the software version from system information, with caching.
     *
     * @return string|null The software version or null if not available.
     */
    protected function getSoftwareVersion(): ?string
    {
        if ($this->softwareVersion === null) {
            $this->softwareVersion = $this->getSysinfo()['SoftwareVersion'] ?? null;
        }

        return $this->softwareVersion;
    }

    /**
     * Get event server configuration.
     *
     * @return string A string containing the URL of the syslog server configured on the device.
     */
    abstract protected function getEventServer(): string;

    /**
     * Initializes properties for the implementing class.
     *
     * @return void
     */
    abstract protected function initializeProperties(): void;

    /**
     * Configure a remote event server.
     *
     * @param string $url Remote event server URL.
     *
     * @return void
     */
    abstract public function configureEventServer(string $url): void;

    /**
     * Get system information.
     *
     * @return array An array containing system information about the device,
     * such as serial number, hardware version, firmware version, etc.
     */
    abstract public function getSysinfo(): array;

    /**
     * Set administrator password.
     *
     * @param string $password Set a password for the administrator account.
     * This is a common password for authorization in WEB, API and RTSP.
     *
     * @return void
     */
    abstract public function setAdminPassword(string $password): void;
}
