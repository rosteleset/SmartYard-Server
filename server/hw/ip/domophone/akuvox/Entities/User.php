<?php

namespace hw\ip\domophone\akuvox\Entities;

use InvalidArgumentException;

/**
 * Represents a user in the Akuvox intercom.
 */
final class User
{
    /**
     * @var array<string, string> Mapping of API keys to class properties.
     */
    private static array $apiParamMap = [
        'ID' => 'id',
        'UserID' => 'userId',
        'DoorNum' => 'doorNum',
        'PrivatePIN' => 'privatePin',
        'LiftFloorNum' => 'liftFloorNum',
        'Name' => 'name',
        'Schedule' => 'schedule',
        'CardCode' => 'cardCode',
        'WebRelay' => 'webRelay',
        'C4Event' => 'c4Event',
        'PriorityCall' => 'priorityCall',
        'DialAccount' => 'dialAccount',
        'PhoneNum' => 'phoneNum',
        'AnalogSystem' => 'analogSystem',
        'AnalogNumber' => 'analogNumber',
        'AnalogReplace' => 'analogReplace',
        'AnalogMode' => 'analogMode',
        'AnalogProxyAddress' => 'analogProxyAddress',
        'ContactID' => 'contactId',
        'Group' => 'group',
        'BLEAuthCode' => 'bleAuthCode',
        'BLE_KEY_ID' => 'bleKeyId',
        'BLE_Expired' => 'bleExpired',
        'BLE_Status' => 'bleStatus',
        'Source' => 'source',
        'Schedule-Relay' => 'scheduleRelay',
        'QrCodeUrl' => 'qrCodeUrl',
    ];

    public string $userId;
    public string $id = '-1';
    public string $doorNum = '0';
    public string $privatePin = '';
    public string $liftFloorNum = '0';
    public string $name = '0';
    public array $schedule = ['1001'];
    public string $cardCode = '';
    public string $webRelay = '0';
    public string $c4Event = '0';
    public string $priorityCall = '0';
    public string $dialAccount = '0';
    public string $phoneNum = '';
    public string $analogSystem = '0';
    public string $analogNumber = '';
    public string $analogReplace = '';
    public string $analogMode = '0';
    public string $analogProxyAddress = '';
    public string $contactId = '';
    public string $group = 'Default';
    public string $bleAuthCode = '';
    public string $bleKeyId = '-1';
    public string $bleExpired = 'N/A';
    public string $bleStatus = 'Unpaired';
    public string $source = 'Local';
    public string $scheduleRelay = '1001-1;';
    public string $qrCodeUrl = '';

    /**
     * Creates a new `User` instance.
     *
     * @param string $userId Unique user ID.
     * @throws InvalidArgumentException if `$userId` is empty.
     */
    public function __construct(string $userId)
    {
        if (empty($userId)) {
            throw new InvalidArgumentException('Cannot create User: UserID is null or empty');
        }

        $this->userId = $userId;
        $this->name = $userId;
    }

    /**
     * Creates a `User` instance from an array (API response).
     *
     * @param array<string, mixed> $data API data.
     * @return self
     * @throws InvalidArgumentException if `UserID` is missing.
     */
    public static function fromArray(array $data): self
    {
        if (empty($data['UserID'])) {
            throw new InvalidArgumentException('Cannot create User: UserID is missing in array');
        }

        $user = new self($data['UserID']);

        foreach (self::$apiParamMap as $apiKey => $prop) {
            if (array_key_exists($apiKey, $data)) {
                $user->$prop = $data[$apiKey];
            }
        }

        return $user;
    }

    /**
     * Returns all user properties as an array matching the API keys.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_map(function ($prop) {
            return $this->$prop;
        }, self::$apiParamMap);
    }
}
