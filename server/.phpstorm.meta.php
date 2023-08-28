<?php

namespace PHPSTORM_META {

    use cli\Cli;

    expectedArguments(
        \loadBackend(),
        0,
        'accounting',
        'addresses',
        'authentication',
        'authorization',
        'cameras',
        'cs',
        'dvr',
        'dvr_exports',
        'files',
        'frs',
        'geocoder',
        'groups',
        'households',
        'inbox',
        'isdn',
        'monitoring',
        'mqtt',
        'plog',
        'processes',
        'providers',
        'queue',
        'sip',
        'tt',
        'tt_journal',
        'users',
        'task'
    );

    override(
        \loadBackend(0),
        map([
            'accounting' => \backends\accounting\accounting::class,
            'addresses' => \backends\addresses\addresses::class,
            'authentication' => \backends\authentication\authentication::class,
            'authorization' => \backends\authorization\authorization::class,
            'cameras' => \backends\cameras\cameras::class,
            'configs' => \backends\configs\configs::class,
            'cs' => \backends\cs\cs::class,
            'dvr' => \backends\dvr\dvr::class,
            'dvr_exports' => \backends\dvr_exports\dvr_exports::class,
            'files' => \backends\files\files::class,
            'frs' => \backends\frs\frs::class,
            'geocoder' => \backends\geocoder\geocoder::class,
            'groups' => \backends\groups\groups::class,
            'households' => \backends\households\households::class,
            'inbox' => \backends\inbox\inbox::class,
            'isdn' => \backends\isdn\isdn::class,
            'monitoring' => \backends\monitoring\monitoring::class,
            'mqtt' => \backends\mqtt\mqtt::class,
            'plog' => \backends\plog\plog::class,
            'processes' => \backends\processes\processes::class,
            'providers' => \backends\providers\providers::class,
            'queue' => \backends\queue\queue::class,
            'sip' => \backends\sip\sip::class,
            'tt' => \backends\tt\tt::class,
            'tt_journal' => \backends\tt_journal\tt_journal::class,
            'users' => \backends\users\users::class,
            'tasks' => \backends\tasks\tasks::class
        ])
    );

    exitPoint(\forgot());
    exitPoint(\response());
    exitPoint(\usage());
}