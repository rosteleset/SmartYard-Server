--
-- Утилиты
--

function trim(s)
    return s:match "^%s*(.-)%s*$"
end

function tonumberExt(v)
    if not pcall(function () v = tonumber(v) end) then
        v = 0
    end

    if v ~= nil then
        return v
    else
        return 0
    end
end

function hasValue(tab, val)
--    if tab[0] == val then
--        return true
--    end

    for index, value in pairs(tab) do
        if value == val then
            return true
        end
    end

    return false
end

function removeValue(tab, val)
    local new = {}

    for index, value in pairs(tab) do
        if value ~= val then
            new[#new + 1] = value
        end
    end

    return new
end

function replaceValue(tab, valFrom, valTo)
    local new = {}

    for index, value in pairs(tab) do
        if value == valFrom then
            new[#new + 1] = valTo
        else
            new[#new + 1] = value
        end
    end

    return new
end

function removeValues(tab, vals)
    for index, value in pairs(vals) do
        tab = removeValue(tab, value)
    end

    return tab
end

function insertAfter(tab, after, val, withSep)
    local new = {}

    for index, value in pairs(tab) do
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

    for index, value in pairs(tab) do
        new[#new + 1] = value
    end

    return new
end

function normalizeArray(tab)
    local new = {}

    if tab[0] ~= nil then
        new[#new + 1] = tab[0]
    end

    for index, value in pairs(tab) do
        new[#new + 1] = value
    end

    return new
end

function count(tab)
    local c = 0

    for i, v in pairs(tab) do
        c = c + 1
    end

    return c
end

-- переменная существует и если таблица то есть элементы

function exists(v)
    if v == nil then
        return false
    end

    if type(v) == "table" then
        for i, v in pairs(v) do
            return true
        end
        return false
    end

    return true
end

function strExists(v)
    return exists(v) and trim(v) ~= ""
end

function intersect(tab1, tab2)
    for i1, v1 in pairs(tab1) do
        for i2, v2 in pairs(tab2) do
            if v1 == v2 then
                return true
            end
        end
    end

    return false
end

function stripActions(tab)
    local new = {}

    for i, v in pairs(tab) do
        if v ~= "-" then
            if mb.substr(v, 0, 1) == "!" then
                new[#new + 1] = mb.substr(v, 1)
            else
                new[#new + 1] = v
            end
        end
    end

   return new
end