<?php

namespace hw\ip\domophone\basip;

/**
 * Represents the type of identifiers used in the BasIP intercom.
 */
enum IdentifierType: string
{
    case Card = 'card';
    case Ukey = 'ukey';
    case PersonalCode = 'inputCode';
    case FaceId = 'face_id';
    case QrCode = 'qr';
    case LicensePlate = 'license_plate';
}
