<?php

namespace Selpol\Task\Tasks;

use Selpol\Task\Task;
use Throwable;

class RecordTask extends Task
{
    public int $subscriberId;
    public int $recordId;

    public function __construct(int $subscriberId, int $recordId)
    {
        parent::__construct('Загрузка архива (' . $subscriberId . ', ' . $recordId . ')');

        $this->subscriberId = $subscriberId;
        $this->recordId = $recordId;
    }

    public function onTask(): bool
    {
        $dvr_exports = backend('dvr_exports');

        if (!$dvr_exports)
            return false;

        $uuid = $dvr_exports->runDownloadRecordTask($this->recordId);

        $inbox = backend('inbox');

        if (!$inbox)
            return false;

        $inbox->sendMessage($this->subscriberId, i18n('dvr.videoReady'), i18n('dvr.threeDays'), config('api.mobile') . '/cctv/download/' . $uuid);

        return true;
    }

    public function onError(Throwable $throwable)
    {
        $inbox = backend('inbox');

        if (!$inbox)
            return;

        $inbox->sendMessage($this->subscriberId, i18n('dvr.video'), i18n('dvr.videoFailed'), 'icomtel://main/chat');
    }
}