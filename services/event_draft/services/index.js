const { SyslogService } = require("./base/SyslogService");
const { WebHookService } = require("./base/WebHookService");
const { BewardService } = require("./BewardService");
const { BewardServiceDS } = require("./BewardServiceDS");
const { QtechService } = require("./QtechService");
const { AkuvoxService } = require("./AkuvoxService");
const { IsService } = require("./IsService");
const { RubetekService } = require("./RubetekService");
const { NonameWebHookService } = require("./NonameWebHookService");
const { SputnikService } = require("./SputnikService");
const { OmnyWebHookService } = require("./OmnyWebHookService");

module.exports = {
    SyslogService,
    WebHookService,
    BewardService,
    BewardServiceDS,
    QtechService,
    AkuvoxService,
    IsService,
    RubetekService,
    SputnikService,
    NonameWebHookService,
    OmnyWebHookService
}