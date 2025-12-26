<?php

namespace backends\issueAdapter {

    use backends\backend;

    abstract class issueAdapter extends backend {
        protected $tt_url;
        protected $tt_token;

        public function __construct($config, $db, $redis, $login = false) {
            parent::__construct($config, $db, $redis, $login);

            $this->tt_url = $this->config['backends']['issueAdapter']['tt_url'];
            $this->tt_token = $this->config['backends']['issueAdapter']['tt_token'];
        }

        abstract public function createIssue($phone, $data);

        abstract public function listConnectIssues($phone);

        abstract public function commentIssue($issueId, $comment);

        abstract public function actionIssue($data);
    }
}
