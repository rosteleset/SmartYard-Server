acChangeQRDelivery = "Сменить способ доставки"
qrDeliveryOffice = "Самовывоз"
qrDeliveryCourier = "Курьер"

-- сервисные функции

function normalizeArray(tab)
    local new = {}

    if tab[0] ~= nil then
        new[#new + 1] = tab[0]
    end

    for index, value in pairs(tab) do
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

-- переменная существует и если таблица то есть элементы

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

function getNewIssueTemplate(catalog)
    return {
        ["fields"] = {
            "subject",
            "description",
            "assigned",
            "tags",
            "_cf_phone"
        }
    }
end

-- special actions:
--
-- saAddComment - add comment
-- saAddFile    - add file
-- saAssignToMe - set assigned to myself
-- saWatch      - add (remove) myself to (from) watchers
-- saDelete     - delete issue
-- saSubIssue   - create subIssue

function getAvailableActions(issue)
    if issue["status"] ~= "Закрыта" then
        actions = {
            "!saAddComment",
            "saAddFile",
        }

        if exists(issue["_cf_qr_delivery"]) then
            table.insert(actions, "-")
            table.insert(actions, acChangeQRDelivery)
        end

        table.insert(actions, "-")
        table.insert(actions, "Закрыть")

        return actions
    else
        return {
            "Переоткрыть",
            "saDelete",
        }
    end
end

function getActionTemplate(issue, action)
    if action == "Закрыть" then
        if issue["status"] ~= "Закрыта" then
            return {
                "resolution",
                "comment",
            }
        else
            return false
        end
    end

    if action == "Переоткрыть" then
        if issue["status"] == "Закрыта" then
            return {
                "comment",
            }
        else
            return false
        end
    end

    if action == acChangeQRDelivery then
        return {
            "_cf_qr_delivery",
            "optionalComment"
        }
    end
end

function action(issue, action, original)
    if action == "Закрыть" and original["status"] == "Открыта" then
        issue["status"] = "Закрыта"
        tt.modifyIssue(issue)
    end

    if action == "Переоткрыть" and original["status"] == "Закрыта" then
        issue["status"] = "Открыта"
        issue["resolution"] = ""
        tt.modifyIssue(issue)
    end

    if action == acChangeQRDelivery and original["_cf_qr_delivery"] ~= issue["_cf_qr_delivery"] and issue["_cf_qr_delivery"] ~= nil then
        local result = tt.modifyIssue(issue, action)
        local comment = "Сменился способ доставки: " .. issue["_cf_qr_delivery"]
        tt.addComment(issue["issueId"], comment, true)
    end
end

function createIssue(issue)
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

function viewIssue(issue)
    return {
        ["issue"] = issue,
        ["actions"] = getAvailableActions(issue),
        ["showJournal"] = true,
        ["fields"] = {
            "project",
            "workflow",
            "catalog",
            "subject",
            "created",
            "updated",
            "status",
            "resolution",
            "description",
            "author",
            "assigned",
            "watchers",
            "*_cf_phone",
            "*_cf_address",
            "*_cf_camera_id",
            "*_cf_qr_delivery"
        }
    }
end

function getWorkflowName()
    return "Mobile"
end

function getWorkflowCatalog()
    return {
        ["Общие"] = {
            "Пустышка",
            "Обратный звонок",
            "Видео фрагмент",
            "Работа с адресами",
            "Услуги",
            "Договор",
        },
    }
end

function issueChanged(issue, action, old, new)
    -- add notifications here
    return mqtt.broadcast("issue/changed", issue["issueId"])
end
