SourceDomain policy (Analytics)

- SourceDomain is not canonical.
- Any file found under SourceDomain must be relocated into the canonical layer-first structure.
- Use tools/analytics-source-domain-migrate.php to print a relocation plan (dry-run).
- Use --apply only when you are ready to execute moves, then follow with namespace normalization + tests.
