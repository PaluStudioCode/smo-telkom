<?php

namespace Tests\Feature;

use App\Models\CompletionRecord;
use App\Models\OrderEdk;
use App\Models\OrderStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_zero_values_when_no_operational_data_exists(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('cards.0.value', 0)
                ->where('cards.1.value', 0)
                ->where('cards.2.value', 0)
                ->where('cards.3.value', 0)
                ->where('cards.4.value', 0)
                ->has('recaps.inputers', 0)
                ->has('recaps.accountManagers', 0));
    }

    public function test_super_admin_dashboard_calculates_metrics_and_recaps(): void
    {
        [$superAdmin, $admin, $otherAdmin, $accountManager, $otherAccountManager] = $this->seedDashboardData();

        $this->actingAs($superAdmin)
            ->get(route('dashboard', ['period_month' => '2026-07']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('cards.0.value', 4)
                ->where('cards.1.value', 1)
                ->where('cards.2.value', 3)
                ->where('cards.3.value', 1)
                ->where('cards.4.value', 1)
                ->where('isSuperAdmin', true)
                ->has('recaps.inputers', 2)
                ->has('recaps.accountManagers', 2)
                ->where('recaps.inputers.0.id', $admin->id)
                ->where('recaps.inputers.0.order_status', 3)
                ->where('recaps.inputers.0.order_edk', 3)
                ->where('recaps.inputers.0.modul_complete', 1)
                ->where('recaps.accountManagers.0.id', $accountManager->id));

        $this->assertNotSame($otherAdmin->id, $admin->id);
        $this->assertNotSame($otherAccountManager->id, $accountManager->id);
    }

    public function test_super_admin_dashboard_filters_by_inputer(): void
    {
        [$superAdmin, $admin] = $this->seedDashboardData();

        $this->actingAs($superAdmin)
            ->get(route('dashboard', [
                'period_month' => '2026-07',
                'inputer_id' => $admin->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('cards.0.value', 3)
                ->where('cards.1.value', 1)
                ->where('cards.2.value', 2)
                ->where('cards.3.value', 1)
                ->where('cards.4.value', 1)
                ->where('filters.inputer_id', (string) $admin->id)
                ->has('recaps.inputers', 1)
                ->where('recaps.inputers.0.id', $admin->id));
    }

    public function test_admin_and_account_manager_dashboard_are_scoped_to_related_data(): void
    {
        [, $admin, $otherAdmin, $accountManager] = $this->seedDashboardData();

        $this->actingAs($admin)
            ->get(route('dashboard', [
                'period_month' => '2026-07',
                'inputer_id' => $otherAdmin->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('cards.0.value', 3)
                ->where('cards.1.value', 1)
                ->where('cards.2.value', 2)
                ->where('cards.3.value', 1)
                ->where('cards.4.value', 1)
                ->where('filters.inputer_id', '')
                ->where('isSuperAdmin', false)
                ->has('recaps.inputers', 0)
                ->has('recaps.accountManagers', 0));

        $this->actingAs($accountManager)
            ->get(route('dashboard', ['period_month' => '2026-07']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('cards.0.value', 3)
                ->where('cards.1.value', 1)
                ->where('cards.2.value', 2)
                ->where('cards.3.value', 1)
                ->where('cards.4.value', 1)
                ->where('isSuperAdmin', false));
    }

    /**
     * @return array{0: User, 1: User, 2: User, 3: User, 4: User}
     */
    private function seedDashboardData(): array
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $admin = User::factory()->adminInputer()->create(['name' => 'Inputer A']);
        $otherAdmin = User::factory()->adminInputer()->create(['name' => 'Inputer B']);
        $accountManager = User::factory()->accountManager()->create(['name' => 'AM A']);
        $otherAccountManager = User::factory()->accountManager()->create(['name' => 'AM B']);

        $this->createOrderStatus($admin, $accountManager, 'OS-001', OrderStatus::STATUS_PENDING_BASO);
        $this->createOrderStatus($admin, $accountManager, 'OS-002', OrderStatus::STATUS_COMPLETE);
        $this->createOrderStatus($admin, $accountManager, 'OS-003', OrderStatus::STATUS_FAILED);
        $this->createOrderStatus($otherAdmin, $otherAccountManager, 'OS-004', OrderStatus::STATUS_PROVISIONING);
        $this->createOrderStatus($admin, $accountManager, 'OS-OLD', OrderStatus::STATUS_PENDING_BASO, '2026-06');

        $this->createOrderEdk($admin, $accountManager, 'EDK-001', OrderEdk::STATUS_COMPLETE);
        $this->createOrderEdk($admin, $accountManager, 'EDK-002', OrderEdk::STATUS_TIDAK_LANJUT);
        $this->createOrderEdk($admin, $accountManager, 'EDK-003', OrderEdk::STATUS_OGP);
        $this->createOrderEdk($otherAdmin, $otherAccountManager, 'EDK-004', OrderEdk::STATUS_COMPLETE);
        $this->createOrderEdk($admin, $accountManager, 'EDK-OLD', OrderEdk::STATUS_COMPLETE, '2026-06');

        CompletionRecord::create([
            'completion_number' => 'COMP-001',
            'order_status_id' => OrderStatus::where('order_number', 'OS-002')->value('id'),
            'order_edk_id' => null,
            'inputer_id' => $admin->id,
            'account_manager_id' => $accountManager->id,
            'approval_status' => CompletionRecord::STATUS_DISETUJUI,
            'completed_at' => '2026-07-03',
            'period_month' => '2026-07',
            'notes' => null,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        return [$superAdmin, $admin, $otherAdmin, $accountManager, $otherAccountManager];
    }

    private function createOrderStatus(User $admin, User $accountManager, string $orderNumber, string $status, string $periodMonth = '2026-07'): void
    {
        OrderStatus::create([
            'order_number' => $orderNumber,
            'customer_name' => 'Pelanggan '.$orderNumber,
            'service_name' => 'Astinet',
            'inputer_id' => $admin->id,
            'account_manager_id' => $accountManager->id,
            'status' => $status,
            'provisioning_stage' => null,
            'period_month' => $periodMonth,
            'source_system' => 'Dashboard NCX',
            'notes' => null,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
    }

    private function createOrderEdk(User $admin, User $accountManager, string $edkReference, string $status, string $periodMonth = '2026-07'): void
    {
        OrderEdk::create([
            'edk_reference' => $edkReference,
            'customer_name' => 'Pelanggan '.$edkReference,
            'inputer_id' => $admin->id,
            'account_manager_id' => $accountManager->id,
            'status' => $status,
            'period_month' => $periodMonth,
            'source_system' => 'Dashboard NCX',
            'notes' => null,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
    }
}
