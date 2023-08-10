-- Общий проект (ЛАНТА) Обращение [точка входа]

--
-- Утилиты
--

function trim(s)
    return s:match "^%s*(.-)%s*$"
end

function tonumberExt(v)
    if not pcall(function () v = tonumber(v) end) then
        v = 0
    end

    if v ~= nil then
        return v
    else
        return 0
    end
end

function hasValue(tab, val)
    if tab[0] == val then
        return true
    end

    for index, value in ipairs(tab) do
        if value == val then
            return true
        end
    end

    return false
end

function removeValue(tab, val)
    local new = {}

    for index, value in ipairs(tab) do
        if value ~= val then
            new[#new + 1] = value
        end
    end

    return new
end

function replaceValue(tab, valFrom, valTo)
    local new = {}

    for index, value in ipairs(tab) do
        if value == valFrom then
            new[#new + 1] = valTo
        else
            new[#new + 1] = value
        end
    end

    return new
end

function removeValues(tab, vals)
    for index, value in ipairs(vals) do
        tab = removeValue(tab, value)
    end

    return tab
end

function insertAfter(tab, after, val, withSep)
    local new = {}

    for index, value in ipairs(tab) do
        if value == after then
            new[#new + 1] = value
            if withSep then
                new[#new + 1] = withSep
            end
            new[#new + 1] = val
        else
            new[#new + 1] = value
        end
    end

    return new
end

function insertFirst(tab, val, withSep)
    local new = {}

    new[#new + 1] = val
    if withSep then
        new[#new + 1] = withSep
    end

    for index, value in ipairs(tab) do
        new[#new + 1] = value
    end

    return new
end

function normalizeArray(tab)
    local new = {}

    if tab[0] ~= nil then
        new[#new + 1] = tab[0]
    end

    for index, value in ipairs(tab) do
        new[#new + 1] = value
    end

    return new
end

function count(tab)
    local c = 0

    for i, v in pairs(tab) do
        c = c + 1
    end

    return c
end

-- переменная сущемтвует и если таблица то есть элементы

function exists(v)
    if v == nil then
        return false
    end

    if type(v) == "table" then
        for i, v in pairs(v) do
            return true
        end
        return false
    end

    return true
end

function strExists(v)
    return exists(v) and trim(v) ~= ""
end

--
-- Условия
--

-- Открытые заявки
-- Статус == Открыто

function isOpened(issue)
    return
        issue["status"] == "" or issue["status"] == nil
        or
        (
            (issue["status"] == "opened" or issue["status"] == "open" or issue["status"] == "Открыта" or issue["status"] == "Открыто")
            and
            issue["status"] ~= "closed" and issue["status"] ~= "Закрыто" and issue["status"] ~= "Закрыта"
        )
end

-- Скоординирована
-- Стоит в листе координации

function isCoordinated(issue)
    return
        exists(issue["_cf_sheet"]) and
        exists(issue["_cf_sheet_date"]) and
        exists(issue["_cf_sheet_col"]) and
        exists(issue["_cf_sheet_cell"]) and
        exists(issue["_cf_sheet_cells"]) and
        not strExists(issue["_cf_install_done"])
end

-- СС. Позвонить сейчас
-- (Колл-центр == "да" и Дата созвона пусто) или (Дата созвона >= Текущая дата) или (Дата координации == Завтра и Дата координации <= Вчера)

function callNow(issue)
    return
        hasValue(tt.myGroups(), "callcenter") and
        tonumberExt(issue["_cf_need_call"]) == 1 and
        tonumberExt(issue["_cf_call_date"]) <= utils.time()
end

-- Координация.Просроченные
-- Заявка стоит в листе координации, имеет дату и время визита более чем на 3 дня вперед

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
-- resolution = "Ожидает распределения"

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

--
-- Заявка
--

-- шаблон новой заявки
function getNewIssueTemplate(catalog)
    if catalog == "Пустышка" then
        return {
            ["fields"] = {
                "subject",
                "description",
                "_cf_phone",
                "_cf_object_id",
                "assigned",
            }
        }
    end
end

function updateObjectId(issue, original)
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
            issue["assigned"] = {
                "callcenter"
            }
        end

        if mb.substr(client_info["common"]["contract_name"], 0, 2) == "ЮЛ" then
            issue["_cf_client_type"] = "ЮЛ"
            issue["assigned"] = {
                "office"
            }
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

-- создание заявки
function createIssue(issue)
    issue = updateObjectId(issue, nil)

    if issue["assigned"] == nil or issue["assigned"] == "" or (type(issue["assigned"]) == "table" and count(issue["assigned"]) == 0) then
        issue["assigned"] = {
            tt.login()
        }
    end
    if type(issue["assigned"]) == "string" then
        issue["assigned"] = {
            issue["assigned"]
        }
    end
    if type(issue["assigned"]) == "table" and count(issue["assigned"]) > 0 then
        issue["assigned"] = normalizeArray(issue["assigned"])
    end

    issue["status"] = "Открыта"

    return tt.createIssue(issue)
end

-- получить список доступных действий

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
        local actions = {
            "Позвонить",
            "Звонок совершен",
            "Недозвон",
            "-",
            "Отложить",
            "-",
            "Назначить",
            "saAssignToMe",
            "-",
            "Изменить идентификатор",
            "-",
            "saWatch",
            "Наблюдатели",
            "-",
            "saAddComment",
            "saAddFile",
            "-",
            "saSubIssue",
            "saLink",
            "-",
            "saCoordinate",
            "Работы завершены",
            "Снять с координации",
            "Исполнители",
            "-",
            "Закрыть",
        }

        if not isCoordinated(issue) then
            actions = removeValues(actions, {
                "Работы завершены",
                "Снять с координации",
            })
        end

        if not callNow(issue) then
            actions = removeValues(actions, {
                "Звонок совершен",
                "Недозвон",
            })
        end

        if hasValue(tt.myGroups(), "callcenter") then
            actions = removeValue(actions, "Отложить")
            actions = replaceValue(actions, "Звонок совершен", "!Звонок совершен")
            actions = replaceValue(actions, "Недозвон", "!Недозвон")
            actions = replaceValue(actions, "Позвонить", "!Позвонить")
        end

        if issue["subject"] ~= "Делопроизводство" then
            actions = removeValue(actions, "Делопроизводство")
        end

        return actions
    else
        return {
            "Переоткрыть",
            "-",
            "saDelete",
        }
    end
end

-- получить шаблон диалога для действия
function getActionTemplate(issue, action)
    if action == "Назначить" then
        return {
            "assigned",
            "optionalComment",
        }
    end

    if action == "Наблюдатели" then
        return {
            "watchers",
        }
    end

    if action == "Координация" then
        return {
            "_cf_sheet",
            "_cf_sheet_date",
            "_cf_sheet_cell",
            "_cf_sheet_col",
            "_cf_sheet_cells",
            "_cf_installers",
            "_cf_can_change",
            "_cf_call_before_visit",
        }
    end

    if action == "Исполнители" then
        return {
            "_cf_installers",
        }
    end

    if action == "Снять с координации" then
        return {
            "comment"
        }
    end

    local doneFilter = {}
    local needAccessInfo = false

    if issue["_cf_object_id"] ~= nil then
        if tonumberExt(issue["_cf_object_id"]) > 0 then
            needAccessInfo = true
            if tonumberExt(issue["_cf_object_id"]) >= 200000000 and tonumberExt(issue["_cf_object_id"]) < 300000000 then
                doneFilter = {
                    "Выполнено",
                    "Проблема с доступом",
                    "Отмена",
                }
            else
                doneFilter = {
                    "Выполнено",
                    "Камера установлена",
                    "Установлен микрофон",
                    "Установлена камера и микрофон",
                    "Проблема с доступом",
                    "Отмена",
                }
            end
        else
            doneFilter = {
                "Выполнено",
                "Не доставлено",
            }
        end
    end

    if action == "Работы завершены" then
        if needAccessInfo then
            return {
                ["%0%_cf_install_done"] = doneFilter,
                "%1%_cf_access_info",
                "%2%_cf_hw_ok",
                "%3%comment",
            }
        else
            return {
                ["%0%_cf_install_done"] = doneFilter,
                "%1%comment",
            }
        end
    end

    if action == "Закрыть" then
        if hasValue(tt.myGroups(), "callcenter") then
            return {
                "_cf_quality_control",
                "optionalComment"
            }
        else
            return {
                "optionalComment"
            }
        end
    end

    if action == "Отложить" then
        return {
            "_cf_delay",
            "comment",
        }
    end

    if action == "Делопроизводство" then
        return {
            "_cf_debt_date",
            "_cf_debt_services",
            "optionalComment",
        }
    end

    if action == "Переоткрыть" then
        return {
            "comment"
        }
    end

    if action == "Позвонить" then
        return {
            "_cf_call_date",
            "_cf_anytime_call",
            "comment"
        }
    end

    if action == "Недозвон" then
        return true
    end

    if action == "Звонок совершен" then
        return {
            "comment"
        }
    end

    if action == "Изменить идентификатор" then
        return {
            "_cf_object_id",
        }
    end

    return false
end

-- выполнить действие
function action(issue, action, original)
    if action == "Назначить" then
        if issue["assigned"] == nil or issue["assigned"] == "" or (type(issue["assigned"]) == "table" and count(issue["assigned"]) == 0) then
            issue["assigned"] = {
                tt.login()
            }
        end
        if type(issue["assigned"]) == "string" then
            issue["assigned"] = {
                issue["assigned"]
            }
        end
        if type(issue["assigned"]) == "table" and count(issue["assigned"]) > 0 then
            issue["assigned"] = normalizeArray(issue["assigned"])
        end
        return tt.modifyIssue(issue)
    end

    if action == "Наблюдатели" then
        return tt.modifyIssue(issue)
    end

    if action == "Координация" then
        if exists(original["_cf_install_done"]) then
            issue["_cf_install_done"] = ""
        end
        if exists(original["_cf_done_date"]) then
            issue["_cf_done_date"] = ""
        end
        if exists(original["_cf_hw_ok"]) then
            issue["_cf_hw_ok"] = ""
        end
        issue["_cf_coordination_date"] = utils.time()
        issue["_cf_coordinator"] = tt.login()
        if exists(issue["assigned"]) then
            issue["assigned"] = { }
        end

        return tt.modifyIssue(issue)
    end

    if action == "Исполнители" then
        issue["_cf_coordination_date"] = utils.time()
        issue["_cf_coordinator"] = tt.login()
        return tt.modifyIssue(issue)
    end

    if action == "Работы завершены" then
        issue["_cf_done_date"] = utils.time()

        -- по умолчанию - автору
        issue["assigned"] = {
            original["author"]
        }
        if original["_cf_object_id"] ~= nil then
            if tonumberExt(original["_cf_object_id"]) >= 200000000 and tonumberExt(original["_cf_object_id"]) < 300000000 then
                -- l2 - в техотдел
                issue["assigned"] = {
                    "tech"
                }
            end
            if tonumberExt(original["_cf_object_id"]) >= 500000000 and tonumberExt(original["_cf_object_id"]) < 600000000 then
                if original["_cf_client_type"] == "ФЛ" then
                    -- ФЛ - в коллцентр
                    issue["assigned"] = {
                        "callcenter"
                    }
                else
                    -- ЮЛ (и прочие) - в офис
                    issue["assigned"] = {
                        "office"
                    }
                end
            end
            if tonumberExt(original["_cf_object_id"]) < 0 and issue["_cf_install_done"] == "Выполнено" then
                issue["status"] = "Закрыта"
            end
        end
        return tt.modifyIssue(issue)
    end

    if action == "Снять с координации" then
        if exists(original["_cf_sheet"]) then
            issue["_cf_sheet"] = ""
        end
        if exists(original["_cf_sheet_date"]) then
            issue["_cf_sheet_date"] = ""
        end
        if exists(original["_cf_sheet_col"]) then
            issue["_cf_sheet_col"] = ""
        end
        if exists(original["_cf_sheet_cell"]) then
            issue["_cf_sheet_cell"] = ""
        end
        if exists(original["_cf_sheet_cells"]) then
            issue["_cf_sheet_cells"] = 0
        end
        if exists(original["_cf_installers"]) then
            issue["_cf_installers"] = {}
        end
        if exists(original["_cf_can_change"]) then
            issue["_cf_can_change"] = 0
        end
        if exists(original["_cf_call_before_visit"]) then
            issue["_cf_call_before_visit"] = 0
        end
        if exists(original["_cf_install_done"]) then
            issue["_cf_install_done"] = ""
        end
        if exists(original["_cf_done_date"]) then
            issue["_cf_done_date"] = ""
        end

        -- по умолчанию - на того кто совершает действие
        issue["assigned"] = {
            tt.login()
        }
        if original["_cf_object_id"] ~= nil then
            if tonumberExt(original["_cf_object_id"]) >= 200000000 and tonumberExt(original["_cf_object_id"]) < 300000000 then
                -- l2 - в техотдел
                issue["assigned"] = {
                    "tech"
                }
            end
            if tonumberExt(original["_cf_object_id"]) >= 500000000 and tonumberExt(original["_cf_object_id"]) < 600000000 then
                if original["_cf_client_type"] == "ФЛ" then
                    -- ФЛ - в коллцентр
                    issue["assigned"] = {
                        "callcenter"
                    }
                else
                    -- ЮЛ (и прочие) - в офис
                    issue["assigned"] = {
                        "office"
                    }
                end
            end
        end
        return tt.modifyIssue(issue)
    end

    if action == "Позвонить" then
        issue["_cf_need_call"] = 1
        issue["_cf_calls_count"] = 0
        return tt.modifyIssue(issue)
    end

    if action == "Звонок совершен" then
        issue["_cf_need_call"] = 0
        issue["_cf_calls_count"] = tonumberExt(original["_cf_calls_count"]) + 1
        return tt.modifyIssue(issue)
    end

    if action == "Недозвон" then
        issue["_cf_call_date"] = utils.time() + 3 * 60
        issue["_cf_calls_count"] = tonumberExt(original["_cf_calls_count"]) + 1
        if issue["_cf_calls_count"] >= 3 then
            issue["_cf_need_call"] = 0
        end
        return tt.modifyIssue(issue)
    end

    if action == "Отложить" then
        return tt.modifyIssue(issue)
    end

    if action == "Делопроизводство" then
        issue["assigned"] = {
            "office"
        }
        return tt.modifyIssue(issue)
    end

    if action == "Закрыть" then
        issue["status"] = "Закрыта"
        return tt.modifyIssue(issue)
    end

    if action == "Переоткрыть" then
        issue["status"] = "Открыта"

-- блок координации и монтажа
        if exists(original["_cf_sheet"]) then
            issue["_cf_sheet"] = ""
        end
        if exists(original["_cf_sheet_date"]) then
            issue["_cf_sheet_date"] = ""
        end
        if exists(original["_cf_sheet_col"]) then
            issue["_cf_sheet_col"] = ""
        end
        if exists(original["_cf_sheet_cell"]) then
            issue["_cf_sheet_cell"] = ""
        end
        if exists(original["_cf_sheet_cells"]) then
            issue["_cf_sheet_cells"] = 0
        end
        if exists(original["_cf_installers"]) then
            issue["_cf_installers"] = {}
        end
        if exists(original["_cf_can_change"]) then
            issue["_cf_can_change"] = 0
        end
        if exists(original["_cf_call_before_visit"]) then
            issue["_cf_call_before_visit"] = 0
        end
        if exists(original["_cf_install_done"]) then
            issue["_cf_install_done"] = ""
        end
        if exists(original["_cf_done_date"]) then
            issue["_cf_done_date"] = ""
        end

-- блок звонков
        if original(issue["_cf_calls_count"]) then
            issue["_cf_calls_count"] = 0
        end
        if original(issue["_cf_need_call"]) then
            issue["_cf_need_call"] = 0
        end
        if original(issue["_cf_call_date"]) then
            issue["_cf_call_date"] = 0
        end
        if original(issue["_cf_anytime_call"]) then
            issue["_cf_anytime_call"] = 0
        end

-- общее
        if original(issue["_cf_delay"]) then
            issue["_cf_delay"] = 0
        end
        if original(issue["_cf_quality_control"]) then
            issue["_cf_quality_control"] = ""
        end

        return tt.modifyIssue(issue)
    end

    if action == "Изменить идентификатор" then
        issue = updateObjectId(issue, original)

        return tt.modifyIssue(issue)
    end

    return false
end

-- просмотр заявки
function viewIssue(issue)
    local coordinationFields = {
        "*_cf_call_before_visit", "_cf_call_before_visit",
        "*_cf_can_change", "_cf_can_change",
        "*_cf_sheet_cells", "_cf_sheet_cells",
        "*_cf_sheet_date", "_cf_sheet_date",
        "*_cf_sheet", "_cf_sheet",
        "*_cf_sheet_cell", "_cf_sheet_cell",
        "*_cf_sheet_cells", "_cf_sheet_cells",
        "*_cf_install_done", "_cf_install_done",
        "*_cf_installers", "_cf_installers",
    }

    local callFields = {
        "*_cf_call_date", "_cf_call_date",
        "*_cf_anytime_call", "_cf_anytime_call",
        "*_cf_calls_count", "_cf_calls_count",
    }

    local notForClosedFields = {
        "*_cf_sheet_date", "_cf_sheet_date",
        "*_cf_sheet_date", "_cf_sheet_date",
        "*_cf_sheet_cell", "_cf_sheet_cell",
        "*_cf_sheet_cells", "_cf_sheet_cells",
        "*_cf_can_change", "_cf_can_change",
        "*_cf_call_before_visit", "_cf_call_before_visit",
        "*_cf_call_date", "_cf_call_date",
        "*_cf_anytime_call", "_cf_anytime_call",
        "*_cf_calls_count", "_cf_calls_count",
        "*_cf_delay", "_cf_delay",
    }

    local fields = {
        "*parent",
        "catalog",
        "*status",
        "author",
        "*assigned",
        "*watchers",
        "*_cf_sheet_date",
        "*_cf_sheet",
--        "*_cf_sheet_cell",
--        "*_cf_sheet_cells",
        "*_cf_install_done",
        "*_cf_installers",
        "*_cf_can_change",
        "*_cf_call_before_visit",
        "*_cf_need_call",
        "*_cf_call_date",
        "*_cf_anytime_call",
        "*_cf_calls_count",
        "*_cf_delay",
        "subject",
        "_cf_phone",
        "_cf_object_id",
        "_cf_debt_date",
        "_cf_debt_services",
        "description",
        "_cf_linked_issue",
    }

    if tonumberExt(issue["_cf_need_call"]) == 0 then
        fields = removeValues(fields, callFields)
    end

    if not isCoordinated(issue) then
        fields = removeValues(fields, coordinationFields)
    end

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

-- имя рабочего процесса
function getWorkflowName()
    return "ЛанТа"
end

-- каталог рабочего процесса
function getWorkflowCatalog()
    return {
        ["Общая"] = {
            "Пустышка",
        },
    }
end

function issueChanged(issue, action, old, new)
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
--                    .. "\n\n"
--                    ..
--                    utils.print_r(old)
--                    ..
--                    "\n =>\n"
--                    ..
--                    utils.print_r(new)
                )
            end
        end
    end
    return mqtt.broadcast("issue/changed", issue["issueId"])
end