<?php

namespace PHPSTORM_META {
    override(
        \backend(0),
        map([
            'accounting' => \backends\accounting\accounting::class,
            'addresses' => \backends\addresses\addresses::class,
            'authentication' => \backends\authentication\authentication::class,
            'authorization' => \backends\authorization\authorization::class,
            'cameras' => \backends\cameras\cameras::class,
            'configs' => \backends\configs\configs::class,
            'dvr' => \backends\dvr\dvr::class,
            'dvr_exports' => \backends\dvr_exports\dvr_exports::class,
            'files' => \backends\files\files::class,
            'frs' => \backends\frs\frs::class,
            'geocoder' => \backends\geocoder\geocoder::class,
            'groups' => \backends\groups\groups::class,
            'households' => \backends\households\households::class,
            'inbox' => \backends\inbox\inbox::class,
            'isdn' => \backends\isdn\isdn::class,
            'plog' => \backends\plog\plog::class,
            'queue' => \backends\queue\queue::class,
            'sip' => \backends\sip\sip::class,
            'users' => \backends\users\users::class
        ])
    );
}