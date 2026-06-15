<?php

namespace hw\ip\domophone\rubetek\Enums;

/**
 * Represents Rubetek apartment call modes.
 */
enum CallType: string
{
    case Sip = 'sip';
    case Analog = 'analog';
    case SipAnalog = 'sip_0_analog';
    case Sip10Analog = 'sip_10_analog';
    case Sip20Analog = 'sip_20_analog';
    case Sip30Analog = 'sip_30_analog';
    case Sip60Analog = 'sip_60_analog';
    case Sip120Analog = 'sip_120_analog';
    case AnalogSip = 'analog_sip';
    case Analog10Sip = 'analog_10_sip';
    case Analog20Sip = 'analog_20_sip';
    case Analog30Sip = 'analog_30_sip';
    case Analog60Sip = 'analog_60_sip';
    case Analog120Sip = 'analog_120_sip';
    case SipP2p = 'sip_p2p';
    case SipSipP2p = 'sip_sip_p2p';
    case Blocked = 'blocked';
}
