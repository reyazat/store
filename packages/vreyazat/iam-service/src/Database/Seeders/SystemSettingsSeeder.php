<?php

namespace IamApi\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('⚙️ Seeding system settings...');

        DB::transaction(function () {
            // Create system settings table if it doesn't exist
            if (!DB::getSchemaBuilder()->hasTable('system_settings')) {
                $this->command->warn('System settings table not found, skipping...');
                return;
            }

            $settings = [
                // Security Settings
                [
                    'key' => 'security.max_login_attempts',
                    'value' => '5',
                    'type' => 'integer',
                    'description' => 'Maximum login attempts before account lockout',
                    'category' => 'security',
                    'is_public' => false,
                ],
                [
                    'key' => 'security.lockout_duration',
                    'value' => '1800',
                    'type' => 'integer',
                    'description' => 'Account lockout duration in seconds (30 minutes)',
                    'category' => 'security',
                    'is_public' => false,
                ],
                [
                    'key' => 'security.password_min_length',
                    'value' => '8',
                    'type' => 'integer',
                    'description' => 'Minimum password length',
                    'category' => 'security',
                    'is_public' => true,
                ],
                [
                    'key' => 'security.password_require_uppercase',
                    'value' => 'true',
                    'type' => 'boolean',
                    'description' => 'Require uppercase letters in passwords',
                    'category' => 'security',
                    'is_public' => true,
                ],
                [
                    'key' => 'security.password_require_lowercase',
                    'value' => 'true',
                    'type' => 'boolean',
                    'description' => 'Require lowercase letters in passwords',
                    'category' => 'security',
                    'is_public' => true,
                ],
                [
                    'key' => 'security.password_require_numbers',
                    'value' => 'true',
                    'type' => 'boolean',
                    'description' => 'Require numbers in passwords',
                    'category' => 'security',
                    'is_public' => true,
                ],
                [
                    'key' => 'security.password_require_symbols',
                    'value' => 'true',
                    'type' => 'boolean',
                    'description' => 'Require special characters in passwords',
                    'category' => 'security',
                    'is_public' => true,
                ],
                [
                    'key' => 'security.password_history_count',
                    'value' => '5',
                    'type' => 'integer',
                    'description' => 'Number of previous passwords to remember',
                    'category' => 'security',
                    'is_public' => false,
                ],
                [
                    'key' => 'security.password_expiry_days',
                    'value' => '90',
                    'type' => 'integer',
                    'description' => 'Password expiry in days',
                    'category' => 'security',
                    'is_public' => false,
                ],

                // JWT Settings
                [
                    'key' => 'jwt.access_token_ttl',
                    'value' => '15',
                    'type' => 'integer',
                    'description' => 'JWT access token TTL in minutes',
                    'category' => 'jwt',
                    'is_public' => false,
                ],
                [
                    'key' => 'jwt.refresh_token_ttl',
                    'value' => '43200',
                    'type' => 'integer',
                    'description' => 'JWT refresh token TTL in minutes (30 days)',
                    'category' => 'jwt',
                    'is_public' => false,
                ],
                [
                    'key' => 'jwt.blacklist_grace_period',
                    'value' => '10',
                    'type' => 'integer',
                    'description' => 'JWT blacklist grace period in seconds',
                    'category' => 'jwt',
                    'is_public' => false,
                ],

                // MFA Settings
                [
                    'key' => 'mfa.totp_window',
                    'value' => '1',
                    'type' => 'integer',
                    'description' => 'TOTP time window tolerance',
                    'category' => 'mfa',
                    'is_public' => false,
                ],
                [
                    'key' => 'mfa.recovery_codes_count',
                    'value' => '10',
                    'type' => 'integer',
                    'description' => 'Number of recovery codes to generate',
                    'category' => 'mfa',
                    'is_public' => false,
                ],
                [
                    'key' => 'mfa.enforce_for_admins',
                    'value' => 'true',
                    'type' => 'boolean',
                    'description' => 'Enforce MFA for admin users',
                    'category' => 'mfa',
                    'is_public' => false,
                ],

                // OTP Settings
                [
                    'key' => 'otp.email_expiry_minutes',
                    'value' => '10',
                    'type' => 'integer',
                    'description' => 'Email OTP expiry in minutes',
                    'category' => 'otp',
                    'is_public' => false,
                ],
                [
                    'key' => 'otp.sms_expiry_minutes',
                    'value' => '5',
                    'type' => 'integer',
                    'description' => 'SMS OTP expiry in minutes',
                    'category' => 'otp',
                    'is_public' => false,
                ],
                [
                    'key' => 'otp.max_attempts',
                    'value' => '5',
                    'type' => 'integer',
                    'description' => 'Maximum OTP verification attempts',
                    'category' => 'otp',
                    'is_public' => false,
                ],

                // Rate Limiting Settings
                [
                    'key' => 'rate_limit.login_attempts',
                    'value' => '5',
                    'type' => 'integer',
                    'description' => 'Login attempts per window',
                    'category' => 'rate_limit',
                    'is_public' => false,
                ],
                [
                    'key' => 'rate_limit.login_window_minutes',
                    'value' => '15',
                    'type' => 'integer',
                    'description' => 'Login rate limit window in minutes',
                    'category' => 'rate_limit',
                    'is_public' => false,
                ],
                [
                    'key' => 'rate_limit.api_requests_per_minute',
                    'value' => '60',
                    'type' => 'integer',
                    'description' => 'API requests per minute per user',
                    'category' => 'rate_limit',
                    'is_public' => false,
                ],

                // Session Settings
                [
                    'key' => 'session.lifetime_minutes',
                    'value' => '30',
                    'type' => 'integer',
                    'description' => 'Session lifetime in minutes',
                    'category' => 'session',
                    'is_public' => false,
                ],
                [
                    'key' => 'session.concurrent_limit',
                    'value' => '5',
                    'type' => 'integer',
                    'description' => 'Maximum concurrent sessions per user',
                    'category' => 'session',
                    'is_public' => false,
                ],

                // Email Settings
                [
                    'key' => 'email.verification_required',
                    'value' => 'true',
                    'type' => 'boolean',
                    'description' => 'Require email verification for new accounts',
                    'category' => 'email',
                    'is_public' => true,
                ],
                [
                    'key' => 'email.verification_expiry_hours',
                    'value' => '24',
                    'type' => 'integer',
                    'description' => 'Email verification link expiry in hours',
                    'category' => 'email',
                    'is_public' => false,
                ],

                // Organization Settings
                [
                    'key' => 'organization.max_members',
                    'value' => '1000',
                    'type' => 'integer',
                    'description' => 'Maximum members per organization',
                    'category' => 'organization',
                    'is_public' => false,
                ],
                [
                    'key' => 'organization.invitation_expiry_hours',
                    'value' => '72',
                    'type' => 'integer',
                    'description' => 'Organization invitation expiry in hours',
                    'category' => 'organization',
                    'is_public' => false,
                ],

                // System Settings
                [
                    'key' => 'system.maintenance_mode',
                    'value' => 'false',
                    'type' => 'boolean',
                    'description' => 'System maintenance mode status',
                    'category' => 'system',
                    'is_public' => true,
                ],
                [
                    'key' => 'system.registration_enabled',
                    'value' => 'true',
                    'type' => 'boolean',
                    'description' => 'Allow new user registrations',
                    'category' => 'system',
                    'is_public' => true,
                ],
                [
                    'key' => 'system.api_version',
                    'value' => 'v1',
                    'type' => 'string',
                    'description' => 'Current API version',
                    'category' => 'system',
                    'is_public' => true,
                ],

                // Audit Settings
                [
                    'key' => 'audit.log_retention_days',
                    'value' => '365',
                    'type' => 'integer',
                    'description' => 'Audit log retention period in days',
                    'category' => 'audit',
                    'is_public' => false,
                ],
                [
                    'key' => 'audit.log_failed_logins',
                    'value' => 'true',
                    'type' => 'boolean',
                    'description' => 'Log failed login attempts',
                    'category' => 'audit',
                    'is_public' => false,
                ],
                [
                    'key' => 'audit.log_password_changes',
                    'value' => 'true',
                    'type' => 'boolean',
                    'description' => 'Log password changes',
                    'category' => 'audit',
                    'is_public' => false,
                ],

                // Notification Settings
                [
                    'key' => 'notifications.security_alerts_enabled',
                    'value' => 'true',
                    'type' => 'boolean',
                    'description' => 'Send security alert notifications',
                    'category' => 'notifications',
                    'is_public' => false,
                ],
                [
                    'key' => 'notifications.password_expiry_warning_days',
                    'value' => '7',
                    'type' => 'integer',
                    'description' => 'Days before password expiry to send warning',
                    'category' => 'notifications',
                    'is_public' => false,
                ],
            ];

            foreach ($settings as $setting) {
                DB::table('system_settings')->updateOrInsert(
                    ['key' => $setting['key']],
                    [
                        'key' => $setting['key'],
                        'value' => $setting['value'],
                        'type' => $setting['type'],
                        'description' => $setting['description'],
                        'category' => $setting['category'],
                        'is_public' => $setting['is_public'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            $settingsCount = count($settings);
            $categories = array_unique(array_column($settings, 'category'));
            $publicCount = count(array_filter($settings, fn($s) => $s['is_public']));

            $this->command->info("✅ Created system settings:");
            $this->command->line("   - Total settings: {$settingsCount}");
            $this->command->line("   - Categories: " . implode(', ', $categories));
            $this->command->line("   - Public settings: {$publicCount}");
            $this->command->line("   - Private settings: " . ($settingsCount - $publicCount));
        });
    }
}
