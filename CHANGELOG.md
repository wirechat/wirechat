# Wirechat Changelog 

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),  
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---


## [Unreleased]  

### Changed
- No unreleased changes documented yet.

---

## [v0.5.10](https://github.com/wirechat/wirechat/releases/tag/v0.5.10) - 2026-06-19

### Changed
- Updated generated panel provider stubs to include the default chat search, home redirect, and create chat actions.

### Fixed
- The first generated panel provider is now marked as the default panel when no panels are registered yet.
- Improved generated panel provider test coverage and reset handling for Wirechat's resolved service instance.

---

## [v0.5.9](https://github.com/wirechat/wirechat/releases/tag/v0.5.9) - 2026-06-19

### Fixed
- Fixed panel provider registration in `config/app.php` for applications that import provider classes.
- Corrected relation return type casing and model PHPDoc imports for cleaner static analysis.

---

## [v0.5.8](https://github.com/wirechat/wirechat/releases/tag/v0.5.8) - 2026-05-17

### Added
- Added PHP 8.5 compatibility to the package constraints and CI matrix.
- Added `Participant::isParticipantable()` to centralize participant identity checks across authenticated models.

### Changed
- Livewire component refresh dispatches now target registered component aliases instead of class references.
- Message wrapping, footer button transitions, and send action styling were refined.

### Fixed
- Hardened participantable checks for null users and custom participantable/sendable resolution.
- Added `.com` to the default bare-domain parsing TLD list.
- Stabilized time-sensitive helper tests by aligning mutable and immutable Carbon clocks.

---

## [v0.5.7](https://github.com/wirechat/wirechat/releases/tag/v0.5.7) - 2026-04-10

### Fixed
- Stopped persisting computed auth model state on chat components so participant identity stays tied to the current request user.

---

## [v0.5.6](https://github.com/wirechat/wirechat/releases/tag/v0.5.6) - 2026-04-10

### Fixed
- Fixed grouped chat date formatting when immutable timestamps are used.

---

## [v0.5.5](https://github.com/wirechat/wirechat/releases/tag/v0.5.5) - 2026-04-09

### Added
- Improved message link handling for email addresses, whitespace-preserving rendering, and dedicated link message parsing.
- More resilient unread indicator updates across active chat lists.

### Changed
- Localized grouped chat dates and relative timestamps.
- Improved member action dropdown behavior so menus switch cleanly instead of stacking.
- Smoothed initial chat scroll behavior and refined chats input spacing.

### Fixed
- Prevented delete-for-everyone from rehydrating removed messages and causing 404s for other participants.
- Fixed realtime unread indicator sync and tightened `loadMore` and scroll stability in chat and chats-list views.

---

## [v0.5.4](https://github.com/wirechat/wirechat/releases/tag/v0.5.4) - 2026-04-06

### Added
- Configurable unread indicator APIs and reactive chat preview badges.
- Configurable message link parsing with allowed TLD defaults and message-body URL parsing.

### Changed
- Renamed the link parsing configuration to `message_links` and the internal parser to `parseMessageUrls`.

### Fixed
- Kept the widget chat drawer mounted across refreshes.
- Fixed undefined media handling and message action styling regressions.

---

## [v0.5.3](https://github.com/wirechat/wirechat/releases/tag/v0.5.3) - 2026-03-27

### Fixed
- Fixed the send message action button background color.

---

## [v0.5.2](https://github.com/wirechat/wirechat/releases/tag/v0.5.2) - 2026-03-27

### Added
- Widget wrapper UI props and refreshed dark palette options.

### Changed
- Added Laravel 13 compatibility and aligned upload finalization with Livewire 4.
- Refactored model boot hooks to use `booted()` and renamed component style properties for consistency.

### Fixed
- Preserved image attachment detection for Livewire 3 and 4 uploads.
- Fixed modal and drawer layering plus scroll blocking when widgets or overlays are open.

---

## [v0.5.1](https://github.com/wirechat/wirechat/releases/tag/v0.5.1) - 2026-03-24

### Added
- Panel chat actions now support custom icons and icon attributes.
- Improved tooltip accessibility, focus handling, and mobile touch support in header actions.
- Added cursor-pagination coverage plus configurable service model resolution and storage disk env support.

### Changed
- Improved chats list pagination, rerender behavior, and responsive widget/sidebar interactions.
- Updated redirect-to-home handling and return controls for widget and mobile contexts.

### Fixed
- Fixed UUID-safe load-more ordering with deterministic cursor tie-breakers.
- Resolved icon rendering, attribute merge, and redirect rendering bugs in chats actions.

---

## [v0.5.0](https://github.com/wirechat/wirechat/releases/tag/v0.5.0) - 2026-01-30

### Changed
- Updated Wirechat to support **Livewire v4**.
- Improved internal component resolution for newer Livewire lifecycle behavior.
- Refined ChatWidget open/close handling for better stability.

### Fixed
- Updated and stabilized tests for Livewire v4.
- Fixed duplicate close events caused by multiple dispatches.

---

## [v0.4.1](https://github.com/wirechat/wirechat/releases/tag/v0.4.1) - 2026-01-29

### Fixed
- Fixed rollback logic so legacy `sendable_id` and `sendable_type` fields are restored correctly.

---

## [v0.4.0](https://github.com/wirechat/wirechat/releases/tag/v0.4.0) - 2026-01-29

### Changed
- Improved internal message sender handling using participants instead of polymorphic fields.

### Fixed
- Rollback now correctly restores legacy `sendable_id` and `sendable_type`.
- General bug fixes and stability improvements.

### Notes
- Compatible with **Livewire v3**.
- This line is maintenance-only (no new features).

---

## [0.3.0](https://github.com/namumakwembo/wirechat/releases/tag/0.3.0) - 2025-12-24

### Added
- Added `hasRoutes()` support so panels can opt out of automatic route registration.

### Fixed
- Hardened auth-user access checks around route registration for the stable 0.3.0 release.

---

## [v0.3.0-beta4](https://github.com/namumakwembo/wirechat/releases/tag/v0.3.0-beta4) - 2025-11-12

### Added
- A new feature `registerRoutes(bool|Closure $condition = true)` API on the Panel class.


---

## [v0.3.0-beta3](https://github.com/namumakwembo/wirechat/releases/tag/v0.3.0-beta3) - 2025-11-09

### Reverted

* Reverted migrations that changed `actionable_id`, `attachable_id` and `attachable_id` to string in their respective tables.

### Removed

* Excessive panel-related logging.

### Fixed

* Typos in CSS classes.

---

## [v0.3.0-beta2](https://github.com/namumakwembo/wirechat/releases/tag/v0.3.0-beta2) - 2025-09-30

### Added

* Command to publish a migration to update `actionable_id`, `attachable_id` and `attachable_id` to string in their respective tables.

---

## [v0.3.0-beta1](https://github.com/namumakwembo/wirechat/releases/tag/v0.3.0-beta1) - 2025-09-29

Transition from config-based to **Panel-based** settings for a cleaner, extensible way to define Wirechat environments.

### Added
- Introduced **Panels** as the new core system for managing chat environments (e.g., user, admin, support).
- New `ChatsPanelProvider` generator (`php artisan make:wirechat-panel`).
- `wirechat:upgrade-namespace-to-v0.3x` command to migrate `Namu\WireChat` → `Wirechat\Wirechat`.
- `wirechat:upgrade-to-v0.3x` command to migrate old `config/wirechat.php` to panel providers.
- `wirechat:upgrade-morph-columns` command to ensure polymorphic relations work with both UUID and bigint IDs.
- `WireChatUser` interface and `InteractsWithWireChat` trait for user models.
- TailwindCSS v4.0+ support.
- Panel-specific user search (`searchUsersUsing`) for flexible customization.
- Support for panel-based notifications with `NotifyParticipant` requiring panel IDs.

### Changed
- Attachment URLs now resolve storage/visibility from the current panel.
- Improved avatar component: falls back to SVG when image load fails.
- Migrated `searchChatables` logic from User model → panel-based search.
- Storage configuration moved from `attachments.*` keys to new `storage.*` keys.
- UUID configuration clarified with `uses_uuid_for_conversations` key.
- Accessor methods renamed with `wirechat` prefix (`getAvatarUrlAttribute` → `getWirechatAvatarUrlAttribute`, etc.).
- Renamed `Chatable` trait → `InteractsWithWireChat`.

### Deprecated
- Old `namu/wirechat` package (remove after upgrade).
- Legacy `attachments.*` config keys.
- Old `uuids` config key (replaced by `uses_uuid_for_conversations`).
- Old `Chatable` trait name (backwards-compatible for now).
- Legacy accessors (`getAvatarUrlAttribute`, `getProfileUrlAttribute`, `getDisplayNameAttribute`).

### Fixed
- Panel migration ensures existing `config/wirechat.php` customizations are preserved.
- Morph column upgrader ensures idempotent, safe migrations without breaking existing foreign keys.

---

## [v0.3.0-alpha2](https://github.com/namumakwembo/wirechat/releases/tag/v0.3.0-alpha2) - 2025-09-28

### Fixed
- Fixed flickering overflow in the group creation flow.

---

## [v0.3.0-alpha1](https://github.com/namumakwembo/wirechat/releases/tag/v0.3.0-alpha1) - 2025-09-28

### Added
- First alpha of the panel-based architecture, including panel providers, panel-aware search, and UUID migration tooling.
- Added emoji configuration options, language updates, and panel-aware route/channel resolution.

### Changed
- Moved attachment URL resolution and UI settings into the panel system.

### Fixed
- Fixed avatar fallback behavior and a broad set of panel migration and test stability issues.

---

## [v0.2.11](https://github.com/namumakwembo/wirechat/releases/tag/v0.2.11) - 2025-09-26

### Added
- Added French translations.

### Fixed
- Fixed long message text wrapping and minor translation cleanup.

-----

## [v0.2.10](https://github.com/namumakwembo/wirechat/releases/tag/v0.2.10) - 2025-05-22

### Fixed
- Fixed close button not working correctly when chat component is widget  

-----

## [v0.2.9](https://github.com/namumakwembo/wirechat/releases/tag/v0.2.9) - 2025-05-15

### Fixed
- Added misssing closing tag in chat-list component 

------

## [v0.2.8](https://github.com/namumakwembo/wirechat/releases/tag/v0.2.8) - 2025-05-04

### Added
- `uuids` configuration option to toggle UUIDs for new installations

### Fixed
- Improved handling of storage URLs when saving attachments

### Updated
- Encrypted parameter keys for Blade method actions to enhance security

---

## [v0.2.7](https://github.com/namumakwembo/wirechat/releases/tag/v0.2.7) - 2025-04-25

### Fixed
- Changelog tag links missing prefix 'v'
- Style: in new-group button to use correct/updated css variable 
- Failing tests


---

## [v0.2.6](https://github.com/namumakwembo/wirechat/releases/tag/v0.2.6) - 2025-04-25

### Added
- New **Theme** documentation page.
- Support for defining CSS variables directly in a single `theme` configuration entry.

### Updated
- Theme system now uses the new CSS variable-based theming approach.
- Improved file storage logic to better support S3 and disk visibility handling.

---

## [v0.2.5](https://github.com/namumakwembo/wirechat/releases/tag/v0.2.5) - 2025-04-15

### Added  
- `wirechat.attachments.disk_visibility` config option to determine if temporary URLs should be generated for private storage disks

### Updated  
- Attachment upload now uses single file uploads to support S3 and similar disks that do not handle `temporary_uploaded_files` with multiple files  

### Fixed  
- PHPStan errors and improved code style with docblocks

---

## [v0.2.4-beta](https://github.com/namumakwembo/wirechat/releases/tag/v0.2.4-beta) - 2025-03-29

### Added
- Early Tailwind CSS v4 compatibility pass.

### Fixed
- Fixed the unwired description property regression.

---

## [v0.2.4](https://github.com/namumakwembo/wirechat/releases/tag/v0.2.4) - 2025-03-30  

### Added  
- support for Tailwind v4 
---

## [v0.2.3](https://github.com/namumakwembo/wirechat/releases/tag/v0.2.3) - 2025-03-29  

### Added  
- Language file translation keys for labels  
- Built-in validation translation keys  
- Separate group info page for groups  
- Empty search results message for the new chat component  
- More tests  

### Fixed  
- Delete photo button incorrectly acting as a submit button while creating a group  

### Updated  
- includes folder to partials  in chats and chat directories

---


## [v0.2.2](https://github.com/namumakwembo/wirechat/releases/tag/v0.2.2) - 2025-03-15
### Updated  
- Storeage url to use storage:disk()->url() instead of static url from database

---

## [v0.2.1](https://github.com/namumakwembo/wirechat/releases/tag/v0.2.1) - 2025-03-15
### Fixed  
- Storage disk to support dynamic storage 
### Added 
- Tests for multiple storage support

---

## [v0.2.0](https://github.com/namumakwembo/wirechat/releases/tag/v0.2.0) - 2025-03-06
### Added  
- Support for Laravel 12

---


## [v0.1.11](https://github.com/namumakwembo/wirechat/releases/tag/v0.1.11) - 2025-03-04
### Added  
- Introduced native notifications feature for new messages
- New `notifications` key to wirechat configuration
 ```php
     'notifications'=>[
        'enabled'=>true,
        'main_sw_script' => 'sw.js',  // Relative to the public folder
     ],
```
- Added docs about notification

---

## [v0.1.1](https://github.com/namumakwembo/wirechat/releases/tag/v0.1.1) - 2025-02-17

### Fixed
- Fixed emoji picker styling in system dark mode.

---


## [v0.1.0](https://github.com/namumakwembo/wirechat/releases/tag/v0.1.0) - 2025-02-16

### Added
- New config variables:
  - `'guards' => ['web']`
  - `'layout' => 'wirechat::layouts.app'`
- Command for publishing views.
- Standalone Wirechat widget.
- Added/Improved documentation on:
  - Authorization
  - Core Components
  - Layout
  - Views
  - Contribution Guide
  - Extending Wirechat Components
- `belongsToConversation` middleware added to the `/chats` view route.

### Changed
- `NotifyParticipant` channel now uses an encoded type and ID to support mixed models in conversations.

  **Breaking Change:**  
  If you previously listened to the `participant` channel, update to the new format:

  ```diff
  + userId = @js(auth()->id());
  + encodedType = @js(Namu\Wirechat\Helpers\MorphClassResolver::encode(auth()->user()->getMorphClass()));

  - Echo.private(`participant.${userId}`)
  + Echo.private(`participant.${encodedType}.${userId}`)
        .listen('.Namu\\Wirechat\\Events\\NotifyParticipant', (e) => {
           console.log(e);
      });
  ```

- `Folder Structure Reorganized `

  We have restructured the package folders to group related components and assets more logically. This improves view publishing and feature additions. If you have previously published or customized views, please re-publish them using the new command and update any file path references accordingly.


### Fixed  
- Updated tests to fully support conversations with mixed models.  
- Improved participant handling for different models.  

### Updated  
- Optimized code and queries for faster conversation loading.  
- Updated brodcasting to use the guards provided in wirechat config the 

---

## [v0.0.7](https://github.com/namumakwembo/wirechat/releases/tag/v0.0.7) - 2024-12-20  
### Added  
- Introduced `Actor` and `Actionable` traits for improved polymorphic relationship handling.  
  - Added tests for `Actor` and `Actionable` traits.

### Fixed  
- Resolved a bug that caused incorrect retrieval of the authenticated participant due to a missing `conversation_id` filter during retrieval.

### Updated  
- Refactored migrations to use `unsignedBigInteger`. All polymorphic relationships now use unsignedBigInteger by default to maintain consistency across databases. This resolves a type mismatch issue found during testing on PostgreSQL.

**Note:** Running `php artisan view:clear` may be required to ensure the changes take effect.

---

## [v0.0.6](https://github.com/namumakwembo/wirechat/releases/tag/v0.0.6) - 2024-12-16  
### Fixed  
- Fixed error caused by missing import in chat blade due to typo.  
  **Note:** Running `php artisan view:clear` may be required to ensure the changes take effect.

---

## [v0.0.5](https://github.com/namumakwembo/wirechat/releases/tag/v0.0.5) - 2024-12-11  
### Fixed  
- Fixed unread messages dot not appearing correctly.  
  **Note:** Running `php artisan view:clear` may be required to ensure the changes take effect.

---

## [v0.0.4](https://github.com/namumakwembo/wirechat/releases/tag/v0.0.4) - 2024-12-8  
### Added  
- Issue template  
- MIT license  
- CODEOWNERS file to assign reviewers automatically

---

## [v0.0.3](https://github.com/namumakwembo/wirechat/releases/tag/v0.0.3) - 2024-12-04

### Changed
- Cleaned up composer and test compatibility for the early Pest 2 and 3 support matrix.

---

## [v0.0.2](https://github.com/namumakwembo/wirechat/releases/tag/v0.0.2) - 2024-12-04

### Changed
- Added Pest 3 compatibility and moved read tracking to participants.
- Improved chats query performance and loading transitions.

---

## [0.0.1](https://github.com/namumakwembo/wirechat/releases/tag/0.0.1) - 2024-11-30  
### Added  
- Introduced `Wirechat` package with the following features:  
  - Basic chat functionality for private conversations.  
  - Group Chats functionality.  
  - Smart Deletes for:
    * Conversations  
    * Messages  
  - Messages: sending, receiving, viewing.  
  - Published initial migrations for conversations and messages.  
