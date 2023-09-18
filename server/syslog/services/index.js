const {SyslogService} = require("./SyslogService");
const {BewardService} = require("./BewardService");
const {BewardServiceDS} = require("./BewardServiceDS");
const {QtechService} = require("./QtechService");
const {AkuvoxService} = require("./AkuvoxService");
const {IsService} = require("./IsService");
const {RubetekService} = require("./RubetekService");

const {WebHookService} = require("./WebHookService");
const {NonameWebHookService} = require("./NonameWebHookService");
const {SputnikService} = require("./SputnikService");

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
    NonameWebHookService
}