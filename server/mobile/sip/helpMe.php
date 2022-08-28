<?php

/**
 * @api {post} /sip/helpMe звонок в техподержку
 * @apiVersion 1.0.0
 * @apiDescription ***в работе***
 *
 * @apiGroup SIP
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiSuccess {Object} - параметры для совершения звонка
 * @apiSuccess {String} -.server адрес сервера
 * @apiSuccess {Number} -.port порт
 * @apiSuccess {String="udp","tcp","tls"} -.transport тип подключения
 * @apiSuccess {String} -.extension внутренний номер (login)
 * @apiSuccess {String} -.pass пароль
 * @apiSuccess {String} -.dial="429999" куда звонить
 * @apiSuccess {String="stun:stun.l.google.com:19302"} [-.stun] stun сервер
 */

auth();

/*$extension = (int)(mysqli_fetch_assoc(mysql("select dm.autoextension() as ext")))['ext'] + 2000000000;
$hash = md5(time() + rand());
mysql("insert into dm.turnusers_lt (realm, name, hmackey, expire) values ('dm.lanta.me', '$extension', md5(concat('$extension', ':', 'dm.lanta.me', ':', '$hash')), addtime(now(), '00:03:00'))");
mysql("insert into asterisk.ps_aors (id, max_contacts, remove_existing, synchronized, expire) values ('$extension', 1, 'yes', true, addtime(now(), '03:00:00'))");
mysql("insert ignore into asterisk.ps_auths (id, auth_type, password, username, synchronized) values ('$extension', 'userpass', '$hash', '$extension', true)");
mysql("insert ignore into asterisk.ps_endpoints (id, auth, outbound_auth, aors, context, disallow, allow, dtmf_mode, rtp_symmetric, force_rport, rewrite_contact, direct_media, transport, synchronized) values ('$extension', '$extension', '$extension', '$extension', 'default', 'all', 'alaw,h264', 'rfc4733', 'yes', 'yes', 'yes', 'no', 'transport-tcp', true)");
mysql("insert into dm.helpme values ('{$bearer['id']}', '$extension')");*/

$extension = '123';
$hash = '123123';

response(200, [
    'server' => 'dm.lanta.me',
    'port' => 54675,
    'transport' => 'tcp',
    'extension' => (string)$extension,
    'pass' => $hash,
    'dial' => '429999',
    'stun' => 'stun:stun.l.google.com:19302',
    'stun_transport' => 'udp',
//        'turn' => 'turn:37.235.209.140:3478',
//        'turn_transport' => 'udp',
//        'turn_username' => $extension,
//        'turn_password' => $hash,
]);
