# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

---

## [2.0.0] - 2026-11-21

### Breaking
- Require PHP 8.4.1+, Triangle Engine 9, Localzet Console 2.2, and Symfony Console/Process 8.1.
- Migrate command discovery to the Engine 9 plugin APIs and support Localzet Console 2's Illuminate command bridge.

### Added
- Compatibility checks for Engine 9 command discovery and Symfony/Illuminate command execution.
- English and Russian installation, usage, command, and upgrade guides.

---

## [1.0.0] - 2023-07-23 - LTS Release

### Added
- Initial LTS release
- Full documentation
- Console command system
- Built-in commands (serve, migrate, etc.)
- Custom command registration
- Command argument parsing
- Command option parsing
- Command output formatting

### Changed
- Improved command execution
- Enhanced argument validation
