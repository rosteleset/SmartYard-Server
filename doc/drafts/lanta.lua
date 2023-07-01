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

    if issue["catalog"] == "Пустышка" then
        issue["status"] = "Открыта"
        return tt.createIssue(issue)
    end
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
    local actions = {
        "saAddComment",
        "saAddFile",
        "-",
        "saDelete",
    }

    return actions
end

-- получить шаблон диалога для действия
function getActionTemplate(issue, action)
    return false
end

-- выполнить действие
function action(issue, action, original)
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
        "*_cf_coordinated_on",
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

    return {
        ["issue"] = issue,
        ["actions"] = getAvailableActions(issue),
        ["showJournal"] = true,
        ["fields"] = fields,
    }
end

-- имя рабочего процесса
function getWorkflowName()
    return "Обращение [точка входа]"
end

-- каталог рабочего процесса
function getWorkflowCatalog()
    return {
        ["Общая"] = {
            "Пустышка",
        },
    }
end