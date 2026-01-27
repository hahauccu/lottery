# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Language

請使用繁體中文回覆。

## Project Overview

Lottery drawing application for organizations. Multi-tenant architecture where each Organization can have multiple LotteryEvents, each with Prizes and eligible Employees.

**Stack:** Laravel 11 + Filament 3 (admin) + Laravel Reverb (WebSocket) + Blade/Tailwind/Alpine.js (frontend)

## Development Commands

```bash
# Start all services (recommended)
composer dev

# Or start individually:
php artisan serve --host=127.0.0.1 --port=8007  # Backend
npm run dev                                      # Vite (frontend assets)
php artisan reverb:start                         # WebSocket server

# Database
php artisan migrate

# Tests
php artisan test
php artisan test --filter=TestName              # Single test

# Code formatting
./vendor/bin/pint

# Route inspection
php artisan route:list | grep lottery
```

## Architecture

### Multi-Tenancy
- Filament uses `Organization` as tenant (configured in `AdminPanelProvider.php`)
- Users belong to Organizations via pivot table
- All resources (Employees, LotteryEvents, Prizes) scoped to Organization

### Key Data Flow
```
Organization → LotteryEvent → Prize → PrizeWinner → Employee
                    ↓
              EventRule / PrizeRule (eligibility filters)
```

### Route Structure
- Admin panel: `/admin/{tenant}/...`
- Lottery frontend: `/{brandCode}/lottery` (public display)
- Draw action: `POST /{brandCode}/draw`
- Winners list: `/{brandCode}/winners`

### Real-time Updates
Events broadcast via Laravel Reverb:
- `LotteryEventUpdated` - when admin changes event/prize settings
- `PrizeWinnersUpdated` - when winners are drawn

Frontend (`resources/js/lottery.js`) listens via Laravel Echo and updates UI.

## Key Files

| Purpose | Location |
|---------|----------|
| Lottery draw logic | `app/Services/LotteryDrawService.php` |
| Eligibility rules | `app/Services/EligibleEmployeesService.php` |
| Frontend controller | `app/Http/Controllers/LotteryFrontendController.php` |
| Lottery UI logic | `resources/js/lottery.js` |
| Filament resources | `app/Filament/Resources/` |
| Admin panel config | `app/Providers/Filament/AdminPanelProvider.php` |

## Prize Draw Modes

- `all_at_once` - Draw all remaining winners in one action
- `one_by_one` - Draw one winner per action

Animation styles and hold times are configurable per prize.

## Debugging Tips

- Session issues: Ensure consistent host usage (always `127.0.0.1` OR `localhost`, not mixed)
- Real-time not working: Check Reverb is running and `.env` REVERB_* settings match
- Check server: `lsof -nP -iTCP:8007 -sTCP:LISTEN`
