# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-11-03

### Added
- Initial release of IAM API package
- JWT authentication with access/refresh tokens
- Multi-Factor Authentication (MFA) with TOTP
- OTP verification via Email and SMS
- Password policies with history tracking
- Account lockout protection
- CAPTCHA integration (mews/captcha - image-based)
- Multi-tenancy support with single organization per user model
- Role-Based Access Control (RBAC) with Spatie Permission
- Activity logging and audit trail
- 16 API controllers with 50+ endpoints
- Repository pattern for data access
- Service layer architecture
- Comprehensive middleware for security
- Database migrations and seeders
- API documentation with Scribe

### Features
- **Authentication**: JWT tokens, MFA, OTP, password policies
- **User Management**: Registration, profile, password management
- **Organizations**: Multi-tenancy, organization members, invitations
- **RBAC**: Roles, permissions, organization-scoped access control
- **Audit**: Activity logging, security event tracking
- **Security**: Account lockout, rate limiting, CAPTCHA

### Database
- 21 migrations for complete schema setup
- 10 Eloquent models
- Support for MySQL, PostgreSQL, SQLite

### Testing
- Unit tests
- Feature tests
- Orchestra Testbench integration

## [Unreleased]

### Planned Features
- API rate limiting improvements
- Advanced audit trail filtering
- Webhook support for events
- Two-factor authentication via SMS
- Social authentication integration
- Session management improvements
- Advanced reporting capabilities
