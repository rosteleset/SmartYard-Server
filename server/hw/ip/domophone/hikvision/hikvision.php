<?php

namespace hw\ip\domophone\hikvision;

use DateInterval;
use DateTime;
use hw\Interface\{
    FreePassInterface,
    LanguageInterface,
};
use hw\ip\domophone\domophone;

/**
 * Abstract class representing a Hikvision domophone.
 */
abstract class hikvision extends domophone implements FreePassInterface, LanguageInterface
{
    use \hw\ip\common\hikvision\hikvision;

    public function addRfid(string $code, int $apartment = 0): void
    {
        $this->apiCall(
            '/AccessControl/CardInfo/Record',
            'POST',
            ['format' => 'json'],
            [
                'CardInfo' => [
                    'employeeNo' => "$apartment",
                    'cardNo' => sprintf("%'.010d", hexdec($code)),
                    'cardType' => 'normalCard',
                ],
            ],
        );
    }

    public function addRfids(array $rfids): void
    {
        foreach ($rfids as $rfid) {
            $this->addRfid($rfid);
        }
    }

    public function configureApartment(
        int   $apartment,
        int   $code = 0,
        array $sipNumbers = [],
        bool  $cmsEnabled = true,
        array $cmsLevels = [],
    ): void
    {
        $now = new DateTime();
        $beginTime = $now->format('Y-m-dTH:i:s');
        $endTime = $now->add(new DateInterval('P10Y'))->format('Y-m-dTH:i:s');

        if ($this->apartmentExists($apartment)) {
            $method = 'PUT';
            $action = 'Modify';
        } else {
            $method = 'POST';
            $action = 'Record';
        }

        $this->apiCall(
            "/AccessControl/UserInfo/$action",
            $method,
            ['format' => 'json'],
            [
                'UserInfo' => [
                    'employeeNo' => "$apartment",
                    'name' => "$apartment",
                    'userType' => 'normal',
                    'localUIRight' => false,
                    'maxOpenDoorTime' => 0,
                    'Valid' => [
                        'enable' => true,
                        'beginTime' => $beginTime,
                        'endTime' => $endTime,
                        'timeType' => 'local',
                    ],
                    'doorRight' => '1',
                    'RightPlan' => [
                        [
                            'doorNo' => 1,
                            'planTemplateNo' => '1',
                        ],
                    ],
                    'roomNumber' => $apartment,
                    'floorNumber' => 0,
                    'userVerifyMode' => '',
                ],
            ],
        );

        $phoneNumbers = [];

        foreach ($sipNumbers as $value) {
            $phoneNumbers[] = ['phoneNumber' => "$value"];
        }

        $this->apiCall(
            '/VideoIntercom/PhoneNumberRecords',
            'POST',
            ['format' => 'json'],
            [
                'PhoneNumberRecord' => [
                    'roomNo' => '1',
                    'PhoneNumbers' => $phoneNumbers,
                ],
            ],
        );
    }

    public function configureEncoding(): void
    {
        $this->apiCall(
            '/Streaming/channels/101',
            'PUT',
            [],
            '<StreamingChannel>
                <id>101</id>
                <channelName>Camera 01</channelName>
                <enabled>true</enabled>
                <Transport>
                    <ControlProtocolList>
                        <ControlProtocol>
                            <streamingTransport>RTSP</streamingTransport>
                        </ControlProtocol>
                        <ControlProtocol>
                            <streamingTransport>HTTP</streamingTransport>
                        </ControlProtocol>
                    </ControlProtocolList>
                    <Security>
                        <enabled>true</enabled>
                    </Security>
                </Transport>
                <Video>
                    <enabled>true</enabled>
                    <videoInputChannelID>1</videoInputChannelID>
                    <videoCodecType>H.264</videoCodecType>
                    <videoScanType>progressive</videoScanType>
                    <videoResolutionWidth>1280</videoResolutionWidth>
                    <videoResolutionHeight>720</videoResolutionHeight>
                    <videoQualityControlType>VBR</videoQualityControlType>
                    <constantBitRate>2048</constantBitRate>
                    <fixedQuality>60</fixedQuality>
                    <vbrUpperCap>1024</vbrUpperCap>
                    <vbrLowerCap>32</vbrLowerCap>
                    <maxFrameRate>2500</maxFrameRate>
                    <keyFrameInterval>2000</keyFrameInterval>
                    <snapShotImageType>JPEG</snapShotImageType>
                    <GovLength>50</GovLength>
                </Video>
                <Audio>
                    <enabled>true</enabled>
                    <audioInputChannelID>1</audioInputChannelID>
                    <audioCompressionType>G.711alaw</audioCompressionType>
                </Audio>
            </StreamingChannel>',
        );

        $this->apiCall(
            '/Streaming/channels/102',
            'PUT',
            [],
            '<StreamingChannel>
                <id>102</id>
                <channelName>Camera 01</channelName>
                <enabled>true</enabled>
                <Transport>
                    <ControlProtocolList>
                        <ControlProtocol>
                            <streamingTransport>RTSP</streamingTransport>
                        </ControlProtocol>
                        <ControlProtocol>
                            <streamingTransport>HTTP</streamingTransport>
                        </ControlProtocol>
                    </ControlProtocolList>
                    <Security>
                        <enabled>true</enabled>
                    </Security>
                </Transport>
                <Video>
                    <enabled>true</enabled>
                    <videoInputChannelID>1</videoInputChannelID>
                    <videoCodecType>H.264</videoCodecType>
                    <videoScanType>progressive</videoScanType>
                    <videoResolutionWidth>704</videoResolutionWidth>
                    <videoResolutionHeight>576</videoResolutionHeight>
                    <videoQualityControlType>VBR</videoQualityControlType>
                    <constantBitRate>512</constantBitRate>
                    <fixedQuality>60</fixedQuality>
                    <vbrUpperCap>348</vbrUpperCap>
                    <vbrLowerCap>32</vbrLowerCap>
                    <maxFrameRate>2500</maxFrameRate>
                    <keyFrameInterval>2000</keyFrameInterval>
                    <snapShotImageType>JPEG</snapShotImageType>
                    <GovLength>50</GovLength>
                </Video>
                <Audio>
                    <enabled>true</enabled>
                    <audioInputChannelID>1</audioInputChannelID>
                    <audioCompressionType>G.711alaw</audioCompressionType>
                </Audio>
            </StreamingChannel>',
        );
    }

    public function configureMatrix(array $matrix): void
    {
        // Empty implementation
    }

    public function configureSip(
        string $login,
        string $password,
        string $server,
        int    $port = 5060,
        bool   $stunEnabled = false,
        string $stunServer = '',
        int    $stunPort = 3478,
    ): void
    {
        $this->apiCall(
            '/System/Network/SIP',
            'PUT',
            [],
            "<SIPServerList>
                <SIPServer>
                    <id>1</id>
                    <Standard>
                        <enabled>true</enabled>
                        <proxy>$server</proxy>
                        <proxyPort>$port</proxyPort>
                        <displayName>$login</displayName>
                        <userName>$login</userName>
                        <authID>$login</authID>
                        <password>$password</password>
                        <expires>25</expires>
                    </Standard>
                </SIPServer>
            </SIPServerList>",
        );
    }

    public function configureUserAccount(string $password): void
    {
        // Empty implementation
    }

    public function deleteApartment(int $apartment = 0): void
    {
        if ($apartment === 0) {
            foreach ($this->getApartmentsNumbers() as $value) {
                $this->deleteApartment($value);
                $this->apiCall(
                    '/VideoIntercom/PhoneNumberRecords/10010110001',
                    'DELETE',
                );
            }
        } else {
            $this->apiCall(
                '/AccessControl/UserInfo/Delete',
                'PUT',
                ['format' => 'json'],
                [
                    'UserInfoDelCond' => [
                        'EmployeeNoList' => [
                            ['employeeNo' => "$apartment"],
                        ],
                    ],
                ],
            );
        }
    }

    public function deleteRfid(string $code = ''): void
    {
        // TODO: Implement deleteRfid() method.
    }

    public function getAudioLevels(): array
    {
        $audioIn = $this->apiCall('/System/Audio/AudioIn/channels/1');
        $audioOut = $this->apiCall('/System/Audio/AudioOut/channels/1');

        return [
            $audioIn['AudioInVolumelist']['AudioInVlome']['volume'],
            $audioOut['AudioOutVolumelist']['AudioOutVlome']['volume'],
            $audioOut['AudioOutVolumelist']['AudioOutVlome']['talkVolume'],
        ];
    }

    public function getLineDiagnostics(int $apartment): int
    {
        return 0;
    }

    public function getRfids(): array
    {
        $rfids = [];
        $pagesNumber = intdiv($this->getRfidsCount(), 30) + 1;

        for ($i = 1; $i <= $pagesNumber; $i++) {
            $res = $this->apiCall(
                '/AccessControl/CardInfo/Search',
                'POST',
                ['format' => 'json'],
                [
                    'CardInfoSearchCond' => [
                        'searchID' => '1',
                        'maxResults' => 30,
                        'searchResultPosition' => ($i - 1) * 30,
                    ],
                ],
            );

            foreach ($res['CardInfoSearch']['CardInfo'] as $value) {
                $rfids[] = '000000' . strtoupper(dechex($value['cardNo']));
            }
        }

        return $rfids;
    }

    public function isFreePassEnabled(): bool
    {
        // TODO: Implement isFreePassEnabled() method.
        return false;
    }

    public function openLock(int $lockNumber = 0): void
    {
        $this->apiCall(
            '/AccessControl/RemoteControl/door/' . ($lockNumber + 1),
            'PUT',
            [],
            '<cmd>open</cmd>',
        );
    }

    public function prepare(): void
    {
        parent::prepare();
        $this->enableDhcp();
    }

    public function setAudioLevels(array $levels): void
    {
        $levels[0] = @$levels[0] ?: 7;
        $levels[1] = @$levels[1] ?: 7;
        $levels[2] = @$levels[2] ?: 7;

        $this->apiCall(
            '/System/Audio/AudioIn/channels/1',
            'PUT',
            [],
            "<AudioIn>
                <id>1</id>
                <AudioInVolumelist><AudioInVlome>
                <type>audioInput</type>
                <volume>$levels[0]</volume>
                </AudioInVlome></AudioInVolumelist>
            </AudioIn>",
        );

        $this->apiCall(
            '/System/Audio/AudioOut/channels/1',
            'PUT',
            [],
            "<AudioOut>
                <id>1</id>
                <AudioOutVolumelist>
                    <AudioOutVlome>
                        <type>audioOutput</type>
                        <volume>$levels[1]</volume>
                        <talkVolume>$levels[2]</talkVolume>
                    </AudioOutVlome>
                </AudioOutVolumelist>
            </AudioOut>",
        );
    }

    public function setCallTimeout(int $timeout): void
    {
        $this->apiCall(
            '/VideoIntercom/operationTime',
            'PUT',
            [],
            "<OperationTime>
                <maxRingTime>$timeout</maxRingTime>
            </OperationTime>",
        );
    }

    public function setCmsModel(string $model = ''): void
    {
        // Empty implementation
    }

    public function setConciergeNumber(int $sipNumber): void
    {
        // Empty implementation
    }

    public function setDtmfCodes(
        string $code1 = '1',
        string $code2 = '2',
        string $code3 = '3',
        string $codeCms = '1',
    ): void
    {
        // Empty implementation
    }

    public function setFreePassEnabled(bool $enabled): void
    {
        $this->apiCall(
            '/AccessControl/RemoteControl/door/1',
            'PUT',
            [],
            $enabled ? '<cmd>alwaysOpen</cmd>' : '<cmd>resume</cmd>',
        );
    }

    public function setLanguage(string $language): void
    {
        $language = match ($language) {
            'ru' => 'Russian',
            default => 'English',
        };

        $this->apiCall(
            '/System/DeviceLanguage',
            'PUT',
            [],
            "<DeviceLanguage><language>$language</language></DeviceLanguage>",
        );
    }

    public function setPublicCode(int $code = 0): void
    {
        // Empty implementation
    }

    public function setSosNumber(int $sipNumber): void
    {
        // Empty implementation
    }

    public function setTalkTimeout(int $timeout): void
    {
        $this->apiCall(
            '/VideoIntercom/operationTime',
            'PUT',
            [],
            "<OperationTime>
                <talkTime>$timeout</talkTime>
            </OperationTime>",
        );
    }

    public function setUnlockTime(int $time = 3): void
    {
        $this->apiCall(
            '/AccessControl/Door/param/1',
            'PUT',
            [],
            "<DoorParam>
                <doorName>Door1</doorName>
                <openDuration>$time</openDuration>
            </DoorParam>",
        );
    }

    public function transformDbConfig(array $dbConfig): array
    {
        return $dbConfig;
    }

    protected function apartmentExists(int $apartment): bool
    {
        $res = $this->apiCall(
            '/AccessControl/UserInfo/Search',
            'POST',
            ['format' => 'json'],
            [
                'UserInfoSearchCond' => [
                    'searchID' => (string)$apartment,
                    'maxResults' => 1,
                    'searchResultPosition' => 0,
                    'EmployeeNoList' => [
                        ['employeeNo' => (string)$apartment],
                    ],
                ],
            ],
        );

        if ($res['UserInfoSearch']['responseStatusStrg'] == 'OK') {
            return true;
        }

        return false;
    }

    protected function enableDhcp(): void
    {
        $this->apiCall(
            '/System/Network/interfaces/1',
            'PUT',
            [],
            "<NetworkInterface>
                <id>1</id>
                <IPAddress>
                    <ipVersion>v4</ipVersion>
                    <addressingType>dynamic</addressingType>
                </IPAddress>
            </NetworkInterface>",
        );
    }

    protected function getApartments(): array
    {
        // TODO: Implement getApartments() method.
        return [];
    }

    protected function getApartmentsCount(): int
    {
        $res = $this->apiCall(
            '/AccessControl/UserInfo/Count',
            'GET',
            ['format' => 'json'],
        );

        return $res['UserInfoCount']['userNumber'];
    }

    protected function getApartmentsNumbers(): array
    {
        $apartments = [];
        $pagesNumber = intdiv($this->getApartmentsCount(), 20) + 1;

        for ($i = 1; $i <= $pagesNumber; $i++) {
            $res = $this->apiCall(
                '/AccessControl/UserInfo/Search',
                'POST',
                ['format' => 'json'],
                [
                    'UserInfoSearchCond' => [
                        'searchID' => '1',
                        'maxResults' => 20,
                        'searchResultPosition' => ($i - 1) * 20,
                    ],
                ],
            );

            $userInfo = $res['UserInfoSearch']['UserInfo'] ?? [];
            foreach ($userInfo as $value) {
                $apartments[] = $value['roomNumber'];
            }
        }

        return $apartments;
    }

    protected function getCmsModel(): string
    {
        // TODO: Implement getCmsModel() method.
        return '';
    }

    protected function getDtmfConfig(): array
    {
        // TODO: Implement getDtmfConfig() method.
        return [];
    }

    protected function getMatrix(): array
    {
        // TODO: Implement getMatrix() method.
        return [];
    }

    protected function getRfidsCount(): int
    {
        $res = $this->apiCall(
            '/AccessControl/CardInfo/Count',
            'GET',
            ['format' => 'json'],
        );

        return $res['CardInfoCount']['cardNumber'];
    }

    protected function getSipConfig(): array
    {
        // TODO: Implement getSipConfig() method.
        return [];
    }
}
