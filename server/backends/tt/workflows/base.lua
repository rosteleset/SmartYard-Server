function initProject(projectId)
    return true;
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
