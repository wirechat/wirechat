# AGENTS.md

This file is the project playbook for AI coding agents working in `wirechat`.

Read this before making changes. If the user gives direct instructions that conflict with this file, follow the user.

## What This Package Is

`wirechat` is the base Laravel + Livewire chat package for:

- private chats
- self chats
- group conversations
- embeddable widget chat
- attachments and media sharing
- replies, deletes, and group member management
- panel-based configuration and theming

This file should read as `wirechat`-first guidance, not as a trimmed copy of pro-only conventions.

Think of this package as the core product surface:

- it should feel complete and coherent on its own
- it should stay friendly to projects using only the base package
- it may share concepts or extension seams with pro, but pro is not the default assumption here

Core expectations:

- it is panel-driven
- it supports polymorphic participants
- it supports multiple guards and participant model types
- it supports full-page and widget chat experiences
- it exposes package APIs through panels, traits, contracts, routes, and model overrides

Changes should preserve package compatibility and keep extension points stable.

## Relationship To Pro

`wirechat-pro` may build on some of the same ideas, naming patterns, or extension surfaces, but this repo should not be shaped around pro-first assumptions.

When working here:

- design and document features so they make sense in the base package on their own
- avoid language that makes the base package sound incomplete or secondary
- acknowledge shared concepts with pro when it helps future compatibility
- do not introduce abstractions that only make sense because of pro unless the user explicitly asks for that direction
- prefer neutral wording like "shared", "compatible", or "future-friendly" over "pro-only" framing

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

If a change affects routes, middleware, search, theming, auth, uploads, group behavior, or widget behavior, check whether it should be panel-aware.

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
- `groupInvitations()`
- `invitePageLayout()`
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

### 4. Group flows are first-class package behavior

Groups are not a side feature. They include:

- ownership and admin roles
- permissions
- adding members
- invite links
- invite landing and join flow
- join requests
- past members and blocked members

Important files include:

- `src/Models/Group.php`
- `src/Models/Invite.php`
- `src/Models/JoinRequest.php`
- `src/Livewire/Chat/Group/*`
- `resources/views/livewire/chat/group/*`
- `tests/Feature/Chat/Group/*`
- `tests/Feature/Info/*`

When changing group behavior, think about:

- owner vs admin vs participant permissions
- self-exited vs admin-removed vs blocked member behavior
- public vs private group access
- panel-aware invite URLs and access
- join-request review flow and counts

Keep the base package implementation solid and self-contained. If a group feature may later be extended in pro, that is fine, but the base package should still feel intentional and complete without needing pro context.

### 5. UI modes are separate UX paths

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

Panel APIs should be easy for base-package users to reason about. Do not frame configuration as if advanced or pro-style setups are the default.

### Model overrides

Users can override:

- action model
- attachment model
- conversation model
- group model
- invite model
- join request model
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

## Registered Livewire Components Worth Knowing

Important components include:

- `wirechat`
- `wirechat.chats`
- `wirechat.chat`
- `wirechat.chat.drawer`
- `wirechat.chat.info`
- `wirechat.chat.group.*`
- `wirechat.modal`

If a change affects component names, arguments, or registration, treat that as public integration surface.

## Search Behavior

Search is panel-driven.

- chat list search uses panel searchable attributes
- user search for new chats/groups can be customized through `searchUsersUsing()`
- invite-link send flows also rely on panel user search

Do not hardcode search or filtering behavior if the panel already provides a callback for that concern.

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

When changing broadcast or notification flows, remember that panels namespace channels and behavior.

## UI Styling Conventions

Use Tailwind zinc utilities for neutral UI by default. Avoid `--wc-light-*` / `--wc-dark-*` border and surface vars in Blade utility classes unless explicitly needed for runtime theming.

Prefer:

- `border-zinc-200 dark:border-zinc-700`
- `bg-zinc-50 dark:bg-zinc-800`
- `text-zinc-500 dark:text-zinc-400`

Reserve `primary-*` utilities for accents, actions, and highlights.

## Testing Notes

Use these commands from the repo root:

```bash
php vendor/bin/pest
php vendor/bin/pest tests/Feature/WireChatTest.php
php vendor/bin/pest tests/Feature/ChatTest.php
php vendor/bin/pest tests/Feature/Chat/Group/InviteFeatureTest.php
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
- `src/Livewire/Chat` - chat, drawers, group flows
- `src/Livewire/Widgets` - widget shell
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
- Preserve package compatibility and extension points.
- Treat panel methods, routes, component aliases, and user-model integration as durable public surfaces.
- Prefer framework- and package-level abstraction points over hardcoded assumptions.
- Use panel/config/model resolution helpers instead of assuming defaults.
- When writing docs, labels, or internal guidance, make the base package feel like the primary product while still leaving room for shared patterns with pro.

## Good Final Checks

Before wrapping up, try to cover:

1. Does this change respect panel configuration?
2. Does it still work with multiple guards or participant model types?
3. Does it preserve package extension points and model overrides?
4. If the change touched groups, invites, or join requests, is there focused test coverage for the updated behavior?
