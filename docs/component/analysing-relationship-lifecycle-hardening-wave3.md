# Analysing relationship/lifecycle hardening — wave 3

This pass hardens runtime, projection, evidence, audit, and processing records without turning them into business aggregates.

Added policy:

- `AnalyticsPipelineLifecyclePolicy`

Rules:

- No `*EnGb*` / translation normalization changes in this pass.
- No Attachment / Attaching changes in this pass.
- Runtime/projection statuses stay string-based to avoid schema drift.
- Cross-component references remain boundary references, not Doctrine ORM relations.
