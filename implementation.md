# Implementation Summary

## Completed so far

### Requirement 1 — Clock-In / Clock-Out System
- Added a dedicated shift table instead of storing clock state directly on the users table.
- Created a `TesterShift` model to represent each shift record.
- Added a `ClockService` with `clockIn()` and `clockOut()` methods.
- Extended the user model with a relationship to shifts and a helper to check whether the user is currently clocked in.
- Added Filament user actions to clock testers in and out from the admin UI.

### Requirement 2 — Auto Clock-Out After Inactivity
- Added a dedicated inactivity command that checks active shifts against the configured timeout from the environment.
- The command closes stale shifts so testers are clocked out automatically after inactivity.
- Added a migration to track the last test submission timestamp for inactivity evaluation.

### Requirement 3 — Automatic Assignment Engine
- Added a global auto-assignment command that assigns unclaimed pending tests to eligible testers.
- The command uses the shift-based clock-in state and a single-pass assignment flow for the global scheduled approach.
- Added a migration to mark pending tests as auto-assigned.

### Requirement 4 — Priority Ranking for Assignment
- Added priority ordering to the auto-assignment command so pending tests are sorted by impact × test value first, then by impact count, and finally by age.
- This gives the queue a weighted urgency approach that balances business importance with fairness.
- Added a dedicated test to verify that higher-priority pending tests are assigned before lower-priority ones.

### Requirement 5 — Fair Rotation Across Testers
- Updated the auto-assignment flow to order eligible testers by how long they have been clocked in, so older shifts receive assignment priority in a simple round-robin style.
- Added coverage for this behavior in the auto-assignment feature tests.

### Requirement 6 — One Auto-Assigned Test Per Tester
- The auto-assignment eligibility check now excludes testers who already have any assigned pending test, preventing a second auto-assignment from being created.
- Added coverage to verify that a tester with an existing auto-assigned task is skipped.

### Requirement 7 — Do Not Overwrite a Manual Claim
- The auto-assignment flow now treats any existing assignment as a blocker for auto-assigning another test, so manual claims are preserved.
- Added feature coverage confirming that a manual claim is not overwritten by the auto-assignment command.

### Requirement 8 — Return Auto-Assigned Tests on Clock-Out
- The clock-out flow now returns any pending test that was auto-assigned to the tester back to the queue by clearing the assignment and claimed state.
- Manual claims remain intact, so only system-assigned work is reopened on clock-out.
- Added a regression test to verify the distinction between auto-assigned and manually claimed rows.

### Requirement 9 — New Subject Grace Period
- Added a configurable grace period for auto-assigned New Subject tests via the new testing config file.
- When a tester clocks out, those tests are deferred with a return-to-queue timestamp instead of being immediately returned.
- The inactivity command now releases any deferred New Subject tests once their grace period has expired.
- Added regression coverage for the delayed-return behavior.

### Requirement 10 — Repeat-Testing Cooldown
- Added a repeat-test cooldown guardrail so auto-assignment skips subjects the tester has tested recently within the configured window.
- The cooldown window is configurable via the testing config file and defaults to 7 days.
- Added regression coverage to verify that cooled-down subjects are not auto-assigned to the same tester.

### Requirement 11 — Pest Test Coverage
- Added feature coverage for the clock service, inactivity handling, and auto-assignment behavior.
- The assignment suite now covers core queue behavior including eligibility, fairness, one-assignment-per-tester, manual-claim protection, and repeat-test cooldown.

## Fixes from review
- **Test database isolation**: `tests/TestCase.php` now forces `database.default` to `sqlite` with an in-memory database right after the application boots, so the suite can never touch the shared MySQL `wand` database used by local dev/Filament (the container's baked-in `DB_CONNECTION=mysql` env var otherwise wins over `phpunit.xml` and `.env.testing` overrides).
- **Inactivity timing bug**: `TestSubmissionService::submit()` now records `last_test_submitted_at` on the tester's active shift, and `AutoClockOutInactiveTesters` was corrected so the timeout is measured from the last submission when one exists, and only falls back to `clocked_in_at` when the tester hasn't submitted anything yet (previously it used clock-in time regardless of recent activity).
- **Concurrency safety**: `AutoAssignTests` no longer relies on MySQL-only `GET_LOCK`/`RELEASE_LOCK`. It now wraps each candidate assignment in a transaction that re-checks the pending test is still unclaimed and the tester still has no assignment under `lockForUpdate()`, then performs a `whereNull('tester_id')`-guarded update so two concurrent runs can never both claim the same test.
- **Repeat-testing cooldown fallback**: added the "unless no eligible alternative exists" escape valve — if every tester/test pairing in the current batch would violate the cooldown, the run ignores the cooldown entirely rather than leaving every tester without work.
- **`is_enabled` guard**: the auto-assignment eligibility filter now excludes disabled testers, matching the domain rule that `is_enabled` controls participation.
- **Manual "Run Assignment Now" action**: added a Filament header action on the Users table that triggers `testers:auto-assign` on demand, in addition to the existing scheduled command.
- Added regression tests for all of the above: submission timestamp propagation, inactivity respecting recent activity, disabled-tester exclusion, cooldown fallback, and running the assignment command twice back-to-back without double-assigning.

## Requirement 1 follow-up — self-service clock in/out and access control
- Added a "Clock In" header action on the Filament dashboard, visible only to testers who aren't currently clocked in (`app/Filament/Pages/Dashboard.php`).
- Added a globally floating "Clock Out" button rendered on every panel page via a `panels::body.end` render hook and a small Livewire component (`app/Livewire/FloatingClockOutButton.php`), visible only to clocked-in testers.
- `TestSubmissionService::submit()` now throws `TesterNotClockedInException` if the tester isn't clocked in, enforcing "no clocked-out tester can take/submit a test" as a domain rule rather than just a UI convention. The Filament "Submit Test Result" action catches this and shows a friendly notification instead of a raw error.
- The tester `Select` in both the "Submit Test Result" action and the raw `PendingTest` create/edit form now only list currently clocked-in testers, so the invalid state can't even be selected in the UI.
- Testers can no longer view the Users resource: added `App\Policies\UserPolicy::viewAny()` restricting it to non-testers (managers), which Filament uses for both hiding the nav item and blocking direct URL access (403).
- Added regression tests: dashboard renders correctly for clocked-in/out testers and managers, the submission guard rejects a not-clocked-in tester, and the Users resource returns 403 for testers / 200 for managers.

### Fix: floating clock-out button not appearing after clocking in
- Root cause: the button was rendered via a one-off `view(...)->render()` call in the panel's render hook — a static Blade snippet evaluated once per full page load, not a real Livewire component. Clicking "Clock In" on the dashboard only re-renders the dashboard's own Livewire component via AJAX, so the separately-rendered static button never got a chance to re-evaluate and stayed hidden.
- Fix: the button is now mounted as a genuine Livewire component (`<livewire:floating-clock-out-button />`), and both it and the Dashboard page dispatch/listen for a shared `tester-shift-changed` event so each refreshes independently the moment the other changes the tester's clock state, without a full page reload.
- Also fixed a related bug this surfaced: the button's Blade view had no permanent root element, so when the condition became false (right after clocking out) Livewire's component render threw `RootTagMissingFromViewException`. Wrapped the conditional content in a persistent outer `<div>`.
- Added regression tests verifying the button appears/disappears on a fresh page load, and that both the dashboard's clock-in action and the floating button's clock-out action dispatch the `tester-shift-changed` event.

## Files added or updated
- [database/migrations/2026_08_06_000300_create_tester_shifts_table.php](database/migrations/2026_08_06_000300_create_tester_shifts_table.php)
- [app/Models/TesterShift.php](app/Models/TesterShift.php)
- [app/Services/ClockService.php](app/Services/ClockService.php)
- [app/Models/User.php](app/Models/User.php)
- [app/Filament/Resources/Users/UserResource.php](app/Filament/Resources/Users/UserResource.php)
- [app/Filament/Resources/Users/Tables/UsersTable.php](app/Filament/Resources/Users/Tables/UsersTable.php)
- [app/Filament/Resources/Users/Pages/ListUsers.php](app/Filament/Resources/Users/Pages/ListUsers.php)
- [tests/Feature/ClockServiceTest.php](tests/Feature/ClockServiceTest.php)
- [tests/Feature/InactivityTimeoutTest.php](tests/Feature/InactivityTimeoutTest.php)
- [tests/Feature/AutoAssignmentTest.php](tests/Feature/AutoAssignmentTest.php)
- [database/migrations/2026_08_06_000301_add_last_test_submitted_at_to_tester_shifts_table.php](database/migrations/2026_08_06_000301_add_last_test_submitted_at_to_tester_shifts_table.php)
- [database/migrations/2026_08_06_000302_add_is_auto_assigned_to_pending_tests_table.php](database/migrations/2026_08_06_000302_add_is_auto_assigned_to_pending_tests_table.php)
- [app/Console/Commands/AutoClockOutInactiveTesters.php](app/Console/Commands/AutoClockOutInactiveTesters.php)
- [app/Console/Commands/AutoAssignTests.php](app/Console/Commands/AutoAssignTests.php)
- [app/Providers/AppServiceProvider.php](app/Providers/AppServiceProvider.php)
- [tests/TestCase.php](tests/TestCase.php)
- [phpunit.xml](phpunit.xml)
- [app/Services/TestSubmissionService.php](app/Services/TestSubmissionService.php)
- [tests/Feature/TestSubmissionTest.php](tests/Feature/TestSubmissionTest.php)
- [tests/Feature/AutoAssignmentPriorityTest.php](tests/Feature/AutoAssignmentPriorityTest.php)
