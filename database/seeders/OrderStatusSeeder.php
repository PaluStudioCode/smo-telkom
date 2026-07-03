<?php

namespace Database\Seeders;

use App\Models\OrderStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderStatusSeeder extends Seeder
{
    /**
     * Seed dummy order status records across multiple months.
     */
    public function run(): void
    {
        $inputers = User::where('role', User::ROLE_ADMIN_INPUTER)->pluck('id')->toArray();
        $accountManagers = User::where('role', User::ROLE_ACCOUNT_MANAGER)->pluck('id')->toArray();
        $superAdmin = User::where('role', User::ROLE_SUPER_ADMIN)->first();

        $services = [
            'Astinet',
            'IP Transit',
            'Metro Ethernet',
            'VPN IP',
            'Dedicated Internet',
            'Cloud Hosting',
            'WiFi Manage Service',
            'SD-WAN',
            'IoT Smart City',
            'Data Center Colocation',
        ];

        $customers = [
            'Dinas Kominfo Kota Palu',
            'BPKAD Provinsi Sulawesi Tengah',
            'Kantor Gubernur Sulteng',
            'RSUD Undata Palu',
            'Universitas Tadulako',
            'Dinas Pendidikan Kota Palu',
            'Kantor Wilayah Kementerian Agama',
            'BPJS Kesehatan Cabang Palu',
            'PLN UP3 Palu',
            'Kantor Imigrasi Kelas II Palu',
            'Dinas Kesehatan Provinsi Sulteng',
            'Pengadilan Negeri Palu',
            'Kantor Pajak Pratama Palu',
            'BPS Provinsi Sulawesi Tengah',
            'Dinas PUPR Kota Palu',
            'Kejaksaan Negeri Palu',
            'Polda Sulawesi Tengah',
            'Dinas Perhubungan Kota Palu',
            'BAPPEDA Provinsi Sulteng',
            'Kantor BPOM Palu',
        ];

        $provisioningStages = [
            'Survei Lokasi',
            'Instalasi Perangkat',
            'Konfigurasi Jaringan',
            'Penarikan Kabel FO',
            'Testing & Commissioning',
            'Menunggu Perangkat',
            'Aktivasi Port',
            null,
        ];

        $sourceSystems = [
            'Dashboard NCX',
            'MyTens',
            'Manual Input',
        ];

        $statuses = [
            OrderStatus::STATUS_PROVISIONING,
            OrderStatus::STATUS_PENDING_BASO,
            OrderStatus::STATUS_PENDING_BILLING_APPROVAL,
            OrderStatus::STATUS_COMPLETE,
            OrderStatus::STATUS_FAILED,
            OrderStatus::STATUS_CANCEL_ABANDONED,
        ];

        $periods = ['2026-04', '2026-05', '2026-06', '2026-07'];

        $orderCounter = 1;

        foreach ($periods as $period) {
            // Generate 8-15 orders per month
            $count = rand(8, 15);

            for ($i = 0; $i < $count; $i++) {
                $inputerId = $inputers[array_rand($inputers)];
                $amId = $accountManagers[array_rand($accountManagers)];
                $status = $statuses[array_rand($statuses)];

                // Weight towards active statuses for current month
                if ($period === '2026-07') {
                    $activeStatuses = [
                        OrderStatus::STATUS_PROVISIONING,
                        OrderStatus::STATUS_PROVISIONING,
                        OrderStatus::STATUS_PENDING_BASO,
                        OrderStatus::STATUS_PENDING_BILLING_APPROVAL,
                        OrderStatus::STATUS_COMPLETE,
                    ];
                    $status = $activeStatuses[array_rand($activeStatuses)];
                }

                $orderNumber = sprintf('ORD-%s-%05d', str_replace('-', '', $period), $orderCounter);
                $orderCounter++;

                $provStage = null;
                if ($status === OrderStatus::STATUS_PROVISIONING) {
                    $provStage = $provisioningStages[array_rand($provisioningStages)];
                }

                $createdAt = fake()->dateTimeBetween(
                    $period . '-01',
                    $period . '-28'
                );

                $notes = null;
                if (rand(1, 4) === 1) {
                    $noteOptions = [
                        'Menunggu konfirmasi dari pelanggan untuk jadwal instalasi.',
                        'Sudah dilakukan survei lokasi, lokasi siap untuk instalasi.',
                        'Perangkat ONT belum tersedia di gudang.',
                        'Pelanggan meminta reschedule instalasi minggu depan.',
                        'Proses aktivasi port di OLT sudah selesai.',
                        'Kendala akses lokasi, perlu koordinasi ulang.',
                        'Dokumen kontrak sudah lengkap dan dikirim ke billing.',
                        'Pelanggan membatalkan order karena perubahan anggaran.',
                    ];
                    $notes = $noteOptions[array_rand($noteOptions)];
                }

                OrderStatus::create([
                    'order_number' => $orderNumber,
                    'customer_name' => $customers[array_rand($customers)],
                    'service_name' => $services[array_rand($services)],
                    'inputer_id' => $inputerId,
                    'account_manager_id' => $amId,
                    'status' => $status,
                    'provisioning_stage' => $provStage,
                    'period_month' => $period,
                    'source_system' => $sourceSystems[array_rand($sourceSystems)],
                    'notes' => $notes,
                    'created_by' => $inputerId,
                    'updated_by' => rand(0, 1) ? $superAdmin->id : null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }
        }
    }
}
