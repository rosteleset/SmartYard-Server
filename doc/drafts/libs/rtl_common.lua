function isOpened(issue)
    return
        issue["status"] == "" or issue["status"] == nil
        or
        (
            (issue["status"] == "opened" or issue["status"] == "open" or issue["status"] == "Открыта" or issue["status"] == "Открыто")
            and
            issue["status"] ~= "closed" and issue["status"] ~= "Закрыто" and issue["status"] ~= "Закрыта"
        )
end

function coordinationTemplate(issue)
    return {
        "_cf_sheet",
        "_cf_sheet_date",
        "_cf_sheet_cell",
        "_cf_sheet_col",
        "_cf_sheet_cells",
        "_cf_installers",
        "_cf_can_change",
        "_cf_call_before_visit",
    }
end

function coordinate(issue, workflow)
    issue["workflow"] = workflow
    issue["_cf_install_done"] = ""
    issue["_cf_coordinated_on"] = utils.strtotime(issue["_cf_sheet_date"] .. " " .. issue["_cf_sheet_cell"] .. ":00")
    issue["_cf_coordination_date"] = utils.time()
    issue["_cf_coordinator"] = tt.login()
    return tt.modifyIssue(issue)
end

function commonViewIssue(issue)
    local fields = {
        "*catalog",
        "*status",
        "*workflow",
        "*_cf_trouble",
        "*author",
        "*assigned",
        "*watchers",
        "*_cf_coordinated_on",
        "*_cf_sheet_cells",
        "*_cf_installers",
        "*_cf_can_change",
        "*_cf_call_before_visit",
        "*_cf_call_date",
        "*_cf_anytime_call",
        "*_cf_calls_count",
        "*_cf_delay",
        "subject",
        "_cf_phone",
        "_cf_client_type",
        "_cf_polygon",
        "_cf_object_id",
        "_cf_debt_date",
        "_cf_debt_services",
        "description",
        "_cf_linked_issue",
    }
    
    if not hasValue(tt.myGroups(), "callcenter") then
        fields = removeValues(fields, {
            "*_cf_call_date",
            "_cf_call_date",
            "*_cf_anytime_call",
            "_cf_anytime_call",
            "*_cf_calls_count",
            "_cf_calls_count",
            "*_cf_call_before_visit",
            "_cf_call_before_visit",
        })
    end
    
    return {
        ["issue"] = issue,
        ["actions"] = getAvailableActions(issue),
        ["showJournal"] = true,
        ["fields"] = fields,
    }
end