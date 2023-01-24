function initProject(projectId)
    utils.error_log(projectId)
    return projectId
end

function createIssueTemplate()
    return {
        ["fields"] = {
            "subject",
            "description",
            "attachments",
            "tags"
        }
    }
end

function availableActions(issueId)
--
end

function actionTemplate(issueId, action)
--
end

function doAction(issueId, action, fields)
--
end

function createIssue(issue)
--
end
