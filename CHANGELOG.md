# Wirechat Changelog 

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),  
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---


## [Unreleased]  

### Added  
- Initial changelog setup.  
- Placeholder for upcoming features.

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

## [v0.0.1](https://github.com/namumakwembo/wirechat/releases/tag/v0.0.1) - 2024-12-8  
### Added  
- Introduced `Wirechat` package with the following features:  
  - Basic chat functionality for private conversations.  
  - Group Chats functionality.  
  - Smart Deletes for:
    * Conversations  
    * Messages  
  - Messages: sending, receiving, viewing.  
  - Published initial migrations for conversations and messages.  
