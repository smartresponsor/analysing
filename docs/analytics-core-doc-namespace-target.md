Historical consumer/domain namespace target (sketch11)

Target prefix
- Historical consumer/domain identity namespace: SmartResponsor\\Analytics\\

Rules
- Historical consumer/domain migration expected production classes under src/ to converge to SmartResponsor\\Analytics\\...
- Temporary legacy roots are allowed during migration:
  - App\Analysing\\...
  - Analytics\\...
- Banned prefixes (must be removed early):
  - Historical consumer/domain namespace: SmartResponsor\\Http\\...
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
