function transferTo(issue, tt)
    if tt == "в общие вопросы" then
        issue["workflow"] = "common"
    end
    
    if tt == "в колл-центр" then
        issue["workflow"] = "callcenter"
    end
    
    if tt == "в офис" then
        issue["workflow"] = "office"
    end
    
    if tt == "сервисным инженерам" then
        issue["workflow"] = "fitters"
    end
    
    if tt == "курьеру" then
        issue["workflow"] = "courier"
    end
    
    return issue
end

function transferTemplate(issue)
    local template = {
        ["%0%_cf_transfer_to"] = {
            "в общие вопросы",
            "в колл-центр",
            "в офис",
            "сервисным инженерам",
            "курьеру",
        },
        ["%1%_cf_trouble"] = {
            
        },
        "%2%optionalComment",
    }
    
    if issue["workflow"] == "common" then
        removeValue(template,  "в общие вопросы")
    end
    
    if issue["workflow"] == "callcenter" then
        removeValue(template, "в колл-центр")
    end
    
    if issue["workflow"] == "office" then
        removeValue(template, "в офис")
    end
    
    if issue["workflow"] == "fitters" then
        removeValue(template, "сервисным инженерам")
    end
    
    if issue["workflow"] == "courier" then
        removeValue(template, "курьеру")
    end
    
    template["%1%_cf_trouble"] = {
        "Обеспечить доступ",
    }

    return template
end