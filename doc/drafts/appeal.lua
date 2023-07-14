-- Обращение (заявка на абонента) (appeal)

function getNewIssueTemplate(catalog)
    if catalog == "Подключение абонента" then
        return {
            ["fields"] = {
                "_cf_client_id",
                "description",
            }
        }
    end
    
    if catalog == "Пустышка" then
        return {
            ["fields"] = {
                "%0%subject",
                "%1%description",
                "%2%_cf_phone",
                ["%3%_cf_transfer_to"] = {
                    "сервисным инженерам",
                    "в ВОЛС",
                    "в техотдел",
                    "в офис",
                    "в колл-центр",
                },
            }
        }
    end
end

function createIssue(issue)
    if issue["_cf_object_id"] ~= nil and tonumber(issue["_cf_object_id"]) >= 500000000 and tonumber(issue["_cf_object_id"]) < 600000000 then
        local client_id = tonumber(issue["_cf_object_id"]) - 500000000
        
        local client_info = custom.GET({
            ["action"] = "client_info",
            ["with_geo"] = 1,
            ["client_id"] = client_id,
        })
    
        issue["_cf_client_type"] = "Прочие"
    
        if mb.substr(client_info["common"]["contract_name"], 0, 2) == "ФЛ" then
            issue["_cf_client_type"] = "ФЛ"
        end
    
        if mb.substr(client_info["common"]["contract_name"], 0, 2) == "ЮЛ" then
            issue["_cf_client_type"] = "ЮЛ"
        end
        
        if client_info["polygon"] ~= nil and client_info["polygon"] ~= "" then
            issue["_cf_polygon"] = client_info["polygon"]
        end
    end
    
    if issue["catalog"] == "Подключение абонента" then
        issue["status"] = "Открыта"
        issue["subject"] = "Подключение абонента"
        issue["workflow"] = "fitters"
        return tt.createIssue(issue)
    end
    
    if issue["catalog"] == "Пустышка" then
        issue["status"] = "Открыта"
        issue["workflow"] = "callcenter"
        issue = transferTo(issue, issue["_cf_transfer_to"])
        issue["_cf_transfer_to"] = nil
        return tt.createIssue(issue)
    end
    
    if issue["catalog"] == "Делопроизводство" then
        issue["status"] = "Открыта"
        issue["workflow"] = "office"
        issue["_cf_polygon"] = nil
        return tt.createIssue(issue)
    end
end

function getAvailableActions(issue)
    return false
end

function getActionTemplate(issue, action)
    return false
end

function action(issue, action, original)
    return false
end

function viewIssue(issue)
    return false
end

function getWorkflowName()
    return "Обращение [точка входа]"
end

function getWorkflowCatalog()
    return {
        ["Общая"] = {
            "Пустышка",  
        },
        ["Монтажные работы"] = {
            "Подключение абонента",
        },
    }
end