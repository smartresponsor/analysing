Marketing America Corp. Oleksandr Tishchenko

Analytics layer map scanner

Purpose
- Build a deterministic inventory of PHP symbols and their current layer placement.
- Detect duplicates by file basename.
- Detect non-canonical "SourceDomain" placement and suggest a target layer/path.
- Detect InterfaceInterface anomalies.
- Detect missing mirror interface files for Domain/Service/Repository/Controller.

Usage
- From repo root:
  - php tools/analytics-scan-layer-map.php
- Custom root/out:
  - php tools/analytics-scan-layer-map.php --root=/path/to/repo --out=/path/to/out

Outputs
- report/analytics-layer-map.json
  - One entry per PHP file.
  - Includes namespace, symbol, fqcn, detected layer, mirror check, and a relocation guess for SourceDomain files.
- report/analytics-duplicate-basename.csv
  - Basename duplicates that can cause ambiguity during refactors.
- report/analytics-scan-summary.json
  - Aggregated counters.

Heuristics
- A file is treated as "SourceDomain" if its relative path starts with:
  - SourceDomain/
  - src/SourceDomain/
- Target relocation guess is based on suffix rules:
  - *Controller.php -> Controller
  - *Service.php -> Service
  - *Repository.php -> Repository
  - *Entity.php -> Entity
  - *Command.php -> Command
  - Otherwise -> Domain

Notes
- The scanner ignores vendor/, .git/, and common runtime dirs.
- The scanner is dry-run by design: it does not change files.
- SourceDomain is not a canonical layer. The intended workflow is:
  1) scan -> report
  2) decide target namespace strategy
  3) migrate by small envelopes (<=16 files per bucket)
