<?php

namespace hw\ip\camera\entities;

final class DetectionZone
{

    public function __construct(
        public float $x,
        public float $y,
        public float $width,
        public float $height,
    )
    {
    }
}
