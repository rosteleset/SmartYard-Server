function getNewIssueTemplate(catalog)
    if catalog == "Подключение абонента" then
        return {
            ["fields"] = {
                "_cf_client_id",
                "description",
            }
        }
    end
end

function createIssue(issue)
    if issue["catalog"] == "Подключение абонента" then
        local contract = custom.GET({
            ["action"] = "contract_by_id",
            ["client_id"] = issue["_cf_client_id"],
        })
        issue["status"] = "opened"
        issue["workflow"] = "connect"
        issue["subject"] = "АВТО: Подключение абонента (прочее)"
        if mb.substr(contract, 0, 2) == "ФЛ" then
            issue["subject"] = "АВТО: Подключение абонента (ФЛ)"
        end
        if mb.substr(contract, 0, 2) == "ЮЛ" then
            issue["subject"] = "АВТО: Подключение абонента (ЮЛ)"
        end
        utils.error_log(issue["subject"])
        return tt.createIssue(issue)
    end
end

function getWorkflowName()
    return "Обращение"
end

function getWorkflowCatalog()
    return {
        ["Монтажные работы"] = {
            "Подключение абонента",
        },
    }
end