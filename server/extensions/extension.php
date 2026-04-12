<?php

    namespace extensions {

        require_once __DIR__ . "/../backends/backend.php";

        use backends\backend;

        class extension extends \backends\backend {
            public function install() {
                return true;
            }

            public function uninstall() {
                return true;
            }

            public function update() {
                return true;
            }
        }
    }