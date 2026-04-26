# IAM API - Identity and Access Management System

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-11.x-red.svg" alt="Laravel Version">
  <img src="https://img.shields.io/badge/PHP-8.2+-blue.svg" alt="PHP Version">
  <img src="https://img.shields.io/badge/License-MIT-green.svg" alt="License">
  <img src="https://img.shields.io/badge/Status-Production%20Ready-brightgreen.svg" alt="Status">
</p>

A comprehensive **Identity and Access Management (IAM)** system built with Laravel, providing enterprise-grade authentication, authorization, and user management capabilities for a single organization.

## 🚀 Features

### 🔐 Authentication & Security
- **JWT Authentication** with access and refresh tokens
- **Multi-Factor Authentication (MFA)** with TOTP (Google Authenticator) and recovery codes
- **UUID-based Route Model Binding** for enhanced security and obfuscation
- **Password Policies** with history tracking and expiry enforcement
- **Account Lockout** protection against brute force attacks
- **Rate Limiting & CAPTCHA** integration with image-based CAPTCHA (mews/captcha)
- **OTP Verification** via Email and SMS for secure operations
- **Session Management** with concurrent session control and device tracking

### 👥 User Management
- **User Registration** with email verification
- **Profile Management** with secure update mechanisms
- **Password Management** with policy enforcement and history
- **Account Status Control** (active, inactive, locked)
- **Email & Phone Verification** workflows

### 🏢 Organization & Department Management
- **Single Organization** with department and team structure
- **Organization Members** with role-based access (owner, admin, member)
- **Automatic Organization Context** based on user profile
- **Invitation System** for organization membership
- **Organization Settings** with customizable security policies

### 🛡️ Role-Based Access Control (RBAC)
- **Spatie Laravel Permission** integration
- **Hierarchical Roles** with granular permissions
- **Organization-scoped Permissions** for access control
- **Dynamic Permission Assignment** and management
- **Middleware Protection** for routes and controllers

### 📊 Monitoring & Audit
- **Comprehensive Activity Logging** with Spatie ActivityLog
- **Security Event Tracking** (login attempts, password changes, MFA events)
- **Audit Trail** for compliance and security monitoring
- **Laravel Horizon** for queue monitoring and management
- **Performance Monitoring** with detailed logging channels

### 🔧 Developer Experience
- **Repository Pattern** with complete interfaces and implementations
- **Service Layer Architecture** for business logic separation
- **Action Classes** for complex operations
- **API Resources** with consistent response formatting
- **Request Validation** with comprehensive form requests
- **Comprehensive Documentation** with Scribe integration

## 📋 Requirements

- **PHP**: 8.2 or higher
- **Laravel**: 11.x
- **Database**: MySQL 8.0+ / PostgreSQL 13+ / SQLite 3.35+
- **Redis**: 6.0+ (for caching and queues)
- **Composer**: 2.0+
- **Node.js**: 18+ (for asset compilation)

## 🛠️ Installation

### 1. Clone the Repository
```bash
git clone https://github.com/your-username/iam-service.git
cd iam-service
```

### 2. Install Dependencies
```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies (if needed)
npm install
```

### 3. Environment Configuration
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Generate JWT secret
php artisan jwt:secret
```

### 4. Configure Environment Variables
Edit `.env` file with your settings:

```env
# Application
APP_NAME="IAM API"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_TIMEZONE=Asia/Tehran

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=iam_api
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# JWT Configuration
JWT_TTL=15
JWT_REFRESH_TTL=43200
JWT_ALGO=HS256

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls

# SMS Configuration (Optional)
SMS_PROVIDER=kavenegar
KAVENEGAR_API_KEY=your-api-key

# CAPTCHA Configuration (mews/captcha - Persian/Farsi)
CAPTCHA_LENGTH=5
CAPTCHA_WIDTH=160
CAPTCHA_HEIGHT=50
CAPTCHA_QUALITY=90
CAPTCHA_MATH=false
CAPTCHA_EXPIRE=60
CAPTCHA_ENCRYPT=true
CAPTCHA_MAX_REQUESTS=60
CAPTCHA_TIME_WINDOW=1
```

### 5. Database Setup
```bash
# Run migrations
php artisan migrate

# Seed the database with development data
php artisan db:seed

# Or seed with specific seeder
php artisan db:seed --class=DevelopmentSeeder
```

### 6. Storage and Cache Setup
```bash
# Create storage link
php artisan storage:link

# Clear and cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set up queue tables (if using database queue)
php artisan queue:table
php artisan migrate
```

### 7. Start the Application
```bash
# Start Laravel development server
php artisan serve

# Start queue worker (in another terminal)
php artisan queue:work

# Start Horizon (for queue monitoring)
php artisan horizon
```

## 📚 API Documentation

### Generate Documentation
```bash
# Generate API documentation with Scribe
php artisan scribe:generate
```

### Access Documentation
- **HTML Documentation**: `http://localhost:8000/docs`
- **Postman Collection**: `http://localhost:8000/docs.postman`
- **OpenAPI Spec**: `http://localhost:8000/docs.openapi`

### Authentication
Most endpoints require JWT authentication:
```bash
# Login to get tokens
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@iam-service.local","password":"Admin123!"}'

# Use access token in subsequent requests
curl -X GET http://localhost:8000/api/v1/auth/me \
  -H "Authorization: Bearer your-jwt-token"
```

## 🧪 Testing

### Run Tests
```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage
```

### Test Accounts
The development seeder creates these test accounts:

**Admin Accounts:**
- Super Admin: `superadmin@iam-service.local` / `SuperAdmin123!`
- System Admin: `admin@iam-service.local` / `Admin123!`
- Org Admin: `org-admin@acme-corp.local` / `OrgAdmin123!`

**Test Users:**
- All test users use password: `User123!`
- Various scenarios: active, locked, expired password, unverified, etc.

## 🔧 Configuration

### Security Settings
Key security configurations in `config/iam.php`:

```php
'password' => [
    'expiry_days' => 90,
    'history_count' => 5,
    'min_length' => 8,
    'require_uppercase' => true,
    'require_lowercase' => true,
    'require_numbers' => true,
    'require_symbols' => true,
],

'authentication' => [
    'throttle' => [
        'max_attempts' => 5,
        'window_seconds' => 900,
        'lock_attempts' => 10,
        'lock_seconds' => 1800,
    ],
],
```

### JWT Configuration
JWT settings in `config/jwt.php`:

```php
'ttl' => env('JWT_TTL', 15), // 15 minutes
'refresh_ttl' => env('JWT_REFRESH_TTL', 43200), // 30 days
'algo' => env('JWT_ALGO', 'HS256'),
'blacklist_enabled' => true,
```

### UUID-based Security
All API endpoints use UUID for route model binding instead of integer IDs:

```bash
# ✅ Correct - Using UUID
GET /api/v1/orgs/9a5f2c8e-4b3a-4d9e-8f7a-1b2c3d4e5f6a

# ❌ Incorrect - Integer ID no longer works
GET /api/v1/orgs/1
```

**Benefits:**
- Prevents enumeration attacks
- Hides database structure
- Globally unique identifiers
- Enhanced security through obfuscation

See `UUID_MIGRATION_SUMMARY.md` for complete details.

## 🚀 Deployment

### Production Setup
1. **Environment**: Set `APP_ENV=production` and `APP_DEBUG=false`
2. **Security**: Generate strong `APP_KEY` and `JWT_SECRET`
3. **Database**: Use production database with proper credentials
4. **Cache**: Configure Redis for caching and sessions
5. **Queue**: Set up queue workers with Supervisor
6. **SSL**: Enable HTTPS and secure cookies
7. **Monitoring**: Configure error tracking (Sentry, Bugsnag)

### Queue Workers
```bash
# Install Supervisor
sudo apt install supervisor

# Create worker configuration
sudo nano /etc/supervisor/conf.d/iam-service-worker.conf

# Start workers
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start iam-service-worker:*
```

### Horizon Dashboard
Access queue monitoring at: `http://your-domain.com/admin/queue-monitor`
(Requires admin authentication)

## 📖 Documentation

### Available Documentation
- **API Documentation**: Generated with Scribe (`/docs`)
- **UUID Migration**: `UUID_MIGRATION_SUMMARY.md`
- **System Configuration**: `docs/SYSTEM_CONFIGURATION.md`
- **Security Configuration**: `docs/SECURITY_CONFIGURATION.md`
- **Services Configuration**: `docs/SERVICES_CONFIGURATION.md`
- **Development Seeders**: `docs/DEVELOPMENT_SEEDERS.md`
- **JWT Configuration**: `docs/JWT_CONFIGURATION.md`
- **Horizon Configuration**: `docs/HORIZON_CONFIGURATION.md`

### Architecture Overview
```
├── app/
│   ├── Actions/           # Business logic actions
│   ├── Http/Controllers/  # API controllers
│   ├── Http/Middleware/   # Custom middleware
│   ├── Http/Requests/     # Form request validation
│   ├── Http/Resources/    # API response resources
│   ├── Models/           # Eloquent models
│   ├── Repositories/     # Repository pattern implementation
│   └── Services/         # Business services
├── config/               # Configuration files
├── database/
│   ├── migrations/       # Database migrations
│   └── seeders/         # Database seeders
└── docs/                # Project documentation
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development Guidelines
- Follow PSR-12 coding standards
- Write comprehensive tests for new features
- Update documentation for API changes
- Use conventional commit messages

## 🔒 Security

### Reporting Security Vulnerabilities
If you discover a security vulnerability, please send an email to [security@your-domain.com](mailto:security@your-domain.com). All security vulnerabilities will be promptly addressed.

### Security Features
- **JWT Token Security**: Short-lived access tokens with secure refresh mechanism
- **Password Security**: Bcrypt hashing with configurable rounds
- **Rate Limiting**: Protection against brute force attacks
- **CAPTCHA Integration**: Image-based CAPTCHA (mews/captcha) for bot protection
- **Account Lockout**: Automatic account protection
- **Audit Logging**: Comprehensive security event tracking
- **Multi-Factor Authentication**: TOTP-based additional security layer

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- **Laravel Framework** - The foundation of this project
- **Spatie Packages** - Permission and ActivityLog packages
- **JWT-Auth** - JWT authentication implementation
- **Laravel Horizon** - Queue monitoring and management

## 📞 Support

- **Documentation**: Check the `/docs` directory for detailed guides
- **Issues**: Report bugs and feature requests on GitHub Issues
- **Discussions**: Join community discussions on GitHub Discussions

---

**Built with ❤️ using Laravel**
