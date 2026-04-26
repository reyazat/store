<?php

namespace Database\Seeders;

use IamApi\Models\JwtToken;
use IamApi\Models\Otp;
use IamApi\Models\PasswordHistory;
use IamApi\Models\User;
use IamApi\Models\UserRecoveryCode;
use IamApi\Models\UserTotpSecret;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class CleanupSeeder extends Seeder
{
    /**
     * Clean up old and orphaned data from the database.
     * This seeder removes expired, orphaned, and unnecessary data.
     */
    public function run(): void
    {
        $this->command->info('🧹 Running database cleanup...');

        if (!$this->command->confirm('This will permanently delete old and orphaned data. Continue?', false)) {
            $this->command->info('Cleanup cancelled.');
            return;
        }

        DB::transaction(function () {
            $this->cleanupExpiredTokens();
            $this->cleanupExpiredOtps();
            $this->cleanupOrphanedRecords();
            $this->cleanupOldPasswordHistory();
            $this->cleanupOldActivityLogs();
            $this->optimizeTables();
        });

        $this->command->info('✅ Database cleanup completed!');
    }

    /**
     * Clean up expired JWT tokens
     */
    private function cleanupExpiredTokens(): void
    {
        $this->command->info('Cleaning up expired JWT tokens...');

        // Delete expired tokens older than 7 days
        $expiredCount = JwtToken::where('expires_at', '<', now()->subDays(7))->count();
        JwtToken::where('expires_at', '<', now()->subDays(7))->delete();

        // Delete revoked tokens older than 30 days
        $revokedCount = JwtToken::where('is_revoked', true)
            ->where('revoked_at', '<', now()->subDays(30))
            ->count();
        JwtToken::where('is_revoked', true)
            ->where('revoked_at', '<', now()->subDays(30))
            ->delete();

        // Delete tokens for non-existent users
        $orphanedTokens = JwtToken::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('users')
                ->whereRaw('users.id = jwt_tokens.user_id');
        })->count();
        
        JwtToken::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('users')
                ->whereRaw('users.id = jwt_tokens.user_id');
        })->delete();

        $this->command->line("   - Deleted {$expiredCount} expired tokens");
        $this->command->line("   - Deleted {$revokedCount} old revoked tokens");
        $this->command->line("   - Deleted {$orphanedTokens} orphaned tokens");
    }

    /**
     * Clean up expired OTP records
     */
    private function cleanupExpiredOtps(): void
    {
        $this->command->info('Cleaning up expired OTP records...');

        // Delete expired OTPs older than 24 hours
        $expiredCount = Otp::where('expires_at', '<', now()->subDay())->count();
        Otp::where('expires_at', '<', now()->subDay())->delete();

        // Delete verified OTPs older than 7 days
        $verifiedCount = Otp::whereNotNull('verified_at')
            ->where('verified_at', '<', now()->subDays(7))
            ->count();
        Otp::whereNotNull('verified_at')
            ->where('verified_at', '<', now()->subDays(7))
            ->delete();

        // Delete OTPs for non-existent users
        $orphanedOtps = Otp::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('users')
                ->whereRaw('users.id = otps.user_id');
        })->count();
        
        Otp::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('users')
                ->whereRaw('users.id = otps.user_id');
        })->delete();

        $this->command->line("   - Deleted {$expiredCount} expired OTPs");
        $this->command->line("   - Deleted {$verifiedCount} old verified OTPs");
        $this->command->line("   - Deleted {$orphanedOtps} orphaned OTPs");
    }

    /**
     * Clean up orphaned records
     */
    private function cleanupOrphanedRecords(): void
    {
        $this->command->info('Cleaning up orphaned records...');

        // Clean up orphaned TOTP secrets
        $orphanedTotpSecrets = UserTotpSecret::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('users')
                ->whereRaw('users.id = user_totp_secrets.user_id');
        })->count();
        
        UserTotpSecret::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('users')
                ->whereRaw('users.id = user_totp_secrets.user_id');
        })->delete();

        // Clean up orphaned recovery codes
        $orphanedRecoveryCodes = UserRecoveryCode::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('users')
                ->whereRaw('users.id = user_recovery_codes.user_id');
        })->count();
        
        UserRecoveryCode::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('users')
                ->whereRaw('users.id = user_recovery_codes.user_id');
        })->delete();

        // Clean up orphaned organization memberships
        $orphanedMemberships = DB::table('organization_members')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('users')
                    ->whereRaw('users.id = organization_members.user_id');
            })
            ->orWhereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('organizations')
                    ->whereRaw('organizations.id = organization_members.organization_id');
            })
            ->count();
        
        DB::table('organization_members')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('users')
                    ->whereRaw('users.id = organization_members.user_id');
            })
            ->orWhereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('organizations')
                    ->whereRaw('organizations.id = organization_members.organization_id');
            })
            ->delete();

        $this->command->line("   - Deleted {$orphanedTotpSecrets} orphaned TOTP secrets");
        $this->command->line("   - Deleted {$orphanedRecoveryCodes} orphaned recovery codes");
        $this->command->line("   - Deleted {$orphanedMemberships} orphaned organization memberships");
    }

    /**
     * Clean up old password history
     */
    private function cleanupOldPasswordHistory(): void
    {
        $this->command->info('Cleaning up old password history...');

        // Keep only the last 10 password changes per user (more than the policy requires)
        $users = User::all();
        $deletedCount = 0;

        foreach ($users as $user) {
            $oldPasswords = PasswordHistory::where('user_id', $user->id)
                ->orderBy('changed_at', 'desc')
                ->skip(10) // Keep the 10 most recent
                ->pluck('id');

            if ($oldPasswords->isNotEmpty()) {
                $deleted = PasswordHistory::whereIn('id', $oldPasswords)->delete();
                $deletedCount += $deleted;
            }
        }

        // Delete password history for non-existent users
        $orphanedPasswords = PasswordHistory::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('users')
                ->whereRaw('users.id = password_histories.user_id');
        })->count();
        
        PasswordHistory::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('users')
                ->whereRaw('users.id = password_histories.user_id');
        })->delete();

        $this->command->line("   - Deleted {$deletedCount} old password history entries");
        $this->command->line("   - Deleted {$orphanedPasswords} orphaned password history entries");
    }

    /**
     * Clean up old activity logs
     */
    private function cleanupOldActivityLogs(): void
    {
        $this->command->info('Cleaning up old activity logs...');

        // Delete activity logs older than 1 year (except security logs)
        $oldGeneralLogs = Activity::where('created_at', '<', now()->subYear())
            ->whereNotIn('log_name', ['security', 'admin', 'audit'])
            ->count();
        
        Activity::where('created_at', '<', now()->subYear())
            ->whereNotIn('log_name', ['security', 'admin', 'audit'])
            ->delete();

        // Delete security logs older than 2 years
        $oldSecurityLogs = Activity::whereIn('log_name', ['security', 'admin', 'audit'])
            ->where('created_at', '<', now()->subYears(2))
            ->count();
        
        Activity::whereIn('log_name', ['security', 'admin', 'audit'])
            ->where('created_at', '<', now()->subYears(2))
            ->delete();

        // Delete activity logs for non-existent users (causer)
        $orphanedCauserLogs = Activity::where('causer_type', User::class)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('users')
                    ->whereRaw('users.id = activity_log.causer_id');
            })
            ->count();
        
        Activity::where('causer_type', User::class)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('users')
                    ->whereRaw('users.id = activity_log.causer_id');
            })
            ->delete();

        // Delete activity logs for non-existent users (subject)
        $orphanedSubjectLogs = Activity::where('subject_type', User::class)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('users')
                    ->whereRaw('users.id = activity_log.subject_id');
            })
            ->count();
        
        Activity::where('subject_type', User::class)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('users')
                    ->whereRaw('users.id = activity_log.subject_id');
            })
            ->delete();

        $this->command->line("   - Deleted {$oldGeneralLogs} old general activity logs");
        $this->command->line("   - Deleted {$oldSecurityLogs} old security activity logs");
        $this->command->line("   - Deleted {$orphanedCauserLogs} orphaned causer activity logs");
        $this->command->line("   - Deleted {$orphanedSubjectLogs} orphaned subject activity logs");
    }

    /**
     * Optimize database tables
     */
    private function optimizeTables(): void
    {
        $this->command->info('Optimizing database tables...');

        $tables = [
            'users',
            'organizations',
            'jwt_tokens',
            'otps',
            'user_totp_secrets',
            'user_recovery_codes',
            'password_histories',
            'activity_log',
            'organization_members',
        ];

        foreach ($tables as $table) {
            try {
                DB::statement("OPTIMIZE TABLE {$table}");
                $this->command->line("   - Optimized table: {$table}");
            } catch (\Exception $e) {
                $this->command->warn("   - Could not optimize table {$table}: " . $e->getMessage());
            }
        }

        // Update table statistics
        try {
            DB::statement('ANALYZE TABLE ' . implode(', ', $tables));
            $this->command->line("   - Updated table statistics");
        } catch (\Exception $e) {
            $this->command->warn("   - Could not update table statistics: " . $e->getMessage());
        }
    }
}
