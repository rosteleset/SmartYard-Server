<?php

    /**
     * Purifies HTML content by removing potentially dangerous elements and attributes
     *
     * This function uses the HTMLPurifier library to sanitize user-provided HTML
     * and ensure it conforms to HTML 4.01 Transitional standards. It removes any
     * scripts, malicious code, or non-compliant markup while preserving safe HTML structure.
     *
     * @param string $dirtyHtml The unsanitized HTML string to be purified
     *
     * @return string The cleaned and safe HTML content
     *
     * @example
     * $userInput = '<p>Hello</p><script>alert("xss")</script>';
     * $safe = htmlPurifier($userInput);
     * // Returns: '<p>Hello</p>'
     */

    function htmlPurifier($dirtyHtml) {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Core.Encoding', 'UTF-8');
        $config->set('HTML.Doctype', 'HTML 4.01 Transitional');
        $config->set('Cache.DefinitionImpl', null);

        $purifier = new HTMLPurifier($config);
        return $purifier->purify($dirtyHtml);
    }