<?php

    namespace hw\cameras {

        require_once __DIR__ . '/../cameras.php';

        class fake extends cameras {

            public function camshot(): string {
                return file_get_contents(__DIR__ . "/img/" . $this->url);
            }

            /**
             * @inheritDoc
             */
            public function get_sysinfo(): array
            {
                // TODO: Implement get_sysinfo() method.
            }

            /**
             * @inheritDoc
             */
            public function ping(): bool {
                return true;
            }

            /**
             * @inheritDoc
             */
            public function set_admin_password(string $password)
            {
                // TODO: Implement set_admin_password() method.
            }

            /**
             * @inheritDoc
             */
            public function write_config()
            {
                // TODO: Implement write_config() method.
            }

            /**
             * @inheritDoc
             */
            public function reboot()
            {
                // TODO: Implement reboot() method.
            }

            /**
             * @inheritDoc
             */
            public function reset()
            {
                // TODO: Implement reset() method.
            }
        }

    }
