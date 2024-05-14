<?php

namespace hw\ip\domophone\rubetek;

class RubetekConst
{
    // Dialplan IDs
    public const CONCIERGE_ID = 'CONCIERGE';
    public const SOS_ID = 'SOS';

    // Door access
    public const RELAY_1_INTERNAL = 1;
    public const RELAY_2_INTERNAL = 2;
    public const RELAY_3_INTERNAL = 3;
    public const RELAY_1_EXTERNAL = 4;
    public const RELAY_2_EXTERNAL = 5;
    public const RELAY_3_EXTERNAL = 6;

    // Call type
    public const SIP = 'sip';
    public const ANALOG = 'analog';
    public const SIP_ANALOG = 'sip_0_analog';
    public const SIP_10_ANALOG = 'sip_10_analog';
    public const SIP_20_ANALOG = 'sip_20_analog';
    public const SIP_30_ANALOG = 'sip_30_analog';
    public const SIP_60_ANALOG = 'sip_60_analog';
    public const SIP_120_ANALOG = 'sip_120_analog';
    public const ANALOG_SIP = 'analog_sip';
    public const ANALOG_10_SIP = 'analog_10_sip';
    public const ANALOG_20_SIP = 'analog_20_sip';
    public const ANALOG_30_SIP = 'analog_30_sip';
    public const ANALOG_60_SIP = 'analog_60_sip';
    public const ANALOG_120_SIP = 'analog_120_sip';
    public const SIP_P2P = 'sip_p2p';
    public const SIP_SIP_P2P = 'sip_sip_p2p';
    public const BLOCKED = 'blocked';

    /**
     * @var array|string[] Mapping of CMS models between database and Rubetek names.
     * @access protected
     */
    public const CMS_MODEL_MAP = [
        // DB          // Rubetek
        'KM100-7.1' => 'km100-7.1',
        'KM100-7.3' => 'km100-7.3',
        'KM100-7.5' => 'km100-7.5',
        'KKM-100S2' => 'kkm-100s2',
        'KKM-105' => 'kkm-105',
        'KKM-108' => 'kkm-108',
        'KMG-100' => 'kmg-100',
    ];
}
