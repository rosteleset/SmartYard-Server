function hasValue(tab, val)
    if tab[0] == val then
        return true
    end
    
    for index, value in ipairs(tab) do
        utils.error_log(utils.print_r({
            index,
            value
        }))
        if value == val then
            return true
        end
    end
    
    return false
end

function removeValue(tab, val)
    local new = {}
    
    for index, value in ipairs(tab) do
        if value ~= val then
            new[#new + 1] = value
        end
    end
    
    return new
end

function removeValues(tab, vals)
    for index, value in ipairs(vals) do
        tab = removeValue(tab, value)
    end
    
    return tab
end

function insertAfter(tab, after, val, withSep)
    local new = {}
    
    for index, value in ipairs(tab) do
        if value == after then
            new[#new + 1] = value
            if withSep then
                new[#new + 1] = withSep
            end
            new[#new + 1] = val
        else
            new[#new + 1] = value
        end
    end
    
    return new
end

function insertFirst(tab, val, withSep)
    local new = {}
    
    new[#new + 1] = val
    if withSep then
        new[#new + 1] = withSep
    end

    for index, value in ipairs(tab) do
        new[#new + 1] = value
    end
    
    return new
end