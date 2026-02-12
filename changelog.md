## Legend

```diff
- bugfix
+ new
! important changes
# removed or deprecated
```

```diff
+ encrypted password authentications (requires "cli.php --keys" and pubKey in client's config)
+ tt workflow new method api.call("GET|POST|PUT|DELETE", "api", "method", "refresh", "data")
+ expanded RODOS support, the entire line should work, from 1 to 16 outputs
+ markdown in comments and description in TT
+ dial to analog intercom by cms matrix (useAnalogNumber)
```

## 2026-01-14 0.0.20 hotfix 8

```diff
+ simple kanban
+ simple system info dashboard
! backend tt type "mongo" renamed to "internal" (need modify server/config/config.json)
+ camTree settings for webUI, camTree = false - off, "houses" - common for all houses, "perHouse" - per house
+ devices (cameras and domophones) tree
+ persistent tables filters in webUI
+ sudo-like administrative mode in webUI
+ fyeo in notes
! addHouseByMagic() now can accept both *_fias_id and *_uuid fields
! massive refactoring in server/utils/*.php
+ @api {get} /api/houses/flat:flatId get flat
- minor fixes in households->modifyFlat
+ bloking webUI interface when in maintenance mode
+ scroll to and hightlight issue when returning from issue to list
+ --force-expire for files backend
- fixed css loader
- fixed sip-ready events
+ added tmpfs, memfs and extfs backends
- fixed issueAdapter backend
+ added webrtc setting (on/off) for cameras
+ autocompact parameter for files backend
+ --mongodb-compact global cli command
```

## 2025-11-09 0.0.18 hotfix 7