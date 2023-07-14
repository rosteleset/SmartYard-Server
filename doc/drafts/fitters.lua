-- Сервисные инженеры (СИ, ВОЛС) (fitters)

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
                "!Звонок совершен",
                "!Недозвон",
                "Позвонить",
                "-",
                "Работы завершены",
                "-",
                "!saAddComment",
                "saAddFile",
                "-",
                "!saCoordinate",
                "Назначить исполнителей",
                "-",
                "Отменить",
            }
        else
            actions = {
                "!Позвонить",
                "-",
                "!Работы завершены",
                "-",
                "!saAddComment",
                "saAddFile",
                "-",
                "!saCoordinate",
                "Назначить исполнителей",
                "-",
                "Отменить",
            }
        end
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
        if action == "Координация" then
            return coordinationTemplate(issue)
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
                    "Камера установлена",
                    "Установлен микрофон",
                    "Установлена камера и микрофон",
                    "Проблема с доступом",
                },
                "%1%_cf_access_info",
                "%2%comment",
            }
        end
        
        if action == "Отменить" then
            return {
                "comment"
            }
        end

        if action == "Позвонить" then
            return {
                "_cf_call_date",
                "_cf_anytime_call",
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
        
        if action == "Назначить исполнителей" then
            issue["_cf_coordination_date"] = utils.time()
            issue["_cf_coordinator"] = tt.login()
            return tt.modifyIssue(issue)
        end
        
        if action == "Работы завершены" then
            issue["_cf_done_date"] = utils.time()
            issue["workflow"] = "callcenter"
            return tt.modifyIssue(issue)
        end

        if action == "Отменить" then
            issue["workflow"] = "callcenter"
            return tt.modifyIssue(issue)
        end

        if action == "Позвонить" then
            issue["_cf_need_call"] = 1
            issue["_cf_calls_count"] = 0
            return tt.modifyIssue(issue)
        end
        
        if action == "Звонок совершен" then
            issue["_cf_need_call"] = 0
            if original["_cf_calls_count"] == nil then
                issue["_cf_calls_count"] = 1
            else
                issue["_cf_calls_count"] = original["_cf_calls_count"] + 1
            end
            return tt.modifyIssue(issue)
        end
        
        if action == "Недозвон" then
            issue["_cf_call_date"] = utils.time() + 30 * 60
            if original["_cf_calls_count"] == nil then
                issue["_cf_calls_count"] = 1
            else
                issue["_cf_calls_count"] = original["_cf_calls_count"] + 1
            end
            if issue["_cf_calls_count"] >= 3 then
                issue["_cf_need_call"] = 0
            end
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
    return "#Сервисные инженеры"
end

function getWorkflowCatalog()
    return false
end

function issueChanged(issue, action, old, new)
    return mqtt.broadcast("issue/changed", issue["issueId"])
end