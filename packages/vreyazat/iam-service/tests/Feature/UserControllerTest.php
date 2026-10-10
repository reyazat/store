<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use IamApi\Database\Seeders\OrgRolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => OrgRolePermissionSeeder::class]);
    }

    #[Test]
    public function test_user_can_register_with_valid_data()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'password' => 'TestPassword123!',
            'password_confirmation' => 'TestPassword123!',
            'send_verification_email' => true,
        ];

        $response = $this->postJson('/api/v1/users/register', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'uuid', 'name', 'email', 'phone', 'status', 'created_at',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ]);
    }

    #[Test]
    public function test_user_cannot_register_with_existing_email()
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $userData = [
            'name' => 'John Doe',
            'email' => 'existing@example.com',
            'password' => 'TestPassword123!',
            'password_confirmation' => 'TestPassword123!',
        ];

        $response = $this->postJson('/api/v1/users/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function test_user_can_view_own_profile()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/v1/users/profile');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'uuid', 'name', 'email', 'status', 'created_at',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'uuid' => $user->uuid,
                    'email' => $user->email,
                ],
            ]);
    }

    #[Test]
    public function test_user_can_update_profile()
    {
        /** @var User $user */
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);
        $token = JWTAuth::fromUser($user);

        $updateData = [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->putJson('/api/v1/users/profile', $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'New Name',
                    'email' => 'new@example.com',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);
    }

    public function test_user_can_change_password()
    {
        $org = Organization::create(['name' => fake()->company()]);
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123!'),
            'organization_id' => $org->id,
        ]);
        $token = JWTAuth::fromUser($user);

        $passwordData = [
            'current_password' => 'OldPassword123!',
            'new_password' => 'NewPassword123!',
            'new_password_confirmation' => 'NewPassword123!',
            'logout_other_devices' => false,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/v1/password/change', $passwordData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Password changed successfully',
            ]);

        // Verify password was changed
        $user->refresh();
        $this->assertTrue(Hash::check('NewPassword123!', $user->password));
    }

    #[Test]
    public function test_user_cannot_change_password_with_wrong_current_password()
    {
        $org = Organization::create(['name' => fake()->company()]);
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123!'),
            'organization_id' => $org->id,
        ]);
        $token = JWTAuth::fromUser($user);

        $passwordData = [
            'current_password' => 'WrongPassword',
            'new_password' => 'NewPassword123!',
            'new_password_confirmation' => 'NewPassword123!',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/v1/password/change', $passwordData);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Current password is incorrect.',
            ]);
    }

    public function test_user_can_delete_account()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->deleteJson('/api/v1/users/account');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'User account deleted successfully',
            ]);

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    public function test_admin_can_view_all_users()
    {
        $org = Organization::create(['name' => fake()->company()]);
        /** @var User $admin */
        $admin = User::factory()->create(['is_super_admin' => true, 'organization_id' => null]);
        $token = JWTAuth::fromUser($admin);

        User::factory()->count(5)->create(['organization_id' => $org->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/v1/users/');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'pagination' => [
                    'current_page', 'last_page', 'per_page', 'total',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    #[Test]
    public function test_regular_user_cannot_view_all_users()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole('member');
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/v1/users/');

        $response->assertStatus(403);
    }

    #[Test]
    public function test_admin_can_view_specific_user()
    {
        $org = Organization::create(['name' => fake()->company()]);
        /** @var User $admin */
        $admin = User::factory()->create(['is_super_admin' => true, 'organization_id' => null]);
        /** @var User $targetUser */
        $targetUser = User::factory()->create(['organization_id' => $org->id]);
        $token = JWTAuth::fromUser($admin);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson("/api/v1/users/{$targetUser->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'uuid' => $targetUser->uuid,
                    'email' => $targetUser->email,
                ],
            ]);
    }

    #[Test]
    public function test_registration_validation_works()
    {
        $response = $this->postJson('/api/v1/users/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    #[Test]
    public function test_password_change_validation_works()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/v1/password/change', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_password', 'new_password', 'new_password_confirmation']);
    }

    #[Test]
    public function test_weak_password_is_rejected()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => '123',
            'password_confirmation' => '123',
        ];

        $response = $this->postJson('/api/v1/users/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }
}
