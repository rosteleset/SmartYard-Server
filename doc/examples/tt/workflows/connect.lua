function getNewIssueTemplate(catalog)
    return {
        ["fields"] = {
            "_cf_client_id",
            "description",
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
    local h = https.POST("https://lfi.bz/tt.php", {
        ["action"] = "ping",
        ["ptp"] = "tpt",
    })

    utils.error_log(utils.print_r(h))

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
    issue["subject"] = "АВТО: Подключение абонента"
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
            "author",
            "created",
            "updated",
            "status",
            "resolution",
            "_cf_client_id",
            "description",
            "assigned",
            "watchers",
        }
    }
end

function getWorkflowName()
    return "#Подключение абонента"
end

function getWorkflowCatalog()
    return false
end

function issueChanged(issue, action, old, new)
    utils.error_log(utils.print_r(issue))
    utils.error_log(utils.print_r(action))
    utils.error_log(utils.print_r(old))
    utils.error_log(utils.print_r(new))
end