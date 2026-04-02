---
name: Test Failures Baseline
description: Known test failures as of 2026-03-29 that are pre-existing and unrelated to lottery logic
type: project
---

Two pre-existing test failures (unrelated to lottery draw/animation code):

1. `Tests\Feature\Auth\AuthenticationTest::test_login_screen_can_be_rendered` -- expects 200 but gets 302. Likely because login route now redirects (reCAPTCHA integration changed the flow).

2. `Tests\Feature\ExampleTest::test_the_application_returns_a_successful_response` -- expects 204 but gets 200. The root route `/` was changed to return a landing page instead of 204 No Content.

**Why:** These are scaffold tests that were not updated after route changes. They do not affect lottery functionality.

**How to apply:** When running `php artisan test`, these 2 failures are expected baseline. Any NEW failures beyond these indicate a regression.
