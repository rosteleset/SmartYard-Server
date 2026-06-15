<?php

namespace hw\ip\domophone\rubetek;

class RubetekConst
{
    // Dialplan IDs
    public const CONCIERGE_ID = 'CONCIERGE';
    public const SOS_ID = 'SOS';

    /**
     * @var array<string, string> Mapping of CMS models between database and Rubetek names.
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

    /**
     * @var array<string, int> Maximum number of matrices supported by each CMS model.
     */
    public const CMS_MAX_COUNT_MAP = [
        'KM100-7.1' => 1,
        'KM100-7.3' => 3,
        'KM100-7.5' => 5,
        'KKM-100S2' => 1,
        'KKM-105' => 5,
        'KKM-108' => 8,
        'KMG-100' => 1,
    ];
}
