-- Общий проект (ЛАНТА) Обращение [точка входа]

--
-- Утилиты
--

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
        issue["_cf_sheet"] ~= nil and issue["_cf_sheet"] ~= "" and
        issue["_cf_sheet_date"] ~= nil and issue["_cf_sheet_date"] ~= "" and
        issue["_cf_sheet_col"] ~= nil and issue["_cf_sheet_col"] ~= "" and
        issue["_cf_sheet_cell"] ~= nil and issue["_cf_sheet_cell"] ~= "" and
        (issue["_cf_install_done"] == nil or issue["_cf_install_done"] == "" or tonumber(issue["_cf_install_done"]) == 0)
end

-- СС. Позвонить сейчас
-- (Колл-центр == "да" и Дата созвона пусто) или (Дата созвона >= Текущая дата) или (Дата координации == Завтра и Дата координации <= Вчера)
	
function callNow(issue)
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
-- Шаблоны и действия
--

function coordinationTemplate(issue)
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

function coordinate(issue, workflow)
    issue["_cf_install_done"] = ""
    issue["_cf_coordination_date"] = utils.time()
    issue["_cf_coordinator"] = tt.login()
    issue["assigned"] = { }

    return tt.modifyIssue(issue)
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

-- создание заявки
function createIssue(issue)
    if issue["_cf_object_id"] ~= nil and tonumber(issue["_cf_object_id"]) >= 500000000 and tonumber(issue["_cf_object_id"]) < 600000000 then
        local client_id = tonumber(issue["_cf_object_id"]) - 500000000

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
    end
    
    if issue["assigned"] == nil or issue["assigned"] == "" or (type(issue["assigned"]) == "table" and #issue["assigned"] == 0) then
        issue["assigned"] = {
            tt.login()
        }
    else
        if type(issue["assigned"]) == "string" then
            issue["assigned"] = {
                issue["assigned"]
            }
        end
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


function getAvailableActions(issue)
    if isOpened(issue) then
        local actions = {
            "Звонок совершен",
            "Недозвон",
            "Позвонить",
            "-",
            "Назначить",
            "saAddComment",
            "saAddFile",
            "-",
            "saDelete",
        }
        
        if isOpened(issue) then
            actions[#actions + 1] = "-"
            actions[#actions + 1] = "saCoordinate"
            actions[#actions + 1] = "Исполнители"
        end
        
        if isCoordinated(issue) then
            actions[#actions + 1] = "-"
            actions[#actions + 1] = "Работы завершены"
            actions[#actions + 1] = "Отменить"
        end
        
        actions[#actions + 1] = "-"
        actions[#actions + 1] = "Отложить"
        actions[#actions + 1] = "Закрыть"
        
        if issue["subject"] == "Делопроизводство" then
            actions[#actions + 1] = "-"
            actions[#actions + 1] = "Делопроизводство"
        end
        
        if not hasValue(tt.myGroups(), "callcenter") or issue["_cf_need_call"] == nil or tonumber(issue["_cf_need_call"]) == 0 or tonumber(issue["_cf_call_date"]) >= utils.time() then
            actions = removeValues(actions, {
                "Звонок совершен",
                "Недозвон",
            })
        else
            actions = replaceValue(actions, "Звонок совершен", "!Звонок совершен")
            actions = replaceValue(actions, "Недозвон", "!Недозвон")
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
        }
    end
    
    if action == "Координация" then
        return coordinationTemplate(issue)
    end

    if action == "Исполнители" then
        return {
            "_cf_installers",
        }
    end

    if action == "Отменить" then
        return {
            "comment"
        }
    end
    
    local doneFilter = {}
    
    if issue["_cf_object_id"] ~= nil then
        if tonumber(issue["_cf_object_id"]) > 0 then
            if tonumber(original["_cf_object_id"]) >= 200000000 and tonumber(original["_cf_object_id"]) < 300000000 then
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
        return {
            ["%0%_cf_install_done"] = doneFilter,
            "%1%_cf_access_info",
            "%2%comment",
        }
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

    return false
end

-- выполнить действие
function action(issue, action, original)
    if action == "Назначить" then
        if issue["assigned"] == nil or issue["assigned"] == "" or (type(issue["assigned"]) == "table" and #issue["assigned"] == 0)then
            issue["assigned"] = {
                tt.login()
            }
        else
            if type(issue["assigned"]) == "string" then
                issue["assigned"] = {
                    issue["assigned"]
                }
            end
        end
        return tt.modifyIssue(issue)
    end

    if action == "Координация" then
        return coordinate(issue)
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
            if tonumber(original["_cf_object_id"]) >= 200000000 and tonumber(original["_cf_object_id"]) < 300000000 then
                -- l2 - в техотдел
                issue["assigned"] = {
                    "tech"
                }
            end
            if tonumber(original["_cf_object_id"]) >= 500000000 and tonumber(original["_cf_object_id"]) < 600000000 then
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

    if action == "Отменить" then
        issue["_cf_sheet"] = ""
        issue["_cf_sheet_date"] = ""
        issue["_cf_sheet_col"] = ""
        issue["_cf_sheet_cell"] = ""
        issue["_cf_sheet_cells"] = 0
        issue["_cf_installers"] = {}
        issue["_cf_can_change"] = 0
        issue["_cf_call_before_visit"] = 0
        issue["assigned"] = {
            tt.login()
        }
        return tt.modifyIssue(issue)
    end

    if action == "Позвонить" then
        issue["_cf_need_call"] = 1
        issue["_cf_calls_count"] = 0
        return tt.modifyIssue(issue)
    end
    
    if action == "Звонок совершен" then
        issue["_cf_need_call"] = 0
        if original["_cf_calls_count"] == nil then
            issue["_cf_calls_count"] = 1
        else
            issue["_cf_calls_count"] = original["_cf_calls_count"] + 1
        end
        return tt.modifyIssue(issue)
    end
    
    if action == "Недозвон" then
        issue["_cf_call_date"] = utils.time() + 30 * 60
        if original["_cf_calls_count"] == nil then
            issue["_cf_calls_count"] = 1
        else
            issue["_cf_calls_count"] = original["_cf_calls_count"] + 1
        end
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
        return tt.modifyIssue(issue)
    end

    return false
end

-- просмотр заявки
function viewIssue(issue)
    local fields = {
        "*catalog",
        "*status",
        "*author",
        "*assigned",
        "*watchers",
        "*_cf_sheet",
        "*_cf_sheet_cell",
        "*_cf_install_done",
        "*_cf_sheet_cells",
        "*_cf_installers",
        "*_cf_can_change",
        "*_cf_call_before_visit",
        "*_cf_call_date",
        "*_cf_anytime_call",
        "*_cf_calls_count",
        "*_cf_delay",
        "subject",
        "_cf_phone",
        "_cf_client_type",
        "_cf_polygon",
        "_cf_object_id",
        "_cf_debt_date",
        "_cf_debt_services",
        "description",
        "_cf_linked_issue",
    }

    if not hasValue(tt.myGroups(), "callcenter") then
        fields = removeValues(fields, {
            "*_cf_call_date",
            "_cf_call_date",
            "*_cf_anytime_call",
            "_cf_anytime_call",
            "*_cf_calls_count",
            "_cf_calls_count",
            "*_cf_call_before_visit",
            "_cf_call_before_visit",
        })
    end
    
    if not isCoordinated(issue) then
        fields = removeValues(fields, {
            "*_cf_call_before_visit",
            "_cf_call_before_visit",
            "*_cf_can_change",
            "_cf_can_change",
            "*_cf_sheet_cells",
            "_cf_sheet_cells",
        })
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
    return mqtt.broadcast("issue/changed", issue["issueId"])
end