-- Заявка на работу с оборудованием (hw)

function getNewIssueTemplate(catalog)
    return {
        ["fields"] = {
            "_cf_object_id",
            "subject",
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
-- saCoordinate - coordinate

function getAvailableActions(issue)
    return false
end

function getActionTemplate(issue, action)
    return false
end

function action(issue, action, original)
    return false
end

function createIssue(issue)
    if issue["_cf_object_id"] ~= nil and tonumber(issue["_cf_object_id"]) >= 200000000 and tonumber(issue["_cf_object_id"]) < 300000000 then
        local l2_sw_id = tonumber(issue["_cf_object_id"]) - 200000000
        
        local l2 = custom.GET({
            ["action"] = "l2",
            ["l2_sw_id"] = l2_sw_id,
        })
    
        if l2["chest"] ~= nil and l2["chest"]["polygon"] ~= nil and l2["chest"]["polygon"] ~= "" then
            issue["_cf_polygon"] = l2["chest"]["polygon"]
        end
    end
    
    issue["workflow"] = "common"
    issue = transferTo(issue, issue["_cf_transfer_to"])
    issue["_cf_transfer_to"] = nil
    issue["status"] = "Открыта"

    return tt.createIssue(issue)
end

function viewIssue(issue)
    return false
end

function getWorkflowName()
    return "Оборудование [точка входа]"
end

function getWorkflowCatalog()
    return {
        ["Оборудование"] = {
            "Пустышка",
        },
    }
end

function issueChanged(issue, action, old, new)
    return mqtt.broadcast("issue/changed", issue["issueId"])
end