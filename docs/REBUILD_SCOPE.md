# Travian Kingdoms-compatible rebuild

This branch is a dedicated workstream for rebuilding the game systems in small, verifiable stages while preserving the established browser strategy-game presentation.

## Important boundary

The target is a highly faithful, independently implemented game inspired by the gameplay and layout of Travian Kingdoms. Do not copy proprietary source code or redistribute protected artwork without permission. Where original assets are not supplied with appropriate rights, use original replacement assets designed to match the visual tone and layout.

## Delivery gates

A stage is not considered complete merely because a page renders. Each stage must include PHP 7.4 syntax checks, database migration/install checks, server-side authorization and validation, and repeatable tests for the relevant state transitions.

1. **Install and account foundation** — idempotent installer, DB configuration, account creation/login/logout, session security, admin bootstrap, and safe re-run behavior.
2. **Village and economy** — owned villages, four resource types, production/capacity, population, resource fields, server-calculated elapsed production, and resource display.
3. **Building system** — slot/type/prerequisite validation, costs, queue timing, cancellation/refunds, tribe-specific rules, and building completion.
4. **Core interface** — responsive game frame, resource bar, village view, resource-field view, building detail dialogs, navigation, and loading/error states.
5. **Military and movement** — troop definitions/training, movement timers, attack/reinforcement/return, combat resolution, reports, and exploit-resistant validation.
6. **World and social systems** — map, villages/players, multiple villages, trade, hero/adventures, quests, rankings, kingdom/duchy/alliance rules, messages, and notifications.
7. **Endgame and administration** — world configuration, world lifecycle, Natar/Wonder systems where in scope, moderation, backups, and admin controls.
8. **Release verification** — clean-install test, upgrade test, two-account interaction tests, PHP 7.4 and MariaDB/MySQL checks, security review, and a documented known-limitations list.

## Acceptance rule

Do not label the project "complete" or "fault-free" until the above applicable tests pass on a clean database and a fresh install. Ordinary shared hosting may not support persistent Node/socket processes; any multiplayer architecture must explicitly document and test its hosting requirements rather than silently depending on unavailable background services.
