function getNewIssueTemplate(catalog)
    utils.error_log(catalog)
    return {
        ["fields"] = {
            "subject",
            "description",
            "assigned",
            "watchers",
            "attachments",
            "tags",
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
    if issue["status"] ~= "closed" then
        return {
            "!saAddComment",
            "saAddFile",
            "-",
            "Закрыть",
        }
    else
        return {
            "Переоткрыть",
        }
    end
end

function getActionTemplate(issue, action)
    if action == "Закрыть" then
        if issue["status"] ~= "closed" then
            return {
                "resolution",
                "comment",
            }
        else
            return false
        end
    end
    if action == "Переоткрыть" then
        if issue["status"] == "closed" then
            return {
                "comment",
            }
        else
            return false
        end
    end
end

function action(issue, action, original)
    if action == "Закрыть" and original["status"] == "opened" then
        issue["status"] = "closed"
        tt.modifyIssue(issue)
    end
    if action == "Переоткрыть" and original["status"] == "closed" then
        issue["status"] = "opened"
        issue["resolution"] = ""
        tt.modifyIssue(issue)
    end
end

function createIssue(issue)
    issue["status"] = "opened";
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
        }
    }
end

function getWorkflowName()
    return "Базовый"
end

function getWorkflowCatalog()
    return {
        ["Общие"] = {
            "Пустышка",
        },
        ["Финансовая/договорная"] = {
            "Ошибочный платеж",
            "Возврат денежных средств",
            "Перерасчет",
        },
        ["Абонентская"] = {
            "Нет мака",
            "Нет запросов",
            "Переобжим коннектора",
        },
    }
end

function issueChanged(issue, action, old, new)
    -- add notifications here
    return mqtt.broadcast("issue/changed", issue["issueId"])
end
