<?php

/**
 * @api {post} /user/restore восстановить доступ в ЛК
 * @apiVersion 1.0.0
 * @apiDescription **[метод готов]**
 *
 * @apiGroup User
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiParam {String} contract номер договора
 * @apiParam {String} [contactId] контакт на который выслать код подтверждения
 * @apiParam {String{4}} [code] код подтверждения
 * @apiParam {String} [comment] комментарий
 * @apiParam {String="t","f"} [notification="t"] использовать для уведомлений (главный номер, владелец договора)
 *
 * @apiSuccess {Object[]} [-] список возможных контактов
 * @apiSuccess {String} [-.id] идентификтор контакта
 * @apiSuccess {String} [-.contact] контакт (со звездами)
 * @apiSuccess {String="email","phone"} [-.type] тип контакта
 *
 * @apiErrorExample Ошибки
 * 403 требуется авторизация
 * 422 неверный формат данных
 * 404 пользователь не найден
 * 410 авторизация отозвана
 * 424 неверный токен
 */

    auth();
    response();

/*
    $contract = trim(@$postdata['contract']);

    if ($contract[0] == 'f') {
        $contract = substr($contract, 1);
    }

    if (mb_stripos($contract, 'фл-') === 0) {
        $contract = substr($contract, strpos($contract, '-') + 1);
    }

    $contract = explode('/', $contract)[0];

    $contract = (int)$contract;

    if (!$contract) {
        response(422, false, 'Обязательное поле', 'Необходимо указать логин');
    }

    $client_id = pg_fetch_result(pg_query("select client_id from clients where contract_name like 'ФЛ-$contract/%' or contract_name like 'ФЛ-0$contract/%' or contract_name like 'ФЛ-00$contract/%' or contract_name like 'ФЛ-000$contract/%'"), 0);

    if (!$client_id) {
        response(404, false, 'Не найден', 'Такой договор не существует');
    }

    if (array_key_exists('contactId', $postdata)) {
        $postdata['contact_id'] = $postdata['contactId'];
    }

    if (array_key_exists('contact_id', $postdata)) {
        $contact_id = pg_escape_string($postdata['contact_id']);
        $email = pg_fetch_result(pg_query("select attrib_value from ext_attrib where client_id=$client_id and attrib_name='E-MAIL' and md5(attrib_value) = '$contact_id'"), 0);
        $phone = pg_fetch_result(pg_query("select phone from client_phones where client_id=$client_id and md5(phone) = '$contact_id'"), 0);
        $already = pg_fetch_result(pg_query("select code from domophones.restore where id='{$bearer['id']}' and dst='$email:$phone'"), 0);
        if ($already) {
            $code = $already;
        } else {
            $code = sprintf("%04d", rand(0, 9999));
        }
        if ($email || $phone) {
            if ($already) {
                pg_query("update domophones.restore set date=now() where id='{$bearer['id']}' and dst='$email:$phone'");
            } else {
                pg_query("insert into domophones.restore (id, client_id, dst, code) values ('{$bearer['id']}', $client_id, '$email:$phone', '$code')");
            }
        } else {
            response(404);
        }
        if ($email) {
            email(
                $email,
                'Подтверждение адреса электронной почты',
                "Код для восстановления пароля $code"
            );
            response();
        }
        if ($phone) {
            pg_query("select sms.send_sms_v2('$phone', 'Код для восстановления пароля $code')");
            response();
        }
    } else {
        if (array_key_exists('code', $postdata)) {
            $code = pg_escape_string($postdata['code']);
            $success = pg_fetch_result(pg_query("select dst from domophones.restore where client_id=$client_id and code='$code' and failure_count<5"), 0);
            if ($success) {
                pg_query("delete from domophones.restore where client_id=$client_id and code='$code'");
    //                $passwd = substr(md5(time().rand()), 24);
    //                pg_query("update account set passwd='$passwd' where client_id=$client_id");
                $passwd = pg_fetch_result(pg_query("select passwd from account where client_id=$client_id"), 0);
                add_phone_to_client($client_id, $bearer['id'], trim(@$postdata['comment']), @$postdata['notification'] != 'f');
                $msg = pg_escape_string(pg_fetch_result(pg_query("select 'https://stat.lanta-net.ru Имя пользователя: '||login||' Пароль: '||passwd msg from account where client_id=$client_id"), 0));
                if ($success[0] == ':') {
                    pg_query("select sms.send_sms_v2('".substr($success, 1)."', '$msg')");
                } else {
                    email(
                        substr($success, 0, -1),
                        'Восстановление пароля учетной записи',
                        $msg
                    );
                }
                response();
            } else {
                pg_query("update domophones.restore set failure_count=failure_count+1 where client_id=$client_id");
                response(403);
            }
        } else {
            $contacts = [];
            $email = pg_fetch_result(pg_query("select attrib_value from ext_attrib where client_id=$client_id and attrib_name='E-MAIL'"), 0);
            if ($email) {
                $eh = $email;
                for ($i = 2; $i < strlen($eh) - 3; $i++) {
                    if ($eh[$i] != '@') {
                        $eh[$i] = '*';
                    }
                }
                $contacts[] = [
                    'id' => md5($email),
                    'contact' => $eh,
                    'type' => 'email'
                ];
            }
            $qr = pg_query("select phone from client_phones where client_id=$client_id and for_notification");
            while ($row = pg_fetch_assoc($qr)) {
                $contacts[] = [
                    'id' => md5($row['phone']),
                    'contact' => format_phone($row['phone'], true),
                    'type' => 'phone'
                ];
            }
            if (count($contacts)) {
                response(200, $contacts);
            } else {
                response();
            }
        }
    }

    response();
*/