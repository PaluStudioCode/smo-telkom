<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_user_management(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($superAdmin)->get(route('users.index'));

        $response->assertOk();
    }

    public function test_non_super_admin_can_not_view_user_management(): void
    {
        $adminInputer = User::factory()->adminInputer()->create();
        $accountManager = User::factory()->accountManager()->create();

        $this->actingAs($adminInputer)->get(route('users.index'))->assertForbidden();
        $this->actingAs($accountManager)->get(route('users.index'))->assertForbidden();
    }

    public function test_all_roles_can_view_monitoring_placeholders(): void
    {
        $users = [
            User::factory()->superAdmin()->create(),
            User::factory()->adminInputer()->create(),
            User::factory()->accountManager()->create(),
        ];

        foreach ($users as $user) {
            $this->actingAs($user)->get(route('order-statuses.index'))->assertOk();
            $this->actingAs($user)->get(route('order-edks.index'))->assertOk();
            $this->actingAs($user)->get(route('completion-records.index'))->assertOk();
        }
    }

    public function test_super_admin_can_create_update_toggle_and_soft_delete_user(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->post(route('users.store'), [
                'name' => 'Inputer Baru',
                'email' => 'inputer.baru@smo.test',
                'password' => 'password',
                'role' => User::ROLE_ADMIN_INPUTER,
                'phone' => '081234567890',
                'bio' => 'Pengguna uji.',
                'is_active' => true,
            ])
            ->assertRedirect();

        $managedUser = User::where('email', 'inputer.baru@smo.test')->firstOrFail();

        $this->assertSame(User::ROLE_ADMIN_INPUTER, $managedUser->role);

        $this->actingAs($superAdmin)
            ->put(route('users.update', $managedUser), [
                'name' => 'Inputer Diperbarui',
                'email' => 'inputer.update@smo.test',
                'password' => '',
                'role' => User::ROLE_ACCOUNT_MANAGER,
                'phone' => '081234567891',
                'bio' => 'Pengguna diperbarui.',
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertSame(User::ROLE_ACCOUNT_MANAGER, $managedUser->refresh()->role);

        $this->actingAs($superAdmin)
            ->patch(route('users.toggle-active', $managedUser), ['is_active' => false])
            ->assertRedirect();

        $this->assertFalse($managedUser->refresh()->is_active);

        $this->actingAs($superAdmin)
            ->delete(route('users.destroy', $managedUser))
            ->assertRedirect();

        $this->assertSoftDeleted($managedUser);
    }

    public function test_super_admin_can_not_delete_or_deactivate_self(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->patch(route('users.toggle-active', $superAdmin), ['is_active' => false])
            ->assertRedirect();

        $this->assertTrue($superAdmin->refresh()->is_active);

        $this->actingAs($superAdmin)
            ->delete(route('users.destroy', $superAdmin))
            ->assertRedirect();

        $this->assertNotSoftDeleted($superAdmin);
    }
}
