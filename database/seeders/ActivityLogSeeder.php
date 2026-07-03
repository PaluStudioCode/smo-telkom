<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\CompletionRecord;
use App\Models\OrderEdk;
use App\Models\OrderStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

class ActivityLogSeeder extends Seeder
{
    /**
     * Seed dummy activity logs based on existing records.
     */
    public function run(): void
    {
        $users = User::all();
        $orderStatuses = OrderStatus::all();
        $orderEdks = OrderEdk::all();
        $completionRecords = CompletionRecord::all();

        // Log user login activities
        foreach ($users as $user) {
            $loginCount = rand(3, 8);
            for ($i = 0; $i < $loginCount; $i++) {
                ActivityLog::create([
                    'user_id' => $user->id,
                    'module' => 'auth',
                    'action' => 'login',
                    'record_type' => User::class,
                    'record_id' => $user->id,
                    'old_values' => null,
                    'new_values' => null,
                    'ip_address' => fake()->ipv4(),
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',
                    'created_at' => fake()->dateTimeBetween('-3 months', 'now'),
                ]);
            }
        }

        // Log order status CRUD activities
        foreach ($orderStatuses->take(25) as $order) {
            // Created
            ActivityLog::create([
                'user_id' => $order->created_by,
                'module' => 'order_status',
                'action' => 'created',
                'record_type' => OrderStatus::class,
                'record_id' => $order->id,
                'old_values' => null,
                'new_values' => [
                    'order_number' => $order->order_number,
                    'customer_name' => $order->customer_name,
                    'status' => $order->status,
                ],
                'ip_address' => fake()->ipv4(),
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'created_at' => $order->created_at,
            ]);

            // Some orders updated
            if ($order->updated_by && rand(1, 2) === 1) {
                $oldStatus = 'provisioning';
                ActivityLog::create([
                    'user_id' => $order->updated_by,
                    'module' => 'order_status',
                    'action' => 'updated',
                    'record_type' => OrderStatus::class,
                    'record_id' => $order->id,
                    'old_values' => ['status' => $oldStatus],
                    'new_values' => ['status' => $order->status],
                    'ip_address' => fake()->ipv4(),
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'created_at' => $order->updated_at,
                ]);
            }
        }

        // Log order EDK CRUD activities
        foreach ($orderEdks->take(20) as $edk) {
            ActivityLog::create([
                'user_id' => $edk->created_by,
                'module' => 'order_edk',
                'action' => 'created',
                'record_type' => OrderEdk::class,
                'record_id' => $edk->id,
                'old_values' => null,
                'new_values' => [
                    'edk_reference' => $edk->edk_reference,
                    'customer_name' => $edk->customer_name,
                    'status' => $edk->status,
                ],
                'ip_address' => fake()->ipv4(),
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'created_at' => $edk->created_at,
            ]);
        }

        // Log completion record activities
        foreach ($completionRecords->take(15) as $record) {
            ActivityLog::create([
                'user_id' => $record->created_by,
                'module' => 'completion_record',
                'action' => 'created',
                'record_type' => CompletionRecord::class,
                'record_id' => $record->id,
                'old_values' => null,
                'new_values' => [
                    'completion_number' => $record->completion_number,
                    'approval_status' => $record->approval_status,
                ],
                'ip_address' => fake()->ipv4(),
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'created_at' => $record->created_at,
            ]);

            // Log approval actions
            if ($record->approved_by) {
                ActivityLog::create([
                    'user_id' => $record->approved_by,
                    'module' => 'completion_record',
                    'action' => 'approved',
                    'record_type' => CompletionRecord::class,
                    'record_id' => $record->id,
                    'old_values' => ['approval_status' => 'menunggu_persetujuan'],
                    'new_values' => ['approval_status' => $record->approval_status],
                    'ip_address' => fake()->ipv4(),
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'created_at' => $record->approved_at,
                ]);
            }
        }
    }
}
