**Panduan Pengerjaan Tugas:**
- Harap pelajari keseluruhan dokumen dari 01 hingga 04 sebelum memulai.
- Pengerjaan akan dilakukan secara bertahap (per phase).
- Setiap phase dan fiturnya harus berpedoman pada rincian spesifikasi di dokumen terkait.
- Selalu jalankan `send.py` untuk mengirimkan notifikasi setelah tahap pengerjaan diselesaikan.
- setiap phase selesai kamu stop. lalu konfirmasi ke saya untuk lanjut ke phase berikutnya
- Pastikan setiap halaman dan komponen mendukung **responsive design** (desktop, tablet, mobile) sesuai breakpoint Tailwind CSS di `04-ui-ux-design-system.md` §1.13.

# Phase 1: Setup Proyek & Instalasi Dependensi
1. **Instalasi Laravel Breeze & Inertia Vue**
   - Install Laravel Breeze menggunakan stack Vue dengan Inertia.js.
   - Jalankan instalasi dependensi Node.js (`npm install`) dan build frontend (`npm run build`).
2. **Konfigurasi Tailwind CSS**
   - Pastikan Tailwind CSS sudah terpasang (bawaan dari Breeze).
   - Konfigurasikan warna tema Telkom (`telkom-red`, `telkom-black`, dll), font, dan konfigurasi lainnya pada `tailwind.config.js` sesuai dengan panduan di `04-ui-ux-design-system.md` §1.14.2.
3. **Setup Design Token**
   - Siapkan token design di Tailwind config dan/atau CSS variables: color token, typography token, spacing token, border radius token, shadow token, dan status token sesuai `04-ui-ux-design-system.md` §1.15.2.
4. **Instalasi Komponen UI & Ikon**
   - Install `shadcn-vue` dan konfigurasikan struktur foldernya di dalam proyek (misalnya `resources/js/components/ui`).
   - Install library ikon `lucide-vue-next`.
5. **Pengujian & Notifikasi**
   - Pastikan aplikasi dasar sudah bisa berjalan.
   - Jalankan `send.py` untuk mengirim notifikasi penyelesaian tugas.

# Phase 2: Skema Database & Autentikasi Dasar
1. **Migrasi Database (`03-data-access-validation.md`)**
   - Modifikasi tabel `users`: tambahkan kolom `role` (enum: super_admin, admin_inputer, account_manager), `phone`, `profile_photo_path`, `bio`, `is_active` (boolean, default true), dan `deleted_at` (soft delete). Pastikan kolom bawaan Laravel (`email_verified_at`, `remember_token`) tetap ada.
   - Buat migration `order_statuses` dengan kolom dan tipe data sesuai `03-data-access-validation.md` §1.4: `order_number`, `customer_name`, `service_name`, `inputer_id`, `account_manager_id`, `status` (enum: provisioning, pending_baso, pending_billing_approval, complete, failed, cancel_abandoned), `provisioning_stage` (teks bebas opsional), `period_month` (char(7), format YYYY-MM), `source_system` (default Dashboard NCX, hidden), `notes`, `created_by`, `updated_by`, timestamps, dan `deleted_at`.
   - Buat migration `order_edks` dengan kolom dan tipe data sesuai `03-data-access-validation.md` §1.5: `edk_reference`, `customer_name`, `inputer_id`, `account_manager_id`, `status` (enum: lanjut, tidak_lanjut, belum_input, ogp, complete), `period_month`, `source_system` (default Dashboard NCX, hidden), `notes`, `created_by`, `updated_by`, timestamps, dan `deleted_at`.
   - Buat migration `completion_records` dengan kolom dan tipe data sesuai `03-data-access-validation.md` §1.6: `completion_number`, `order_status_id` (nullable), `order_edk_id` (nullable), `inputer_id`, `account_manager_id`, `approval_status` (enum: menunggu_persetujuan, disetujui, tidak_disetujui, revisi), `completed_at`, `approved_by` (nullable), `approved_at` (nullable), `revision_note` (nullable), `period_month`, `notes`, `created_by`, `updated_by`, timestamps, dan `deleted_at`.
   - Buat migration `activity_logs` sesuai `03-data-access-validation.md` §1.7: `user_id`, `module`, `action`, `record_type`, `record_id`, `old_values` (json), `new_values` (json), `ip_address`, `user_agent`, `created_at`.
   - Buat semua foreign key sesuai `03-data-access-validation.md` §1.9.
2. **Index & Constraint (`03-data-access-validation.md` §1.11)**
   - Buat unique index pada kombinasi `order_number` + `period_month` di `order_statuses`.
   - Buat unique index pada kombinasi `edk_reference` + `period_month` di `order_edks`.
   - Buat unique index pada kombinasi `completion_number` + `period_month` di `completion_records`.
   - Buat index pada field `inputer_id`, `account_manager_id`, `status`, `approval_status`, dan `period_month`.
   - Field enum dibatasi hanya pada value yang terdaftar di `03-data-access-validation.md` §1.13.
3. **Pembuatan Seeder & Factory**
   - Buat seeder akun untuk 3 peran utama (Super Admin, Admin/Inputer, Account Manager).
4. **Penyesuaian Autentikasi (Breeze)**
   - Nonaktifkan fitur registrasi publik (register route).
   - Sesuaikan UI form Login agar mengikuti _Auth Layout_ (hanya email & password di tengah layar) berdasarkan `04-ui-ux-design-system.md` §1.1.1 dan §1.2.1. Tidak ada fitur forgot password.
   - Implementasikan fitur show/hide password pada form login sesuai `04-ui-ux-design-system.md` §1.2.1 poin 3.
   - Fokus awal (autofocus) pada field email.
   - Tombol login masuk loading state saat form dikirim.
5. **Pengujian & Notifikasi**
   - Uji coba login dengan masing-masing role.
   - Jalankan `send.py` untuk mengirim notifikasi penyelesaian tugas.

# Phase 3: Layout Utama, Manajemen Pengguna & Otorisasi
1. **Implementasi Layout & Navigasi Utama**
   - Buat komponen `AppLayout` dasar mencakup _Sidebar_ dan _Topbar_ berdasarkan `04-ui-ux-design-system.md` §1.1.2.
   - Sidebar menggunakan **group label "Monitoring"** (label grup, tidak dapat diklik) dengan Order Status, Order EDK, dan Modul Complete sebagai sub-item berindentasi sesuai `04-ui-ux-design-system.md` §1.3.1.
   - Sidebar menyesuaikan hak akses: Super Admin melihat semua menu, Admin/Inputer & Account Manager tidak melihat Manajemen Pengguna sesuai `04-ui-ux-design-system.md` §1.3.2.
   - Profil Pengguna dan Logout diakses melalui menu pengguna di _Topbar_.
   - Sidebar desktop dapat dibuat collapsible. Pada mobile, navigasi menggunakan drawer/sheet sesuai `04-ui-ux-design-system.md` §1.3.3.
2. **Pembuatan Komponen UI Standar (`04-ui-ux-design-system.md` §1.15.3)**
   - Buat komponen reusable: `AuthLayout`, `SidebarNav`, `TopBar`, `PageHeader`, `FilterBar`, `StatCard`, `StatusBadge`, `DataTable` (dengan search, sorting, dan pagination bawaan), `Pagination` (dengan pilihan jumlah baris per halaman), `EmptyState`, `ConfirmDialog`, `FormError`, `UserMenu`.
   - Implementasikan **toast notification** (success, error, warning, info) sesuai `04-ui-ux-design-system.md` §1.9.1.
3. **Setup Otorisasi (Gates / Policies)**
   - Konfigurasikan hak akses spesifik di backend (Policies) berdasarkan role sesuai dokumen `03-data-access-validation.md` §2.5 dan daftar permission di §2.6.
   - Implementasikan scope query berdasarkan peran: Super Admin tanpa batasan, Admin/Inputer berdasarkan `inputer_id`, Account Manager berdasarkan `account_manager_id` sesuai `03-data-access-validation.md` §3.12.
4. **Modul Manajemen Pengguna (Khusus Super Admin)**
   - Buat halaman daftar pengguna dengan tabel (kolom: Nama, Email, Peran, No. Telepon, Status Akun, Dibuat Pada, Aksi sesuai `04-ui-ux-design-system.md` §1.2.6), filter (peran & status), search, sorting, dan pagination.
   - Aksi CRUD melalui _modal/dialog_ sesuai `04-ui-ux-design-system.md` §1.8.3.
   - Implementasikan fitur aktif/nonaktif akun dan soft delete. Pengguna yang memiliki data operasional hanya boleh dinonaktifkan/soft delete, bukan dihapus permanen sesuai `03-data-access-validation.md` §3.3.
   - Validasi form menggunakan Laravel Form Request sesuai `03-data-access-validation.md` §3.3.
5. **Modul Profil Pengguna & Ubah Password**
   - Buat halaman Profil Pengguna terpadu (satu halaman memuat form profil dan form password, dengan aksi simpan terpisah) sesuai `04-ui-ux-design-system.md` §1.2.7.
   - Implementasikan fitur upload foto profil dengan preview, validasi format (jpg/jpeg/png/webp), dan ukuran maksimal 2MB sesuai `03-data-access-validation.md` §3.13.
   - Pengguna **tidak dapat mengubah `role` dan `is_active` miliknya sendiri** — tampilkan sebagai informasi read-only sesuai `03-data-access-validation.md` §3.12 poin 7.
   - Field password memiliki tombol show/hide sesuai `04-ui-ux-design-system.md` §1.2.7.
6. **Pengujian & Notifikasi**
   - Uji batas hak akses _routing_ URL dan batasan tampilan menu.
   - Jalankan `send.py`.

# Phase 4: Modul Monitoring Operasional Utama
1. **Pembangunan Modul Order Status**
   - Buat CRUD Order Status menggunakan _modal/dialog_ sesuai `04-ui-ux-design-system.md` §1.8.3. Jika Super Admin melakukan input data, wajib memilih `inputer_id` dari dropdown.
   - Tampilkan **ringkasan jumlah status** (Provisioning, Pending BASO, Pending Billing Approval, Complete, Failed, Cancel/Abandoned) di atas tabel sesuai `04-ui-ux-design-system.md` §1.2.3.
   - Terapkan filter (Inputer, Account Manager, status, periode) secara auto-submit sesuai `04-ui-ux-design-system.md` §1.2.3.
   - Kolom tabel: Nomor Order, Nama Pelanggan, Layanan, Inputer, Account Manager, Status, Periode, Update Terakhir, Aksi sesuai `04-ui-ux-design-system.md` §1.2.3.
   - Implementasikan search (berdasarkan nomor order/nama pelanggan), sorting, dan pagination.
   - Pembatasan data: Super Admin (semua data + CRUD), Admin/Inputer (CRUD data miliknya berdasarkan `inputer_id`), Account Manager (lihat data miliknya berdasarkan `account_manager_id`, read-only).
   - Validasi form menggunakan Laravel Form Request sesuai `03-data-access-validation.md` §3.4.
   - Implementasikan **aturan transisi status** sesuai `02-product-specification.md` §4.6.1: validasi bahwa perubahan status hanya mengikuti alur yang diizinkan. Hanya Super Admin yang bisa mengubah status akhir (Complete, Failed, Cancel).
2. **Pembangunan Modul Order EDK**
   - Buat CRUD Order EDK menggunakan _modal/dialog_. Jika Super Admin melakukan input data, wajib memilih `inputer_id` dari dropdown.
   - Tampilkan **ringkasan statistik**: Lanjut, Tidak Lanjut, Belum Input, OGP, Complete, % Achievement, dan Sisa Populasi di atas tabel sesuai `04-ui-ux-design-system.md` §1.2.4.
   - Terapkan filter auto-submit (Inputer, Account Manager, status, periode) sesuai `04-ui-ux-design-system.md` §1.2.4.
   - Kolom tabel: Referensi EDK, Nama Pelanggan, Inputer, Account Manager, Status, Periode, Update Terakhir, Aksi sesuai `04-ui-ux-design-system.md` §1.2.4.
   - Implementasikan search, sorting, dan pagination.
   - Terapkan kalkulasi otomatis: `% Achievement = (Jumlah Complete / Total Populasi) x 100` dan `Sisa Populasi = Total Populasi - Complete - Tidak Lanjut` sesuai `02-product-specification.md` §4.3.3. Jika Total Populasi = 0, Achievement = 0%.
   - Validasi form menggunakan Laravel Form Request sesuai `03-data-access-validation.md` §3.5.
   - Implementasikan **aturan transisi status** sesuai `02-product-specification.md` §4.6.2. Hanya Super Admin yang bisa merubah status akhir (Complete, Tidak Lanjut).
3. **Pembangunan Modul Complete**
   - Buat CRUD pencatatan pekerjaan selesai menggunakan _modal/dialog_. Wajib mengisi minimal salah satu `order_status_id` atau `order_edk_id`. Status awal otomatis `menunggu_persetujuan` saat dibuat oleh Admin/Inputer.
   - Tampilkan **ringkasan statistik**: total data Modul Complete, Disetujui, Tidak Disetujui, dan Revisi di atas tabel sesuai `04-ui-ux-design-system.md` §1.2.5.
   - Terapkan filter auto-submit (Account Manager, Inputer, status persetujuan, periode) sesuai `04-ui-ux-design-system.md` §1.2.5.
   - Kolom tabel: Nomor Complete, Inputer, Account Manager, Status Persetujuan, Tanggal Complete, Catatan Revisi, Periode, Update Terakhir, Aksi sesuai `04-ui-ux-design-system.md` §1.2.5.
   - Implementasikan search, sorting, dan pagination.
   - Implementasikan status persetujuan (Menunggu Persetujuan, Disetujui, Tidak Disetujui, Revisi) — aksi approve/reject/revisi khusus Super Admin.
   - Wajibkan pengisian _revision note_ jika status diubah ke Revisi. Simpan `approved_by` dan `approved_at` saat status persetujuan berubah sesuai `03-data-access-validation.md` §3.6.
   - Validasi form menggunakan Laravel Form Request sesuai `03-data-access-validation.md` §3.6.
   - Implementasikan **aturan transisi persetujuan** sesuai `02-product-specification.md` §4.6.3.
4. **Aturan Idempotensi & Concurrency (`02-product-specification.md` §4.7)**
   - Cegah duplikasi data berdasarkan unique index (identifier + period_month).
   - Gunakan database transaction untuk perubahan yang melibatkan lebih dari satu tabel.
   - Terapkan optimistic locking atau deteksi konflik menggunakan `updated_at` untuk mencegah data rusak saat dua pengguna memperbarui data bersamaan.
   - Nonaktifkan tombol submit sementara / tampilkan loading state saat proses penyimpanan berjalan untuk mencegah double submit.
5. **Activity Logs & Validasi UI**
   - Catat setiap perubahan data penting (create/update/delete/approve/reject/revisi) ke dalam tabel `activity_logs` sesuai `03-data-access-validation.md` §1.7.
   - Gunakan indikator _Badge_ dengan warna spesifik (Info, Warning, Success, Danger, Neutral) sesuai `04-ui-ux-design-system.md` §1.16.
   - Terapkan _Empty State_ sesuai `04-ui-ux-design-system.md` §1.11 dan _Loading State_ (skeleton card, skeleton row, button spinner) sesuai `04-ui-ux-design-system.md` §1.10.
   - Tampilkan toast notification setelah aksi berhasil atau gagal sesuai `04-ui-ux-design-system.md` §1.9.1.
   - Tampilkan pesan error standar sesuai `03-data-access-validation.md` §3.9.
6. **Pengujian & Notifikasi**
   - Uji fungsionalitas CRUD, perhitungan status, filter, search, sorting, dan pagination.
   - Uji aturan transisi status dan validasi form.
   - Uji pembatasan data dan hak akses per role.
   - Uji skenario concurrency dan double submit.
   - Jalankan `send.py`.

# Phase 5: Dashboard & Visualisasi Data
1. **Pengembangan UI Dashboard**
   - Tampilkan kartu statistik: Total Order, Pending BASO, Complete, Failed, dan Sisa Populasi sesuai `04-ui-ux-design-system.md` §1.7.1.
   - Setiap kartu memiliki: label metrik, nilai utama, konteks periode, icon pendukung, dan indikator warna sesuai jenis metrik.
2. **Logika Perhitungan Data & Hak Akses**
   - Hitung statistik dari database sesuai batasan akses role pengguna yang sedang login sesuai `02-product-specification.md` §4.3.1:
     - Super Admin melihat seluruh data.
     - Admin/Inputer melihat data dengan `inputer_id` miliknya.
     - Account Manager melihat data dengan `account_manager_id` miliknya.
   - Dashboard tetap tampil dengan nilai 0 apabila belum ada data.
3. **Integrasi Grafik & Filter**
   - Buat "Grafik Monitoring Operasional" menggunakan:
     - **Donut chart** untuk komposisi status.
     - **Bar chart** untuk rekapitulasi berdasarkan Inputer/Account Manager.
     - **Line chart** (opsional) untuk tren per periode jika tersedia data historis.
   - Sesuai `04-ui-ux-design-system.md` §1.7.2.
   - Implementasikan filter periode (Month picker format YYYY-MM) yang merefresh seluruh dashboard secara dinamis. Semua filter menggunakan mekanisme auto-submit.
   - Untuk Super Admin, tambahkan juga **filter Inputer dan filter Account Manager** sesuai `04-ui-ux-design-system.md` §1.17.1.
   - Grafik tetap menampilkan empty state apabila data kosong.
4. **Tabel Rekapitulasi Kinerja**
   - Tampilkan tabel rekapitulasi berdasarkan Inputer dan Account Manager sesuai `02-product-specification.md` §2.1.2 dan §4.3.1.
   - Tabel rekapitulasi ini hanya ditampilkan untuk Super Admin.
5. **Pengujian & Notifikasi**
   - Uji perhitungan dashboard dengan berbagai skenario data dan filter.
   - Uji tampilan dashboard per role.
   - Jalankan `send.py`.
