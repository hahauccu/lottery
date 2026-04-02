---
name: Pint Style Failures Baseline
description: Known Pint code style failures as of 2026-03-29 that pre-date lottery inspection
type: project
---

6 files fail `./vendor/bin/pint --test`:

1. `AdminPanelProvider.php` -- concat_space, ordered_imports
2. `LoginRequest.php` -- unary_operator_spaces, not_operator_with_successor_space, ordered_imports
3. `RegisterAccountController.php` -- ordered_imports
4. `RegisteredUserController.php` -- ordered_imports
5. `DemoLotteryController.php` -- not_operator_with_successor_space
6. `config/ecpay.php` -- concat_space

**Why:** These are cosmetic style issues unrelated to the lottery draw pipeline. None are in the critical path files (LotteryDrawService, EligibleEmployeesService, LotteryFrontendController, Events).

**How to apply:** When verifying Pint compliance, these 6 files are the expected baseline. New violations beyond these indicate regressions from recent changes.
