--------------------------------------------------------------------------------
-- Делопроизводство (ЛАНТА) [точка входа]
--------------------------------------------------------------------------------

--------------------------------------------------------------------------------
-- Настройки
--------------------------------------------------------------------------------

--------------------------------------------------------------------------------
-- Сервисные функции
--------------------------------------------------------------------------------

--------------------------------------------------------------------------------
-- Открытые заявки
--------------------------------------------------------------------------------

function isOpened(issue)
    return issue["status"] == "Открыта"
end

--------------------------------------------------------------------------------
-- изменение полей связанных с идентификатором объекта
--------------------------------------------------------------------------------

function updateObjectId(issue, original)
    -- заявка на абонента (договор)
    if tonumberExt(issue["_cf_object_id"]) >= 500000000 and tonumberExt(issue["_cf_object_id"]) < 600000000 then
        local client_id = tonumberExt(issue["_cf_object_id"]) - 500000000

        local client_info = custom.GET({
            ["action"] = "client_info",
            ["with_geo"] = 1,
            ["client_id"] = client_id,
        })

        issue["_cf_client_type"] = "Прочие"

        if mb.substr(client_info["common"]["contract_name"], 0, 2) == "ФЛ" then
            issue["_cf_client_type"] = "ФЛ"
        end

        if mb.substr(client_info["common"]["contract_name"], 0, 2) == "ЮЛ" then
            issue["_cf_client_type"] = "ЮЛ"
        end

        if client_info["polygon"] ~= nil and client_info["polygon"] ~= "" then
            issue["_cf_polygon"] = client_info["polygon"]
        end

        if client_info["geo"] ~= nil then
            issue["_cf_geo"] = {
                ["type"] = "Point",
                ["coordinates"] = {
                    client_info["geo"]["lon"],
                    client_info["geo"]["lat"],
                }
            }
        end
    end

    return issue
end

--------------------------------------------------------------------------------
-- Заявка
--------------------------------------------------------------------------------

--------------------------------------------------------------------------------
-- шаблон новой заявки
--------------------------------------------------------------------------------

function getNewIssueTemplate(catalog)
    return {
        ["fields"] = {
            "catalog",
            "_cf_object_id",
            "_cf_debt_date",
            "_cf_debt_services",
        }
    }
end

--------------------------------------------------------------------------------
-- создание заявки
--------------------------------------------------------------------------------

function createIssue(issue)
    issue["subject"] = "Делопроизводство"

    -- специальное поле, когда заявка создается или обрабатывается сотрудником
    if tt.login() ~= "abonent" and tt.login() ~= "wx" then
        issue["_cf_updated"] = utils.time()
    end

    if tonumber(issue["_cf_object_id"]) < 100000000 then
        issue["_cf_object_id"] = tonumber(issue["_cf_object_id"]) + 500000000
    end

    -- заполняем поля связанные с идентификатором объекта
    issue = updateObjectId(issue, nil)

    issue["assigned"] = {
        "office",
    }

    -- если уже массив, то приводим к "правильному виду"
    if type(issue["assigned"]) == "table" and count(issue["assigned"]) > 0 then
        issue["assigned"] = normalizeArray(issue["assigned"])
    end

    -- заявка всегда создается в статусе "Открыта"
    issue["status"] = "Открыта"
    issue["resolution"] = "Созвон"

    return tt.createIssue(issue)
end

--------------------------------------------------------------------------------
-- получить список доступных действий
--------------------------------------------------------------------------------

-- особые действия:
--
-- saAddComment - добавить комментарий
-- saAddFile    - добавить файл(ы)
-- saAssignToMe - назначить на себя
-- saWatch      - добавить себя в наблюдатели или убрать себя из наблюдателей
-- saDelete     - удалить задачу
-- saSubIssue   - создать подзадачу
-- saCoordinate - скоординировать
-- saLink       - связать с другой задачей

function getAvailableActions(issue)
    if isOpened(issue) then

        -- если заявка открыта, то по умолчанию доступны все действия
        local actions = {
            "!Взять в работу",
            "Отложить",
            "-",
            "Задолженность",
            "-",
            "Созвон",
            "Досудебное урегулирование",
            "Отстойник №1",
            "Обращение в суд",
            "Отстойник №2",
            "Передача приставам",
            "Ответ от приставов",
            "-",
            "!saAddComment",
            "saAddFile",
            "-",
            "Закрыть",
        }
        
        actions = removeValue(actions, issue["resolution"])

        return actions
    else
        -- если заявка закрыта, то можно только переоткрыть и удалить
        return {
            "!Переоткрыть",
            "-",
            "saDelete",
        }
    end
end

--------------------------------------------------------------------------------
-- получить шаблон диалога для действия
--------------------------------------------------------------------------------

function getActionTemplate(issue, action)
    local available = getAvailableActions(issue)

    if not hasValue(available, action) then
        return false
    end

    -- передать заявку в другой отдел
    -- кастомная функция
    if action == "Передать" then
        return "assign";
    end

    -- изменить список наблюдающих
    if action == "Наблюдатели" then
        return {
            "watchers",
        }
    end

    -- откладываем заявку, на какую-то дату и указываем причину (комментарий)
    if action == "Отложить" then
        return {
            "_cf_delay",
            "comment",
        }
    end

    -- меняем (если нужно) дату образования задолженности и список услуг
    if action == "Задолженность" then
        return {
            "_cf_debt_date",
            "_cf_debt_services",
        }
    end

    if action == "Созвон" then
        return true
    end
    
    if action == "Досудебное урегулирование" then
        return true
    end

    if action == "Отстойник №1" then
        return {
            "_cf_claim_date",
        }
    end        

    -- закрываем заявку
    if action == "Закрыть" then
        return {
            ["%0%resolution"] = {
                "Выполнено",
                "Дело прекращено",
                "Денежные средства возвращены",
                "Оборудование возвращено",
                "Прочее",
            },
            "%1%optionalComment",
        }
    end

    return false
end

--------------------------------------------------------------------------------
-- выполнить действие
--------------------------------------------------------------------------------

function action(issue, action, original)
    local available = getAvailableActions(original)

    if not hasValue(available, action) then
        return false
    end

    -- специальное поле, когда заявка создается или обрабатывается сотрудником
    if tt.login() ~= "abonent" and tt.login() ~= "wx" then
        issue["_cf_updated"] = utils.time()
    end

    if action == "Закрыть" then
        issue["status"] = "Закрыта"
        issue["_cf_closed_by"] = tt.login()
        issue["_cf_close_date"] = utils.time()
    end

    if action == "Задолженность" then
        -- только то что есть в диалоге
    end

    if action == "Созвон" then
        issue["resolution"] = "Созвон"
    end
    
    if action == "Досудебное урегулирование" then
        issue["resolution"] = "Досудебное урегулирование"
    end

    if action == "Отстойник №1" then
        issue["resolution"] = "Отстойник №1"
    end        

    return tt.modifyIssue(issue, action)
end

--------------------------------------------------------------------------------
-- просмотр заявки
--------------------------------------------------------------------------------

function viewIssue(issue)
    local notForClosedFields = {
        "*_cf_calls_count", "_cf_calls_count",
        "*_cf_delay", "_cf_delay",
        "*resolution", "resolution"
    }

    local fields = {
        "*subject",
        "*catalog",
        "*status",
        "*created",
        "*updated",
        "*assigned",
        "*resolution",
        "*_cf_worker",
        "*_cf_work_start",
        "*_cf_closed_by",
        "*_cf_close_date",
        "*watchers",
        "*_cf_sheet_date",
        "*_cf_sheet",
        "*_cf_install_done",
        "*_cf_installers",
        "*_cf_can_change",
        "*_cf_call_before_visit",
        "*_cf_calls_count",
        "*_cf_delay",
        "_cf_phone",
        "_cf_amount",
        "_cf_bank_details",
        "_cf_object_id",
        "_cf_debt_date",
        "_cf_debt_services",
        "_cf_clients",
        "_cf_linked_issue",
    }

    if not isOpened(issue) then
        fields = removeValues(fields, notForClosedFields)
    end

    return {
        ["issue"] = issue,
        ["actions"] = getAvailableActions(issue),
        ["showJournal"] = true,
        ["fields"] = fields,
    }
end

--------------------------------------------------------------------------------
-- имя рабочего процесса
--------------------------------------------------------------------------------

function getWorkflowName()
    return "Делопроизводство"
end

--------------------------------------------------------------------------------
-- каталог рабочего процесса
--------------------------------------------------------------------------------

function getWorkflowCatalog()
    return {
        ["Делопроизводство"] = {
            "Аренда",
            "Рассрочка",
        },
    }
end

--------------------------------------------------------------------------------
-- действия при изменении заявки
--------------------------------------------------------------------------------

function issueChanged(issue, action, old, new, workflowAction)
    if exists(issue["watchers"]) then
        for i, w in pairs(issue["watchers"]) do
            if w ~= tt.login() then
                users.notify(w, issue["issueId"],
                    "Заявка"
                    ..
                    "\n"
                    ..
                    "https://tt.lanta.me//?#tt&issue=" .. issue["issueId"]
                    ..
                    "\n"
                    ..
                    "изменена (" .. action .. ")"
                    ..
                    "\n"
                    ..
                    "пользователем " .. tt.login()
                )
            end
        end
    end

    return mqtt.broadcast("issue/changed", issue["issueId"])
end