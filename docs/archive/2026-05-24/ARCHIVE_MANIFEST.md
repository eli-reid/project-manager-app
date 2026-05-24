# Archive Manifest - 2026-05-24

## Why these files were archived
These files were moved out of active `docs/completed-features` because they are no longer aligned with the current architecture baseline in `docs/development/CONSOLIDATED_ARCHITECTURE_SPEC.md`.

Primary reason:
- Active design decision treats security verification as in-progress until pending fuzzing and endpoint checks are closed.
- The archived files present security and cross-session testing as fully complete, which conflicts with the current baseline.

## Moved files
- `docs/completed-features/PHASE_3_PERMISSIONS_TESTING_SESSION_SUMMARY.md`
- `docs/completed-features/SESSION_4_FINAL_SECURITY_REPORT.md`
- `docs/completed-features/SESSION_4_SUMMARY.md`
- `docs/completed-features/SESSION_4_TESTING_RESULTS.md`
- `docs/completed-features/SESSIONS_4_5_COMPLETE_SECURITY_SUMMARY.md`
- `docs/completed-features/SESSIONS_4_6_COMPLETE_TESTING_SUMMARY.md`
- `docs/completed-features/SESSION_5_INTEGRATION_TESTING.md`
- `docs/completed-features/SESSION_6_PERFORMANCE_TESTING.md`
- `docs/completed-features/PERFORMANCE_TESTING_NOTES.md`
- `docs/completed-features/UI_END_TO_END_TEST_COVERAGE_COMPLETE.md`

## New locations
All moved to:
- `docs/archive/2026-05-24/completed-features/`

## Kept active on purpose
These were left in active docs because they still support the current in-progress baseline and/or architecture decisions:
- `docs/development/CONSOLIDATED_ARCHITECTURE_SPEC.md`
- `docs/development/ARCHITECTURE_ALIGNMENT_CHECKLIST.md`
- `docs/completed-features/PERMISSIONS_AND_SECURITY_FUZZING_REPORT.md`
- `docs/completed-features/PERMISSIONS_FUZZING_TEST_PLAN.md`
- `docs/completed-features/BOOT_ORDER_HARDENING.md`
- `docs/completed-features/CORE_MIGRATION_MATRIX.md`

## Re-activation rule
If a future test run closes the pending security matrix and this is verified in code + test artifacts, these archived docs may be restored or replaced by a new canonical security report.
