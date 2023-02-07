function initProject(projectId)
    return projectId
end

function createIssueTemplate()
    return {
        ["fields"] = {
            "subject",
            "description",
            "assigned",
            "watchers",
            "attachments",
            "tags",
            "_cf_text",
        }
    }
end

function availableActions(issue)
    if issue["status"] ~= "closed" then
        return {
            "Закрыть",
        }
    else
        return {}
    end
end

function actionTemplate(issue, action)
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
end

function doAction(issue, action, fields)
    --
end

function createIssue(issue)
    issue["status"] = "opened";
    return tt.createIssue(issue)
end

function viewIssue(issue)
    return {
        ["issue"] = issue,
        ["actions"] = availableActions(issue),
        ["fields"] = {
            "issueId",
            "project",
            "workflow",
            "subject",
            "created",
            "updated",
            "status",
            "resolution",
            "description",
            "author",
            "assigned",
            "watchers",
            "tags",
            "attachments",
            "comments",
            "journal",
            "_cf_text",
        }
    }
end

function workflowName()
    return "Базовый"
end