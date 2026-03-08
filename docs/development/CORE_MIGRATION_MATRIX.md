# Core Migration Matrix

## Scope
This matrix maps cross-cutting/platform features from the old `project-manager` app into the `project-manager-app` Core architecture.

## Matrix

| Area | Old file(s) | Target in new app | Status | Priority | Notes |
|---|---|---|---|---|---|
| Settings policy ownership | `app/Providers/AppServiceProvider.php` (settings observer/gates), settings auth checks | `app/Core/Settings/Providers/SettingServiceProvider.php`, `app/Core/Settings/Policies/*` | In progress | P0 | Keep `SettingsSqlite` policy registration in Settings provider. |
| Settings engine | `app/Services/SettingsSqliteService.php`, `app/Models/SettingsSqlite.php`, `app/Observers/SettingsSqliteObserver.php`, `config/settings-db.php`, `config/settings.php` | `app/Core/Settings/Services/*`, `app/Core/Settings/Models/*`, `app/Core/Settings/Observers/*` | Exists | P0 | Already correctly placed in Core. |
| User + RBAC policies/gates | `app/Providers/AuthServiceProvider.php`, `app/Policies/*User*`, `app/Policies/*Role*` | `app/Core/User/Policies/*`, `app/Core/User/Providers/UserServiceProvider.php` | In progress | P0 | Continue moving model policies + permission gates into User core module. |
| Role/permission sync + cache invalidation | `app/Observers/RoleObserver.php`, `app/Observers/PermissionObserver.php` | `app/Core/User/Observers/*` (or `Core/User/Services` event hooks) | Missing/partial | P0 | Needed for production cache correctness after RBAC updates. |
| Password-change enforcement | `app/Http/Middleware/CheckPasswordChange.php`, `EnsurePasswordChanged.php` | `app/Core/User/Middleware/*` + registration in bootstrap | Missing | P0 | High security/UX impact; belongs in User core. |
| User status enforcement | `app/Http/Middleware/RedirectIfNotActive.php` | `app/Core/User/Middleware/RedirectIfNotActive.php` | Missing | P1 | Keeps disabled users blocked consistently. |
| Role preload middleware | `app/Http/Middleware/LoadRoles.php` | `app/Core/User/Middleware/LoadRoles.php` | Missing | P1 | Useful performance/caching pattern from old app. |
| Security headers | `app/Http/Middleware/SecurityHeadersMiddleware.php`, `config/security.php` | `app/Core/Security/Middleware/SecurityHeadersMiddleware.php`, `app/Core/Security/Providers/*`, `config/security.php` | Missing | P0 | Core cross-cutting concern. |
| Suspicious request tracking | `app/Http/Middleware/TrackSuspiciousRequests.php`, `LogAccessRequests.php`, `config/access-log.php` | `app/Core/Security/Middleware/*`, `app/Core/Security/Services/*`, `config/access-log.php` | Missing | P1 | Platform telemetry/audit concern. |
| Bot/honeypot protection | `app/Http/Middleware/BlockBots.php`, `HoneypotProtection.php` | `app/Core/Security/Middleware/*` | Missing | P2 | Good hardening, lower urgency than auth/policy items. |
| Cookie defaults service | `app/Services/CookieService.php`, `app/Http/Middleware/SetCookieDefaults.php`, `config/cookies.php` | `app/Core/Security/Services/CookieService.php`, `app/Core/Security/Middleware/SetCookieDefaults.php` | Partial | P1 | Keep cookie policy centralized in Core Security. |
| CSP service integration | `app/Services/CspService.php`, CSP middleware config | `app/Core/Security/Services/CspService.php` + Spatie CSP config | Partial | P1 | Spatie CSP is preferred; keep custom logic minimal. |
| cPanel integration stack | `app/Services/CpanelService.php`, `CpanelLiveApiClient.php`, `CompanyEmailService.php`, `CpanelEmailManagementService.php` | `app/Core/Cpanel/Services/*` | Exists | P0 | Validate all old features mapped into existing `Core/Cpanel`. |
| Queue/scheduler infra | `app/Services/QueueService.php`, `ScheduledTaskService.php`, `SchedulerService.php`, queue gates in Auth provider | `app/Core/Scheduler/Services/*`, `app/Core/Scheduler/Routes/*`, possibly `Core/Queue/*` | Partial | P1 | Scheduler core exists; queue ops permissions/gates may still need migration. |
| Installation/bootstrap guard | `app/Services/InstallationService.php`, `app/Http/Middleware/CheckInstallation.php`, `routes/install.php` | `app/Core/Installation/*` | Missing | P2 | Needed only if installer mode is still supported. |
| Announcement feed/platform UI hooks | `app/View/Components/AnnouncementFeed.php`, announcement policy/routes | `app/Core/Announcement/*` | Exists | P1 | Confirm old feed features fully carried over. |

## Out Of Scope For Core
These should remain domain modules, not Core:

- Project finance/labor/timecard specifics (`ProjectFinancialService`, `ProjectLaborCostService`, `TimecardService`, etc.)
- Domain-specific observers tied to business workflows (unless extracted as generic infrastructure)

## Suggested Migration Order

1. P0: finalize policy ownership, add RBAC cache observers, implement security headers.
2. P1: user middleware parity, security telemetry, scheduler/queue authorization.
3. P2: bot/honeypot protection and installer lifecycle (if still required).
