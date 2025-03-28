<?php

    function htmlPurifier($dirtyHtml) {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Core.Encoding', 'UTF-8');
        $config->set('HTML.Doctype', 'HTML 4.01 Transitional');
        $config->set('Cache.DefinitionImpl', null);

        $purifier = new HTMLPurifier($config);
        return $purifier->purify($dirtyHtml);
    }