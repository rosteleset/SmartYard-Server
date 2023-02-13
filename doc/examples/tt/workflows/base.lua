function initProject(project)
    utils.error_log(utils.print_r(project))
    return project
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

-- special actions:
--
-- saAddComment - add comment
-- saAddFile    - add file
-- saAssignToMe - set assigned to myself
-- saWatch      - add myself to watchers
-- saDelete     - delete issue
-- saLink       - add link to another issue
-- saSubTask    - create subIssue

function availableActions(issue)
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

function actionTemplate(issue, action)
    if action == "Закрыть" then
        if issue["status"] ~= "closed" then
            return {
                "resolution",
                "tags",
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

function doAction(issue, action, original)
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