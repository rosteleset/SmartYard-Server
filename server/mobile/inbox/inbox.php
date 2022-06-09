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

    auth(10);

    mysql('set names utf8mb4');

    require_once "lib/Parsedown.php";

    $parsedown = new Parsedown();

    $id = $bearer['id'];
    $id[0] = '7';

    $msgs = [];

    $qr = clickhouse("select date, msg from inbox where id='$id'");
    while ($row = mysqli_fetch_assoc($qr)) {
        $msgs[] = $row;
    }

    $qr = mysql("select date, msg from dm.inbox where id='$id'");
    while ($row = mysqli_fetch_assoc($qr)) {
        $msgs[] = $row;
    }

    usort($msgs, function ($a, $b) {
        if (strtotime($a['date']) > strtotime($b['date'])) {
            return -1;
        } else
            if (strtotime($a['date']) < strtotime($b['date'])) {
                return 1;
            } else {
                return 0;
            }
    });

    $nd = false;
    setlocale (LC_TIME, 'ru_RU.UTF-8', 'Rus');
    $h = '';
    foreach ($msgs as $row) {
        $dd = strtotime($row['date']);
        $rd = strftime("%d %b %Y", $dd);
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

    $html = str_replace("%c", $h, file_get_contents("templates/inbox.html"));

    mysql("update dm.inbox set readed=true, code='app' where id='$id' and code is null");
    mysql("update dm.inbox set readed=true where id='$id'");

    response(200, [ 'basePath' => 'https://dm.lanta.me/', 'code' => trim($html) ]);
