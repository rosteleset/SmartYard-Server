<?php

namespace Selpol\Controller\mobile;

use Selpol\Controller\Controller;
use Selpol\Http\Response;

class InboxController extends Controller
{
    public function read(): Response
    {
        /** @var array|null $user */
        $user = $this->request->getAttribute('auth')();

        if (!$user)
            return $this->rbtResponse(401);

        $messageId = $this->request->getQueryParam('messageId');

        backend("inbox")->markMessageAsReaded($user['subscriberId'], $messageId ?? false);

        return $this->rbtResponse();
    }

    public function unread(): Response
    {
        /** @var array|null $user */
        $user = $this->request->getAttribute('auth')();

        if (!$user)
            return $this->rbtResponse(401);

        return $this->rbtResponse(data: ['count' => backend('inbox')->unreaded($user['subscriberId']), 'chat' => 0]);
    }
}