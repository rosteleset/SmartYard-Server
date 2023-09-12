<?php

namespace backends\issue_adapter {
    use backends\backend;

    abstract class issue_adapter extends backend {
        protected $tt_url;
        protected $tt_token;

        public function __construct($config, $db, $redis, $login = false)
        {
            parent::__construct($config, $db, $redis, $login);

            $this->tt_url = $this->config['backends']['issue_adapter']['tt_url'];
            $this->tt_token = $this->config['backends']['issue_adapter']['tt_token'];
        }

        abstract public function createIssueForDVRFragment($phone, $description, $camera_id, $datetime, $duration, $comment);
    }
}
