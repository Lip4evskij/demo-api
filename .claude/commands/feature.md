---
description: Implement a feature with a coder → tester → fix loop until all tests pass
argument-hint: <feature description>
---

You are the orchestrator. You do NOT write code or tests yourself — you only direct
the laravel-coder and laravel-tester agents and pass reports between them.

Task: $ARGUMENTS

## Step 0. Specification
Write a short specification (it doubles as the acceptance criteria):
- entities and fields, routes, validation rules, who has access;
- expected behaviour in error cases.
If the task has ambiguities that change behaviour, ask the user and wait for the answer.
Pass this specification VERBATIM to BOTH agents.

## Step 1. Code
Call the laravel-coder agent in IMPLEMENT mode with the specification.
If it returns STATUS: blocked, show the reason to the user and stop.

## Step 2. Tests
Call the laravel-tester agent in WRITE mode with the specification and the coder's report (CHANGED_FILES, PUBLIC_BEHAVIOUR).
Important: the tester writes tests against the SPECIFICATION; the coder's report is only a hint about where things live.

## Step 3. Fix loop (max 3 iterations)
While the tester returns STATUS: red:
1. Call laravel-coder in FIX mode and pass the full FAILURES block from the tester's report.
2. If the coder returns DISPUTED_TESTS, compare each disputed test with the specification yourself:
   - the test contradicts the specification → tell laravel-tester to fix that specific test;
   - the test matches the specification → send it back to the coder: the test is correct, fix the code.
3. Call laravel-tester in RETEST mode with the coder's report (FIXED).
   It re-runs failing tests, adds regression tests and runs the full suite.

If tests are still red after 3 iterations, stop and show the user:
which tests fail, what was tried, and your hypothesis about the cause. Do not loop forever.

If the tester reports MISSING (factory, route, etc.), that is the coder's job — send it to the coder in FIX mode.

## Step 4. Final check
When STATUS is green, run these yourself:
- vendor/bin/pint --test
- vendor/bin/phpstan analyse --memory-limit=1G
- vendor/bin/pest --parallel
If anything fails, go back to Step 3 (this counts as an iteration).

## Summary for the user
Reply in the language the user wrote the task in. Keep it short:
- what was implemented (list of code and test files);
- number of tests and how many fix iterations were needed;
- which bugs the tests caught and how they were fixed;
- anything disputed or requiring a human decision.
