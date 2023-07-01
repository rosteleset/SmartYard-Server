-- Общие вопросы (common)

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
-- saCoordinate - coordinate

function getAvailableActions(issue)
    local actions = {}
    
    if isOpened(issue) then
        if tonumber(issue["_cf_need_call"]) == 1 and tonumber(issue["_cf_call_date"]) <= utils.time() then
            actions = {
                "!Передать",
                "-",
                "!Звонок совершен",
                "!Недозвон",
                "Позвонить",
                "-",
                "saSubIssue",
                "!saAddComment",
                "saAddFile",
                "-",
                "Закрыть",
                "-",
                "saDelete",
            }
        else
            actions = {
                "!Передать",
                "-",
                "!Позвонить",
                "-",
                "saSubIssue",
                "!saAddComment",
                "saAddFile",
                "-",
                "Закрыть",
                "-",
                "saDelete",
            }
        end
    else
        return {
            "Переоткрыть",
            "-",
            "saDelete",
        }
    end
    
    if not hasValue(tt.myGroups(), "callcenter") then
        actions = removeValues(actions, {
            "!Звонок совершен",
            "Звонок совершен",
            "!Недозвон",
            "Недозвон",
        })
    end
    
    return actions
end

function getActionTemplate(issue, action)
    if isOpened(issue) then
        if action == "Передать" then
            return transferTemplate(issue)
        end

        if action == "Закрыть" then
            return {
                "optionalComment"
            }
        end
    end
end

function action(issue, action, original)
    if isOpened(original) then
        if action == "Передать" then
            issue = transferTo(issue, issue["_cf_transfer_to"])
            issue["_cf_transfer_to"] = nil
            return tt.modifyIssue(transferTo(issue, t))
        end

        if action == "Закрыть" then
            issue["status"] = "Закрыта"
            return tt.modifyIssue(issue)
        end
    else
        if action == "Переоткрыть" then
            return {
                "comment"
            }
        end
    end
end

function createIssue(issue)
    return false
end

function viewIssue(issue)
    return commonViewIssue(issue)
end

function getWorkflowName()
    return "#Общие вопросы"
end

function getWorkflowCatalog()
    return false
end

function issueChanged(issue, action, old, new)
    return mqtt.broadcast("issue/changed", issue["issueId"])
end