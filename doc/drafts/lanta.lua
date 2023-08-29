-- Общий проект (ЛАНТА) Обращение [точка входа]

--
-- Настройки
--

-- Офисная работа

office_work = { 5002, 5003, 5013, 5017, 5018, }

-- Монтажные работы

hardware_work = {
    -- на точку присутствия
    1001,
    -- на коммутатор
    2001,
    -- абонентские
    5001, 5004, 5005, 5006, 5007, 5008, 5009, 5010, 5011, 5012, 5014, 5015, 5016,
}

refund_work = { 5005, 5006, 5015, }

-- Видеонаблюдения

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
        exists(issue["_cf_sheet_cells"])
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
    return isCoordinated(issue) and issue["watchers"] ~= nil and hasValue(tt.login(), issue["watchers"])
end

-- Связаться позже
-- Связаться по заявке позже сегодняшнего дня

function callLater(issue)
end

-- Отстойник заявок
-- Открытые заявки, Выполнено СИ=да

function fitDone(issue)
    return isOpened(issue) and (issue["_cf_install_done"] == "Выполнено" or issue["_cf_install_done"] == "Камера установлена" or issue["_cf_install_done"] == "Установлен микрофон" or issue["_cf_install_done"] == "Установлена камера и микрофон")
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
-- Сервисные функции
--

-- числовой id каталога

function catalogId(catalog)
    local ok, id = pcall(
        function ()
            return tonumberExt(utils.explode("]", utils.explode("[", catalog)[1])[0])
        end
    )

    if not ok then
        return -1
    else
        return id
    end
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

    if tonumberExt(issue["_cf_object_id"]) >= 100000000 and tonumberExt(issue["_cf_object_id"]) < 200000000 then
        local chest_id = tonumberExt(issue["_cf_object_id"]) - 100000000

        local chest_info = custom.GET({
            ["action"] = "chest",
            ["chest_id"] = chest_id,
        })

        if chest_info["polygon"] ~= nil and chest_info["polygon"] ~= "" then
            issue["_cf_polygon"] = chest_info["polygon"]
        end

        if chest_info["lat"] ~= nil and chest_info["lon"] ~= nil then
            issue["_cf_geo"] = {
                ["type"] = "Point",
                ["coordinates"] = {
                    chest_info["lon"],
                    chest_info["lat"],
                }
            }
        end
    end

    if tonumberExt(issue["_cf_object_id"]) >= 200000000 and tonumberExt(issue["_cf_object_id"]) < 300000000 then
        local l2_sw_id = tonumberExt(issue["_cf_object_id"]) - 200000000

        local l2_sw_info = custom.GET({
            ["action"] = "l2",
            ["l2_sw_id"] = l2_sw_id,
        })

        if l2_sw_info["chest"]["polygon"] ~= nil and l2_sw_info["chest"]["polygon"] ~= "" then
            issue["_cf_polygon"] = l2_sw_info["chest"]["polygon"]
        end

        if l2_sw_info["chest"]["lat"] ~= nil and l2_sw_info["chest"]["lon"] ~= nil then
            issue["_cf_geo"] = {
                ["type"] = "Point",
                ["coordinates"] = {
                    l2_sw_info["chest"]["lon"],
                    l2_sw_info["chest"]["lat"],
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
            "Назначить (передать)",
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

        if not hasValue(hardware_work, catalogId(issue.catalog)) then
            actions = removeValue(actions, "saCoordinate")
            actions = removeValue(actions, "Работы завершены")
            actions = removeValue(actions, "Снять с координации")
            actions = removeValue(actions, "Исполнители")
        end

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
    if action == "Назначить (передать)" then
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
            doneFilter = {
                "Выполнено",
                "Отмена",
            }
            if tonumberExt(issue["_cf_object_id"]) >= 100000000 and tonumberExt(issue["_cf_object_id"]) < 200000000 then
                needAccessInfo = true
                doneFilter = {
                    "Выполнено",
                    "Проблема с доступом",
                    "Отмена",
                }
            end
            if tonumberExt(issue["_cf_object_id"]) >= 200000000 and tonumberExt(issue["_cf_object_id"]) < 300000000 then
                needAccessInfo = true
                doneFilter = {
                    "Выполнено",
                    "Проблема с доступом",
                    "Отмена",
                }
            end
            if tonumberExt(issue["_cf_object_id"]) >= 500000000 and tonumberExt(issue["_cf_object_id"]) < 600000000 then
                needAccessInfo = true
                -- абонентские с выездом
                if hasValue(hardware_work, catalogId(issue.catalog)) then
                    doneFilter = {
                        "Выполнено",
                        "Проблема с доступом",
                        "Отмена",
                    }
                end
            end
        else
            -- курьер
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
        if tonumberExt(issue["_cf_object_id"]) >= 500000000 and tonumberExt(issue["_cf_object_id"]) < 600000000 then
            if hasValue(tt.myGroups(), "callcenter") then
                return {
                    "_cf_quality_control",
                    "_cf_amount",
                    "optionalComment"
                }
            else
                return {
                    "_cf_amount",
                    "optionalComment"
                }
            end
        else
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
    local comment = false

    if action == "Назначить (передать)" then
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
        return tt.modifyIssue(issue, action)
    end

    if action == "Наблюдатели" then
        return tt.modifyIssue(issue, action)
    end

    -- координация монтажных работ
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

        return tt.modifyIssue(issue, action)
    end

    -- назначить исполнителей на монтажные работы
    if action == "Исполнители" then
        issue["_cf_coordination_date"] = utils.time()
        issue["_cf_coordinator"] = tt.login()
        return tt.modifyIssue(issue, action)
    end

    -- завершение монтажных работ
    if action == "Работы завершены" then
        issue["_cf_done_date"] = utils.time()

        -- по умолчанию - автору
        issue["assigned"] = {
            original["author"]
        }

        if original["_cf_object_id"] ~= nil then
            if tonumberExt(original["_cf_object_id"]) >= 100000000 and tonumberExt(original["_cf_object_id"]) < 200000000 then
                -- точка присутствия - в техотдел
                issue["assigned"] = {
                    "tech"
                }
            elseif tonumberExt(original["_cf_object_id"]) >= 200000000 and tonumberExt(original["_cf_object_id"]) < 300000000 then
                -- l2 - в техотдел
                issue["assigned"] = {
                    "tech"
                }
            elseif tonumberExt(original["_cf_object_id"]) >= 500000000 and tonumberExt(original["_cf_object_id"]) < 600000000 then
                if hasValue(refund_work, catalogId(original["catalog"])) then
                    if hasValue({ "Не доставлено", "Проблема с доступом", "Отмена", }, issue["_cf_install_done"]) then
                        -- надо-бы в офис, но возникли проблемы, пусть колл-центр разбирается
                        issue["assigned"] = {
                            "office"
                        }
                    else
                        comment = "Выполнить перерасчет"
                        -- на перерасчет в офис
                        issue["assigned"] = {
                            "office"
                        }
                    end
                elseif original["_cf_client_type"] == "ФЛ" then
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
            elseif tonumberExt(original["_cf_object_id"]) < 0 and issue["_cf_install_done"] == "Выполнено" then
                issue["status"] = "Закрыта"
            end
        end
        
        if catalogId(original["catalog"]) == 5009 and issue["_cf_install_done"] == "Выполнено" then
            custom.POST({
                ["action"] = "extAttrib",
                ["client_id"] = tonumberExt(original["_cf_object_id"]) - 500000000,
                ["attrib"] = "FTTX",
                ["value"] = utils.date("Y-m-d"),
            })
            custom.POST({
                ["action"] = "extAttrib",
                ["client_id"] = tonumberExt(original["_cf_object_id"]) - 500000000,
                ["attrib"] = "CONNECTION_TYPE",
                ["value"] = 2,
            })
        end

        local result = tt.modifyIssue(issue, action)

        if result and comment then
            tt.addComment(issue["issueId"], comment, true)
        end

        return result
    end

    -- снять заявку с листа координации
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
            if tonumberExt(original["_cf_object_id"]) >= 100000000 and tonumberExt(original["_cf_object_id"]) < 200000000 then
                -- точка присутствия - в техотдел
                issue["assigned"] = {
                    "tech"
                }
            end
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
        return tt.modifyIssue(issue, action)
    end

    if action == "Позвонить" then
        issue["_cf_need_call"] = 1
        issue["_cf_calls_count"] = 0
        return tt.modifyIssue(issue, action)
    end

    if action == "Звонок совершен" then
        issue["_cf_need_call"] = 0
        issue["_cf_calls_count"] = tonumberExt(original["_cf_calls_count"]) + 1
        return tt.modifyIssue(issue, action)
    end

    if action == "Недозвон" then
        issue["_cf_call_date"] = utils.time() + 3 * 60
        issue["_cf_calls_count"] = tonumberExt(original["_cf_calls_count"]) + 1
        if issue["_cf_calls_count"] >= 3 then
            issue["_cf_need_call"] = 0
        end
        return tt.modifyIssue(issue, action)
    end

    if action == "Отложить" then
        return tt.modifyIssue(issue, action)
    end

    if action == "Делопроизводство" then
        issue["assigned"] = {
            "office"
        }
        return tt.modifyIssue(issue, action)
    end

    if action == "Закрыть" then
        issue["status"] = "Закрыта"

        if tonumberExt(issue._cf_amount) > 0 then
            -- заявки на возврат через бухгалтерию "ходят" сами по себе
            if catalogId(original["catalog"]) ~= 5003 then
                custom.POST({
                    ["action"] = "writeoff",
                    ["client_id"] = tonumberExt(original["_cf_object_id"]) - 500000000,
                    ["amount"] = tonumberExt(issue._cf_amount),
                    ["credit"] = true,
                    ["issue_id"] = issue["issueId"],
                })
                issue._cf_amount = -tonumberExt(issue._cf_amount)
            end
        end
        
        return tt.modifyIssue(issue, action)
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
        if exists(original["_cf_calls_count"]) then
            issue["_cf_calls_count"] = 0
        end
        if exists(original["_cf_need_call"]) then
            issue["_cf_need_call"] = 0
        end
        if exists(original["_cf_call_date"]) then
            issue["_cf_call_date"] = 0
        end
        if exists(original["_cf_anytime_call"]) then
            issue["_cf_anytime_call"] = 0
        end

-- общее
        if exists(original["_cf_delay"]) then
            issue["_cf_delay"] = 0
        end
        if exists(original["_cf_quality_control"]) then
            issue["_cf_quality_control"] = ""
        end

        return tt.modifyIssue(issue, action)
    end

    if action == "Изменить идентификатор" then
        issue = updateObjectId(issue, original)

        return tt.modifyIssue(issue, action)
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
        "_cf_amount",
        "_cf_bank_details",
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

    pcall(function ()
        if isCoordinated(issue) then
            local title
            local installers = {}

            if action == "addComment" or utils.explode("#", action)[0] == "modifyComment" then
                title = "TT: Добавлен комментарий"
                installers = issue["_cf_installers"]
            end

            if workflowAction == "Координация" then
                title = "TT: Заявка скоординирована"
                installers = issue["_cf_installers"]
            end

            if workflowAction == "Снять с координации" then
                title = "TT: Заявка снята с координации"
                installers = old["_cf_installers"]
            end

            if workflowAction == "Работы завершены" then
                title = "TT: Работы завершены"
                installers = issue["_cf_installers"]
            end

            if title then
                for i, w in pairs(installers) do
                    if w ~= tt.login() then
                        custom.POST({
                            ["action"] = "push",
                            ["login"] = w,
                            ["title"] = title,
                            ["body"] = issue.issueId,
                        })
                    end
                end
            end
        end
    end)

    return mqtt.broadcast("issue/changed", issue["issueId"])
end