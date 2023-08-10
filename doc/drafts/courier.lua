-- Курьерская доставка (courier)

function getNewIssueTemplate(catalog)
    return {
        ["fields"] = {
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
    if isOpened(issue) then
        return {
            "!Работы завершены",
            "-",
            "!saAddComment",
            "saAddFile",
            "-",
            "!saCoordinate",
            "Назначить исполнителей",
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
    
        if action == "Назначить исполнителей" then
            return {
                "_cf_installers",
            }
        end

        if action == "Работы завершены" then
            return {
                ["%0%_cf_install_done"] = {
                    "Выполнено",
                    "Не доставлено",
                    "Отмена",
                },
                "%1%comment",
            }
        end
    end
end

function action(issue, action, original)
    if isOpened(original) then
        if action == "Координация" then
            return coordinate(issue, "courier")
        end
        
        if action == "Назначить исполнителей" then
            issue["_cf_coordinator"] = tt.login()
            issue["_cf_coordination_date"] = utils.time()
            return tt.modifyIssue(issue)
        end
        
        if action == "Работы завершены" then
            issue["_cf_done_date"] = utils.time()
            issue["workflow"] = "callcenter"
            return tt.modifyIssue(issue)
        end
    end
end

function createIssue(issue)
    if issue["catalog"] == "Пустышка" then
        issue["status"] = "Открыта"
        issue["workflow"] = "courier"
        issue["_cf_transfer_to"] = nil
        return tt.createIssue(issue)
    end
end

function viewIssue(issue)
    return commonViewIssue(issue)
end

function getWorkflowName()
    return "Курьерская доставка"
end

function getWorkflowCatalog()
    return {
        ["Общая"] = {
            "Пустышка",  
        },
    }
end

function issueChanged(issue, action, old, new)
    return mqtt.broadcast("issue/changed", issue["issueId"])
end