<?php

/**
 * @api {post} /inbox/inbox входящие
 * @apiVersion 1.0.0
 * @apiDescription **[нет верстки]**
 *
 * @apiGroup Inbox
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiSuccess {Object} - страничка которую надо отобразить во вьюшке
 * @apiSuccess {String} -.basePath базовый путь (от которго должна была загрузиться страница)
 * @apiSuccess {String} -.code html страница
 */

    auth();

    require_once __DIR__ . "/../../lib/parsedown/Parsedown.php";

    $parsedown = new Parsedown();
    $inbox = loadBackend("inbox");
    $subscriber_id = (int)$subscriber['subscriberId'];
    $msgs = array_map(function($item) {
        return ['msgId' => $item['msgId'], 'date' => $item['date'], 'msg' => $item['msg']];
    }, $inbox->getMessages($subscriber_id, "dates", ["dateFrom" => 946684800, "dateTo" => 2147483646]));

    usort($msgs, function ($a, $b) {
        if ($a['date'] > $b['date']) {
            return -1;
        } else
            if ($a['date'] < $b['date']) {
                return 1;
            } else {
                return 0;
            }
    });

    $nd = false;
    // setlocale (LC_TIME, 'ru_RU.UTF-8', 'Rus');
    $formatter = new IntlDateFormatter('ru_RU.UTF-8', IntlDateFormatter::LONG, IntlDateFormatter::NONE);
    $h = '';
    foreach ($msgs as $row) {
        $dd = $row['date'];
        $rd = $formatter->format($dd);
        if ($nd != $rd) {
            $nd = $rd;
            $h .= "<span class=\"inbox-date\">$rd</span><div class=\"inbox-message-primary\"><i class=\"inbox-message-icon icon-avatar\"></i><div class=\"inbox-message-content\">";
        } else {
            $h .= "<div class=\"inbox-message-secondary\"><div class=\"inbox-message-content\">";
        }
        $h .= "<p>";
        $msg = nl2br($row['msg']);
        $msg = str_replace(" lnt.ooo/", " https://lnt.ooo/", $msg);
        $msg = str_replace("\tlnt.ooo/", "\thttps://lnt.ooo/", $msg);
        $msg = str_replace(">lnt.ooo/", ">https://lnt.ooo/", $msg);
        $msg = str_replace(";lnt.ooo/", ";https://lnt.ooo/", $msg);
        if (strpos($msg, "lnt.ooo/") === 0) {
            $msg = "https://".$msg;
        }
        $msg = $parsedown->parse($msg);
        $msg = str_replace("<a href=", "<a target=\"_blank\" href=", $msg);
        $msg = preg_replace("/([+7][0-9]{11})|([7|8][0-9]{10})/i", "<a href='tel:\${0}'>\${0}</a>", $msg);
        $h .= $msg;
        $h .= "</p>";
        $h .= "<span class=\"inbox-message-time\">".date("H:i", $dd)."</span></div></div>";
    //        $h .= "<script type=\"application/javascript\">scrollingElement = (document.scrollingElement || document.body);scrollingElement.scrollTop = scrollingElement.scrollHeight;</script>";
    }
    $html = str_replace("%c", $h, file_get_contents(__DIR__ . "/../../mobile/templates/inbox.html"));

    // помечаем все сообщения как прочитанные
    $inbox->markMessageAsReaded($subscriber_id);

    response(200, [ 'basePath' => $config['mobile']['web_server_base_path'], 'code' => trim($html) ]);
