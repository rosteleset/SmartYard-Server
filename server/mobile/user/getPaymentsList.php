<?php


   /**
    * @api {post} /mobile/user/getPaymentsList получить список договоров для оплаты
    * @apiVersion 1.0.0
    * @apiDescription ***в работе***
    *
    * @apiGroup User
    *
    * @apiHeader {String} authorization токен авторизации
    *
    * @apiSuccess {Object[]} - все адреса
    * @apiSuccess {Number} [-.houseId] идентификатор дома
    * @apiSuccess {Number} [-.flatId] идентификатор квартиры
    * @apiSuccess {String} -.address адрес
    * @apiSuccess {Object[]} -.accounts список договоров по адресу
    * @apiSuccess {Number} -.accounts.clientId идентификатор клиента
    * @apiSuccess {String} -.accounts.contractName номер договора
    * @apiSuccess {Number} -.accounts.contractPayName номер договора для оплаты
    * @apiSuccess {String="t","f"} -.accounts.blocked заблокирован
    * @apiSuccess {Number} -.accounts.balance баланс
    * @apiSuccess {Number} -.accounts.bonus бонусы
    * @apiSuccess {Number} -.accounts.bonusLevel бонусный уровень
    * @apiSuccess {Number} [-.accounts.payAdvice] рекомендуемая сумма к пополнению
    * @apiSuccess {String[]="internet","iptv","ctv","phone","cctv","domophone","gsm"} -.accounts.services подключенные услуги
    * @apiSuccess {String} [-.accounts.lcab] личный кабинет
    * @apiSuccess {String} [-.accounts.lcabPay] страница оплаты в ЛК
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
