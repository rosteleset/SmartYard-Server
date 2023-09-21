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
        abstract public function createIssueCallback($phone);
        abstract public function createIssueForgotEverything($phone);
        abstract public function createIssueConfirmAddress($phone, $description, $name, $address, $lat, $lon);
        abstract public function createIssueDeleteAddress($phone, $description, $name, $address, $lat, $lon, $reason);
        abstract public function createIssueUnavailableServices($phone, $description, $name, $address, $lat, $lon, $services);
        abstract public function createIssueAvailableWithSharedServices($phone, $description, $name, $address, $lat, $lon, $services);
        abstract public function createIssueAvailableWithoutSharedServices($phone, $description, $name, $address, $lat, $lon, $services);

        abstract public function listConnectIssues($phone);

        abstract public function commentIssue($issueId, $comment);

        abstract public function closeIssue($issueId);
    }
}
