--------------------------------------------------------------------------------
-- Общий проект (ЛАНТА) Обращение [точка входа]
--------------------------------------------------------------------------------

--------------------------------------------------------------------------------
-- Настройки
--------------------------------------------------------------------------------

--------------------------------------------------------------------------------
-- Монтажные работы, можно координировать
--------------------------------------------------------------------------------

can_coordinate = {
    -- пустышка на точку присутствия
    1001,
    -- пустышка на коммутатор
    2001,
    -- пустышка на договор
    5001,
    -- оказание доп услуг
    5301,
    -- отсутствует доступ в интернет
    5302,
    -- настройка абонентского роутера
    5304,
    -- артефакты или квн
    5401,
    -- проблема с iptv player
    5402,
    -- проблемы с телефонией
    5502,
    -- проблема с домофонной панелью
	5703,
}

--------------------------------------------------------------------------------
-- После выполнения монтажных работ отправить на перерасчет
--------------------------------------------------------------------------------

refund = {
    -- недоступно оборудование
    5101,
    -- проблема с линком
    5102,
    -- перенос точки доступа
    5103,
    -- отсутствует доступ в интернет
    5302,
    -- перерасчет
    5601,
    -- восстановление договора
    5607
}

--------------------------------------------------------------------------------
-- Можно забирать заявки в работу
--------------------------------------------------------------------------------

can_work = {
    -- техотдел
    "tech",
    -- офис
    "office",
}

--------------------------------------------------------------------------------
-- Закрываем сразу, без дополнительных вопросов
--------------------------------------------------------------------------------

close_immediately = {
    -- исходящий звонок
    5303,
}

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
-- Скоординирована
--------------------------------------------------------------------------------

function isCoordinated(issue)
    return
        exists(issue["_cf_sheet"]) and
        exists(issue["_cf_sheet_date"]) and
        exists(issue["_cf_sheet_col"]) and
        exists(issue["_cf_sheet_cell"]) and
--        utils.strtotime(issue["_cf_sheet_date"] .. " " .. issue["_cf_sheet_cell"]) < utils.time() and
        exists(issue["_cf_sheet_cells"])
end

--------------------------------------------------------------------------------
-- числовой id каталога
--------------------------------------------------------------------------------

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

--------------------------------------------------------------------------------
-- каталог
--------------------------------------------------------------------------------

function catalogSubject(catalog)
    local ok, subject = pcall(
        function ()
            return trim(utils.explode("]", catalog)[1])
        end
    )

    if not ok then
        return false
    else
        return subject
    end
end

--------------------------------------------------------------------------------
-- изменение полей связанных с идентификатором объекта
--------------------------------------------------------------------------------

function updateObjectId(issue, original)

    -- заявка на точку присутствия
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

    -- заявка на коммутатор доступа
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

    -- из веб-морды создаем только "пустышки"
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

--------------------------------------------------------------------------------
-- создание заявки
--------------------------------------------------------------------------------

function createIssue(issue)

    -- специальное поле, когда заявка создается или обрабатывается сотрудником
    if tt.login() ~= "abonent" and tt.login() ~= "wx" then
        issue["_cf_updated"] = utils.time()
    end

    -- заполняем поля связанные с идентификатором объекта
    issue = updateObjectId(issue, nil)

    -- по умолчанию - на себя
    if issue["assigned"] == nil or issue["assigned"] == "" or (type(issue["assigned"]) == "table" and count(issue["assigned"]) == 0) then
        issue["assigned"] = {
            tt.login()
        }
    end

    -- если передали строку - преобразуем в массив
    if type(issue["assigned"]) == "string" then
        issue["assigned"] = {
            issue["assigned"]
        }
    end

    -- если уже массив, то приводим к "правильному виду"
    if type(issue["assigned"]) == "table" and count(issue["assigned"]) > 0 then
        issue["assigned"] = normalizeArray(issue["assigned"])
    end

-- так делать не надо, т.к. из админки может прилететь номер вида "[000-882] 8 (905) ***-*829"
--    if exists(issue["_cf_phone"]) and (issue["_cf_phone"]:sub(1, 1) ~= "8" or issue["_cf_phone"]:len() ~= 11) then
--        return false
--    end

    -- заявка всегда создается в статусе "Открыта"
    issue["status"] = "Открыта"

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
            "-",
            "Позвонить",
            "Позвонить сейчас",
            "Звонок совершен",
            "Недозвон",
            "-",
            "Отложить",
            "-",
            "Передать",
            "saAssignToMe",
            "-",
            "Изменить идентификатор",
            "-",
            "saWatch",
            "Наблюдатели",
            "-",
            "!saAddComment",
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

        primaryGroup = tt.myself()["primaryGroupAcronym"]

        -- НЕ "координируемые заявки", НЕ заявки на доставку, НЕ заявки назначенные на курьеров или си
        -- убираем координацию
        if not (hasValue(can_coordinate, catalogId(issue["catalog"])) or tonumberExt(issue["_cf_object_id"]) == -1 or intersect(issue["assigned"], { "courier", "sengineers" })) then
            actions = removeValues(actions, { "saCoordinate" })
        end

        -- если завка скоординирована, убираем действие "Отложить"
        -- если заявка не скоординированна, убираем "Работы завершены", "Снять с координации" и "Исполнители"
        if isCoordinated(issue) then
            actions = removeValue(actions, "Отложить")
        else
            actions = removeValues(actions, {
                "Работы завершены",
                "Снять с координации",
                "Исполнители"
            })
        end

        -- если сотрудник не из коллцентра или не в созвоне,
        -- то убираем "Звонок совершен" и "Недозвон"
        if not hasValue(tt.myGroups(), "callcenter") or tonumberExt(issue["_cf_calls_count"]) <= 0 then
            actions = removeValue(actions, "Звонок совершен")
            actions = removeValue(actions, "Недозвон")
        end

        -- если сотрудник не из техотдела "Позвонить сейчас"
        if primaryGroup ~= "tech" then
            actions = removeValue(actions, "Позвонить сейчас")
        end

        -- если сотрудник коллцентра и заявка в созвоне то переносим действия в шапку
        if hasValue(tt.myGroups(), "callcenter") and tonumberExt(issue["_cf_calls_count"]) > 0 then
            actions = replaceValue(actions, "Звонок совершен", "!Звонок совершен")
            actions = replaceValue(actions, "Недозвон", "!Недозвон")
        end

        -- если сотрудник коллцентра убираем Взять в работу" и "Назначить на себя"
        if primaryGroup == "callcenter" then
            actions = removeValue(actions, "saAssignToMe")
        end

        -- если сотрудник не принадлежит группе на которую назначена заявка
        -- или он ее уже взял, то взять в работу он ее не может
        if not intersect(tt.myGroups(), issue["assigned"]) or issue["_cf_worker"] == tt.login() then
            actions = removeValues(actions, { "Взять в работу", "!Взять в работу" })
        end

        -- если сотрудник не принадлежит группе на которую назначена заявка
        -- или он ее уже взял, то взять в работу он ее не может
        if not intersect(tt.myGroups(), issue["assigned"]) or issue["_cf_worker"] == tt.login() then
            actions = removeValues(actions, { "Взять в работу", "!Взять в работу" })
        end

        -- если нельзя брать заявки в работу, значит нельзя
        if not intersect(tt.myGroups(), can_work) then
            actions = removeValues(actions, { "Взять в работу", "!Взять в работу" })
        end

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
        return "assign"
    end

    -- изменить список наблюдающих
    if action == "Наблюдатели" then
        return {
            "watchers",
        }
    end

    -- координация
    if action == "saCoordinate" then
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

    -- изменить список исполнителей (монтажников)
    if action == "Исполнители" then
        return {
            "_cf_installers",
        }
    end

    -- при снятии с координации обязательно указываем причину (комментарий)
    if action == "Снять с координации" then
        return {
            "comment"
        }
    end

    -- работы завершены
    if action == "Работы завершены" then
        local doneFilter = {}

        if issue["_cf_object_id"] ~= nil and tonumberExt(issue["_cf_object_id"]) > 0 then
            -- на какой-то объект, могут быть проблемы с доступом
            doneFilter = {
                "Выполнено",
                "Проблема с доступом",
                "Отмена",
            }
        else
            -- курьер
            doneFilter = {
                "Выполнено",
                "Отмена",
            }
        end

        -- результат выполнения и обязательный комментарий
        return {
            ["%0%_cf_install_done"] = doneFilter,
            "%1%comment",
        }
    end

    -- закрываем заявку
    if action == "Закрыть" then
        if hasValue(close_immediately, catalogId(issue["catalog"])) then
            return true
        else
            if tonumberExt(issue["_cf_object_id"]) >= 500000000 and tonumberExt(issue["_cf_object_id"]) < 600000000 then
                -- абонентская заявка, может потребоваться оплата услуг по заявке
                if hasValue(tt.myGroups(), "callcenter") and exists(issue["_cf_install_done"]) and issue["_cf_install_done"] == "Выполнено" then
                    -- если закрывает сотрудник коллцентра после успешного
                    -- выезда монтажника - надо указать оценку
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
                -- заявка не на абонента, списывать не с кого
                if hasValue(tt.myGroups(), "callcenter") then
                    -- если закрывает сотрудник коллцентра, надо указать оценку
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
    end

    -- откладываем заявку, на какую-то дату и указываем причину (комментарий)
    if action == "Отложить" then
        return {
            "_cf_delay",
            "comment",
        }
    end

    -- изменяем поля делопроизводства: дата возникновения задолженности,
    -- список сервисов и необязательный комментарий
    if action == "Делопроизводство" then
        return {
            "_cf_debt_date",
            "_cf_debt_services",
            "optionalComment",
        }
    end

    -- переоткрываем заявку, надо указать причину (комментарий)
    if action == "Переоткрыть" then
        return {
            "comment"
        }
    end

    -- отправляем на созвон, надо указать дату созвона и обязательный комментарий
    if action == "Позвонить" then
        return {
            "_cf_delay",
            "comment"
        }
    end

    -- просто недозвон, ничего дополнительно указывать не надо
    if action == "Недозвон" then
        return true
    end

    -- звонок совершен, указываем что там "наболтали" (комментарий)
    if action == "Звонок совершен" then
        return {
            "comment"
        }
    end

    -- меняем идентификатор объекта
    if action == "Изменить идентификатор" then
        return {
            "_cf_object_id",
        }
    end

    -- взять в работу
    if action == "Взять в работу" then
        return true;
    end

    -- позвонить прям щаз
    if action == "Позвонить сейчас" then
        return true
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

    local comment = false

    if action == "Передать" then
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

        if tonumberExt(original["_cf_delay"]) == -1 then
            issue["_cf_delay"] = "%%unset"
        end

        issue["_cf_worker"] = "%%unset"
        issue["_cf_work_start"] = "%%unset"

        if hasValue(issue["assigned"], "callcenter") then
            issue["_cf_calls_count"] = 3
        else
            issue["_cf_calls_count"] = "%%unset"
        end

        return tt.modifyIssue(issue, action)
    end

    if action == "Наблюдатели" then
        return tt.modifyIssue(issue, action)
    end

    -- координация монтажных работ
    if action == "saCoordinate" then
        issue["_cf_install_done"] = "%%unset"
        issue["_cf_done_date"] = "%%unset"
        issue["_cf_hw_ok"] = "%%unset"
        issue["_cf_delay"] = "%%unset"

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
                if hasValue(refund, catalogId(original["catalog"])) then
                    if hasValue({ "Не доставлено", "Проблема с доступом", "Отмена", }, issue["_cf_install_done"]) then
                        -- надо-бы в офис, но возникли проблемы, пусть колл-центр разбирается
                        issue["assigned"] = {
                            "callcenter"
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

        -- оптика в квартиру, выставляем доп. атрибуты
        if catalogId(original["catalog"]) == 5106 and issue["_cf_install_done"] == "Выполнено" then
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
        issue["_cf_sheet"] = "%%unset"
        issue["_cf_sheet_date"] = "%%unset"
        issue["_cf_sheet_col"] = "%%unset"
        issue["_cf_sheet_cell"] = "%%unset"
        issue["_cf_sheet_cells"] = "%%unset"
        issue["_cf_installers"] = "%%unset"
        issue["_cf_can_change"] = "%%unset"
        issue["_cf_call_before_visit"] = "%%unset"
        issue["_cf_install_done"] = "%%unset"
        issue["_cf_done_date"] = "%%unset"

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
        issue["_cf_calls_count"] = 3
        return tt.modifyIssue(issue, action)
    end

    if action == "Звонок совершен" then
        issue["_cf_calls_count"] = "%%unset"
        return tt.modifyIssue(issue, action)
    end

    if action == "Недозвон" then
        issue["_cf_delay"] = utils.time() + 60 * 60
        issue["_cf_calls_count"] = tonumberExt(original["_cf_calls_count"]) - 1
        return tt.modifyIssue(issue, action)
    end

    if action == "Отложить" then
        issue["_cf_worker"] = "%%unset"
        issue["_cf_work_start"] = "%%unset"
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
        issue["_cf_closed_by"] = tt.login()
        issue["_cf_close_date"] = utils.time()

        -- есть сумма к списанию
        -- заявки на возврат через бухгалтерию "ходят" сами по себе
        if tonumberExt(issue["_cf_amount"]) > 0 and catalogId(original["catalog"]) ~= 5606 then
            custom.POST({
                ["action"] = "writeoff",
                ["client_id"] = tonumberExt(original["_cf_object_id"]) - 500000000,
                ["amount"] = tonumberExt(issue["_cf_amount"]),
                ["credit"] = true,
                ["issue_id"] = issue["issueId"],
            })
            issue["_cf_amount"] = -tonumberExt(issue["_cf_amount"])
        end

        return tt.modifyIssue(issue, action)
    end

    if action == "Переоткрыть" then
        issue["status"] = "Открыта"

        issue["_cf_closed_by"] = "%%unset"
        issue["_cf_close_date"] = "%%unset"

        issue["assigned"] = {
            tt.login()
        }

        issue["resolution"] = "%%unset"
        issue["_cf_worker"] = "%%unset"
        issue["_cf_work_start"] = "%%unset"

-- блок координации и монтажа
        issue["_cf_sheet"] = "%%unset"
        issue["_cf_sheet_date"] = "%%unset"
        issue["_cf_sheet_col"] = "%%unset"
        issue["_cf_sheet_cell"] = "%%unset"
        issue["_cf_sheet_cells"] = "%%unset"
        issue["_cf_installers"] = "%%unset"
        issue["_cf_can_change"] = "%%unset"
        issue["_cf_call_before_visit"] = "%%unset"
        issue["_cf_install_done"] = "%%unset"
        issue["_cf_done_date"] = "%%unset"

-- блок звонков
        issue["_cf_calls_count"] = "%%unset"

-- общее
        issue["_cf_delay"] = "%%unset"
        issue["_cf_quality_control"] = "%%unset"

        return tt.modifyIssue(issue, action)
    end

    if action == "Изменить идентификатор" then
        issue = updateObjectId(issue, original)

        return tt.modifyIssue(issue, action)
    end

    if action == "Взять в работу" then
        issue["_cf_worker"] = tt.login()
        issue["_cf_work_start"] = utils.time()
        issue["_cf_delay"] = "%%unset"

        return tt.modifyIssue(issue, action)
    end

    if action == "Позвонить сейчас" then
        issue["_cf_delay"] = -1
        issue["_cf_calls_count"] = 3

        return tt.modifyIssue(issue, action)
    end

    if tt.login() == "wx" and action == "Сброс отложенных" and original["_cf_delay"] < utils.time() then
        issue["_cf_delay"] = "%%unset"
        return tt.modifyIssue(issue, action, false)
    end

    if tt.login() == "wx" and action == "Сброс взятых в работу" and original["_cf_work_start"] < utils.time() then
        issue["_cf_work_start"] = "%%unset"
        issue["_cf_worker"] = "%%unset"
        return tt.modifyIssue(issue, action, false)
    end

    return false
end

--------------------------------------------------------------------------------
-- просмотр заявки
--------------------------------------------------------------------------------

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
        "*_cf_calls_count", "_cf_calls_count",
    }

    local notForClosedFields = {
        "*_cf_sheet_date", "_cf_sheet_date",
        "*_cf_sheet_date", "_cf_sheet_date",
        "*_cf_sheet_cell", "_cf_sheet_cell",
        "*_cf_sheet_cells", "_cf_sheet_cells",
        "*_cf_can_change", "_cf_can_change",
        "*_cf_call_before_visit", "_cf_call_before_visit",
        "*_cf_calls_count", "_cf_calls_count",
        "*_cf_delay", "_cf_delay",
        "*resolution", "resolution"
    }

    local fields = {
        "*parent",
        "catalog",
        "*status",
        "*author",
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
        "*_cf_in_call",
        "subject",
        "_cf_phone",
        "_cf_amount",
        "_cf_bank_details",
        "_cf_object_id",
        "_cf_debt_date",
        "_cf_debt_services",
        "_cf_clients",
        "description",
        "_cf_linked_issue",
    }

    if tonumberExt(issue["_cf_calls_count"]) == 0 then
        fields = removeValues(fields, callFields)
    end

    if not isOpened(issue) then
        fields = removeValues(fields, notForClosedFields)
    end

    if catalogSubject(issue["catalog"]) == issue["subject"] then
        fields = removeValue(fields, "subject")
    end

    if exists(issue["_cf_install_done"]) then
        fields = removeValues(fields, { "_cf_can_change", "*_cf_can_change" })
        fields = removeValues(fields, { "_cf_call_before_visit", "*_cf_call_before_visit" })
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
    return "ЛанТа"
end

--------------------------------------------------------------------------------
-- каталог рабочего процесса
--------------------------------------------------------------------------------

function getWorkflowCatalog()
    return {
        ["Общая"] = {
            "Пустышка",
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

    if isCoordinated(issue) then
        local title
        local installers = {}

        if action == "addComment" or utils.explode("#", action)[0] == "modifyComment" then
            title = "TT: Добавлен комментарий"
            installers = issue["_cf_installers"]
        end

        if workflowAction == "saCoordinate" then
            title = "TT: Заявка скоординирована"
            installers = issue["_cf_installers"]
        end

        if workflowAction == "Снять с координации" then
            title = "TT: Заявка снята с координации"
            installers = old["_cf_installers"]
        end

        if workflowAction == "Работы завершены" then
            title = "TT: Работы завершены"
            installers = old["_cf_installers"]
        end

        if title then
            pcall(function ()
                for i, w in pairs(installers) do
                    if w ~= tt.login() then
                        custom.POST({
                            ["action"] = "push",
                            ["login"] = w,
                            ["title"] = title,
                            ["body"] = issue["issueId"],
                        })
                    end
                end
            end)
        end
    end

    return mqtt.broadcast("issue/changed", issue["issueId"])
end