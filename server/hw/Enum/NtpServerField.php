<?php

namespace hw\Enum;

/**
 * Enum representing supported fields of {@see NtpServer}.
 * Used to define which NtpServer fields a device supports.
 */
enum NtpServerField
{
    case Address;
    case Port;
    case Timezone;
}
