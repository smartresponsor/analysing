Analytics namespace target (sketch11)

Target prefix
- SmartResponsor\\Analytics\\

Rules
- All production classes under src/ SHOULD converge to SmartResponsor\\Analytics\\...
- Temporary legacy roots are allowed during migration:
  - App\Analysing\\...
  - Analytics\\...
- Banned prefixes (must be removed early):
  - SmartResponsor\\Http\\...
  - Analytics\\Bootstrap\\...

Migration order (recommended)
1) Controller + ControllerInterface
2) Service + ServiceInterface
3) Domain + DomainInterface
4) Command
5) Entity/ValueObject/DTO

Guard
- composer run guard:namespace  (report-only, non-blocking)
- enforce mode is used only after migration reaches 0 banned + 0 unknown roots.
