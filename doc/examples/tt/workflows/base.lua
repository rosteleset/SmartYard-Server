function initProject(projectId)
    utils.error_log(projectId)
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
            "tags"
        }
    }
end

function availableActions(issue)
    return {}
end

function actionTemplate(issue, action)
    --
end

function doAction(issue, action, fields)
    --
end

function createIssue(issue)
    utils.error_log(utils.print_r(issue))
    return tt.createIssue(issue)
end

function viewIssue(issue)
    return {
        ["issue"] = issue,
        ["actions"] = {},
        ["fields"] = {
            "issueId",
            "project",
            "workflow",
            "subject",
            "description",
            "assigned",
            "watchers",
            "attachments",
            "tags"
        }
    }
end

function workflowName()
    return "Базовый"
end