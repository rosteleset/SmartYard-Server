<?php

namespace backends\issueAdapter {

    use backends\backend;

    abstract class issueAdapter extends backend {
        abstract public function createIssue($phone, $data);

        abstract public function listConnectIssues($phone);

        abstract public function commentIssue($issueId, $comment);

        abstract public function actionIssue($data);
    }
}
