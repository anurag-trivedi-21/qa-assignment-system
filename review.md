# Project Review — Testing Queue Take-Home

Reviewed against [TAKEHOME.md](TAKEHOME.md) and the current state of the codebase (migrations, models, services, commands, Filament resources, tests, config, seeder).

> **Update:** All issues identified below have since been fixed and verified — see the "Fixes from review" section in [implementation.md](implementation.md) for what changed. This document is kept as-is as the original findings record.

## Overall

Core behavior for all 11 requirement areas has automated coverage and the full Pest suite passes (12 tests, 37 assertions) as of this review. However, a few things are missing, implemented incompletely, or risky enough to call out before submission.

---

## 🔴 Issues found (should fix)

### 1. Test suite runs against the same MySQL database used for local dev (`wand`)
- `compose.yaml` sets `DB_CONNECTION=mysql`, `DB_DATABASE=wand` as real container environment variables.
- `phpunit.xml` only overrides `DB_DATABASE` to `:memory:` (a leftover default from Laravel's sqlite template) and never sets `DB_CONNECTION`, so it has no effect — confirmed via a live probe (`config('database.default')` = `mysql`, `DB::connection()->getDatabaseName()` = `wand`).
- Since tests use `RefreshDatabase`, **every `php artisan test` / `pest` run drops and reseeds the actual `wand` database** — the same one the admin panel and manual QA use. This already happened during this session and the seed had to be restored afterward.
- Fix direction: point testing at a dedicated database (e.g. `wand_test`, or a true sqlite `:memory:` connection) so running the suite never touches dev data.

### 2. `last_test_submitted_at` is never populated
- The inactivity requirement is: measure the 3-hour timeout from clock-in *unless* the tester has submitted a test, in which case measure from that submission.
- `TesterShift` has a `last_test_submitted_at` column and `AutoClockOutInactiveTesters` checks it, but nothing ever writes to it — `TestSubmissionService::submit()` only creates a `TestResult` and deletes the `PendingTest`; it never touches the tester's active shift.
- Net effect: the timeout **always** measures from `clocked_in_at`, regardless of activity. A tester who submitted a test 5 minutes ago is treated identically to one who has submitted nothing all shift. This is a real gap against the spec, not just an edge case.

### 3. Auto-assignment update isn't safe against two concurrent runs assigning the same test
- The command fetches the full candidate `pendingTests` list once at the start, then in the per-tester loop does:
  ```php
  PendingTest::query()->whereKey($test->getKey())->update([...]);
  ```
  with no `whereNull('tester_id')` guard and no check of the affected row count.
- The `GET_LOCK`/`RELEASE_LOCK` pair is scoped to `assign:{tester->id}`, which only prevents the **same tester** from being double-assigned concurrently. It does **not** prevent two concurrent runs from both selecting the same unclaimed `PendingTest` for two *different* testers — the second `update()` would silently overwrite the first tester's claim.
- The take-home explicitly calls out idempotency/safety under concurrent runs as something to get right and explain. Currently the write-up would be describing a guarantee the code doesn't fully provide.
- Fix direction: scope the update with `->whereNull('tester_id')` and check `update()`'s return value; if 0 rows affected, move to the next candidate for that tester.

### 4. Repeat-testing cooldown fallback ("unless no eligible alternative exists") isn't implemented
- Current behavior: if every remaining pending test is cooldown-blocked for a given tester, that tester is simply skipped (`continue`) for this run.
- The spec's fallback nuance — allow a cooled-down subject to be assigned anyway when there is genuinely no eligible alternative anywhere in the queue — isn't implemented at all. Right now a cooldown-blocked tester can go without work indefinitely even if nothing else is claimable.

### 5. No `is_enabled` filter in the auto-assignment eligibility check
- `AutoAssignTests` filters eligible testers by `is_tester` only, not `is_enabled`.
- The domain guide states `is_enabled` "controls whether an account may participate at all." A disabled tester who has a lingering open shift (e.g., disabled mid-shift) could still receive an auto-assignment. Worth an explicit guard even though the seeded scenario doesn't currently trigger it.

---

## 🟡 Smaller gaps / things that could be better

- **No Filament "Run Assignment Now" action.** The take-home asks for "a Filament action or another practical way to run the assignment manually." The `testers:auto-assign` Artisan command satisfies "another practical way," but a button next to the Clock In/Out actions on the Users table would be a more discoverable, manager-friendly interface and match the spirit of the other Filament actions already built.
- **`AutoClockOutInactiveTesters` doesn't filter by `is_tester`/`is_enabled`** when scanning open shifts — currently harmless since only testers get shifts, but it's an implicit assumption rather than an explicit guard.
- **No test for double-run idempotency of `testers:auto-assign`.** Given issue #3 above, there's no regression test that actually calls `$this->artisan('testers:auto-assign')` twice (or simulates a race) to prove a test isn't double-assigned. Existing tests cover single-run correctness only.
- **No test for the `is_enabled` exclusion** in auto-assignment (follows from gap #5 — there's nothing to test since the filter doesn't exist yet).
- **Manual "Run Assignment Now" and cooldown fallback aren't covered by any write-up section** in [implementation.md](implementation.md) beyond what's implemented — worth expanding the write-up once (or if) the fallback behavior is added, since the take-home explicitly asks for the ranking/trade-off explanation.
- **Migration naming/dates are in the future (2026)** — consistent with the rest of the repo's existing migrations, so not a real problem, just noting it's intentional/pre-existing style rather than an error.

---

## ✅ What's implemented correctly

- Dedicated `tester_shifts` table (rather than columns on `users`) giving full shift history — a deliberate, reasonable design choice.
- `ClockService::clockIn()/clockOut()` with Filament actions for managers to act immediately (Requirement: manager action takes effect immediately, no wait for scheduled job) — correct, it's a direct synchronous update.
- Inactivity timeout is configurable via `config('testing.inactivity_timeout_hours')` / `TESTER_INACTIVITY_HOURS` env var.
- 15-minute eligibility delay and every-15-minutes global scheduled command — matches the explicitly-allowed "global scheduled task" approach.
- Priority ranking (impact × test_value, then impact, then oldest `created_at`) is implemented and covered by a dedicated test, with rationale captured in `implementation.md`.
- Fairness rotation by longest-clocked-in-first is implemented and tested.
- One-auto-assignment-per-tester and manual-claim protection share a single "any existing assignment blocks new auto-assignment" check — correct per spec, and both are tested.
- Clock-out returns auto-assigned tests to the queue while preserving manual claims — implemented and tested.
- "New Subject" grace period is configurable, deferred via `return_to_queue_at`, and released by the inactivity command once expired — implemented and tested.
- Repeat-testing cooldown (base case, not the fallback) is implemented using `test_results.tested_at` as required, and is configurable.
- Full Pest suite is green (12 passed, 37 assertions) at time of review.

---

## Summary of recommended next steps, in priority order
1. Fix the test database isolation issue (highest risk — currently unsafe to run tests without wiping dev data).
2. Populate `last_test_submitted_at` on test submission so the inactivity timeout actually reflects activity.
3. Harden the auto-assignment update against concurrent double-claiming (`whereNull('tester_id')` + affected-row check).
4. Add the cooldown fallback ("assign anyway if no eligible alternative exists").
5. Add an `is_enabled` guard to the assignment eligibility filter.
6. Optional polish: a Filament "Run Assignment Now" action, plus a regression test that runs the assignment command twice to prove idempotency.
