<?php

function v98_virtual_intercom($db)
{
    require_once __DIR__ . '/../../virtual-intercom/PanelRepository.php';
    (new \VirtualIntercom\PanelRepository($db))->registerFields();
    return true;
}
