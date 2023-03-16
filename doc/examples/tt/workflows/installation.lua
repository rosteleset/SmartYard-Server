function getNewIssueTemplate(catalog)
    return false
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
        }
    end
end

function getActionTemplate(issue, action)
    if action == "Координация" then
        if issue["status"] ~= "closed" then
            return {
                "_cf_installers",
                "_cf_visit_date",
                "_cf_execution_time",
                "_cf_can_change",
            }
        else
            return false
        end
    end
end

function action(issue, action, original)
    if action == "Координация" and original["status"] == "opened" then
        issue["resolution"] = "Координация"
        issue["workflow"] = "installation"
        issue["_cf_coordinator"] = tt.login()
        tt.modifyIssue(issue)
    end
end

function createIssue(issue)
    return false
end

function viewIssue(issue)
    return {
        ["issue"] = issue,
        ["actions"] = getAvailableActions(issue),
        ["showJournal"] = true,
        ["rightFields"] = {
            "_cf_installers",
        },
        ["fields"] = {
            "project",
            "workflow",
            "subject",
            "author",
            "_cf_client_id",
            "description",
            "assigned",
            "watchers",
            "_cf_coordinator",
            "_cf_execution_time",
            "_cf_installers",
            "_cf_visit_date",
            "_cf_can_change",
        }
    }
end

function getWorkflowName()
    return "#Монтажные работы"
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