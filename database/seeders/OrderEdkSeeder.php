<?php

namespace Database\Seeders;

use App\Models\OrderEdk;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderEdkSeeder extends Seeder
{
    /**
     * Seed dummy order EDK records across multiple months.
     */
    public function run(): void
    {
        $inputers = User::where('role', User::ROLE_ADMIN_INPUTER)->pluck('id')->toArray();
        $accountManagers = User::where('role', User::ROLE_ACCOUNT_MANAGER)->pluck('id')->toArray();
        $superAdmin = User::where('role', User::ROLE_SUPER_ADMIN)->first();

        $customers = [
            'Dinas Sosial Kota Palu',
            'Kantor Kemenag Kab. Donggala',
            'DPRD Provinsi Sulawesi Tengah',
            'Dinas Perindustrian Kota Palu',
            'RSUD Mokopido Toli-Toli',
            'Dinas Pertanian Kab. Sigi',
            'Kantor BPN Kota Palu',
            'Dinas Koperasi & UKM Sulteng',
            'Bank Sulteng Cabang Utama',
            'PT Pelindo Regional IV Pantoloan',
            'Kantor Bea Cukai Palu',
            'Dinas Lingkungan Hidup Kota Palu',
            'Kantor BMKG Mutiara Palu',
            'Dinas Pariwisata Provinsi Sulteng',
            'Kantor SAR Palu',
        ];

        $sourceSystems = [
            'Dashboard NCX',
            'MyTens',
            'Manual Input',
        ];

        $statuses = [
            OrderEdk::STATUS_LANJUT,
            OrderEdk::STATUS_TIDAK_LANJUT,
            OrderEdk::STATUS_BELUM_INPUT,
            OrderEdk::STATUS_OGP,
            OrderEdk::STATUS_COMPLETE,
        ];

        $periods = ['2026-04', '2026-05', '2026-06', '2026-07'];

        $edkCounter = 1;

        foreach ($periods as $period) {
            // Generate 6-12 EDK records per month
            $count = rand(6, 12);

            for ($i = 0; $i < $count; $i++) {
                $inputerId = $inputers[array_rand($inputers)];
                $amId = $accountManagers[array_rand($accountManagers)];
                $status = $statuses[array_rand($statuses)];

                // Weight towards belum_input and lanjut for current month
                if ($period === '2026-07') {
                    $activeStatuses = [
                        OrderEdk::STATUS_BELUM_INPUT,
                        OrderEdk::STATUS_BELUM_INPUT,
                        OrderEdk::STATUS_LANJUT,
                        OrderEdk::STATUS_LANJUT,
                        OrderEdk::STATUS_OGP,
                    ];
                    $status = $activeStatuses[array_rand($activeStatuses)];
                }

                $edkRef = sprintf('EDK-%s-%05d', str_replace('-', '', $period), $edkCounter);
                $edkCounter++;

                $createdAt = fake()->dateTimeBetween(
                    $period . '-01',
                    $period . '-28'
                );

                $notes = null;
                if (rand(1, 3) === 1) {
                    $noteOptions = [
                        'Pelanggan sudah dikonfirmasi, siap untuk proses lanjut.',
                        'Menunggu kelengkapan dokumen dari pelanggan.',
                        'Pelanggan tidak merespon setelah 3x follow-up.',
                        'OGP diajukan karena area belum tercover jaringan.',
                        'EDK sudah diverifikasi dan siap untuk provisioning.',
                        'Pelanggan meminta penawaran harga ulang.',
                        'Lokasi pelanggan di luar jangkauan FO eksisting.',
                    ];
                    $notes = $noteOptions[array_rand($noteOptions)];
                }

                OrderEdk::create([
                    'edk_reference' => $edkRef,
                    'customer_name' => $customers[array_rand($customers)],
                    'inputer_id' => $inputerId,
                    'account_manager_id' => $amId,
                    'status' => $status,
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
