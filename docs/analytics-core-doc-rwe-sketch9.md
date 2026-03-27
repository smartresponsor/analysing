Marketing America Corp. Oleksandr Tishchenko

Roadmap with Envelopes (RWE) for Analytics sketch9

Context snapshot
- PHP files total: 356
- Namespace roots observed: App (214), SmartResponsor (113), Analytics (17)
- DomainInterface files: 165
- InterfaceInterface anomalies: 103
- Mirror missing (Service -> ServiceInterface): 7
- SourceDomain candidates: 0

Envelopes

- SK9-A0 inventory and layer map
  Goal: deterministic inventory + duplicates + mirror/status issues
  Slice: ATOM
  Limits: files_max=5 loc_max=600
  Canon: singular naming, mirror interface layers, English-only comments, copyright header
  Inputs: repo tree
  Paths: tools/** report/** docs/**
  Outputs: scanner tool + report json/csv + docs
  Acceptance Criteria: scanner runs on repo root, excludes vendor/.git, produces report files
  Notes: detects SourceDomain but does not migrate

- SK9-A1 export hygiene + composer scripts
  Goal: keep exports clean and make scan/lint runnable
  Slice: ATOM
  Limits: files_max=5 loc_max=600
  Inputs: .gitattributes .gitignore composer.json
  Outputs: export-ignore for vendor/.git/report, ignore report/, composer script "scan"
  Acceptance Criteria: composer run scan works; export-ignore covers vendor/.git

- SK9-A2 hotfix: clickhouse bind + signed url concat
  Goal: remove PHP concat bugs that break runtime
  Slice: ATOM
  Limits: files_max=5 loc_max=600
  Paths: src/Domain/Analytics/**
  Outputs: fixed implementations + php -l clean
  Acceptance Criteria: no '+' string concat, placeholder replace works, sign() deterministic per call

- SK9-A3 php header guard + normalize headers
  Goal: enforce "<?php" first and add guard tool
  Slice: MAX-BUCKET
  Limits: files_max=20 loc_max=1500
  Paths: src/** tools/**
  Outputs: guard tool + normalized headers
  Acceptance Criteria: php tools/analytics-php-header-guard.php returns 0

Next envelopes (not yet applied)

- SK9-B1 purge InterfaceInterface anomalies (batch)
  Goal: remove *InterfaceInterface.php duplication; keep single Interface
  Slice: BUCKET (repeatable)
  Limits: files_max=16 loc_max=1200
  Inputs: report/analytics-layer-map.json
  Paths: src/*Interface/**
  Outputs: delete/rename extra interfaces, update references, phpunit green
  Acceptance Criteria: no symbol names end with InterfaceInterface; phpstan level=max passes
  Notes: expect ~7 buckets to cover ~103 files

- SK9-B2 choose canonical namespace root
  Goal: lock canonical namespace and autoload mapping
  Slice: ATOM
  Limits: files_max=5 loc_max=600
  Inputs: composer.json + scan report
  Outputs: docs decision + composer autoload adjustment proposal
  Acceptance Criteria: decision documented; follow-up buckets become mechanical
  Notes: recommend one root (App\ or SmartResponsor\Analytics\) and migrate consistently

- SK9-B3 namespace alignment for Controller layer
  Goal: align Controller namespaces with folder and composer autoload
  Slice: BUCKET
  Limits: files_max=16 loc_max=1200
  Paths: src/Controller/** src/ControllerInterface/**
  Outputs: namespace fixes, use/import fixes, updated references
  Acceptance Criteria: php -l and phpstan green for these files

- SK9-B4 namespace alignment for Domain + DomainInterface
  Goal: align Domain and its interfaces; remove dead entrypoints
  Slice: MAX-BUCKET
  Limits: files_max=20 loc_max=1500
  Paths: src/Domain/** src/DomainInterface/**
  Outputs: consistent namespaces, interface pairing, remove or relocate src/Domain/Analytics/index.php if not used
  Acceptance Criteria: mirror policy holds, no InterfaceInterface, phpstan green

- SK9-B5 enforce mirror ServiceInterface for missing services
  Goal: add interfaces for 7 Service classes and adjust wiring
  Slice: BUCKET
  Limits: files_max=16 loc_max=1200
  Paths: src/Service/** src/ServiceInterface/**
  Outputs: 7 new interfaces + minimal DI adjustments
  Acceptance Criteria: mirror_missing_total becomes 0

- SK9-C0 SourceDomain migration (conditional)
  Goal: relocate non-canonical SourceDomain classes into proper layers
  Slice: BUCKET (repeatable)
  Limits: files_max=16 loc_max=1200
  Inputs: scan report + mapping
  Paths: SourceDomain/** src/SourceDomain/**
  Outputs: moved files, updated namespaces, mirror interfaces where required
  Acceptance Criteria: source_domain_candidate_total becomes 0
  Notes: current snapshot has 0 SourceDomain files; enable when it appears
