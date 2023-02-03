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
    utils.error_log(utils.print_r(issue))
    return issue
end

function workflowName()
    return "Базовый"
end