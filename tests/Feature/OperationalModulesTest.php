<?php

namespace Tests\Feature;

use App\Models\CompletionRecord;
use App\Models\OrderEdk;
use App\Models\OrderStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OperationalModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_order_status_and_activity_log_is_written(): void
    {
        $admin = User::factory()->adminInputer()->create();
        $accountManager = User::factory()->accountManager()->create();

        $this->actingAs($admin)
            ->post(route('order-statuses.store'), $this->orderStatusPayload($admin, $accountManager))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $record = OrderStatus::where('order_number', 'OS-001')->firstOrFail();

        $this->assertSame($admin->id, $record->inputer_id);
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'order_status',
            'action' => 'create',
            'record_id' => $record->id,
        ]);
    }

    public function test_account_manager_can_not_create_order_status(): void
    {
        $admin = User::factory()->adminInputer()->create();
        $accountManager = User::factory()->accountManager()->create();

        $this->actingAs($accountManager)
            ->post(route('order-statuses.store'), $this->orderStatusPayload($admin, $accountManager))
            ->assertForbidden();
    }

    public function test_admin_can_not_set_order_status_to_final_status(): void
    {
        $admin = User::factory()->adminInputer()->create();
        $accountManager = User::factory()->accountManager()->create();

        $this->actingAs($admin)
            ->from(route('order-statuses.index'))
            ->post(route('order-statuses.store'), $this->orderStatusPayload($admin, $accountManager, [
                'order_number' => 'OS-FINAL',
                'status' => OrderStatus::STATUS_COMPLETE,
            ]))
            ->assertRedirect(route('order-statuses.index'))
            ->assertSessionHasErrors('status');

        $this->assertDatabaseMissing('order_statuses', [
            'order_number' => 'OS-FINAL',
        ]);
    }

    public function test_order_status_update_rejects_stale_updated_at_token(): void
    {
        $admin = User::factory()->adminInputer()->create();
        $accountManager = User::factory()->accountManager()->create();
        $record = $this->createOrderStatus($admin, $accountManager);

        $this->actingAs($admin)
            ->from(route('order-statuses.index'))
            ->put(route('order-statuses.update', $record), $this->orderStatusPayload($admin, $accountManager, [
                'order_number' => $record->order_number,
                'status' => OrderStatus::STATUS_PENDING_BASO,
                'updated_at' => now()->subMinute()->format('Y-m-d H:i:s'),
            ]))
            ->assertRedirect(route('order-statuses.index'))
            ->assertSessionHasErrors('updated_at');
    }

    public function test_operational_index_is_scoped_by_role(): void
    {
        $admin = User::factory()->adminInputer()->create();
        $otherAdmin = User::factory()->adminInputer()->create();
        $accountManager = User::factory()->accountManager()->create();
        $otherAccountManager = User::factory()->accountManager()->create();

        $this->createOrderStatus($admin, $accountManager, ['order_number' => 'OS-OWN']);
        $this->createOrderStatus($otherAdmin, $otherAccountManager, ['order_number' => 'OS-OTHER']);

        $this->actingAs($admin)
            ->get(route('order-statuses.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OrderStatuses/Index')
                ->has('orderStatuses.data', 1)
                ->where('orderStatuses.data.0.order_number', 'OS-OWN'));

        $this->actingAs($accountManager)
            ->get(route('order-statuses.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OrderStatuses/Index')
                ->has('orderStatuses.data', 1)
                ->where('orderStatuses.data.0.order_number', 'OS-OWN'));
    }

    public function test_order_edk_admin_can_not_transition_to_final_status(): void
    {
        $admin = User::factory()->adminInputer()->create();
        $accountManager = User::factory()->accountManager()->create();
        $record = $this->createOrderEdk($admin, $accountManager, ['status' => OrderEdk::STATUS_OGP]);

        $this->actingAs($admin)
            ->from(route('order-edks.index'))
            ->put(route('order-edks.update', $record), $this->orderEdkPayload($admin, $accountManager, [
                'edk_reference' => $record->edk_reference,
                'status' => OrderEdk::STATUS_COMPLETE,
                'updated_at' => $record->updated_at->format('Y-m-d H:i:s'),
            ]))
            ->assertRedirect(route('order-edks.index'))
            ->assertSessionHasErrors('status');
    }

    public function test_completion_record_requires_at_least_one_linked_order(): void
    {
        $admin = User::factory()->adminInputer()->create();
        $accountManager = User::factory()->accountManager()->create();

        $this->actingAs($admin)
            ->from(route('completion-records.index'))
            ->post(route('completion-records.store'), $this->completionPayload($admin, $accountManager))
            ->assertRedirect(route('completion-records.index'))
            ->assertSessionHasErrors('order_status_id');
    }

    public function test_super_admin_can_request_revision_with_note_and_activity_log(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $admin = User::factory()->adminInputer()->create();
        $accountManager = User::factory()->accountManager()->create();
        $orderStatus = $this->createOrderStatus($admin, $accountManager);
        $completion = $this->createCompletionRecord($admin, $accountManager, [
            'order_status_id' => $orderStatus->id,
        ]);

        $this->actingAs($superAdmin)
            ->from(route('completion-records.index'))
            ->patch(route('completion-records.approval', $completion), [
                'approval_status' => CompletionRecord::STATUS_REVISI,
                'revision_note' => '',
                'updated_at' => $completion->updated_at->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect(route('completion-records.index'))
            ->assertSessionHasErrors('revision_note');

        $this->actingAs($superAdmin)
            ->patch(route('completion-records.approval', $completion), [
                'approval_status' => CompletionRecord::STATUS_REVISI,
                'revision_note' => 'Lengkapi dokumen pendukung.',
                'updated_at' => $completion->fresh()->updated_at->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $completion->refresh();

        $this->assertSame(CompletionRecord::STATUS_REVISI, $completion->approval_status);
        $this->assertSame($superAdmin->id, $completion->approved_by);
        $this->assertNotNull($completion->approved_at);
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'complete',
            'action' => 'request_revision',
            'record_id' => $completion->id,
        ]);
    }

    public function test_duplicate_order_identifier_in_same_period_is_rejected(): void
    {
        $admin = User::factory()->adminInputer()->create();
        $accountManager = User::factory()->accountManager()->create();

        $this->createOrderStatus($admin, $accountManager, [
            'order_number' => 'OS-DUP',
            'period_month' => '2026-07',
        ]);

        $this->actingAs($admin)
            ->from(route('order-statuses.index'))
            ->post(route('order-statuses.store'), $this->orderStatusPayload($admin, $accountManager, [
                'order_number' => 'OS-DUP',
                'period_month' => '2026-07',
            ]))
            ->assertRedirect(route('order-statuses.index'))
            ->assertSessionHasErrors('order_number');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function orderStatusPayload(User $admin, User $accountManager, array $overrides = []): array
    {
        return [
            'order_number' => 'OS-001',
            'customer_name' => 'Pemda Palu',
            'service_name' => 'Astinet',
            'inputer_id' => $admin->id,
            'account_manager_id' => $accountManager->id,
            'status' => OrderStatus::STATUS_PROVISIONING,
            'provisioning_stage' => 'Survey',
            'period_month' => '2026-07',
            'source_system' => 'Dashboard NCX',
            'notes' => 'Catatan order status.',
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createOrderStatus(User $admin, User $accountManager, array $overrides = []): OrderStatus
    {
        return OrderStatus::create([
            ...$this->orderStatusPayload($admin, $accountManager),
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
            ...$overrides,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function orderEdkPayload(User $admin, User $accountManager, array $overrides = []): array
    {
        return [
            'edk_reference' => 'EDK-001',
            'customer_name' => 'Pemda Palu',
            'inputer_id' => $admin->id,
            'account_manager_id' => $accountManager->id,
            'status' => OrderEdk::STATUS_BELUM_INPUT,
            'period_month' => '2026-07',
            'source_system' => 'Dashboard NCX',
            'notes' => 'Catatan order edk.',
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createOrderEdk(User $admin, User $accountManager, array $overrides = []): OrderEdk
    {
        return OrderEdk::create([
            ...$this->orderEdkPayload($admin, $accountManager),
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
            ...$overrides,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function completionPayload(User $admin, User $accountManager, array $overrides = []): array
    {
        return [
            'completion_number' => 'COMP-001',
            'order_status_id' => null,
            'order_edk_id' => null,
            'inputer_id' => $admin->id,
            'account_manager_id' => $accountManager->id,
            'approval_status' => CompletionRecord::STATUS_MENUNGGU_PERSETUJUAN,
            'completed_at' => '2026-07-03',
            'revision_note' => '',
            'period_month' => '2026-07',
            'notes' => 'Catatan complete.',
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createCompletionRecord(User $admin, User $accountManager, array $overrides = []): CompletionRecord
    {
        return CompletionRecord::create([
            ...$this->completionPayload($admin, $accountManager),
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
            ...$overrides,
        ]);
    }
}
