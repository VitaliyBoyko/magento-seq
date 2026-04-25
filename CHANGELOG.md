# Changelog

All notable changes to this project will be documented in this file.

## [2026.1.1] - 2026-04-25

### Fixed

- The RequireJS frontend bootstrap now sends browser event context as `contextJson`, matching the Magento Web API contract used by the collector endpoint.
- Frontend bootstrap event delivery is now consistent between the inline template bootstrap and the JavaScript module bootstrap.

### Changed

- README documentation now highlights the Seq MCP JetBrains plugin as a companion for inspecting Magento events in Seq from the IDE.
- Internal code documentation was expanded across the public service contracts, Seq client, config handling, and frontend bootstrap components.

## [2026.1.0] - 2026-04-19

Initial release of `vitaliyboyko/magento-seq`.

### Added

- Magento 2 module packaging and registration for local Seq instrumentation.
- Monolog mirroring to Seq, preserving log level, channel, and record context.
- Backend `InstrumentationInterface` service for custom structured events.
- Frontend `window.devSeq` helper for browser-side event collection.
- Anonymous storefront Web API endpoint for relaying frontend events through Magento.
- Admin configuration for enabling the module and setting the Seq host or raw CLEF endpoint.
- Optional password or API key support via the `X-Seq-ApiKey` header.
- Automatic normalization from a plain Seq host to `/api/events/raw?clef`.
- Validation of the configured Seq URL plus live reachability checks when saving admin config.

### Changed

- Storefront event collection now uses Magento Web API instead of the earlier approach.
- Installation and configuration documentation were expanded and clarified in the README.
