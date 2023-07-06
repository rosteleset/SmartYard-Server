({
    init: function () {
        if (AVAIL("addresses", "region", "PUT")) {
            leftSide("fas fa-fw fa-globe-americas", i18n("addresses.addresses"), "?#addresses", "households");
        }

        loadSubModules("addresses", [
            "houses",
            "domophones",
            "cameras",
            "subscribers", // and keys
            "subscriberInbox",
        ], this);
    },

    timezones: [
        "Africa/Abidjan",
        "Africa/Accra",
        "Africa/Addis_Ababa",
        "Africa/Algiers",
        "Africa/Asmara",
        "Africa/Asmera",
        "Africa/Bamako",
        "Africa/Bangui",
        "Africa/Banjul",
        "Africa/Bissau",
        "Africa/Blantyre",
        "Africa/Brazzaville",
        "Africa/Bujumbura",
        "Africa/Cairo",
        "Africa/Casablanca",
        "Africa/Ceuta",
        "Africa/Conakry",
        "Africa/Dakar",
        "Africa/Dar_es_Salaam",
        "Africa/Djibouti",
        "Africa/Douala",
        "Africa/El_Aaiun",
        "Africa/Freetown",
        "Africa/Gaborone",
        "Africa/Harare",
        "Africa/Johannesburg",
        "Africa/Juba",
        "Africa/Kampala",
        "Africa/Khartoum",
        "Africa/Kigali",
        "Africa/Kinshasa",
        "Africa/Lagos",
        "Africa/Libreville",
        "Africa/Lome",
        "Africa/Luanda",
        "Africa/Lubumbashi",
        "Africa/Lusaka",
        "Africa/Malabo",
        "Africa/Maputo",
        "Africa/Maseru",
        "Africa/Mbabane",
        "Africa/Mogadishu",
        "Africa/Monrovia",
        "Africa/Nairobi",
        "Africa/Ndjamena",
        "Africa/Niamey",
        "Africa/Nouakchott",
        "Africa/Ouagadougou",
        "Africa/Porto-Novo",
        "Africa/Sao_Tome",
        "Africa/Timbuktu",
        "Africa/Tripoli",
        "Africa/Tunis",
        "Africa/Windhoek",
        "America/Adak",
        "America/Anchorage",
        "America/Anguilla",
        "America/Antigua",
        "America/Araguaina",
        "America/Argentina/Buenos_Aires",
        "America/Argentina/Catamarca",
        "America/Argentina/ComodRivadavia",
        "America/Argentina/Cordoba",
        "America/Argentina/Jujuy",
        "America/Argentina/La_Rioja",
        "America/Argentina/Mendoza",
        "America/Argentina/Rio_Gallegos",
        "America/Argentina/Salta",
        "America/Argentina/San_Juan",
        "America/Argentina/San_Luis",
        "America/Argentina/Tucuman",
        "America/Argentina/Ushuaia",
        "America/Aruba",
        "America/Asuncion",
        "America/Atikokan",
        "America/Atka",
        "America/Bahia",
        "America/Bahia_Banderas",
        "America/Barbados",
        "America/Belem",
        "America/Belize",
        "America/Blanc-Sablon",
        "America/Boa_Vista",
        "America/Bogota",
        "America/Boise",
        "America/Buenos_Aires",
        "America/Cambridge_Bay",
        "America/Campo_Grande",
        "America/Cancun",
        "America/Caracas",
        "America/Catamarca",
        "America/Cayenne",
        "America/Cayman",
        "America/Chicago",
        "America/Chihuahua",
        "America/Ciudad_Juarez",
        "America/Coral_Harbour",
        "America/Cordoba",
        "America/Costa_Rica",
        "America/Creston",
        "America/Cuiaba",
        "America/Curacao",
        "America/Danmarkshavn",
        "America/Dawson",
        "America/Dawson_Creek",
        "America/Denver",
        "America/Detroit",
        "America/Dominica",
        "America/Edmonton",
        "America/Eirunepe",
        "America/El_Salvador",
        "America/Ensenada",
        "America/Fortaleza",
        "America/Fort_Nelson",
        "America/Fort_Wayne",
        "America/Glace_Bay",
        "America/Godthab",
        "America/Goose_Bay",
        "America/Grand_Turk",
        "America/Grenada",
        "America/Guadeloupe",
        "America/Guatemala",
        "America/Guayaquil",
        "America/Guyana",
        "America/Halifax",
        "America/Havana",
        "America/Hermosillo",
        "America/Indiana/Indianapolis",
        "America/Indiana/Knox",
        "America/Indiana/Marengo",
        "America/Indiana/Petersburg",
        "America/Indianapolis",
        "America/Indiana/Tell_City",
        "America/Indiana/Vevay",
        "America/Indiana/Vincennes",
        "America/Indiana/Winamac",
        "America/Inuvik",
        "America/Iqaluit",
        "America/Jamaica",
        "America/Jujuy",
        "America/Juneau",
        "America/Kentucky/Louisville",
        "America/Kentucky/Monticello",
        "America/Knox_IN",
        "America/Kralendijk",
        "America/La_Paz",
        "America/Lima",
        "America/Los_Angeles",
        "America/Louisville",
        "America/Lower_Princes",
        "America/Maceio",
        "America/Managua",
        "America/Manaus",
        "America/Marigot",
        "America/Martinique",
        "America/Matamoros",
        "America/Mazatlan",
        "America/Mendoza",
        "America/Menominee",
        "America/Merida",
        "America/Metlakatla",
        "America/Mexico_City",
        "America/Miquelon",
        "America/Moncton",
        "America/Monterrey",
        "America/Montevideo",
        "America/Montreal",
        "America/Montserrat",
        "America/Nassau",
        "America/New_York",
        "America/Nipigon",
        "America/Nome",
        "America/Noronha",
        "America/North_Dakota/Beulah",
        "America/North_Dakota/Center",
        "America/North_Dakota/New_Salem",
        "America/Nuuk",
        "America/Ojinaga",
        "America/Panama",
        "America/Pangnirtung",
        "America/Paramaribo",
        "America/Phoenix",
        "America/Port-au-Prince",
        "America/Porto_Acre",
        "America/Port_of_Spain",
        "America/Porto_Velho",
        "America/Puerto_Rico",
        "America/Punta_Arenas",
        "America/Rainy_River",
        "America/Rankin_Inlet",
        "America/Recife",
        "America/Regina",
        "America/Resolute",
        "America/Rio_Branco",
        "America/Rosario",
        "America/Santa_Isabel",
        "America/Santarem",
        "America/Santiago",
        "America/Santo_Domingo",
        "America/Sao_Paulo",
        "America/Scoresbysund",
        "America/Shiprock",
        "America/Sitka",
        "America/St_Barthelemy",
        "America/St_Johns",
        "America/St_Kitts",
        "America/St_Lucia",
        "America/St_Thomas",
        "America/St_Vincent",
        "America/Swift_Current",
        "America/Tegucigalpa",
        "America/Thule",
        "America/Thunder_Bay",
        "America/Tijuana",
        "America/Toronto",
        "America/Tortola",
        "America/Vancouver",
        "America/Virgin",
        "America/Whitehorse",
        "America/Winnipeg",
        "America/Yakutat",
        "America/Yellowknife",
        "Antarctica/Casey",
        "Antarctica/Davis",
        "Antarctica/DumontDUrville",
        "Antarctica/Macquarie",
        "Antarctica/Mawson",
        "Antarctica/McMurdo",
        "Antarctica/Palmer",
        "Antarctica/Rothera",
        "Antarctica/South_Pole",
        "Antarctica/Syowa",
        "Antarctica/Troll",
        "Antarctica/Vostok",
        "Arctic/Longyearbyen",
        "Asia/Aden",
        "Asia/Almaty",
        "Asia/Amman",
        "Asia/Anadyr",
        "Asia/Aqtau",
        "Asia/Aqtobe",
        "Asia/Ashgabat",
        "Asia/Ashkhabad",
        "Asia/Atyrau",
        "Asia/Baghdad",
        "Asia/Bahrain",
        "Asia/Baku",
        "Asia/Bangkok",
        "Asia/Barnaul",
        "Asia/Beirut",
        "Asia/Bishkek",
        "Asia/Brunei",
        "Asia/Calcutta",
        "Asia/Chita",
        "Asia/Choibalsan",
        "Asia/Chongqing",
        "Asia/Chungking",
        "Asia/Colombo",
        "Asia/Dacca",
        "Asia/Damascus",
        "Asia/Dhaka",
        "Asia/Dili",
        "Asia/Dubai",
        "Asia/Dushanbe",
        "Asia/Famagusta",
        "Asia/Gaza",
        "Asia/Harbin",
        "Asia/Hebron",
        "Asia/Ho_Chi_Minh",
        "Asia/Hong_Kong",
        "Asia/Hovd",
        "Asia/Irkutsk",
        "Asia/Istanbul",
        "Asia/Jakarta",
        "Asia/Jayapura",
        "Asia/Jerusalem",
        "Asia/Kabul",
        "Asia/Kamchatka",
        "Asia/Karachi",
        "Asia/Kashgar",
        "Asia/Kathmandu",
        "Asia/Katmandu",
        "Asia/Khandyga",
        "Asia/Kolkata",
        "Asia/Krasnoyarsk",
        "Asia/Kuala_Lumpur",
        "Asia/Kuching",
        "Asia/Kuwait",
        "Asia/Macao",
        "Asia/Macau",
        "Asia/Magadan",
        "Asia/Makassar",
        "Asia/Manila",
        "Asia/Muscat",
        "Asia/Nicosia",
        "Asia/Novokuznetsk",
        "Asia/Novosibirsk",
        "Asia/Omsk",
        "Asia/Oral",
        "Asia/Phnom_Penh",
        "Asia/Pontianak",
        "Asia/Pyongyang",
        "Asia/Qatar",
        "Asia/Qostanay",
        "Asia/Qyzylorda",
        "Asia/Rangoon",
        "Asia/Riyadh",
        "Asia/Saigon",
        "Asia/Sakhalin",
        "Asia/Samarkand",
        "Asia/Seoul",
        "Asia/Shanghai",
        "Asia/Singapore",
        "Asia/Srednekolymsk",
        "Asia/Taipei",
        "Asia/Tashkent",
        "Asia/Tbilisi",
        "Asia/Tehran",
        "Asia/Tel_Aviv",
        "Asia/Thimbu",
        "Asia/Thimphu",
        "Asia/Tokyo",
        "Asia/Tomsk",
        "Asia/Ujung_Pandang",
        "Asia/Ulaanbaatar",
        "Asia/Ulan_Bator",
        "Asia/Urumqi",
        "Asia/Ust-Nera",
        "Asia/Vientiane",
        "Asia/Vladivostok",
        "Asia/Yakutsk",
        "Asia/Yangon",
        "Asia/Yekaterinburg",
        "Asia/Yerevan",
        "Atlantic/Azores",
        "Atlantic/Bermuda",
        "Atlantic/Canary",
        "Atlantic/Cape_Verde",
        "Atlantic/Faeroe",
        "Atlantic/Faroe",
        "Atlantic/Jan_Mayen",
        "Atlantic/Madeira",
        "Atlantic/Reykjavik",
        "Atlantic/South_Georgia",
        "Atlantic/Stanley",
        "Atlantic/St_Helena",
        "Australia/ACT",
        "Australia/Adelaide",
        "Australia/Brisbane",
        "Australia/Broken_Hill",
        "Australia/Canberra",
        "Australia/Currie",
        "Australia/Darwin",
        "Australia/Eucla",
        "Australia/Hobart",
        "Australia/LHI",
        "Australia/Lindeman",
        "Australia/Lord_Howe",
        "Australia/Melbourne",
        "Australia/North",
        "Australia/NSW",
        "Australia/Perth",
        "Australia/Queensland",
        "Australia/South",
        "Australia/Sydney",
        "Australia/Tasmania",
        "Australia/Victoria",
        "Australia/West",
        "Australia/Yancowinna",
        "Brazil/Acre",
        "Brazil/DeNoronha",
        "Brazil/East",
        "Brazil/West",
        "Canada/Atlantic",
        "Canada/Central",
        "Canada/Eastern",
        "Canada/Mountain",
        "Canada/Newfoundland",
        "Canada/Pacific",
        "Canada/Saskatchewan",
        "Canada/Yukon",
        "CET",
        "Chile/Continental",
        "Chile/EasterIsland",
        "CST6CDT",
        "Cuba",
        "EET",
        "Egypt",
        "Eire",
        "EST",
        "EST5EDT",
        "Etc/GMT",
        "Etc/GMT+0",
        "Etc/GMT-0",
        "Etc/GMT0",
        "Etc/GMT+1",
        "Etc/GMT-1",
        "Etc/GMT+10",
        "Etc/GMT-10",
        "Etc/GMT+11",
        "Etc/GMT-11",
        "Etc/GMT+12",
        "Etc/GMT-12",
        "Etc/GMT-13",
        "Etc/GMT-14",
        "Etc/GMT+2",
        "Etc/GMT-2",
        "Etc/GMT+3",
        "Etc/GMT-3",
        "Etc/GMT+4",
        "Etc/GMT-4",
        "Etc/GMT+5",
        "Etc/GMT-5",
        "Etc/GMT+6",
        "Etc/GMT-6",
        "Etc/GMT+7",
        "Etc/GMT-7",
        "Etc/GMT+8",
        "Etc/GMT-8",
        "Etc/GMT+9",
        "Etc/GMT-9",
        "Etc/Greenwich",
        "Etc/UCT",
        "Etc/Universal",
        "Etc/UTC",
        "Etc/Zulu",
        "Europe/Amsterdam",
        "Europe/Andorra",
        "Europe/Astrakhan",
        "Europe/Athens",
        "Europe/Belfast",
        "Europe/Belgrade",
        "Europe/Berlin",
        "Europe/Bratislava",
        "Europe/Brussels",
        "Europe/Bucharest",
        "Europe/Budapest",
        "Europe/Busingen",
        "Europe/Chisinau",
        "Europe/Copenhagen",
        "Europe/Dublin",
        "Europe/Gibraltar",
        "Europe/Guernsey",
        "Europe/Helsinki",
        "Europe/Isle_of_Man",
        "Europe/Istanbul",
        "Europe/Jersey",
        "Europe/Kaliningrad",
        "Europe/Kiev",
        "Europe/Kirov",
        "Europe/Kyiv",
        "Europe/Lisbon",
        "Europe/Ljubljana",
        "Europe/London",
        "Europe/Luxembourg",
        "Europe/Madrid",
        "Europe/Malta",
        "Europe/Mariehamn",
        "Europe/Minsk",
        "Europe/Monaco",
        "Europe/Moscow",
        "Europe/Nicosia",
        "Europe/Oslo",
        "Europe/Paris",
        "Europe/Podgorica",
        "Europe/Prague",
        "Europe/Riga",
        "Europe/Rome",
        "Europe/Samara",
        "Europe/San_Marino",
        "Europe/Sarajevo",
        "Europe/Saratov",
        "Europe/Simferopol",
        "Europe/Skopje",
        "Europe/Sofia",
        "Europe/Stockholm",
        "Europe/Tallinn",
        "Europe/Tirane",
        "Europe/Tiraspol",
        "Europe/Ulyanovsk",
        "Europe/Uzhgorod",
        "Europe/Vaduz",
        "Europe/Vatican",
        "Europe/Vienna",
        "Europe/Vilnius",
        "Europe/Volgograd",
        "Europe/Warsaw",
        "Europe/Zagreb",
        "Europe/Zaporozhye",
        "Europe/Zurich",
        "Factory",
        "GB",
        "GB-Eire",
        "GMT",
        "GMT+0",
        "GMT-0",
        "GMT0",
        "Greenwich",
        "Hongkong",
        "HST",
        "Iceland",
        "Indian/Antananarivo",
        "Indian/Chagos",
        "Indian/Christmas",
        "Indian/Cocos",
        "Indian/Comoro",
        "Indian/Kerguelen",
        "Indian/Mahe",
        "Indian/Maldives",
        "Indian/Mauritius",
        "Indian/Mayotte",
        "Indian/Reunion",
        "Iran",
        "Israel",
        "Jamaica",
        "Japan",
        "Kwajalein",
        "Libya",
        "MET",
        "Mexico/BajaNorte",
        "Mexico/BajaSur",
        "Mexico/General",
        "MST",
        "MST7MDT",
        "Navajo",
        "NZ",
        "NZ-CHAT",
        "Pacific/Apia",
        "Pacific/Auckland",
        "Pacific/Bougainville",
        "Pacific/Chatham",
        "Pacific/Chuuk",
        "Pacific/Easter",
        "Pacific/Efate",
        "Pacific/Enderbury",
        "Pacific/Fakaofo",
        "Pacific/Fiji",
        "Pacific/Funafuti",
        "Pacific/Galapagos",
        "Pacific/Gambier",
        "Pacific/Guadalcanal",
        "Pacific/Guam",
        "Pacific/Honolulu",
        "Pacific/Johnston",
        "Pacific/Kanton",
        "Pacific/Kiritimati",
        "Pacific/Kosrae",
        "Pacific/Kwajalein",
        "Pacific/Majuro",
        "Pacific/Marquesas",
        "Pacific/Midway",
        "Pacific/Nauru",
        "Pacific/Niue",
        "Pacific/Norfolk",
        "Pacific/Noumea",
        "Pacific/Pago_Pago",
        "Pacific/Palau",
        "Pacific/Pitcairn",
        "Pacific/Pohnpei",
        "Pacific/Ponape",
        "Pacific/Port_Moresby",
        "Pacific/Rarotonga",
        "Pacific/Saipan",
        "Pacific/Samoa",
        "Pacific/Tahiti",
        "Pacific/Tarawa",
        "Pacific/Tongatapu",
        "Pacific/Truk",
        "Pacific/Wake",
        "Pacific/Wallis",
        "Pacific/Yap",
        "Poland",
        "Portugal",
        "PRC",
        "PST8PDT",
        "ROC",
        "ROK",
        "Singapore",
        "Turkey",
        "UCT",
        "Universal",
        "US/Alaska",
        "US/Aleutian",
        "US/Arizona",
        "US/Central",
        "US/Eastern",
        "US/East-Indiana",
        "US/Hawaii",
        "US/Indiana-Starke",
        "US/Michigan",
        "US/Mountain",
        "US/Pacific",
        "US/Samoa",
        "UTC",
        "WET",
        "W-SU",
        "Zulu",
    ],

    timezonesOptions: function () {
        let tz = [{
            id: "-",
            text: "-",
        }];

        let already = {};

        for (let i in modules.addresses.timezones) {
            if (!already[modules.addresses.timezones[i]]) {
                tz.push({
                    id: modules.addresses.timezones[i],
                    text: modules.addresses.timezones[i],
                });
            }
            already[modules.addresses.timezones[i]] = true;
        }

        return tz;
    },

    addresses: function (addresses) {
        modules.addresses.meta = addresses["addresses"];
    },

    path: function (object, id, link) {
        let sp = "<i class=\"fas fa-xs fa-angle-double-right ml-2 mr-2\"></i>";

        function link(target, text, id) {
            return `<a href="?#addresses&show=${target}&${target}Id=${id}">${text}</a>`;
        }

        function region(id) {
            for (let i in modules.addresses.meta.regions) {
                if (modules.addresses.meta.regions[i].regionId == id) {
                    return modules.addresses.meta.regions[i];
                }
            }
        }

        function area(id) {
            for (let i in modules.addresses.meta.areas) {
                if (modules.addresses.meta.areas[i].areaId == id) {
                    let a = modules.addresses.meta.areas[i];
                    let r = region(a.regionId);
                    a.parent = link("region", r.regionWithType, r.regionId);
                    return a;
                }
            }
        }

        function city(id) {
            for (let i in modules.addresses.meta.cities) {
                if (modules.addresses.meta.cities[i].cityId == id) {
                    let c = modules.addresses.meta.cities[i];
                    if (c.regionId) {
                        let r = region(c.regionId);
                        c.parent = link("region", r.regionWithType, r.regionId);
                    } else {
                        let a = area(c.areaId);
                        c.parent = a.parent + sp + link("area", a.areaWithType, a.areaId);
                    }
                    return c;
                }
            }
        }

        function settlement(id) {
            for (let i in modules.addresses.meta.settlements) {
                if (modules.addresses.meta.settlements[i].settlementId == id) {
                    let s = modules.addresses.meta.settlements[i];
                    if (s.areaId) {
                        let a = area(s.areaId);
                        s.parent = a.parent + sp + link("area", a.areaWithType, a.areaId);
                    } else {
                        let c = city(s.cityId);
                        s.parent = c.parent + sp + link("city", c.city, c.cityId);
                    }
                    return s;
                }
            }
        }

        function street(id) {
            for (let i in modules.addresses.meta.streets) {
                if (modules.addresses.meta.streets[i].streetId == id) {
                    let s = modules.addresses.meta.streets[i];
                    if (s.cityId) {
                        let c = city(s.cityId);
                        s.parent = c.parent + sp + link("city", c.city, c.cityId);
                    } else {
                        let e = settlement(s.settlementId);
                        s.parent = e.parent + sp + link("settlement", e.settlement, e.settlementId);
                    }
                    return s;
                }
            }
        }

        switch (object) {
            case "region":
                return region(id).regionWithType;

            case "area":
                let a = area(id);
                return a.parent + sp + a.areaWithType;

            case "city":
                let c = city(id);
                return c.parent + sp + c.city;

            case "settlement":
                let se = settlement(id);
                if (link) {
                    return se.parent + sp + link("settlement", se.settlement, id);
                } else {
                    return se.parent + sp + se.settlement;
                }

            case "street":
                let st = street(id);
                if (link) {
                    return st.parent + sp + link("street", st.street, id);
                } else {
                    return st.parent + sp + st.street;
                }

            default:
                return "";
        }
    },

    doAddRegion: function (regionUuid, regionIsoCode, regionWithType, regionType, regionTypeFull, region, timezone) {
        loadingStart();
        POST("addresses", "region", false, {
            regionUuid,
            regionIsoCode,
            regionWithType,
            regionType,
            regionTypeFull,
            region,
            timezone,
        }).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.regionWasAdded"));
        }).
        always(modules.addresses.renderRegions);
    },

    doAddArea: function (regionId, areaUuid, areaWithType, areaType, areaTypeFull, area, timezone) {
        loadingStart();
        POST("addresses", "area", false, {
            regionId,
            areaUuid,
            areaWithType,
            areaType,
            areaTypeFull,
            area,
            timezone,
        }).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.areaWasAdded"));
        }).
        always(() => {
            modules.addresses.renderRegion(regionId);
        });
    },

    doAddCity: function (regionId, areaId, cityUuid, cityWithType, cityType, cityTypeFull, city, timezone) {
        loadingStart();
        POST("addresses", "city", false, {
            regionId,
            areaId,
            cityUuid,
            cityWithType,
            cityType,
            cityTypeFull,
            city,
            timezone,
        }).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.cityWasAdded"));
        }).
        always(() => {
            if (regionId) {
                modules.addresses.renderRegion(regionId);
            } else {
                modules.addresses.renderArea(areaId);
            }
        });
    },

    doAddSettlement: function (areaId, cityId, settlementUuid, settlementWithType, settlementType, settlementTypeFull, settlement) {
        loadingStart();
        POST("addresses", "settlement", false, {
            areaId,
            cityId,
            settlementUuid,
            settlementWithType,
            settlementType,
            settlementTypeFull,
            settlement,
        }).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.settlementWasAdded"));
        }).
        always(() => {
            if (areaId) {
                modules.addresses.renderArea(areaId);
            } else {
                modules.addresses.renderCity(cityId);
            }
        });
    },

    doAddStreet: function (cityId, settlementId, streetUuid, streetWithType, streetType, streetTypeFull, street) {
        loadingStart();
        POST("addresses", "street", false, {
            cityId,
            settlementId,
            streetUuid,
            streetWithType,
            streetType,
            streetTypeFull,
            street,
        }).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.streetWasAdded"));
        }).
        always(() => {
            if (cityId) {
                modules.addresses.renderCity(cityId);
            } else {
                modules.addresses.renderSettlement(settlementId);
            }
        });
    },

    doAddHouse: function (settlementId, streetId, houseUuid, houseType, houseTypeFull, houseFull, house) {
        loadingStart();
        POST("addresses", "house", false, {
            settlementId,
            streetId,
            houseUuid,
            houseType,
            houseTypeFull,
            houseFull,
            house,
        }).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.houseWasAdded"));
        }).
        always(() => {
            if (settlementId) {
                modules.addresses.renderSettlement(settlementId);
            } else {
                modules.addresses.renderStreet(streetId);
            }
        });
    },

    doModifyRegion: function (regionId, regionUuid, regionIsoCode, regionWithType, regionType, regionTypeFull, region, timezone) {
        loadingStart();
        PUT("addresses", "region", regionId, {
            regionUuid,
            regionIsoCode,
            regionWithType,
            regionType,
            regionTypeFull,
            region,
            timezone,
        }).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.regionWasChanged"));
        }).
        always(modules.addresses.renderRegions);
    },

    doModifyArea: function (areaId, regionId, areaUuid, areaWithType, areaType, areaTypeFull, area, targetRegionId, timezone) {
        loadingStart();
        PUT("addresses", "area", areaId, {
            regionId,
            areaUuid,
            areaWithType,
            areaType,
            areaTypeFull,
            area,
            timezone,
        }).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.areaWasChanged"));
        }).
        always(() => {
            if (regionId == targetRegionId) {
                modules.addresses.renderRegion(regionId);
            } else {
                location.href = "?#addresses&show=region&regionId=" + regionId;
            }
        });
    },

    doModifyCity: function (cityId, regionId, areaId, cityUuid, cityWithType, cityType, cityTypeFull, city, targetRegionId, targetAreaId, timezone) {
        loadingStart();
        PUT("addresses", "city", cityId, {
            areaId,
            regionId,
            cityUuid,
            cityWithType,
            cityType,
            cityTypeFull,
            city,
            timezone,
        }).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.cityWasChanged"));
        }).
        always(() => {
            if (regionId) {
                if (regionId == targetRegionId) {
                    modules.addresses.renderRegion(regionId);
                } else {
                    location.href = "?#addresses&show=region&regionId=" + targetRegionId + "&_=" + Math.random();
                }
            } else {
                if (areaId == targetAreaId) {
                    modules.addresses.renderArea(areaId);
                } else {
                    location.href = "?#addresses&show=area&areaId=" + targetAreaId + "&_=" + Math.random();
                }
            }
        });
    },

    doModifySettlement: function (settlementId, areaId, cityId, settlementUuid, settlementWithType, settlementType, settlementTypeFull, settlement, targetAreaId, targetCityId) {
        loadingStart();
        PUT("addresses", "settlement", settlementId, {
            settlementId,
            areaId,
            cityId,
            settlementUuid,
            settlementWithType,
            settlementType,
            settlementTypeFull,
            settlement
        }).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.settlementWasChanged"));
        }).
        always(() => {
            if (areaId) {
                if (areaId == targetAreaId) {
                    modules.addresses.renderArea(areaId);
                } else {
                    location.href = "?#addresses&show=area&areaId=" + areaId;
                }
            } else {
                if (cityId == targetCityId) {
                    modules.addresses.renderCity(cityId);
                } else {
                    location.href = "?#addresses&show=city&cityId=" + cityId;
                }
            }
        });
    },

    doModifyStreet: function (streetId, cityId, settlementId, streetUuid, streetWithType, streetType, streetTypeFull, street, targetCityId, targetSettlementId) {
        loadingStart();
        PUT("addresses", "street", streetId, {
            streetId,
            cityId,
            settlementId,
            streetUuid,
            streetWithType,
            streetType,
            streetTypeFull,
            street
        }).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.streetWasChanged"));
        }).
        always(() => {
            if (cityId) {
                if (cityId == targetCityId) {
                    modules.addresses.renderCity(cityId);
                } else {
                    location.href = "?#addresses&show=city&cityId=" + cityId;
                }
            } else {
                if (settlementId == targetSettlementId) {
                    modules.addresses.renderSettlement(settlementId);
                } else {
                    location.href = "?#addresses&show=settlement&settlementId=" + settlementId;
                }
            }
        });
    },

    doModifyHouse: function (houseId, settlementId, streetId, houseUuid, houseType, houseTypeFull, houseFull, house, targetSettlementId, targetStreetId) {
        loadingStart();
        PUT("addresses", "house", houseId, {
            houseId,
            settlementId,
            streetId,
            houseUuid,
            houseType,
            houseTypeFull,
            houseFull,
            house
        }).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.houseWasChanged"));
        }).
        always(() => {
            if (settlementId) {
                if (settlementId == targetSettlementId) {
                    modules.addresses.renderSettlement(settlementId);
                } else {
                    location.href = "?#addresses&show=settlement&settlementId=" + settlementId;
                }
            } else {
                if (streetId == targetStreetId) {
                    modules.addresses.renderStreet(streetId);
                } else {
                    location.href = "?#addresses&show=street&streetId=" + streetId;
                }
            }
        });
    },

    doDeleteRegion: function (regionId) {
        loadingStart();
        DELETE("addresses", "region", regionId).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.regionWasDeleted"));
        }).
        always(modules.addresses.renderRegions);
    },

    doDeleteArea: function (areaId, regionId) {
        loadingStart();
        DELETE("addresses", "area", areaId).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.areaWasDeleted"));
        }).
        always(() => {
            modules.addresses.renderRegion(regionId);
        });
    },

    doDeleteCity: function (cityId, regionId, areaId) {
        loadingStart();
        DELETE("addresses", "city", cityId).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.cityWasDeleted"));
        }).
        always(() => {
            if (regionId) {
                modules.addresses.renderRegion(regionId);
            } else {
                modules.addresses.renderArea(areaId);
            }
        });
    },

    doDeleteSettlement: function (settlementId, areaId, cityId) {
        loadingStart();
        DELETE("addresses", "settlement", settlementId).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.settlementWasDeleted"));
        }).
        always(() => {
            if (areaId) {
                modules.addresses.renderArea(areaId);
            } else {
                modules.addresses.renderCity(cityId);
            }
        });
    },

    doDeleteStreet: function (streetId, cityId, settlementId) {
        loadingStart();
        DELETE("addresses", "street", streetId).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.streetWasDeleted"));
        }).
        always(() => {
            if (cityId) {
                modules.addresses.renderCity(cityId);
            } else {
                modules.addresses.renderSettlement(settlementId);
            }
        });
    },

    doDeleteHouse: function (houseId, settlementId, streetId) {
        loadingStart();
        DELETE("addresses", "house", houseId).
        fail(FAIL).
        done(() => {
            message(i18n("addresses.houseWasDeleted"));
        }).
        always(() => {
            if (settlementId) {
                modules.addresses.renderSettlement(settlementId);
            } else {
                modules.addresses.renderStreet(streetId);
            }
        });
    },

    deleteRegion: function (regionId) {
        mConfirm(i18n("addresses.confirmDeleteRegion", regionId), i18n("confirm"), `danger:${i18n("addresses.deleteRegion")}`, () => {
            modules.addresses.doDeleteRegion(regionId);
        });
    },

    deleteArea: function (areaId, regionId) {
        mConfirm(i18n("addresses.confirmDeleteArea", areaId), i18n("confirm"), `danger:${i18n("addresses.deleteArea")}`, () => {
            modules.addresses.doDeleteArea(areaId, regionId);
        });
    },

    deleteCity: function (cityId, areaId, regionId) {
        mConfirm(i18n("addresses.confirmDeleteCity", areaId), i18n("confirm"), `danger:${i18n("addresses.deleteCity")}`, () => {
            modules.addresses.doDeleteCity(cityId, areaId, regionId);
        });
    },

    deleteSettlement: function (settlementId, cityId, areaId) {
        mConfirm(i18n("addresses.confirmDeleteSettlement", settlementId), i18n("confirm"), `danger:${i18n("addresses.deleteSettlement")}`, () => {
            modules.addresses.doDeleteSettlement(settlementId, cityId, areaId);
        });
    },

    deleteStreet: function (streetId, settlementId, cityId) {
        mConfirm(i18n("addresses.confirmDeleteStreet", streetId), i18n("confirm"), `danger:${i18n("addresses.deleteStreet")}`, () => {
            modules.addresses.doDeleteStreet(streetId, settlementId, cityId);
        });
    },

    deleteHouse: function (houseId, streetId, settlementId) {
        mConfirm(i18n("addresses.confirmDeleteHouse", houseId), i18n("confirm"), `danger:${i18n("addresses.deleteHouse")}`, () => {
            modules.addresses.doDeleteHouse(houseId, streetId, settlementId);
        });
    },

    modifyRegion: function (regionId) {
        let region = false;

        for (let i in modules.addresses.meta.regions) {
            if (modules.addresses.meta.regions[i].regionId == regionId) {
                region = modules.addresses.meta.regions[i];
                break;
            }
        }

        if (region) {
            cardForm({
                title: i18n("addresses.editRegion"),
                footer: true,
                borderless: true,
                topApply: true,
                delete: i18n("addresses.deleteRegion"),
                size: "lg",
                fields: [
                    {
                        id: "regionId",
                        type: "text",
                        title: i18n("addresses.regionId"),
                        value: regionId,
                        readonly: true,
                    },
                    {
                        id: "regionUuid",
                        type: "text",
                        title: i18n("addresses.regionUuid"),
                        placeholder: i18n("addresses.regionUuid"),
                        value: region.regionUuid,
                        validate: v => {
                            return !!v;
                        },
                    },
                    {
                        id: "regionIsoCode",
                        type: "text",
                        title: i18n("addresses.regionIsoCode"),
                        placeholder: i18n("addresses.regionIsoCode"),
                        value: region.regionIsoCode,
                    },
                    {
                        id: "regionWithType",
                        type: "text",
                        title: i18n("addresses.regionWithType"),
                        placeholder: i18n("addresses.regionWithType"),
                        validate: (v) => {
                            return $.trim(v) !== "";
                        },
                        value: region.regionWithType,
                    },
                    {
                        id: "regionType",
                        type: "text",
                        title: i18n("addresses.regionType"),
                        placeholder: i18n("addresses.regionType"),
                        value: region.regionType,
                    },
                    {
                        id: "regionTypeFull",
                        type: "text",
                        title: i18n("addresses.regionTypeFull"),
                        placeholder: i18n("addresses.regionTypeFull"),
                        value: region.regionTypeFull,
                    },
                    {
                        id: "region",
                        type: "text",
                        title: i18n("addresses.region"),
                        placeholder: i18n("addresses.region"),
                        validate: (v) => {
                            return $.trim(v) !== "";
                        },
                        value: region.region,
                    },
                    {
                        id: "timezone",
                        type: "select2",
                        title: i18n("addresses.timezone"),
                        placeholder: i18n("addresses.timezone"),
                        options: modules.addresses.timezonesOptions(),
                        validate: (v) => {
                            return $.trim(v) !== "";
                        },
                        value: region.timezone,
                    },
                ],
                callback: function (result) {
                    if (result.delete === "yes") {
                        modules.addresses.deleteRegion(result.regionId);
                    } else {
                        modules.addresses.doModifyRegion(regionId, result.regionUuid, result.regionIsoCode, result.regionWithType, result.regionType, result.regionTypeFull, result.region, result.timezone);
                    }
                },
            }).show();
        } else {
            error(i18n("addresses.regionNotFound"));
        }
    },

    modifyArea: function (areaId) {
        let area = false;

        for (let i in modules.addresses.meta.areas) {
            if (modules.addresses.meta.areas[i].areaId == areaId) {
                area = modules.addresses.meta.areas[i];
                break;
            }
        }

        let regions = [];
        for (let i in modules.addresses.meta.regions) {
            regions.push({
                id: modules.addresses.meta.regions[i].regionId,
                text: modules.addresses.meta.regions[i].regionWithType,
            });
        }

        if (area) {
            cardForm({
                title: i18n("addresses.editArea"),
                footer: true,
                borderless: true,
                topApply: true,
                delete: i18n("addresses.deleteArea"),
                size: "lg",
                fields: [
                    {
                        id: "areaId",
                        type: "text",
                        title: i18n("addresses.areaId"),
                        value: areaId,
                        readonly: true,
                    },
                    {
                        id: "regionId",
                        type: "select2",
                        title: i18n("addresses.region"),
                        value: area.regionId,
                        options: regions,
                        validate: v => {
                            return !!v;
                        },
                    },
                    {
                        id: "areaUuid",
                        type: "text",
                        title: i18n("addresses.areaUuid"),
                        placeholder: i18n("addresses.areaUuid"),
                        value: area.areaUuid,
                        validate: v => {
                            return !!v;
                        },
                    },
                    {
                        id: "areaWithType",
                        type: "text",
                        title: i18n("addresses.areaWithType"),
                        placeholder: i18n("addresses.areaWithType"),
                        validate: (v) => {
                            return $.trim(v) !== "";
                        },
                        value: area.areaWithType,
                    },
                    {
                        id: "areaType",
                        type: "text",
                        title: i18n("addresses.areaType"),
                        placeholder: i18n("addresses.areaType"),
                        value: area.areaType,
                    },
                    {
                        id: "areaTypeFull",
                        type: "text",
                        title: i18n("addresses.areaTypeFull"),
                        placeholder: i18n("addresses.areaTypeFull"),
                        value: area.areaTypeFull,
                    },
                    {
                        id: "area",
                        type: "text",
                        title: i18n("addresses.area"),
                        placeholder: i18n("addresses.area"),
                        validate: (v) => {
                            return $.trim(v) !== "";
                        },
                        value: area.area,
                    },
                    {
                        id: "timezone",
                        type: "select2",
                        title: i18n("addresses.timezone"),
                        placeholder: i18n("addresses.timezone"),
                        options: modules.addresses.timezonesOptions(),
                        validate: (v) => {
                            return $.trim(v) !== "";
                        },
                        value: area.timezone,
                    },
                ],
                callback: function (result) {
                    if (result.delete === "yes") {
                        modules.addresses.deleteArea(result.areaId, parseInt(area.regionId));
                    } else {
                        modules.addresses.doModifyArea(areaId, parseInt(result.regionId), result.areaUuid, result.areaWithType, result.areaType, result.areaTypeFull, result.area, parseInt(area.regionId), result.timezone);
                    }
                },
            }).show();
        } else {
            error(i18n("addresses.areaNotFound"));
        }
    },

    modifyCity: function (cityId) {
        let city = false;

        for (let i in modules.addresses.meta.cities) {
            if (modules.addresses.meta.cities[i].cityId == cityId) {
                city = modules.addresses.meta.cities[i];
                break;
            }
        }

        let regions = [];

        regions.push({
            id: "0",
            text: "-",
        })
        for (let i in modules.addresses.meta.regions) {
            regions.push({
                id: modules.addresses.meta.regions[i].regionId,
                text: modules.addresses.meta.regions[i].regionWithType,
            });
        }

        let areas = [];

        areas.push({
            id: "0",
            text: "-",
        })
        for (let i in modules.addresses.meta.areas) {
            areas.push({
                id: modules.addresses.meta.areas[i].areaId,
                text: modules.addresses.meta.areas[i].areaWithType,
            });
        }

        if (city) {
            cardForm({
                title: i18n("addresses.editCity"),
                footer: true,
                borderless: true,
                topApply: true,
                delete: i18n("addresses.deleteCity"),
                size: "lg",
                fields: [
                    {
                        id: "cityId",
                        type: "text",
                        title: i18n("addresses.cityId"),
                        value: cityId,
                        readonly: true,
                    },
                    {
                        id: "regionId",
                        type: "select2",
                        title: i18n("addresses.region"),
                        value: city.regionId,
                        options: regions,
                        select: (el, id, prefix) => {
                            $(`#${prefix}areaId`).val("0").trigger("change");
                        },
                        validate: (v, prefix) => {
                            return !!parseInt(v) || !!parseInt($(`#${prefix}areaId`).val());
                        },
                    },
                    {
                        id: "areaId",
                        type: "select2",
                        title: i18n("addresses.area"),
                        value: city.areaId,
                        options: areas,
                        select: (el, id, prefix) => {
                            $(`#${prefix}regionId`).val("0").trigger("change");
                        },
                        validate: (v, prefix) => {
                            return !!parseInt(v) || !!parseInt($(`#${prefix}regionId`).val());
                        },
                    },
                    {
                        id: "cityUuid",
                        type: "text",
                        title: i18n("addresses.cityUuid"),
                        placeholder: i18n("addresses.cityUuid"),
                        value: city.cityUuid,
                        validate: v => {
                            return !!v;
                        },
                    },
                    {
                        id: "cityWithType",
                        type: "text",
                        title: i18n("addresses.cityWithType"),
                        placeholder: i18n("addresses.cityWithType"),
                        validate: (v) => {
                            return $.trim(v) !== "";
                        },
                        value: city.cityWithType,
                    },
                    {
                        id: "cityType",
                        type: "text",
                        title: i18n("addresses.cityType"),
                        placeholder: i18n("addresses.cityType"),
                        value: city.cityType,
                    },
                    {
                        id: "cityTypeFull",
                        type: "text",
                        title: i18n("addresses.cityTypeFull"),
                        placeholder: i18n("addresses.cityTypeFull"),
                        value: city.cityTypeFull,
                    },
                    {
                        id: "city",
                        type: "text",
                        title: i18n("addresses.city"),
                        placeholder: i18n("addresses.city"),
                        validate: (v) => {
                            return $.trim(v) !== "";
                        },
                        value: city.city,
                    },
                    {
                        id: "timezone",
                        type: "select2",
                        title: i18n("addresses.timezone"),
                        placeholder: i18n("addresses.timezone"),
                        options: modules.addresses.timezonesOptions(),
                        validate: (v) => {
                            return $.trim(v) !== "";
                        },
                        value: city.timezone,
                    },
                ],
                callback: function (result) {
                    if (result.delete === "yes") {
                        modules.addresses.deleteCity(result.cityId, parseInt(city.regionId), parseInt(city.areaId));
                    } else {
                        modules.addresses.doModifyCity(cityId, parseInt(result.regionId), parseInt(result.areaId), result.cityUuid, result.cityWithType, result.cityType, result.cityTypeFull, result.city, parseInt(city.regionId), parseInt(city.areaId), result.timezone);
                    }
                },
            }).show();
        } else {
            error(i18n("addresses.cityNotFound"));
        }
    },

    modifySettlement: function (settlementId) {
        let settlement = false;

        for (let i in modules.addresses.meta.settlements) {
            if (modules.addresses.meta.settlements[i].settlementId == settlementId) {
                settlement = modules.addresses.meta.settlements[i];
                break;
            }
        }

        let areas = [];

        areas.push({
            id: "0",
            text: "-",
        })
        for (let i in modules.addresses.meta.areas) {
            areas.push({
                id: modules.addresses.meta.areas[i].areaId,
                text: modules.addresses.meta.areas[i].areaWithType,
            });
        }

        let cities = [];

        cities.push({
            id: "0",
            text: "-",
        })
        for (let i in modules.addresses.meta.cities) {
            cities.push({
                id: modules.addresses.meta.cities[i].cityId,
                text: modules.addresses.meta.cities[i].cityWithType,
            });
        }

        if (settlement) {
            cardForm({
                title: i18n("addresses.editSettlement"),
                footer: true,
                borderless: true,
                topApply: true,
                delete: i18n("addresses.deleteSettlement"),
                size: "lg",
                fields: [
                    {
                        id: "settlementId",
                        type: "text",
                        title: i18n("addresses.settlementId"),
                        value: settlementId,
                        readonly: true,
                    },
                    {
                        id: "areaId",
                        type: "select2",
                        title: i18n("addresses.area"),
                        value: settlement.areaId,
                        options: areas,
                        select: (el, id, prefix) => {
                            $(`#${prefix}cityId`).val("0").trigger("change");
                        },
                        validate: (v, prefix) => {
                            return !!parseInt(v) || !!parseInt($(`#${prefix}cityId`).val());
                        },
                    },
                    {
                        id: "cityId",
                        type: "select2",
                        title: i18n("addresses.city"),
                        value: settlement.cityId,
                        options: cities,
                        select: (el, id, prefix) => {
                            $(`#${prefix}areaId`).val("0").trigger("change");
                        },
                        validate: (v, prefix) => {
                            return !!parseInt(v) || !!parseInt($(`#${prefix}areaId`).val());
                        },
                    },
                    {
                        id: "settlementUuid",
                        type: "text",
                        title: i18n("addresses.settlementUuid"),
                        placeholder: i18n("addresses.settlementUuid"),
                        value: settlement.settlementUuid,
                        validate: v => {
                            return !!v;
                        },
                    },
                    {
                        id: "settlementWithType",
                        type: "text",
                        title: i18n("addresses.settlementWithType"),
                        placeholder: i18n("addresses.settlementWithType"),
                        validate: (v) => {
                            return $.trim(v) !== "";
                        },
                        value: settlement.settlementWithType,
                    },
                    {
                        id: "settlementType",
                        type: "text",
                        title: i18n("addresses.settlementType"),
                        placeholder: i18n("addresses.settlementType"),
                        value: settlement.settlementType,
                    },
                    {
                        id: "settlementTypeFull",
                        type: "text",
                        title: i18n("addresses.settlementTypeFull"),
                        placeholder: i18n("addresses.settlementTypeFull"),
                        value: settlement.settlementTypeFull,
                    },
                    {
                        id: "settlement",
                        type: "text",
                        title: i18n("addresses.settlement"),
                        placeholder: i18n("addresses.settlement"),
                        validate: (v) => {
                            return $.trim(v) !== "";
                        },
                        value: settlement.settlement,
                    },
                ],
                callback: function (result) {
                    if (result.delete === "yes") {
                        modules.addresses.deleteSettlement(result.settlementId, parseInt(settlement.areaId), parseInt(settlement.cityId));
                    } else {
                        modules.addresses.doModifySettlement(settlementId, parseInt(result.areaId), parseInt(result.cityId), result.settlementUuid, result.settlementWithType, result.settlementType, result.settlementTypeFull, result.settlement, parseInt(settlement.areaId), parseInt(settlement.cityId));
                    }
                },
            }).show();
        } else {
            error(i18n("addresses.settlementNotFound"));
        }
    },

    modifyStreet: function (streetId) {
        let street = false;

        for (let i in modules.addresses.meta.streets) {
            if (modules.addresses.meta.streets[i].streetId == streetId) {
                street = modules.addresses.meta.streets[i];
                break;
            }
        }

        let cities = [];

        cities.push({
            id: "0",
            text: "-",
        })
        for (let i in modules.addresses.meta.cities) {
            cities.push({
                id: modules.addresses.meta.cities[i].cityId,
                text: modules.addresses.meta.cities[i].cityWithType,
            });
        }

        let settlements = [];

        settlements.push({
            id: "0",
            text: "-",
        })
        for (let i in modules.addresses.meta.settlements) {
            settlements.push({
                id: modules.addresses.meta.settlements[i].settlementId,
                text: modules.addresses.meta.settlements[i].settlementWithType,
            });
        }

        if (street) {
            cardForm({
                title: i18n("addresses.editStreet"),
                footer: true,
                borderless: true,
                topApply: true,
                delete: i18n("addresses.deleteStreet"),
                size: "lg",
                fields: [
                    {
                        id: "streetId",
                        type: "text",
                        title: i18n("addresses.streetId"),
                        value: streetId,
                        readonly: true,
                    },
                    {
                        id: "cityId",
                        type: "select2",
                        title: i18n("addresses.city"),
                        value: street.cityId,
                        options: cities,
                        select: (el, id, prefix) => {
                            $(`#${prefix}settlementId`).val("0").trigger("change");
                        },
                        validate: (v, prefix) => {
                            return !!parseInt(v) || !!parseInt($(`#${prefix}settlementId`).val());
                        },
                    },
                    {
                        id: "settlementId",
                        type: "select2",
                        title: i18n("addresses.settlement"),
                        value: street.settlementId,
                        options: settlements,
                        select: (el, id, prefix) => {
                            $(`#${prefix}cityId`).val("0").trigger("change");
                        },
                        validate: (v, prefix) => {
                            return !!parseInt(v) || !!parseInt($(`#${prefix}cityId`).val());
                        },
                    },
                    {
                        id: "streetUuid",
                        type: "text",
                        title: i18n("addresses.streetUuid"),
                        placeholder: i18n("addresses.streetUuid"),
                        value: street.streetUuid,
                        validate: v => {
                            return !!v;
                        },
                    },
                    {
                        id: "streetWithType",
                        type: "text",
                        title: i18n("addresses.streetWithType"),
                        placeholder: i18n("addresses.streetWithType"),
                        validate: (v) => {
                            return $.trim(v) !== "";
                        },
                        value: street.streetWithType,
                    },
                    {
                        id: "streetType",
                        type: "text",
                        title: i18n("addresses.streetType"),
                        placeholder: i18n("addresses.streetType"),
                        value: street.streetType,
                    },
                    {
                        id: "streetTypeFull",
                        type: "text",
                        title: i18n("addresses.streetTypeFull"),
                        placeholder: i18n("addresses.streetTypeFull"),
                        value: street.streetTypeFull,
                    },
                    {
                        id: "street",
                        type: "text",
                        title: i18n("addresses.street"),
                        placeholder: i18n("addresses.street"),
                        validate: (v) => {
                            return $.trim(v) !== "";
                        },
                        value: street.street,
                    },
                ],
                callback: function (result) {
                    if (result.delete === "yes") {
                        modules.addresses.deleteStreet(streetId, parseInt(street.cityId), parseInt(street.settlementId));
                    } else {
                        modules.addresses.doModifyStreet(streetId, parseInt(result.cityId), parseInt(result.settlementId), result.streetUuid, result.streetWithType, result.streetType, result.streetTypeFull, result.street, parseInt(street.cityId), parseInt(street.settlementId));
                    }
                },
            }).show();
        } else {
            error(i18n("addresses.streetNotFound"));
        }
    },

    modifyHouse: function (houseId) {
        let house = false;

        for (let i in modules.addresses.meta.houses) {
            if (modules.addresses.meta.houses[i].houseId == houseId) {
                house = modules.addresses.meta.houses[i];
                break;
            }
        }

        let settlements = [];

        settlements.push({
            id: "0",
            text: "-",
        })
        for (let i in modules.addresses.meta.settlements) {
            settlements.push({
                id: modules.addresses.meta.settlements[i].settlementId,
                text: modules.addresses.meta.settlements[i].settlementWithType,
            });
        }

        let streets = [];

        streets.push({
            id: "0",
            text: "-",
        })
        for (let i in modules.addresses.meta.streets) {
            streets.push({
                id: modules.addresses.meta.streets[i].streetId,
                text: modules.addresses.meta.streets[i].streetWithType,
            });
        }

        if (house) {
            cardForm({
                title: i18n("addresses.editHouse"),
                footer: true,
                borderless: true,
                topApply: true,
                delete: i18n("addresses.deleteHouse"),
                size: "lg",
                fields: [
                    {
                        id: "houseId",
                        type: "text",
                        title: i18n("addresses.houseId"),
                        value: houseId,
                        readonly: true,
                    },
                    {
                        id: "settlementId",
                        type: "select2",
                        title: i18n("addresses.settlement"),
                        value: house.settlementId,
                        options: settlements,
                        select: (el, id, prefix) => {
                            $(`#${prefix}streetId`).val("0").trigger("change");
                        },
                        validate: (v, prefix) => {
                            return !!parseInt(v) || !!parseInt($(`#${prefix}streetId`).val());
                        },
                    },
                    {
                        id: "streetId",
                        type: "select2",
                        title: i18n("addresses.street"),
                        value: house.streetId,
                        options: streets,
                        select: (el, id, prefix) => {
                            $(`#${prefix}settlementId`).val("0").trigger("change");
                        },
                        validate: (v, prefix) => {
                            return !!parseInt(v) || !!parseInt($(`#${prefix}settlementId`).val());
                        },
                    },
                    {
                        id: "houseUuid",
                        type: "text",
                        title: i18n("addresses.houseUuid"),
                        placeholder: i18n("addresses.houseUuid"),
                        value: house.houseUuid,
                        validate: v => {
                            return !!v;
                        },
                    },
                    {
                        id: "houseType",
                        type: "text",
                        title: i18n("addresses.houseType"),
                        placeholder: i18n("addresses.houseType"),
                        value: house.houseType,
                    },
                    {
                        id: "houseTypeFull",
                        type: "text",
                        title: i18n("addresses.houseTypeFull"),
                        placeholder: i18n("addresses.houseTypeFull"),
                        value: house.houseTypeFull,
                    },
                    {
                        id: "houseFull",
                        type: "text",
                        title: i18n("addresses.houseFull"),
                        placeholder: i18n("addresses.houseFull"),
                        validate: (v) => {
                            return $.trim(v) !== "";
                        },
                        value: house.houseFull,
                    },
                    {
                        id: "house",
                        type: "text",
                        title: i18n("addresses.house"),
                        placeholder: i18n("addresses.house"),
                        validate: (v) => {
                            return $.trim(v) !== "";
                        },
                        value: house.house,
                    },
                ],
                callback: function (result) {
                    if (result.delete === "yes") {
                        modules.addresses.deleteHouse(houseId, parseInt(house.settlementId), parseInt(house.streetId));
                    } else {
                        modules.addresses.doModifyHouse(houseId, parseInt(result.settlementId), parseInt(result.streetId), result.houseUuid, result.houseType, result.houseTypeFull, result.houseFull, result.house, parseInt(house.settlementId), parseInt(house.streetId));
                    }
                },
            }).show();
        } else {
            error(i18n("addresses.houseNotFound"));
        }
    },

    addRegion: function () {
        cardForm({
            title: i18n("addresses.addRegion"),
            footer: true,
            borderless: true,
            topApply: true,
            apply: i18n("add"),
            size: "lg",
            fields: [
                {
                    id: "regionUuid",
                    type: "text",
                    title: i18n("addresses.regionUuid"),
                    placeholder: i18n("addresses.regionUuid"),
                    button: {
                        class: "fas fa-magic",
                        click: prefix => {
                            $(`#${prefix}regionUuid`).val(guid());
                        },
                    },
                    validate: (v) => {
                        return $.trim(v) !== "";
                    }
                },
                {
                    id: "regionIsoCode",
                    type: "text",
                    title: i18n("addresses.regionIsoCode"),
                    placeholder: i18n("addresses.regionIsoCode"),
                },
                {
                    id: "regionWithType",
                    type: "text",
                    title: i18n("addresses.regionWithType"),
                    placeholder: i18n("addresses.regionWithType"),
                    validate: (v) => {
                        return $.trim(v) !== "";
                    }
                },
                {
                    id: "regionType",
                    type: "text",
                    title: i18n("addresses.regionType"),
                    placeholder: i18n("addresses.regionType"),
                },
                {
                    id: "regionTypeFull",
                    type: "text",
                    title: i18n("addresses.regionTypeFull"),
                    placeholder: i18n("addresses.regionTypeFull"),
                },
                {
                    id: "region",
                    type: "text",
                    title: i18n("addresses.region"),
                    placeholder: i18n("addresses.region"),
                    validate: (v) => {
                        return $.trim(v) !== "";
                    }
                },
                {
                    id: "timezone",
                    type: "select2",
                    title: i18n("addresses.timezone"),
                    placeholder: i18n("addresses.timezone"),
                    options: modules.addresses.timezonesOptions(),
                    validate: (v) => {
                        return $.trim(v) !== "";
                    }
                },
            ],
            callback: function (result) {
                modules.addresses.doAddRegion(result.regionUuid, result.regionIsoCode, result.regionWithType, result.regionType, result.regionTypeFull, result.region, result.timezone);
            },
        }).show();
    },

    addArea: function (regionId) {
        cardForm({
            title: i18n("addresses.addArea"),
            footer: true,
            borderless: true,
            topApply: true,
            apply: i18n("add"),
            size: "lg",
            fields: [
                {
                    id: "areaUuid",
                    type: "text",
                    title: i18n("addresses.areaUuid"),
                    placeholder: i18n("addresses.areaUuid"),
                    button: {
                        class: "fas fa-magic",
                        click: prefix => {
                            $(`#${prefix}areaUuid`).val(guid());
                        },
                    },
                    validate: v => {
                        return !!v;
                    },
                },
                {
                    id: "areaWithType",
                    type: "text",
                    title: i18n("addresses.areaWithType"),
                    placeholder: i18n("addresses.areaWithType"),
                    validate: (v) => {
                        return $.trim(v) !== "";
                    }
                },
                {
                    id: "areaType",
                    type: "text",
                    title: i18n("addresses.areaType"),
                    placeholder: i18n("addresses.areaType"),
                },
                {
                    id: "areaTypeFull",
                    type: "text",
                    title: i18n("addresses.areaTypeFull"),
                    placeholder: i18n("addresses.areaTypeFull"),
                },
                {
                    id: "area",
                    type: "text",
                    title: i18n("addresses.area"),
                    placeholder: i18n("addresses.area"),
                    validate: (v) => {
                        return $.trim(v) !== "";
                    }
                },
                {
                    id: "timezone",
                    type: "select2",
                    title: i18n("addresses.timezone"),
                    placeholder: i18n("addresses.timezone"),
                    options: modules.addresses.timezonesOptions(),
                    validate: (v) => {
                        return $.trim(v) !== "";
                    }
                },
            ],
            callback: function (result) {
                modules.addresses.doAddArea(regionId, result.areaUuid, result.areaWithType, result.areaType, result.areaTypeFull, result.area, result.timezone);
            },
        }).show();
    },

    addCity: function (regionId, areaId) {
        cardForm({
            title: i18n("addresses.addCity"),
            footer: true,
            borderless: true,
            topApply: true,
            apply: i18n("add"),
            size: "lg",
            fields: [
                {
                    id: "cityUuid",
                    type: "text",
                    title: i18n("addresses.cityUuid"),
                    placeholder: i18n("addresses.cityUuid"),
                    button: {
                        class: "fas fa-magic",
                        click: prefix => {
                            $(`#${prefix}cityUuid`).val(guid());
                        },
                    },
                    validate: v => {
                        return !!v;
                    },
                },
                {
                    id: "cityWithType",
                    type: "text",
                    title: i18n("addresses.cityWithType"),
                    placeholder: i18n("addresses.cityWithType"),
                    validate: (v) => {
                        return $.trim(v) !== "";
                    }
                },
                {
                    id: "cityType",
                    type: "text",
                    title: i18n("addresses.cityType"),
                    placeholder: i18n("addresses.cityType"),
                },
                {
                    id: "cityTypeFull",
                    type: "text",
                    title: i18n("addresses.cityTypeFull"),
                    placeholder: i18n("addresses.cityTypeFull"),
                },
                {
                    id: "city",
                    type: "text",
                    title: i18n("addresses.city"),
                    placeholder: i18n("addresses.city"),
                    validate: (v) => {
                        return $.trim(v) !== "";
                    }
                },
                {
                    id: "timezone",
                    type: "select2",
                    title: i18n("addresses.timezone"),
                    placeholder: i18n("addresses.timezone"),
                    options: modules.addresses.timezonesOptions(),
                    validate: (v) => {
                        return $.trim(v) !== "";
                    }
                },
            ],
            callback: function (result) {
                modules.addresses.doAddCity(regionId, areaId, result.cityUuid, result.cityWithType, result.cityType, result.cityTypeFull, result.city, result.timezone);
            },
        }).show();
    },

    addSettlement: function (areaId, cityId) {
        cardForm({
            title: i18n("addresses.addSettlement"),
            footer: true,
            borderless: true,
            topApply: true,
            apply: i18n("add"),
            size: "lg",
            fields: [
                {
                    id: "settlementUuid",
                    type: "text",
                    title: i18n("addresses.settlementUuid"),
                    placeholder: i18n("addresses.settlementUuid"),
                    button: {
                        class: "fas fa-magic",
                        click: prefix => {
                            $(`#${prefix}settlementUuid`).val(guid());
                        },
                    },
                    validate: v => {
                        return !!v;
                    },
                },
                {
                    id: "settlementWithType",
                    type: "text",
                    title: i18n("addresses.settlementWithType"),
                    placeholder: i18n("addresses.settlementWithType"),
                    validate: (v) => {
                        return $.trim(v) !== "";
                    }
                },
                {
                    id: "settlementType",
                    type: "text",
                    title: i18n("addresses.settlementType"),
                    placeholder: i18n("addresses.settlementType"),
                },
                {
                    id: "settlementTypeFull",
                    type: "text",
                    title: i18n("addresses.settlementTypeFull"),
                    placeholder: i18n("addresses.settlementTypeFull"),
                },
                {
                    id: "settlement",
                    type: "text",
                    title: i18n("addresses.settlement"),
                    placeholder: i18n("addresses.settlement"),
                    validate: (v) => {
                        return $.trim(v) !== "";
                    }
                },
            ],
            callback: function (result) {
                modules.addresses.doAddSettlement(areaId, cityId, result.settlementUuid, result.settlementWithType, result.settlementType, result.settlementTypeFull, result.settlement);
            },
        }).show();
    },

    addStreet: function (cityId, settlementId) {
        cardForm({
            title: i18n("addresses.addStreet"),
            footer: true,
            borderless: true,
            topApply: true,
            apply: i18n("add"),
            size: "lg",
            fields: [
                {
                    id: "streetUuid",
                    type: "text",
                    title: i18n("addresses.streetUuid"),
                    placeholder: i18n("addresses.streetUuid"),
                    button: {
                        class: "fas fa-magic",
                        click: prefix => {
                            $(`#${prefix}streetUuid`).val(guid());
                        },
                    },
                    validate: v => {
                        return !!v;
                    },
                },
                {
                    id: "streetWithType",
                    type: "text",
                    title: i18n("addresses.streetWithType"),
                    placeholder: i18n("addresses.streetWithType"),
                    validate: (v) => {
                        return $.trim(v) !== "";
                    }
                },
                {
                    id: "streetType",
                    type: "text",
                    title: i18n("addresses.streetType"),
                    placeholder: i18n("addresses.streetType"),
                },
                {
                    id: "streetTypeFull",
                    type: "text",
                    title: i18n("addresses.streetTypeFull"),
                    placeholder: i18n("addresses.streetTypeFull"),
                },
                {
                    id: "street",
                    type: "text",
                    title: i18n("addresses.street"),
                    placeholder: i18n("addresses.street"),
                    validate: (v) => {
                        return $.trim(v) !== "";
                    }
                },
            ],
            callback: function (result) {
                modules.addresses.doAddStreet(cityId, settlementId, result.streetUuid, result.streetWithType, result.streetType, result.streetTypeFull, result.street);
            },
        }).show();
    },

    addHouse: function (settlementId, streetId) {
        cardForm({
            title: i18n("addresses.addHouse"),
            footer: true,
            borderless: true,
            topApply: true,
            apply: i18n("add"),
            size: "lg",
            fields: [
                {
                    id: "houseUuid",
                    type: "text",
                    title: i18n("addresses.houseUuid"),
                    placeholder: i18n("addresses.houseUuid"),
                    button: {
                        class: "fas fa-magic",
                        click: prefix => {
                            $(`#${prefix}houseUuid`).val(guid());
                        },
                    },
                    validate: v => {
                        return !!v;
                    },
                },
                {
                    id: "houseType",
                    type: "text",
                    title: i18n("addresses.houseType"),
                    placeholder: i18n("addresses.houseType"),
                },
                {
                    id: "houseTypeFull",
                    type: "text",
                    title: i18n("addresses.houseTypeFull"),
                    placeholder: i18n("addresses.houseTypeFull"),
                },
                {
                    id: "houseFull",
                    type: "text",
                    title: i18n("addresses.houseFull"),
                    placeholder: i18n("addresses.houseFull"),
                    validate: (v) => {
                        return $.trim(v) !== "";
                    }
                },
                {
                    id: "house",
                    type: "text",
                    title: i18n("addresses.house"),
                    placeholder: i18n("addresses.house"),
                    validate: (v) => {
                        return $.trim(v) !== "";
                    }
                },
            ],
            callback: function (result) {
                modules.addresses.doAddHouse(settlementId, streetId, result.houseUuid, result.houseType, result.houseTypeFull, result.houseFull, result.house);
            },
        }).show();
    },

    renderCities: function (target, regionId, areaId) {
        cardTable({
            target,
            title: {
                caption: i18n("addresses.cities"),
                button: {
                    caption: i18n("addresses.addCity"),
                    click: () => {
                        modules.addresses.addCity(regionId, areaId);
                    },
                },
                filter: true,
            },
            edit: modules.addresses.modifyCity,
            columns: [
                {
                    title: i18n("addresses.cityId"),
                },
                {
                    title: i18n("addresses.city"),
                    fullWidth: true,
                },
            ],
            rows: () => {
                let rows = [];

                for (let i in modules.addresses.meta.cities) {
                    if ((regionId && modules.addresses.meta.cities[i].regionId == regionId && !modules.addresses.meta.cities[i].areaId) || (areaId && modules.addresses.meta.cities[i].areaId == areaId && !modules.addresses.meta.cities[i].regionId)) {
                        rows.push({
                            uid: modules.addresses.meta.cities[i].cityId,
                            cols: [
                                {
                                    data: modules.addresses.meta.cities[i].cityId,
                                },
                                {
                                    data: modules.addresses.meta.cities[i].cityWithType,
                                    nowrap: true,
                                    click: "#addresses&show=city&cityId=%s",
                                },
                            ],
                        });
                    }
                }

                return rows;
            },
        }).show();
    },

    renderSettlements: function (target, areaId, cityId) {
        cardTable({
            target,
            title: {
                caption: i18n("addresses.settlements"),
                button: {
                    caption: i18n("addresses.addSettlement"),
                    click: () => {
                        modules.addresses.addSettlement(areaId, cityId);
                    },
                },
                filter: true,
            },
            edit: modules.addresses.modifySettlement,
            columns: [
                {
                    title: i18n("addresses.settlementId"),
                },
                {
                    title: i18n("addresses.settlement"),
                    fullWidth: true,
                },
            ],
            rows: () => {
                let rows = [];

                for (let i in modules.addresses.meta.settlements) {
                    if ((areaId && modules.addresses.meta.settlements[i].areaId == areaId && !modules.addresses.meta.settlements[i].cityId) || (cityId && modules.addresses.meta.settlements[i].cityId == cityId && !modules.addresses.meta.settlements[i].areaId)) {
                        rows.push({
                            uid: modules.addresses.meta.settlements[i].settlementId,
                            cols: [
                                {
                                    data: modules.addresses.meta.settlements[i].settlementId,
                                },
                                {
                                    data: modules.addresses.meta.settlements[i].settlementWithType,
                                    nowrap: true,
                                    click: "#addresses&show=settlement&settlementId=%s",
                                },
                            ],
                        });
                    }
                }

                return rows;
            },
        }).show();
    },

    renderStreets: function (target, cityId, settlementId) {
        cardTable({
            target,
            title: {
                caption: i18n("addresses.streets"),
                button: {
                    caption: i18n("addresses.addStreet"),
                    click: () => {
                        modules.addresses.addStreet(cityId, settlementId);
                    },
                },
                filter: true,
            },
            edit: modules.addresses.modifyStreet,
            columns: [
                {
                    title: i18n("addresses.streetId"),
                },
                {
                    title: i18n("addresses.street"),
                    fullWidth: true,
                },
            ],
            rows: () => {
                let rows = [];

                for (let i in modules.addresses.meta.streets) {
                    if ((cityId && modules.addresses.meta.streets[i].cityId == cityId && !modules.addresses.meta.streets[i].settlementId) || (settlementId && modules.addresses.meta.streets[i].settlementId == settlementId && !modules.addresses.meta.streets[i].cityId)) {
                        rows.push({
                            uid: modules.addresses.meta.streets[i].streetId,
                            cols: [
                                {
                                    data: modules.addresses.meta.streets[i].streetId,
                                },
                                {
                                    data: modules.addresses.meta.streets[i].streetWithType,
                                    nowrap: true,
                                    click: "#addresses&show=street&streetId=%s",
                                },
                            ],
                        });
                    }
                }

                return rows;
            },
        }).show();
    },

    renderHouses: function (target, settlementId, streetId) {
        cardTable({
            target,
            title: {
                caption: i18n("addresses.houses"),
                button: {
                    caption: i18n("addresses.addHouse"),
                    click: () => {
                        modules.addresses.addHouse(settlementId, streetId);
                    },
                },
                filter: true,
            },
            edit: modules.addresses.modifyHouse,
            columns: [
                {
                    title: i18n("addresses.houseId"),
                },
                {
                    title: i18n("addresses.houseFull"),
                    fullWidth: true,
                },
            ],
            rows: () => {
                let rows = [];

                for (let i in modules.addresses.meta.houses) {
                    if ((settlementId && modules.addresses.meta.houses[i].settlementId == settlementId && !modules.addresses.meta.houses[i].streetId) || (streetId && modules.addresses.meta.houses[i].streetId == streetId && !modules.addresses.meta.houses[i].settlementId)) {
                        rows.push({
                            uid: modules.addresses.meta.houses[i].houseId,
                            cols: [
                                {
                                    data: modules.addresses.meta.houses[i].houseId,
                                },
                                {
                                    data: modules.addresses.meta.houses[i].houseFull,
                                    nowrap: true,
                                    click: "#addresses.houses&houseId=%s",
                                },
                            ],
                        });
                    }
                }

                return rows;
            },
        }).show();
    },

    renderRegions: function () {
        loadingStart();
        QUERY("addresses", "addresses", {
            include: "regions",
        }, true).
        done(modules.addresses.addresses).
        done(() => {
            cardTable({
                title: {
                    caption: i18n("addresses.regions"),
                    button: {
                        caption: i18n("addresses.addRegion"),
                        click: modules.addresses.addRegion,
                    },
                    filter: true,
                },
                edit: modules.addresses.modifyRegion,
                columns: [
                    {
                        title: i18n("addresses.regionId"),
                    },
                    {
                        title: i18n("addresses.region"),
                        fullWidth: true,
                    },
                ],
                rows: () => {
                    let rows = [];

                    for (let i in modules.addresses.meta.regions) {
                        rows.push({
                            uid: modules.addresses.meta.regions[i].regionId.toString(),
                            cols: [
                                {
                                    data: modules.addresses.meta.regions[i].regionId,
                                },
                                {
                                    data: modules.addresses.meta.regions[i].regionWithType,
                                    nowrap: true,
                                    click: "#addresses&show=region&regionId=%s",
                                },
                            ],
                        });
                    }

                    return rows;
                },
                target: "#mainForm",
            });
        }).
        fail(FAIL).
        always(loadingDone);
    },

    renderRegion: function (regionId) {
        loadingStart();
        QUERY("addresses", "addresses", {
            regionId: regionId,
            include: "regions,areas,cities",
        }, true).
        done(modules.addresses.addresses).
        done(() => {
            let region = false;

            for (let i in modules.addresses.meta.regions) {
                if (modules.addresses.meta.regions[i].regionId == regionId) {
                    region = modules.addresses.meta.regions[i];
                    break;
                }
            }
            if (!region) {
                page404();
                return;
            }

            subTop(modules.addresses.path("region", regionId));

            cardTable({
                target: "#mainForm",
                title: {
                    caption: i18n("addresses.areas"),
                    button: {
                        caption: i18n("addresses.addArea"),
                        click: () => {
                            modules.addresses.addArea(regionId);
                        },
                    },
                    filter: true,
                },
                edit: modules.addresses.modifyArea,
                columns: [
                    {
                        title: i18n("addresses.areaId"),
                    },
                    {
                        title: i18n("addresses.area"),
                        fullWidth: true,
                    },
                ],
                rows: () => {
                    let rows = [];

                    for (let i in modules.addresses.meta.areas) {
                        if (modules.addresses.meta.areas[i].regionId == regionId) {
                            rows.push({
                                uid: modules.addresses.meta.areas[i].areaId,
                                cols: [
                                    {
                                        data: modules.addresses.meta.areas[i].areaId,
                                    },
                                    {
                                        data: modules.addresses.meta.areas[i].areaWithType,
                                        nowrap: true,
                                        click: "#addresses&show=area&areaId=%s",
                                    },
                                ],
                            });
                        }
                    }

                    return rows;
                },
            });
            modules.addresses.renderCities("#altForm", regionId, false);
        }).
        fail(FAIL).
        always(loadingDone);
    },

    renderArea: function (areaId) {
        loadingStart();
        QUERY("addresses", "addresses", {
            areaId: areaId,
            include: "regions,areas,cities,settlements",
        }, true).
        done(modules.addresses.addresses).
        done(() => {
            let area = false;

            for (let i in modules.addresses.meta.areas) {
                if (modules.addresses.meta.areas[i].areaId == areaId) {
                    area = modules.addresses.meta.areas[i];
                    break;
                }
            }

            if (!area) {
                page404();
                return;
            }

            subTop(modules.addresses.path("area", areaId));

            modules.addresses.renderCities("#mainForm", false, areaId);
            modules.addresses.renderSettlements("#altForm", areaId, false);
        }).
        fail(FAIL).
        always(loadingDone);
    },

    renderCity: function (cityId) {
        loadingStart();
        QUERY("addresses", "addresses", {
            cityId: cityId,
            include: "regions,areas,cities,settlements,streets",
        }, true).
        done(modules.addresses.addresses).
        done(() => {
            let f = false;

            for (let i in modules.addresses.meta.cities) {
                if (modules.addresses.meta.cities[i].cityId == cityId) {
                    f = true;
                    break;
                }
            }
            if (!f) {
                page404();
                return;
            }

            subTop(modules.addresses.path("city", cityId));

            modules.addresses.renderStreets("#mainForm", cityId, false);
            modules.addresses.renderSettlements("#altForm", false, cityId);
        }).
        fail(FAIL).
        always(loadingDone);
    },

    renderSettlement: function (settlementId) {
        loadingStart();
        QUERY("addresses", "addresses", {
            settlementId: settlementId,
            include: "regions,areas,settlements,streets,houses",
        }, true).
        done(modules.addresses.addresses).
        done(() => {
            let f = false;

            for (let i in modules.addresses.meta.settlements) {
                if (modules.addresses.meta.settlements[i].settlementId == settlementId) {
                    f = true;
                    break;
                }
            }
            if (!f) {
                page404();
                return;
            }

            subTop(modules.addresses.path("settlement", settlementId));

            modules.addresses.renderStreets("#mainForm", false, settlementId);
            modules.addresses.renderHouses("#altForm", settlementId, false);
        }).
        fail(FAIL).
        always(loadingDone);
    },

    renderStreet: function (streetId) {
        loadingStart();
        QUERY("addresses", "addresses", {
            streetId: streetId,
            include: "regions,areas,cities,settlements,streets,houses",
        }, true).
        done(modules.addresses.addresses).
        done(() => {
            let f = false;

            for (let i in modules.addresses.meta.streets) {
                if (modules.addresses.meta.streets[i].streetId == streetId) {
                    f = true;
                }
            }

            if (!f) {
                page404();
                return;
            }

            subTop(modules.addresses.path("street", streetId));

            modules.addresses.renderHouses("#mainForm", false, streetId);

            loadingDone();
        }).
        fail(FAIL).
        fail(loadingDone);
    },

    topMenu: function () {
        let top = '';

        if (AVAIL("geo", "suggestions")) {
            top += `
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="javascript:void(0)" class="addHouseMagic nav-link nav-item-back-hover text-dark">${i18n("addresses.addHouse")}</a>
                </li>
            `;
        }

        $("#leftTopDynamic").html(top);
        $(".addHouseMagic").off("click").on("click", modules.addresses.houses.houseMagic);
    },

    route: function (params) {
        $("#altForm").hide();

        document.title = i18n("windowTitle") + " :: " + i18n("addresses.addresses");

        if (!params.show) {
            params.show = "regions";
        }

        modules.addresses.topMenu();

        switch (params.show) {
            case "region":
                modules.addresses.renderRegion(params.regionId);
                break;
            case "area":
                modules.addresses.renderArea(params.areaId);
                break;
            case "city":
                modules.addresses.renderCity(params.cityId);
                break;
            case "settlement":
                modules.addresses.renderSettlement(params.settlementId);
                break;
            case "street":
                modules.addresses.renderStreet(params.streetId);
                break;
            case "regions":
                $("#subTop").html("");
                modules.addresses.renderRegions();
                break;
            default:
                page404();
                break;
        }
    },

    // if search function is defined, search string will be displayed
    search: function (str) {
        console.log("addresses: " + str);
    },
}).init();