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
    utils.error_log(utils.print_r(issue))
    return tt.createIssue(issue)
end
