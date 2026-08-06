# Testing Queue — take-home

This application is a small internal testing queue used to coordinate work across a flexible group of testers. Managers need a dependable way to understand who is available and distribute pending tests without repeatedly assigning the same people.

Build a clock-in/out system and automatically assign pending tests fairly. The assignment feature must meet the following requirements.

## Clocking in and queue access

- A tester must clock in before they can access the testing queue or receive automatic assignments.
- Testers may clock out explicitly.
- A manager must be able to clock a tester in or out on that tester's behalf. The change must take effect immediately when the manager invokes the action; it must not wait for the scheduled assignment process.
- Automatically clock out a tester after three hours without a submitted test. For a tester who has not submitted a test during the current shift, measure the three-hour timeout from clock-in.
- The timeout must be configurable.

## Automatic assignment

- First consider a tester for automatic assignment 15 minutes after they clock in, if they do not already have an active claimed test.
- Continue considering eligible testers every 15 minutes while they have no active claimed test. A global scheduled task that runs every 15 minutes or a per-tester elapsed-time approach is acceptable, provided a tester is never assigned before their 15-minute delay has elapsed.
- Only assign unclaimed pending tests.
- Rotate assignments fairly across clocked-in testers; do not repeatedly select the same tester when comparable alternatives are available.
- A tester should hold at most one automatically assigned test at a time. They should still be able to claim work themselves.
- Do not overwrite a manual claim. The job must be idempotent and safe if it runs twice concurrently.
- How pending tests are prioritized (for example, by impact, test value, age, or reason) is your design decision. Explain your ranking and trade-offs in the write-up.

## Inactivity and reassignment

- When a tester clocks out or is automatically clocked out, return their automatically assigned test to the queue.
- Do not immediately unassign an automatically assigned `New Subject` test: use a configurable longer timeout, with any default between 3 and 6 hours.
- Preserve manually claimed work when a tester clocks out or is automatically clocked out.

## Repeat-testing guardrail

- Do not automatically assign a subject to a tester who has tested that same subject in the last *X* days (use a configurable cooldown, such as 7 days), unless no eligible alternative exists.
- Use `test_results.tested_at`, rather than `created_at`, for this rule and for detecting recent test submissions.

## Quality

- Start by running the existing test suite and investigate any failures.
- Keep the suite green throughout the work and add automated Pest coverage for all new behavior.
- Treat existing assertions as the contract: fix the application behavior rather than weakening or deleting assertions. Different persistence designs are acceptable when they preserve the required behavior.
- Use the **Submit Test Result** action on any pending-test row to exercise the existing workflow. Select the tester, outcome, and optional notes.

## What to submit

- migration(s), implementation, and automated Pest tests for every new behavior;
- the clock-in/out flow, a queued job or scheduled Artisan command, and a practical way to invoke or observe them;
- a brief description of your ranking rules and trade-offs;
- a Filament action or another practical way to run the assignment manually.

## Domain guide

- `test_subjects` contains a local catalog of 55 generic subjects and each subject's base `test_value`. `pending_tests.impact_count` indicates the scale of a request.
- `pending_tests` is the work queue. An item is unclaimed when `tester_id` and `claimed_at` are null. Testers are displayed by `users.username`; all relationships use numeric IDs.
- `users.is_enabled` controls whether an account may participate at all. It is unrelated to a tester's shift state; clocked-in, clocked-out, and timed-out state is part of the feature you will design.
- The seed contains 16 enabled tester accounts, 3 disabled tester accounts, and recent submissions at multiple intervals (15, 45, 90, 165, 195, and 300 minutes ago). These are intended to support current-day shift and timeout scenarios. The intervals are relative to seed time, so re-run `./vendor/bin/sail artisan migrate:fresh --seed` to refresh them after a break.
- The existing schema deliberately has no clock state or distinction between manual and automatic claims. Decide what persistence and indexes the feature needs, and justify that choice.

## Interfaces and concurrency

- A Filament action, Artisan command, or service method with Pest coverage is an acceptable interface for clock-in/out, manual claims, and automatic assignment. We are evaluating the behavior and design, not a specific UI surface.
- Authentication and authorization are out of scope. You do not need to introduce a roles or permissions system to distinguish managers from testers.
- Explain your locking and idempotency strategy in the write-up. You do not need to prove real concurrent writes in SQLite tests.

## Logistics

- We expect this exercise to take roughly 3–4 hours. Do not over-invest in polish or unrequested features; if you run out of time, document what you would do next and why.
- Submit a ZIP of the project source through Ashby. Include the application code, migrations, tests, `composer.lock`, `compose.yaml`, documentation, and the `.git` directory — commit as you work; we read the history. Exclude `vendor`, `.env`, `database/database.sqlite`, and generated cache/log files.
- AI tools are permitted. You are expected to understand and be able to discuss every change you submit, including why you agree or disagree with any tool-generated approach.

## Evaluation

We prioritize correct behavior, data integrity, and well-chosen Pest coverage over UI polish. We also evaluate the clarity of your trade-off and locking/idempotency explanations. A simple interface that makes the behavior easy to exercise is sufficient.

## Local setup

```sh
cp .env.example .env # only needed if .env was removed
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate:fresh --seed
```

Open [http://localhost:8091/admin](http://localhost:8091/admin). The seed manager account is `manager@example.test` / `password`.

Run tests with `./vendor/bin/sail pest`. The full suite should pass before submission.
