-- Открытые заявки
-- Статус == Открыто

function isOpened(issue)
    return issue["status"] ~= "Закрыта" and issue["status"] ~= "Закрыто"
end

-- Скоординирована
-- Стоит в листе координации

function isCoordinated(issue)
    return
        issue["_cf_sheet"] ~= nil and issue["_cf_sheet"] ~= "" and
        issue["_cf_sheet_date"] ~= nil and issue["_cf_sheet_date"] ~= "" and
        issue["_cf_sheet_col"] ~= nil and issue["_cf_sheet_col"] ~= "" and
        issue["_cf_sheet_cell"] ~= nil and issue["_cf_sheet_cell"] ~= ""
end

-- СС. Позвонить сейчас
-- (Колл-центр == "да" и Дата созвона пусто) или (Дата созвона >= Текущая дата) или (Дата координации == Завтра и Дата координации <= Вчера)
	
function callNow(issue)
end

-- Координация.Просроченные
-- Заявка стоит в листе координации,имеет дату и время визита более чем на 3 дня вперед

function coordinationExpired(issue)
    return isCoordinated(issue) and utils.strtotime(issue["_cf_sheet_date"] .. " " .. issue["_cf_sheet_cell"] .. ":00") - utils.time() > 3 * 24 * 60 * 60
end
	
-- Открытые заявки пользователя
-- Заявка создана мной, она не закрыта

function myIssue(issue)
    return isCoordinated(issue) and issue["author"] == tt.login()
end

-- Добавлен в наблюдатели
-- Заявка не закрыта, добавлен в наблюдатели

function watching(issue)
    return isCoordinated(issue) and issue["watchers"] ~= nil and utils.in_array(tt.login(), issue["watchers"])
end
	
-- Связаться позже
-- Связаться по заявке позже сегодняшнего дня

function callLater(issue)
end
	
-- Отстойник заявок
-- Открытые заявки, Выполнено СИ=да

function fitDone(issue)
    return isOpened(issue) and issue["_cf_install_done"] == "Да"
end

-- Связаться сегодня
-- Дата созвона >= Текущая дата и Дата созвона < Завтра

function callToday(issue)
    if issue["_cf_call_date"] ~= nil then
        return isOpened(issue) and utils.strtotime(utils.date("Y-m-d")) <= issue["_cf_call_date"] and issue["_cf_call_date"] <= utils.strtotime(utils.date("Y-m-d", utils.strtotime("+1 day")))
    else
        return false
    end
end

-- Есть тех. возможность подключения
-- resolution="Ожидает распределения"

function waitingForCoordination(issue)
end
	
-- ЮЛ в офисе
-- Заявки с типом ЮЛ, в статусе "открыта", с любым вопросом: перерасчет, ремонт, пустышки, кроме авто подключений.

function officeUL(issue)
    return isOpened(issue) and issue["_cf_client_type"] ~= "ФЛ" and subject ~= "Подключение абонента"
end

-- Офис ЮЛ Автоподключения
-- Заявки с типом ЮЛ, в статусе АВТО: Подключение абонента, то есть в этом фильтре заявки которые уже были в работе, или только планируем подключение

function autoconnectUL(issue)
    return isOpened(issue) and issue["_cf_client_type"] ~= "ФЛ" and subject == "Подключение абонента"
end

-- Офис Оформление договора
-- Заявки с типом ЮЛ, в статусе АВТО: заявка с сайта - попадают в фильтр после того как в ней менеджер указывает условия подключения,
-- то есть передает ее на заведение учетки в адинке и дальнейшему оформлению и координациии

function waitingForContractUL(issue)
end