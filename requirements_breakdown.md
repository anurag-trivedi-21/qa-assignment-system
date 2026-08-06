# Take-Home Requirements Breakdown

## What is this project?

This is a **Testing Queue Manager**. Think of it like a task assignment system where:
- **Testers** are employees who test products/subjects.
- **Managers** can oversee and control testers.
- **Pending Tests** are tasks sitting in a queue waiting to be done.
- **Test Subjects** are the 55 things that can be tested.

The existing code already has the basic queue table and a "Submit Test Result" feature. **You need to build the clock-in/out system and the auto-assignment engine on top of it.**

---

## Existing Database (What's Already There)

| Table | Purpose |
|---|---|
| `users` | All users. Has `is_tester` and `is_enabled` flags. |
| `test_subjects` | Catalog of 55 things to test. Has `test_value` (importance score). |
| `pending_tests` | The work queue. `tester_id` and `claimed_at` are null when unclaimed. |
| `test_results` | Completed tests. Has `tested_at` timestamp. |

> [!IMPORTANT]
> The existing schema has **no clock-in/out state at all**. You must design and add this yourself via new migrations.

---

## Requirement 1 — Clock-In / Clock-Out System

### What it means in simple words:
A tester must "start their shift" (clock in) before they can be assigned any work or see the queue. They can also "end their shift" (clock out). A manager can do this for any tester instantly.

### What needs to be built:
- **New columns on `users` table** (via a migration):
  - `clocked_in_at` — timestamp (null = clocked out)
  - `last_test_submitted_at` — timestamp (used for the 3-hour timeout below)
- **A `ClockService`** (or equivalent) with `clockIn(User)` and `clockOut(User)` methods.
- **A Filament Action** on the Users admin page so managers can click a button to clock any tester in or out immediately.

### Alternate options:
- Instead of columns on `users`, you could create a separate `tester_shifts` table with `user_id`, `clocked_in_at`, and `clocked_out_at`. This gives you full history but is more complex.
- An Artisan command (`php artisan tester:clock-in {userId}`) is also acceptable per the spec, but a UI button is friendlier.

---

## Requirement 2 — Auto Clock-Out After 3 Hours of Inactivity

### What it means in simple words:
If a tester has been clocked in but hasn't submitted any test for 3 hours, the system should automatically clock them out. The 3-hour limit must be changeable in config (not hardcoded).

### What needs to be built:
- A **scheduled Laravel command** (cron job, e.g. runs every 5 minutes) that:
  1. Finds all clocked-in testers.
  2. Checks if their last test submission (`test_results.tested_at`) or clock-in time is older than the configurable timeout.
  3. Clocks them out and returns their auto-assigned test to the queue (see Req 5).
- A **config value** in `config/queue.php` or a new `config/testing.php`:
  ```php
  'inactivity_timeout_hours' => env('TESTER_INACTIVITY_HOURS', 3),
  ```

### Alternate options:
- A per-tester queued job (a `CheckTesterInactivity` job dispatched at clock-in, scheduled to fire after 3 hours). This is more precise but harder to cancel if the tester manually clocks out. A scheduled command that polls periodically is simpler and safer.

---

## Requirement 3 — Automatic Assignment (The Core Engine)

### What it means in simple words:
15 minutes after a tester clocks in, if they don't already have an assigned test, the system should automatically pick one from the queue and assign it to them. This repeats every 15 minutes as long as they have nothing assigned. Don't give the same test to the same tester over and over — rotate fairly.

### What needs to be built:
- A **`AutoAssignJob`** (queued job) or **scheduled Artisan command** that runs every 15 minutes.
- The job must:
  1. Find all clocked-in testers who clocked in **at least 15 minutes ago** AND have **no currently active auto-assigned test**.
  2. For each eligible tester, pick the best unclaimed `pending_test` using a **priority ranking** (see Req 4).
  3. Assign it using a **database lock** to prevent two simultaneous job runs from assigning the same test twice.
- **New column on `pending_tests`**: `is_auto_assigned` (boolean, default false) — so we can tell if a test was assigned by the system vs. manually claimed by the tester.

### Alternate options:
- Global scheduled command (one job assigns everyone at once) vs. per-tester jobs (one job per tester). The spec says either is fine. A single global command is simpler and avoids duplicate job management.
- Instead of `is_auto_assigned`, you could use a separate `tester_assignments` table, but a boolean column is much simpler.

---

## Requirement 4 — Priority Ranking for Assignment

### What it means in simple words:
When picking which test to assign, you need a smart ordering. The spec leaves this to you, but you must explain your choice.

### Proposed ranking (highest priority first):
1. **Highest `impact_count`** — The test with the widest impact gets done first.
2. **Highest `test_value`** from `test_subjects` — More important subjects get priority.
3. **Oldest `created_at`** — Break ties by age (oldest waiting first = FIFO fairness).

This is a **weighted urgency** approach: impact × value, then FIFO as tiebreaker.

### Trade-offs:
- A pure FIFO (oldest first) is simpler but ignores importance.
- A pure value-based sort might starve low-value tests forever.
- The combined approach balances urgency with fairness.

---

## Requirement 5 — Fair Rotation Across Testers

### What it means in simple words:
Don't keep assigning tests to the same tester. If Tester A and Tester B are both free, alternate between them.

### What needs to be built:
- When selecting which tester gets the next assignment, sort eligible testers by who has received the **fewest auto-assignments** in the current shift, or by who was **clocked in longest ago**.
- The simplest approach: order testers by `clocked_in_at` ascending (longest waiting gets priority). This is a round-robin by shift start time.

### Alternate options:
- Track a `total_auto_assigned_count` on `users` and pick whoever has the lowest count. More accurate but requires another column.
- Track `last_auto_assigned_at` and always pick whoever was assigned least recently.

---

## Requirement 6 — At Most One Auto-Assigned Test Per Tester

### What it means in simple words:
The system can only automatically assign ONE test to a tester at a time. But the tester can still manually grab additional tests on their own.

### What needs to be built:
- Before assigning, the job checks: does this tester have any `pending_tests` row where `tester_id = tester.id` AND `is_auto_assigned = true`?
- If yes → skip them.
- The check + assignment must happen inside a **database transaction with a lock** (e.g., `lockForUpdate()`) to be idempotent even if the job runs twice simultaneously.

---

## Requirement 7 — Don't Overwrite a Manual Claim

### What it means in simple words:
If a tester has already manually grabbed a test for themselves, the auto-assign job must leave them alone (not assign them another one on top of it).

### What needs to be built:
- The eligibility check in the job: a tester is eligible for auto-assignment only if `pending_tests` has **zero** rows where `tester_id = tester.id`. This covers both manual and automatic claims.

---

## Requirement 8 — Return Auto-Assigned Tests to Queue on Clock-Out

### What it means in simple words:
If a tester clocks out (manually or automatically), any test the **system** assigned to them goes back to the unclaimed pool. But if they had **manually claimed** a test themselves, that stays assigned to them (it's their responsibility).

### What needs to be built:
- The `clockOut(User)` method in `ClockService` must:
  - Find all `pending_tests` where `tester_id = user.id` AND `is_auto_assigned = true`.
  - Set `tester_id = null`, `claimed_at = null`, `is_auto_assigned = false` on those rows.
  - Leave any manually claimed tests untouched.

---

## Requirement 9 — "New Subject" Grace Period on Clock-Out

### What it means in simple words:
There's a special type of test called a `New Subject` test. When a tester is clocked out, instead of immediately returning this test to the queue, give it a longer grace period (between 3–6 hours configurable). After that time, if still uncompleted, return it.

### What needs to be built:
- A new **config value**: `'new_subject_grace_hours' => env('NEW_SUBJECT_GRACE_HOURS', 4)`.
- A new **column on `pending_tests`**: `return_to_queue_at` (nullable timestamp).
- When clocking out a tester who has an auto-assigned `New Subject` test, instead of clearing it immediately, set `return_to_queue_at = now() + grace_period`.
- The scheduled command (from Req 2) also checks for rows where `return_to_queue_at <= now()` and clears those assignments.

---

## Requirement 10 — Repeat-Testing Cooldown (Guardrail)

### What it means in simple words:
If a tester has already tested a specific subject in the last 7 days, don't auto-assign that same subject to them again, unless there are absolutely no other options.

### What needs to be built:
- In the assignment query, add a `WHERE NOT EXISTS` condition that checks `test_results` for the tester + subject combination within the last X days.
- The cooldown period comes from config: `'repeat_test_cooldown_days' => env('REPEAT_TEST_COOLDOWN_DAYS', 7)`.
- Use `test_results.tested_at` (not `created_at`) for this check.
- The "unless no eligible alternative exists" part: if ALL remaining pending tests would violate the cooldown for ALL testers, fall back and allow it anyway.

---

## Requirement 11 — Pest Test Coverage

### What it means in simple words:
Write automated tests for every new feature using Pest (the testing framework already in place). Don't break existing tests. Fix any failing tests in the existing suite before adding new ones.

### What needs to be built (tests for):
- `ClockService::clockIn()` and `clockOut()` behavior.
- Auto clock-out timeout logic.
- Assignment: eligible tester gets assigned, ineligible (< 15 min) does not.
- Fairness rotation: tester with fewer assignments gets priority.
- Return-to-queue on clock-out (auto vs. manual distinction).
- New Subject grace period.
- Repeat-testing cooldown guardrail.
- Idempotency: running the assignment job twice doesn't double-assign.

