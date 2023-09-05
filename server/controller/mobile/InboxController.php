<?php

namespace Selpol\Controller\mobile;

use Selpol\Controller\Controller;
use Selpol\Http\Response;

class InboxController extends Controller
{
    public function read(): Response
    {
        $user = $this->getSubscriber();

        $messageId = $this->request->getQueryParam('messageId');

        backend("inbox")->markMessageAsReaded($user['subscriberId'], $messageId ?? false);

        return $this->rbtResponse();
    }

    public function unread(): Response
    {
        $user = $this->getSubscriber();

        return $this->rbtResponse(data: ['count' => backend('inbox')->unreaded($user['subscriberId']), 'chat' => 0]);
    }
}