<?php

namespace IamApi\Database\Seeders;

use IamApi\Models\PasswordHistory;
use IamApi\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PasswordHistorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔐 Seeding password history...');

        DB::transaction(function () {
            // Get users who have been around for a while
            $users = User::where('created_at', '<', now()->subDays(30))
                ->limit(8)
                ->get();

            foreach ($users as $user) {
                // Create password history entries (simulate password changes over time)
                $passwordCount = rand(2, 5); // Each user has changed password 2-5 times
                
                for ($i = 0; $i < $passwordCount; $i++) {
                    $changeDate = $user->created_at->addDays(rand(1, 30 * ($i + 1)));
                    
                    PasswordHistory::create([
                        'user_id' => $user->id,
                        'password_hash' => Hash::make($this->generateOldPassword($i)),
                        'changed_at' => $changeDate,
                        'created_at' => $changeDate,
                        'updated_at' => $changeDate,
                    ]);
                }

                $this->command->line("   - Created {$passwordCount} password history entries for: {$user->name}");
            }

            $historyCount = PasswordHistory::count();
            $recentHistoryCount = PasswordHistory::where('changed_at', '>', now()->subDays(30))->count();
            $oldHistoryCount = PasswordHistory::where('changed_at', '<=', now()->subDays(90))->count();

            $this->command->info("✅ Created password history:");
            $this->command->line("   - Total entries: {$historyCount}");
            $this->command->line("   - Recent changes (30 days): {$recentHistoryCount}");
            $this->command->line("   - Old changes (90+ days): {$oldHistoryCount}");
        });
    }

    /**
     * Generate old password for history
     */
    private function generateOldPassword(int $index): string
    {
        $passwords = [
            'OldPassword1!',
            'PreviousPass2@',
            'FormerSecret3#',
            'LastPassword4$',
            'AncientPass5%',
        ];

        return $passwords[$index] ?? 'GenericOld' . $index . '!';
    }
}
