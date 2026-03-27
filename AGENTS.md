# AGENTS.md

This file is the project playbook for AI coding agents working in `wirechat`.

Read this before making changes. If the user gives direct instructions that conflict with this file, follow the user.

## What This Package Is

`wirechat` is a Laravel + Livewire chat package for:

- private chats
- self chats
- group conversations
- embeddable widget chat
- attachments and media sharing
- replies, deletes, and group member management
- panel-based configuration and theming

This is a reusable package, not just an app. Changes should be made with package users in mind.

## Core Stack

Main runtime dependencies from `composer.json`:

- PHP `^8.1|^8.2|^8.3|^8.4`
- Laravel `^10|^11|^12|^13`
- Livewire `^3.7|^4.0`
- Laravel Prompts

Main dev/test tooling:

- Orchestra Testbench
- Pest
- Laravel Pint
- Larastan / PHPStan

Backward compatibility across supported Laravel and Livewire versions matters.

## Main Package Concepts

### 1. Panels are a core extension surface

Panels are one of the most important concepts in Wirechat.

- Panels are registered through `PanelProvider` classes.
- Each panel must have an `id()`.
- Panels can be resolved by id or provider class.
- One panel can be marked as `->default()`.
- Panel state is tracked through `PanelRegistry`.

Key files:

- `src/Panel.php`
- `src/PanelProvider.php`
- `src/PanelRegistry.php`
- `src/Services/WirechatService.php`
- `workbench/app/Providers/Wirechat/TestPanelProvider.php`

If a change affects routes, middleware, search, theming, auth, uploads, or widget behavior, check whether it should be panel-aware.

### 2. Panel configuration is public API

Do not casually break panel methods or their meaning.

Important panel options currently include:

- `id()`
- `path()`
- `default()`
- `layout()`
- `middleware()`
- `chatMiddleware()`
- `guards()`
- `groups()`
- `maxGroupMembers()`
- `attachments()`
- `mediaAttachments()`
- `fileAttachments()`
- `maxUploads()`
- `mediaMimes()`
- `mediaMaxUploadSize()`
- `fileMimes()`
- `fileMaxUploadSize()`
- `chatsSearch()`
- `searchableAttributes()`
- `searchUsersUsing()`
- `createChatAction()`
- `createGroupAction()`
- `clearChatAction()`
- `deleteChatAction()`
- `deleteMessageActions()`
- `emojiPicker()`
- `webPushNotifications()`
- `serviceWorkerPath()`
- `broadcasting()`
- `messagesQueue()`
- `eventsQueue()`
- `colors()`
- `favicon()`
- `heading()`
- `heart()`
- `redirectToHomeAction()`
- `homeUrl()`

When changing panel behavior:

- treat it as package API
- consider existing panel providers in user apps
- prefer additive changes over breaking renames or semantic changes

### 3. Multi-auth and multi-model support are fundamental

Wirechat is built around polymorphic participants, not a single hard-coded user model.

- Participants use `participantable_id` + `participantable_type`.
- Multiple authenticatable model types can participate in conversations.
- Panels can authenticate through multiple guards via `guards([...])`.
- Access is also controlled per user model via `canAccessWirechatPanel(Panel $panel)`.

Important public surfaces:

- `src/Contracts/WirechatUser.php`
- `src/Traits/InteractsWithWirechat.php`
- `src/Models/Participant.php`
- `src/Models/Conversation.php`

The expected user-model integration is:

- implement `WirechatUser`
- use `InteractsWithWirechat`

The old `Chatable` trait still exists for backward compatibility, but `InteractsWithWirechat` is the preferred path.

Avoid changes that assume:

- only one auth guard
- only one user model
- only `App\Models\User`

### 4. User-facing package capabilities matter more than one-off internals

The long-lived behavior to protect is:

- creating and reopening private conversations
- self conversations
- group creation and group permissions
- owner/admin/participant role flows
- member add/remove/promote/dismiss flows
- replies
- text messages and attachments
- media rendering
- search
- widget embedding
- panel theming and panel route behavior
- web push hooks and broadcast integration

One-off bug workarounds are less important than preserving these stable package features.

## Configuration Surface

Global config in `config/wirechat.php` currently includes:

- `uses_uuid_for_conversations`
- `table_prefix`
- `models.*` overrides
- `storage.disk`
- `storage.visibility`
- `storage.directories.attachments`

Treat these as compatibility-sensitive.

In particular:

- model overrides are part of package extensibility
- table prefix and UUID settings affect migrations and schema expectations
- storage settings affect attachment persistence and URL generation

## Public Integration Points

These are high-value extension points and should be changed carefully:

### User model integration

- `WirechatUser` contract
- `InteractsWithWirechat` trait
- accessors like `wirechat_name`, `wirechat_avatar_url`, `display_name`, `cover_url`
- methods like `canCreateChats()`, `canCreateGroups()`, `canAccessWirechatPanel()`

### Panel providers

Users configure behavior through panel providers. Example providers live in:

- `workbench/app/Providers/Wirechat/TestPanelProvider.php`
- `workbench/app/Providers/Wirechat/AdminPanelProvider.php`

### Model overrides

Users can override:

- action model
- attachment model
- conversation model
- group model
- message model
- participant model

Do not assume the concrete classes are always the defaults. Prefer resolving models through the `Wirechat` facade/service where possible.

## Routes, Middleware, and Access

Wirechat registers routes and middleware aliases through `WirechatServiceProvider`.

Important middleware:

- `belongsToConversation`
- `wirechat.setPanel`
- `wirechat.panelAccess`

Panel access is not just route auth:

- route middleware may allow a request
- the user model may still deny access through `canAccessWirechatPanel()`

Changes in this area should consider both route-level and model-level access checks.

## UI Modes

There are two important presentation modes:

- full-page chat pages
- embeddable widget mode

Treat them as separate UX paths. A fix in one does not guarantee the other is correct.

Important files:

- `src/Livewire/Pages/*`
- `src/Livewire/Widgets/Wirechat.php`
- `resources/views/livewire/widgets/*`
- `resources/views/livewire/chat/*`

When changing drawers, modals, shell layout, or Alpine event flows, verify both modes.

## Search Behavior

Search is panel-driven.

- chat list search uses panel searchable attributes
- user search for new chats/groups can be customized through `searchUsersUsing()`

Do not hardcode search behavior if a panel callback already exists for that concern.

## Broadcasting and Notifications

Wirechat includes:

- Echo/broadcast-based real-time updates
- panel-specific queue configuration
- optional web push notification support

Important files:

- `src/Panel/Concerns/HasBroadcasting.php`
- `src/Panel/Concerns/HasWebPushNotifications.php`
- `src/WirechatServiceProvider.php`
- `src/Jobs/*`
- `src/Events/*`

When changing notification or broadcast flows, remember that panels namespace channels and behavior.

## Testing Notes

Use these commands from the repo root:

```bash
php vendor/bin/pest
php vendor/bin/pest tests/Feature/WireChatTest.php
php vendor/bin/pest tests/Feature/ChatTest.php
php vendor/bin/pint --test
php vendor/bin/phpstan analyse
composer test
```

Testing environment notes:

- tests use Orchestra Testbench
- the workbench app lives in `workbench/`
- package test setup is in `tests/TestCase.php`
- Vite is disabled in tests with `$this->withoutVite()`
- SQLite in-memory is the default test database

When changing behavior, prefer adding a focused regression test near the affected feature.

## Repo Map

High-value directories:

- `src/Livewire` - components and UI state
- `src/Models` - data model behavior
- `src/Traits` - public integration helpers for user models
- `src/Panel` - panel options and public configuration surface
- `resources/views/livewire` - Blade UI structure
- `routes` - package routes and channels
- `tests/Feature` - user-facing behavior
- `tests/Unit` - model/service/trait behavior
- `workbench/` - example app and panel providers used in tests

## Working Style For This Repo

- Favor small, targeted changes unless the user asks for broader refactors.
- Preserve package API stability where possible.
- Prefer framework- and package-level abstraction points over hardcoded assumptions.
- Use panel/config/model resolution helpers instead of assuming defaults.
- When fixing something in `wirechat`, consider whether the same change should exist in `wirechat-pro`.

## Good Final Checks

Before wrapping up, try to cover:

1. Does this change respect panel configuration?
2. Does it still work with multiple guards or participant model types?
3. Did we preserve public extension points and config behavior?
4. Did we add or update a focused regression test if behavior changed?

