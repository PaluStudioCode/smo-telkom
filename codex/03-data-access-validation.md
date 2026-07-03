# 1. Skema Database

## 1.1 Prinsip Desain Data
1. Database utama menggunakan MySQL.
2. Setiap data operasional harus memiliki relasi ke Inputer dan Account Manager apabila data tersebut digunakan untuk monitoring kinerja.
3. Data dari Dashboard NCX diinput atau diperbarui oleh Admin/Inputer ke dalam sistem sebagai data monitoring internal.
4. Data operasional harus memiliki periode agar dapat difilter berdasarkan waktu.
5. Data yang berstatus akhir tetap dapat dikoreksi oleh pengguna berwenang, tetapi perubahan penting perlu tercatat pada log aktivitas.
6. Tabel operasional menggunakan timestamp `created_at` dan `updated_at`.
7. Tabel utama disarankan menggunakan `deleted_at` untuk soft delete agar histori monitoring tidak hilang secara permanen.
8. Status menggunakan enum atau value terkontrol agar data konsisten dan mudah dihitung pada dashboard.

## 1.2 Daftar Tabel
| Tabel | Fungsi |
| --- | --- |
| `users` | Menyimpan akun, peran, status akun, dan profil dasar pengguna. |
| `order_statuses` | Menyimpan data monitoring Order Status. |
| `order_edks` | Menyimpan data monitoring Order EDK. |
| `completion_records` | Menyimpan data pekerjaan yang masuk Modul Complete beserta status persetujuannya. |
| `activity_logs` | Menyimpan riwayat aktivitas penting pengguna di sistem. |
| `password_reset_tokens` | Tabel bawaan Laravel untuk proses reset password apabila digunakan. |
| `sessions` | Tabel bawaan Laravel untuk penyimpanan sesi apabila session driver menggunakan database. |

## 1.3 Struktur Tabel `users`
| Field | Tipe Data | Keterangan |
| --- | --- | --- |
| `id` | bigint unsigned | Primary key. |
| `name` | varchar(150) | Nama pengguna. |
| `email` | varchar(150) | Email login, harus unik. |
| `password` | varchar(255) | Password dalam bentuk hash. |
| `role` | enum | Peran pengguna: `super_admin`, `admin_inputer`, `account_manager`. |
| `phone` | varchar(30), nullable | Nomor telepon pengguna. |
| `profile_photo_path` | varchar(255), nullable | Path foto profil. |
| `bio` | text, nullable | Biodata singkat pengguna. |
| `is_active` | boolean | Status aktif akun. Default `true`. |
| `email_verified_at` | timestamp, nullable | Waktu verifikasi email apabila digunakan. |
| `remember_token` | varchar(100), nullable | Token remember me Laravel. |
| `created_at` | timestamp, nullable | Waktu pembuatan akun. |
| `updated_at` | timestamp, nullable | Waktu pembaruan akun. |
| `deleted_at` | timestamp, nullable | Waktu soft delete akun. |

## 1.4 Struktur Tabel `order_statuses`

| Field | Tipe Data | Keterangan |
| --- | --- | --- |
| `id` | bigint unsigned | Primary key. |
| `order_number` | varchar(100) | Nomor order atau identifier dari sumber data. |
| `customer_name` | varchar(150), nullable | Nama pelanggan atau instansi. |
| `service_name` | varchar(150), nullable | Nama layanan apabila tersedia. |
| `inputer_id` | bigint unsigned | Foreign key ke `users.id` untuk Admin/Inputer. |
| `account_manager_id` | bigint unsigned | Foreign key ke `users.id` untuk Account Manager. |
| `status` | enum | Status order. |
| `provisioning_stage` | varchar(150), nullable | Tahapan provisioning berupa teks bebas opsional. |
| `period_month` | char(7) | Periode data dalam format `YYYY-MM`. |
| `source_system` | varchar(100) | Sumber data, default `Dashboard NCX` (hidden/read-only). |
| `notes` | text, nullable | Catatan tambahan. |
| `created_by` | bigint unsigned | Pengguna yang membuat data. |
| `updated_by` | bigint unsigned, nullable | Pengguna terakhir yang memperbarui data. |
| `created_at` | timestamp, nullable | Waktu pembuatan data. |
| `updated_at` | timestamp, nullable | Waktu pembaruan data. |
| `deleted_at` | timestamp, nullable | Waktu soft delete data. |

## 1.5 Struktur Tabel `order_edks`

| Field | Tipe Data | Keterangan |
| --- | --- | --- |
| `id` | bigint unsigned | Primary key. |
| `edk_reference` | varchar(100) | Nomor referensi EDK atau identifier sumber data. |
| `customer_name` | varchar(150), nullable | Nama pelanggan atau instansi. |
| `inputer_id` | bigint unsigned | Foreign key ke `users.id` untuk Admin/Inputer. |
| `account_manager_id` | bigint unsigned | Foreign key ke `users.id` untuk Account Manager. |
| `status` | enum | Status EDK. |
| `period_month` | char(7) | Periode data dalam format `YYYY-MM`. |
| `source_system` | varchar(100) | Sumber data, default `Dashboard NCX` (hidden/read-only). |
| `notes` | text, nullable | Catatan tambahan. |
| `created_by` | bigint unsigned | Pengguna yang membuat data. |
| `updated_by` | bigint unsigned, nullable | Pengguna terakhir yang memperbarui data. |
| `created_at` | timestamp, nullable | Waktu pembuatan data. |
| `updated_at` | timestamp, nullable | Waktu pembaruan data. |
| `deleted_at` | timestamp, nullable | Waktu soft delete data. |

## 1.6 Struktur Tabel `completion_records`

| Field | Tipe Data | Keterangan |
| --- | --- | --- |
| `id` | bigint unsigned | Primary key. |
| `completion_number` | varchar(100) | Nomor complete atau identifier penyelesaian. |
| `order_status_id` | bigint unsigned, nullable | Relasi opsional ke `order_statuses.id`. |
| `order_edk_id` | bigint unsigned, nullable | Relasi opsional ke `order_edks.id`. |
| `inputer_id` | bigint unsigned | Foreign key ke `users.id` untuk Admin/Inputer. |
| `account_manager_id` | bigint unsigned | Foreign key ke `users.id` untuk Account Manager. |
| `approval_status` | enum | Status persetujuan Modul Complete. |
| `completed_at` | date, nullable | Tanggal pekerjaan dinyatakan selesai. |
| `approved_by` | bigint unsigned, nullable | Pengguna yang menyetujui atau menolak data. |
| `approved_at` | timestamp, nullable | Waktu keputusan persetujuan. |
| `revision_note` | text, nullable | Catatan revisi apabila status `revisi`. |
| `period_month` | char(7) | Periode data dalam format `YYYY-MM`. |
| `notes` | text, nullable | Catatan tambahan. |
| `created_by` | bigint unsigned | Pengguna yang membuat data. |
| `updated_by` | bigint unsigned, nullable | Pengguna terakhir yang memperbarui data. |
| `created_at` | timestamp, nullable | Waktu pembuatan data. |
| `updated_at` | timestamp, nullable | Waktu pembaruan data. |
| `deleted_at` | timestamp, nullable | Waktu soft delete data. |

## 1.7 Struktur Tabel `activity_logs`

| Field | Tipe Data | Keterangan |
| --- | --- | --- |
| `id` | bigint unsigned | Primary key. |
| `user_id` | bigint unsigned, nullable | Pengguna yang melakukan aktivitas. |
| `module` | varchar(100) | Modul terkait, misalnya `order_status`, `order_edk`, `complete`, atau `user_management`. |
| `action` | varchar(100) | Jenis aksi, misalnya `create`, `update`, `delete`, `approve`, atau `reject`. |
| `record_type` | varchar(150), nullable | Nama model atau tabel yang berubah. |
| `record_id` | bigint unsigned, nullable | ID data yang berubah. |
| `old_values` | json, nullable | Nilai sebelum perubahan. |
| `new_values` | json, nullable | Nilai setelah perubahan. |
| `ip_address` | varchar(45), nullable | Alamat IP pengguna. |
| `user_agent` | text, nullable | Informasi perangkat atau browser. |
| `created_at` | timestamp, nullable | Waktu aktivitas dicatat. |

Catatan: Log aktivitas hanya disimpan di database untuk audit backend, tidak ada antarmuka UI khusus untuk fase ini.

## 1.8 Primary Key

1. Setiap tabel utama menggunakan field `id` bertipe `bigint unsigned` sebagai primary key.
2. Primary key menggunakan auto increment.
3. Tabel `password_reset_tokens` menggunakan `email` sebagai identifier sesuai default Laravel.

## 1.9 Foreign Key
1. `order_statuses.inputer_id` mengacu ke `users.id`.
2. `order_statuses.account_manager_id` mengacu ke `users.id`.
3. `order_statuses.created_by` mengacu ke `users.id`.
4. `order_statuses.updated_by` mengacu ke `users.id`.
5. `order_edks.inputer_id` mengacu ke `users.id`.
6. `order_edks.account_manager_id` mengacu ke `users.id`.
7. `order_edks.created_by` mengacu ke `users.id`.
8. `order_edks.updated_by` mengacu ke `users.id`.
9. `completion_records.order_status_id` mengacu ke `order_statuses.id`.
10. `completion_records.order_edk_id` mengacu ke `order_edks.id`.
11. `completion_records.inputer_id` mengacu ke `users.id`.
12. `completion_records.account_manager_id` mengacu ke `users.id`.
13. `completion_records.approved_by` mengacu ke `users.id`.
14. `completion_records.created_by` mengacu ke `users.id`.
15. `completion_records.updated_by` mengacu ke `users.id`.
16. `activity_logs.user_id` mengacu ke `users.id`.

## 1.10 Relasi Antar Data / Model
1. Satu pengguna dengan peran Admin/Inputer dapat memiliki banyak data `order_statuses`, `order_edks`, dan `completion_records` sebagai Inputer.
2. Satu pengguna dengan peran Account Manager dapat memiliki banyak data `order_statuses`, `order_edks`, dan `completion_records` sebagai Account Manager.
3. Satu data `completion_records` dapat terhubung ke satu data `order_statuses` atau satu data `order_edks`.
4. Satu pengguna dapat memiliki banyak `activity_logs`.
5. Super Admin tidak harus memiliki data operasional, tetapi dapat melihat dan mengelola seluruh data sesuai hak akses.

## 1.11 Index dan Constraint
1. `users.email` harus unik.
2. `order_statuses` disarankan memiliki unique index pada kombinasi `order_number` dan `period_month`.
3. `order_edks` disarankan memiliki unique index pada kombinasi `edk_reference` dan `period_month`.
4. `completion_records` disarankan memiliki unique index pada kombinasi `completion_number` dan `period_month`.
5. Index dibuat pada field `inputer_id`, `account_manager_id`, `status`, `approval_status`, dan `period_month`.
6. Field enum harus dibatasi hanya pada value yang terdaftar di bagian status value.
7. Foreign key menggunakan aturan `restrict` atau `set null` sesuai kebutuhan agar data histori tidak rusak ketika pengguna dinonaktifkan.
8. Penghapusan pengguna yang masih memiliki relasi data operasional sebaiknya menggunakan soft delete atau penonaktifan akun.

## 1.12 Soft Delete
1. Tabel `users`, `order_statuses`, `order_edks`, dan `completion_records` menggunakan soft delete.
2. Data yang sudah soft delete tidak muncul pada dashboard default.
3. Super Admin dapat diberikan akses untuk melihat data soft delete apabila diperlukan untuk audit.
4. Data soft delete tidak dihitung pada statistik operasional aktif.

## 1.13 Enum / Status Value

### 1.13.1 Peran Pengguna

| Value | Label Tampilan |
| --- | --- |
| `super_admin` | Super Admin |
| `admin_inputer` | Admin / Inputer |
| `account_manager` | Account Manager |

### 1.13.2 Status Order Status

| Value | Label Tampilan |
| --- | --- |
| `provisioning` | Provisioning |
| `pending_baso` | Pending BASO |
| `pending_billing_approval` | Pending Billing Approval |
| `complete` | Complete |
| `failed` | Failed |
| `cancel_abandoned` | Cancel / Abandoned |

### 1.13.3 Status Order EDK

| Value | Label Tampilan |
| --- | --- |
| `lanjut` | Lanjut |
| `tidak_lanjut` | Tidak Lanjut |
| `belum_input` | Belum Input |
| `ogp` | OGP |
| `complete` | Complete |

### 1.13.4 Status Persetujuan Modul Complete

| Value | Label Tampilan |
| --- | --- |
| `menunggu_persetujuan` | Menunggu Persetujuan |
| `disetujui` | Disetujui |
| `tidak_disetujui` | Tidak Disetujui |
| `revisi` | Revisi |

# 2. Peran dan Hak Akses

## 2.1 Daftar Peran
1. Super Admin
2. Admin / Inputer
3. Account Manager

## 2.2 Deskripsi Peran
1. Super Admin adalah peran dengan hak akses tertinggi. Peran ini digunakan oleh Manager atau Mentor untuk memantau seluruh data, mengelola pengguna, mengatur akses sistem, dan mengubah status persetujuan pada Modul Complete.
2. Admin/Inputer adalah peran yang bertugas menginput, memperbarui, dan mengelola data monitoring operasional.
3. Account Manager adalah peran yang hanya memantau perkembangan data dengan `account_manager_id` miliknya.

## 2.3 Hak Akses Per Fitur
| Fitur | Super Admin | Admin/Inputer | Account Manager |
| --- | --- | --- | --- |
| Login | Ya | Ya | Ya |
| Dashboard | Lihat semua data | Lihat data dengan `inputer_id` miliknya | Lihat data dengan `account_manager_id` miliknya |
| Order Status | Lihat semua data | Tambah, lihat, ubah, hapus data dengan `inputer_id` miliknya | Lihat data dengan `account_manager_id` miliknya |
| Order EDK | Lihat semua data | Tambah, lihat, ubah, hapus data dengan `inputer_id` miliknya | Lihat data dengan `account_manager_id` miliknya |
| Modul Complete | Lihat semua data dan ubah status persetujuan | Tambah, lihat, ubah, hapus data dengan `inputer_id` miliknya | Lihat data dengan `account_manager_id` miliknya |
| Manajemen Pengguna | Tambah, lihat, ubah, hapus, aktif/nonaktif | Tidak | Tidak |
| Profil Pengguna | Kelola profil dan password sendiri | Kelola profil dan password sendiri | Kelola profil dan password sendiri |
| Logout | Ya | Ya | Ya |

## 2.4 Batasan Akses Data
1. Super Admin dapat melihat seluruh data operasional tanpa batasan Inputer atau Account Manager.
2. Admin/Inputer dapat melihat dan mengelola data dengan `inputer_id` miliknya.
3. Account Manager hanya dapat melihat data dengan `account_manager_id` miliknya.
4. Account Manager tidak dapat melakukan aksi create, update, delete, approve, reject, atau revisi terhadap data operasional.
5. Admin/Inputer tidak dapat mengakses Manajemen Pengguna.
6. Pengguna yang tidak aktif tidak dapat login ke sistem.
7. Pengguna belum login hanya dapat mengakses halaman login.

## 2.5 Aturan Otorisasi
1. Authorization diterapkan menggunakan Laravel Policies atau Gate.
2. Setiap route internal wajib menggunakan middleware autentikasi.
3. Route Manajemen Pengguna hanya dapat diakses oleh Super Admin.
4. Aksi create, update, dan delete pada Order Status, Order EDK, dan Modul Complete hanya dapat dilakukan oleh Super Admin atau Admin/Inputer yang memiliki hak terhadap data tersebut.
5. Aksi view untuk Account Manager wajib memfilter data berdasarkan `account_manager_id`.
6. Aksi update dan delete untuk Admin/Inputer wajib memeriksa `inputer_id` sebelum perubahan disimpan.
7. Aksi persetujuan pada Modul Complete hanya dapat dilakukan oleh Super Admin.
8. Jika pengguna tidak memiliki akses, sistem menampilkan pesan tidak memiliki izin atau mengarahkan pengguna ke halaman yang diizinkan.

## 2.6 Daftar Permission
Catatan: permission profil dan password tetap dipisahkan di backend, tetapi keduanya digunakan dari halaman Profil Pengguna yang sama.

| Permission | Super Admin | Admin/Inputer | Account Manager |
| --- | --- | --- | --- |
| `dashboard.view_all` | Ya | Tidak | Tidak |
| `dashboard.view_related` | Ya | Ya | Ya |
| `order_status.view` | Ya | Ya | Ya |
| `order_status.create` | Ya | Ya | Tidak |
| `order_status.update` | Ya | Ya | Tidak |
| `order_status.delete` | Ya | Ya | Tidak |
| `order_edk.view` | Ya | Ya | Ya |
| `order_edk.create` | Ya | Ya | Tidak |
| `order_edk.update` | Ya | Ya | Tidak |
| `order_edk.delete` | Ya | Ya | Tidak |
| `complete.view` | Ya | Ya | Ya |
| `complete.create` | Ya | Ya | Tidak |
| `complete.update` | Ya | Ya | Tidak |
| `complete.delete` | Ya | Ya | Tidak |
| `complete.approve` | Ya | Tidak | Tidak |
| `complete.reject` | Ya | Tidak | Tidak |
| `complete.request_revision` | Ya | Tidak | Tidak |
| `user.view` | Ya | Tidak | Tidak |
| `user.create` | Ya | Tidak | Tidak |
| `user.update` | Ya | Tidak | Tidak |
| `user.delete` | Ya | Tidak | Tidak |
| `profile.update_self` | Ya | Ya | Ya |
| `password.update_self` | Ya | Ya | Ya |

# 3. Aturan Bisnis dan Validasi

## 3.1 Validasi Umum
1. Semua request dari halaman internal harus berasal dari pengguna yang sudah login.
2. Sistem harus memvalidasi peran dan hak akses sebelum membaca atau mengubah data.
3. Field wajib tidak boleh kosong.
4. Field enum hanya boleh menerima value yang sudah ditentukan.
5. Field relasi pengguna harus mengarah ke pengguna aktif dengan peran sesuai konteks.
6. Field periode menggunakan format `YYYY-MM`.
7. Teks bebas seperti catatan dan biodata harus memiliki batas panjang.
8. Data soft delete tidak boleh ikut dihitung pada dashboard operasional aktif.
9. Perubahan data penting harus menyimpan `created_by`, `updated_by`, atau activity log.
10. Password tidak boleh disimpan dalam bentuk plain text.

## 3.2 Validasi Form Login
| Field | Rule |
| --- | --- |
| `email` | required, email, exists pada `users.email` |
| `password` | required |

Aturan tambahan:
1. Login ditolak apabila akun tidak aktif.
2. Login ditolak apabila email atau password salah.
3. Setelah login berhasil, pengguna diarahkan ke Dashboard.
4. Tidak ada fitur forgot password; jika lupa, pengguna harus meminta reset melalui Super Admin.

## 3.3 Validasi Form Manajemen Pengguna
| Field | Rule |
| --- | --- |
| `name` | required, string, maksimal 150 karakter |
| `email` | required, email, unique pada `users.email` |
| `password` | required saat create, nullable saat update, minimal 8 karakter |
| `role` | required, in: `super_admin`, `admin_inputer`, `account_manager` |
| `phone` | nullable, maksimal 30 karakter |
| `bio` | nullable, maksimal 1000 karakter |
| `is_active` | required, boolean |

Aturan tambahan:
1. Hanya Super Admin yang dapat mengelola pengguna.
2. Email pengguna tidak boleh duplikat.
3. Pengguna yang sudah memiliki data operasional sebaiknya dinonaktifkan atau soft delete, bukan dihapus permanen.

## 3.4 Validasi Form Order Status

| Field | Rule |
| --- | --- |
| `order_number` | required, string, maksimal 100 karakter |
| `customer_name` | nullable, string, maksimal 150 karakter |
| `service_name` | nullable, string, maksimal 150 karakter |
| `inputer_id` | required, exists pada `users.id` dengan peran `admin_inputer` (Super Admin harus memilih user saat input) |
| `account_manager_id` | required, exists pada `users.id` dengan peran `account_manager` |
| `status` | required, enum status Order Status |
| `provisioning_stage` | nullable, string, maksimal 150 karakter |
| `period_month` | required, format `YYYY-MM` |
| `source_system` | required, string, maksimal 100 karakter |
| `notes` | nullable, maksimal 1000 karakter |

Aturan tambahan:
1. Kombinasi `order_number` dan `period_month` tidak boleh duplikat.
2. Status akhir hanya boleh dikoreksi oleh pengguna berwenang.
3. Account Manager hanya dapat melihat data dengan `account_manager_id` miliknya.

## 3.5 Validasi Form Order EDK

| Field | Rule |
| --- | --- |
| `edk_reference` | required, string, maksimal 100 karakter |
| `customer_name` | nullable, string, maksimal 150 karakter |
| `inputer_id` | required, exists pada `users.id` dengan peran `admin_inputer` (Super Admin harus memilih user saat input) |
| `account_manager_id` | required, exists pada `users.id` dengan peran `account_manager` |
| `status` | required, enum status Order EDK |
| `period_month` | required, format `YYYY-MM` |
| `source_system` | required, string, maksimal 100 karakter |
| `notes` | nullable, maksimal 1000 karakter |

Aturan tambahan:
1. Kombinasi `edk_reference` dan `period_month` tidak boleh duplikat.
2. Status `belum_input` digunakan ketika data EDK belum diperbarui oleh Admin/Inputer.
3. Nilai Achievement dihitung dari data tersimpan, bukan dari input manual.

## 3.6 Validasi Form Modul Complete

| Field | Rule |
| --- | --- |
| `completion_number` | required, string, maksimal 100 karakter |
| `order_status_id` | nullable, exists pada `order_statuses.id` |
| `order_edk_id` | nullable, exists pada `order_edks.id` |
| `inputer_id` | required, exists pada `users.id` dengan peran `admin_inputer` (Super Admin harus memilih user saat input) |
| `account_manager_id` | required, exists pada `users.id` dengan peran `account_manager` |
| `approval_status` | required (otomatis `menunggu_persetujuan` saat create), enum status persetujuan Modul Complete |
| `completed_at` | nullable, date |
| `approved_by` | nullable, exists pada `users.id` dengan peran `super_admin` |
| `revision_note` | required jika `approval_status` adalah `revisi` |
| `period_month` | required, format `YYYY-MM` |
| `notes` | nullable, maksimal 1000 karakter |

Aturan tambahan:
1. Kombinasi `completion_number` dan `period_month` tidak boleh duplikat.
2. Wajib mengisi minimal salah satu dari `order_status_id` atau `order_edk_id` (tidak boleh berdiri sendiri tanpa relasi).
3. Status `revisi` wajib memiliki catatan revisi.
4. Status `disetujui`, `tidak_disetujui`, dan `revisi` perlu menyimpan `approved_by` dan `approved_at`.

## 3.7 Validasi Request
1. Validasi request dilakukan di sisi backend menggunakan Laravel Form Request.
2. Validasi frontend hanya digunakan untuk membantu pengalaman pengguna dan tidak menggantikan validasi backend.
3. Setiap aksi create dan update harus memvalidasi authorization sebelum menyimpan data.
4. Request filter dashboard harus memvalidasi periode, Admin/Inputer, dan Account Manager.
5. Request delete harus memvalidasi bahwa data ada, belum terhapus, dan pengguna memiliki izin.

## 3.8 Validasi Database
1. Database harus memiliki constraint unique untuk email pengguna.
2. Database harus memiliki index pada field yang sering digunakan untuk filter dashboard.
3. Database harus menjaga foreign key agar data relasi tidak rusak.
4. Proses penyimpanan yang melibatkan lebih dari satu tabel harus menggunakan database transaction.
5. Data duplikat dicegah melalui unique index dan validasi request.
6. Field `updated_at` dapat digunakan untuk mendeteksi konflik pembaruan data.

## 3.9 Pesan Error
| Kondisi | Pesan yang Ditampilkan |
| --- | --- |
| Login gagal | Email atau password tidak sesuai. |
| Akun tidak aktif | Akun Anda tidak aktif. Hubungi Super Admin. |
| Tidak memiliki akses | Anda tidak memiliki izin untuk mengakses fitur ini. |
| Data tidak ditemukan | Data tidak ditemukan. |
| Validasi gagal | Periksa kembali data yang wajib diisi. |
| Data duplikat | Data dengan identifier yang sama sudah tersedia pada periode ini. |
| Konflik update | Data sudah diperbarui oleh pengguna lain. Muat ulang halaman untuk melihat data terbaru. |
| Penyimpanan gagal | Data gagal disimpan. Silakan coba kembali. |

## 3.10 Edge Case
1. Dashboard tetap tampil dengan nilai 0 apabila belum ada data.
2. Filter yang tidak memiliki hasil menampilkan empty state.
3. Jika Account Manager belum memiliki data, sistem hanya menampilkan data kosong untuk akun tersebut.
4. Jika pengguna yang menjadi Admin/Inputer atau Account Manager dinonaktifkan, data historis tetap tersimpan.
5. Jika data dihapus dengan soft delete, data tidak dihitung pada statistik aktif.
6. Jika tombol submit ditekan lebih dari satu kali, sistem tidak boleh membuat data ganda.
7. Jika proses simpan gagal di tengah jalan, transaksi dibatalkan agar tidak ada data sebagian.

## 3.11 Penanganan Error di UI
1. Form menampilkan pesan validasi di field yang bermasalah.
2. Halaman tabel menampilkan empty state saat data kosong.
3. Aksi simpan menampilkan loading state sampai request selesai.
4. Tombol submit dinonaktifkan sementara saat request diproses.
5. Notifikasi berhasil ditampilkan setelah data tersimpan.
6. Notifikasi gagal ditampilkan saat server mengembalikan error.
7. Pengguna diarahkan ke login apabila sesi berakhir.

## 3.12 Validasi Otorisasi dan Kepemilikan Data
1. Setiap query data operasional harus menerapkan scope berdasarkan peran pengguna.
2. Scope Super Admin tidak membatasi data.
3. Scope Admin/Inputer membatasi data pada `inputer_id` miliknya.
4. Scope Account Manager membatasi data pada `account_manager_id` miliknya.
5. Aksi update dan delete harus memeriksa ownership sebelum perubahan dilakukan.
6. Aksi terhadap pengguna hanya dapat dilakukan oleh Super Admin.
7. Pengguna tidak boleh mengubah peran atau status aktif akunnya sendiri melalui halaman profil.

## 3.13 Validasi File Upload
1. File upload hanya digunakan untuk foto profil apabila fitur foto profil diaktifkan.
2. Format file yang diperbolehkan adalah `jpg`, `jpeg`, `png`, atau `webp`.
3. Ukuran file maksimal disarankan 2 MB.
4. File harus disimpan di storage aplikasi, bukan di database sebagai binary.
5. Nama file harus dibuat ulang oleh sistem untuk menghindari konflik nama.
6. File lama dapat dihapus atau diganti saat pengguna memperbarui foto profil.
7. Upload ditolak apabila format atau ukuran file tidak sesuai aturan.
