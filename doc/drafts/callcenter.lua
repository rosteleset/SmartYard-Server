-- Звонки (callcenter)

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
    if isOpened(issue) then
        return {
            "Изменить тему и описание",
            "-",
            "!Передать",
            "!saCoordinate",
            "Отложить",
            "-",
            "Связать",
            "-",
            "!saAddComment",
            "saAddFile",
            "-",
            "Закрыть",
            "-",
            "saDelete",
        }
    else
        return {
            "Переоткрыть",
            "-",
            "saDelete",
        }
    end
end

function getActionTemplate(issue, action)
    if isOpened(issue) then
        if action == "Координация" then
            return coordinationTemplate(issue)
        end

        if action == "Передать" then
            return transferTemplate(issue)
        end
    
        if action == "Изменить тему и описание" then
            return {
                "subject",
                "description",
                "optionalComment",
            }
        end
        
        if action == "Закрыть" then
            return {
                "_cf_quality_control",
                "optionalComment"
            }
        end
        
        if action == "Отложить" then
            return {
                "_cf_delay",
                "optionalComment",
            }
        end
        
        if action == "Связать" then
            return {
                "_cf_linked_issue",
            }
        end
    else
        if action == "Переоткрыть" then
            return {
                "comment"
            }
        end
    end
end

function action(issue, action, original)
    if isOpened(original) then
        if action == "Координация" then
            return coordinate(issue, "fitters")
        end
    
        if action == "Передать" then
            issue = transferTo(issue, issue["_cf_transfer_to"])
            issue["_cf_transfer_to"] = nil
            return tt.modifyIssue(transferTo(issue, t))
        end
        
        if action == "Изменить тему и описание" then
            return tt.modifyIssue(issue)
        end
        
        if action == "Закрыть" then
            issue["status"] = "Закрыта"
            return tt.modifyIssue(issue)
        end
        
        if action == "Отложить" then
            issue["_cf_delay"] = utils.strtotime(issue["_cf_delay"])
            return tt.modifyIssue(issue)
        end

        if action == "Связать" then
            return tt.modifyIssue(issue)
        end
    else
        if action == "Переоткрыть" then
            issue["status"] = "Открыта"
            return tt.modifyIssue(issue)
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
    return "#Звонки"
end

function getWorkflowCatalog()
    return false
end

function issueChanged(issue, action, old, new)
    return mqtt.broadcast("issue/changed", issue["issueId"])
end