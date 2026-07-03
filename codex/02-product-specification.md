# 1. Teknologi yang Digunakan
1. **Laravel** digunakan sebagai framework backend untuk mengelola routing, controller, validasi, database, autentikasi, dan proses bisnis aplikasi.
2. **Vue.js** digunakan sebagai framework frontend untuk membangun tampilan aplikasi yang interaktif, responsif, dan berbasis komponen.
3. **Inertia.js** digunakan sebagai penghubung antara Laravel dan Vue.js agar aplikasi dapat berjalan seperti single page application tanpa perlu membuat REST API terpisah.
4. **MySQL** digunakan sebagai database utama untuk menyimpan dan mengelola data aplikasi.
5. **Tailwind CSS** digunakan untuk mengatur tampilan aplikasi melalui utility class, seperti layout, spacing, warna, typography, dan responsive design.
6. **shadcn-vue** digunakan sebagai library komponen UI untuk membantu pembuatan antarmuka yang rapi, konsisten, dan mudah dikembangkan.
7. **Laravel Breeze** digunakan sebagai starter kit autentikasi untuk menyediakan fitur dasar seperti login, logout, profil, dan penggantian password. Registrasi publik dapat dinonaktifkan karena akun pengguna dikelola oleh Super Admin.
8. **Laravel Policies** digunakan untuk mengatur hak akses pengguna terhadap fitur atau data tertentu di dalam aplikasi.
9. **localStorage** digunakan untuk menyimpan data ringan di sisi browser, seperti preferensi tampilan atau pengaturan non-sensitif. Token, password, dan data operasional sensitif tidak disimpan di localStorage.

# 2. Fitur Utama

## 2.1 Detail Fitur Utama

### 2.1.1 Login
Tujuan fitur:
1. Memastikan hanya pengguna terdaftar yang dapat mengakses sistem.
2. Menjaga keamanan akses ke dalam sistem.
3. Mencegah pengguna yang tidak memiliki akun untuk masuk ke halaman utama sistem.

### 2.1.2 Dashboard
Informasi yang ditampilkan:
1. Total Order
2. Pending BASO
3. Complete
4. Failed
5. Sisa Populasi
6. Grafik monitoring operasional
7. Rekapitulasi berdasarkan Inputer
8. Rekapitulasi berdasarkan Account Manager

Tujuan fitur:
1. Memberikan gambaran kondisi operasional secara cepat.
2. Membantu pengguna memantau perkembangan pekerjaan.
3. Menyediakan informasi ringkas untuk evaluasi kinerja.
4. Mempermudah pemantauan data operasional harian.

### 2.1.3 Order Status
Informasi yang ditampilkan:
1. Jumlah pekerjaan pada setiap tahapan provisioning
2. Pending BASO
3. Pending Billing Approval
4. Complete
5. Failed
6. Cancel / Abandoned

Filter data:
1. Inputer
2. Account Manager
3. Periode tertentu

Tujuan fitur:
1. Membantu pengguna memantau status pekerjaan secara terstruktur.
2. Mempermudah evaluasi terhadap pekerjaan yang masih pending, complete, failed, atau cancel.
3. Menyediakan data monitoring yang dapat digunakan untuk laporan operasional.

### 2.1.4 Order EDK
Informasi yang ditampilkan:
1. Status Lanjut
2. Status Tidak Lanjut
3. Belum Input
4. OGP
5. Complete
6. Persentase Achievement (% ACH)
7. Sisa Populasi

Tujuan fitur:
1. Membantu proses monitoring progres pekerjaan EDK.
2. Menyajikan informasi status EDK secara ringkas.
3. Mempermudah evaluasi terhadap pekerjaan yang lanjut, tidak lanjut, belum input, OGP, dan complete.
4. Menyediakan informasi pencapaian kerja melalui persentase achievement.

### 2.1.5 Modul Complete
Informasi yang ditampilkan:
1. Jumlah pekerjaan yang masuk Modul Complete
2. Status persetujuan
3. Status tidak disetujui
4. Catatan revisi
5. Statistik penyelesaian berdasarkan Account Manager

Tujuan fitur:
1. Membantu pengguna memantau hasil akhir pekerjaan.
2. Menyediakan informasi pekerjaan yang telah diselesaikan.
3. Mempermudah evaluasi terhadap status persetujuan pekerjaan.
4. Menyediakan data penyelesaian pekerjaan untuk kebutuhan laporan operasional.

### 2.1.6 Manajemen Pengguna
Data pengguna yang dikelola:
1. Nama pengguna
2. Email
3. Peran pengguna
4. Status akun
5. Informasi profil pengguna

Tujuan fitur:
1. Memastikan hanya pengguna terdaftar yang dapat mengakses sistem.
2. Mengatur pembagian peran sesuai struktur organisasi.
3. Mengontrol data pengguna dalam sistem.
4. Menjaga keamanan sistem melalui pengelolaan akun pengguna.

### 2.1.7 Profil Pengguna
Informasi yang dapat dikelola:
1. Nama
2. Email
3. Nomor telepon
4. Foto profil
5. Biodata
6. Password akun melalui section penggantian password

Tujuan fitur:
1. Memberikan akses kepada pengguna untuk mengelola informasi akunnya sendiri.
2. Menjaga agar data profil pengguna tetap sesuai dan terbaru.
3. Mempermudah identifikasi pengguna dalam sistem.
4. Memberikan kontrol keamanan akun kepada pengguna melalui form penggantian password pada halaman yang sama.
5. Mengurangi risiko penyalahgunaan akun.
6. Menjaga kerahasiaan akses sistem.

### 2.1.8 Logout
Tujuan fitur:
1. Menjaga keamanan akun pengguna.
2. Mencegah akses tidak sah pada perangkat yang digunakan bersama.
3. Memastikan sesi penggunaan sistem berakhir dengan aman.

# 3. Alur Pengguna

## 3.1 Alur Umum
1. Pengguna membuka Sistem Monitoring Operasional melalui browser.
2. Sistem memeriksa sesi login.
3. Jika pengguna belum login, sistem menampilkan halaman login.
4. Pengguna memasukkan email dan password.
5. Sistem memvalidasi kredensial, status akun, dan peran pengguna.
6. Jika login berhasil, sistem mengarahkan pengguna ke Dashboard.
7. Sistem menampilkan menu dan data sesuai hak akses pengguna.
8. Pengguna membuka Dashboard, Order Status, Order EDK, Modul Complete, Manajemen Pengguna, atau Profil Pengguna sesuai izin perannya.
9. Pengguna dapat menggunakan filter periode, status, Admin/Inputer, atau Account Manager apabila filter tersebut relevan dengan hak aksesnya.
10. Setelah selesai, pengguna memilih Logout.
11. Sistem mengakhiri sesi dan mengarahkan pengguna kembali ke halaman login.

## 3.2 Aktivitas Berdasarkan Peran
| Peran | Aktivitas Utama | Batasan Utama |
| --- | --- | --- |
| Super Admin | Melihat seluruh dashboard, memantau semua data operasional, mengelola pengguna, dan mengubah status persetujuan pada Modul Complete. | Tidak ada batasan data operasional. |
| Admin/Inputer | Menginput, memperbarui, dan menghapus data Order Status, Order EDK, dan Modul Complete berdasarkan data dari Dashboard NCX. | Tidak dapat mengelola pengguna dan hanya mengelola data dengan `inputer_id` miliknya. |
| Account Manager | Melihat Dashboard, Order Status, Order EDK, dan Modul Complete dengan `account_manager_id` miliknya. | Tidak dapat menambah, mengubah, menghapus, atau menyetujui data operasional. |

## 3.3 Alur Fitur Utama

### 3.3.1 Dashboard
1. Pengguna membuka Dashboard setelah login.
2. Sistem menampilkan statistik dan grafik sesuai cakupan data pengguna.
3. Pengguna dapat memilih periode atau filter lain yang tersedia.
4. Sistem memperbarui statistik, grafik, dan rekapitulasi berdasarkan filter.

### 3.3.2 Order Status
1. Pengguna membuka halaman Order Status.
2. Sistem menampilkan daftar data Order Status sesuai hak akses.
3. Super Admin dan Admin/Inputer dapat menambah, mengubah, atau menghapus data sesuai izin.
4. Account Manager hanya dapat melihat data Order Status dengan `account_manager_id` miliknya.
5. Sistem menampilkan status terakhir setiap order agar data tidak dihitung ganda.

### 3.3.3 Order EDK
1. Pengguna membuka halaman Order EDK.
2. Sistem menampilkan daftar data EDK sesuai hak akses.
3. Super Admin dan Admin/Inputer dapat menambah, mengubah, atau menghapus data sesuai izin.
4. Account Manager hanya dapat melihat data EDK dengan `account_manager_id` miliknya.
5. Sistem menghitung Achievement dan Sisa Populasi berdasarkan data tersimpan.

### 3.3.4 Modul Complete
1. Pengguna membuka halaman Modul Complete.
2. Sistem menampilkan daftar pekerjaan yang masuk tahap penyelesaian.
3. Admin/Inputer dapat menambah atau memperbarui data pada Modul Complete sesuai hak akses. Saat menambah data baru, status persetujuan otomatis menjadi Menunggu Persetujuan.
4. Super Admin dapat memantau seluruh data, melakukan CRUD jika diperlukan, dan mengubah status persetujuan menjadi Menunggu Persetujuan, Disetujui, Tidak Disetujui, atau Revisi.
5. Account Manager hanya dapat melihat data pada Modul Complete dengan `account_manager_id` miliknya.
6. Jika status diubah menjadi Revisi, sistem wajib meminta catatan revisi.

### 3.3.5 Manajemen Pengguna
1. Super Admin membuka halaman Manajemen Pengguna.
2. Sistem menampilkan daftar pengguna.
3. Super Admin dapat menambah, mengubah, menonaktifkan, atau menghapus pengguna sesuai aturan validasi.
4. Admin/Inputer dan Account Manager tidak dapat mengakses halaman ini.

### 3.3.6 Profil dan Password
1. Pengguna membuka halaman Profil Pengguna.
2. Sistem menampilkan data akun pengguna yang sedang login dan form penggantian password dalam satu halaman.
3. Pengguna dapat memperbarui profil dan password miliknya sendiri dari halaman tersebut.
4. Pengguna tidak dapat mengubah peran atau status aktif akunnya sendiri.

## 3.4 Alur Error
1. Jika email atau password salah, sistem menolak login dan menampilkan pesan error.
2. Jika akun tidak aktif, sistem menolak login dan menampilkan pesan bahwa akun tidak aktif.
3. Jika pengguna membuka halaman internal tanpa sesi login aktif, sistem mengarahkan pengguna ke halaman login.
4. Jika pengguna membuka fitur yang tidak sesuai hak aksesnya, sistem menampilkan pesan tidak memiliki izin atau mengarahkan ke halaman yang diizinkan.
5. Jika data hasil filter tidak ditemukan, sistem menampilkan empty state.
6. Jika form tidak valid, sistem menampilkan pesan validasi pada field terkait.
7. Jika data sudah diperbarui oleh pengguna lain, sistem menampilkan pesan konflik dan meminta pengguna memuat ulang data.
8. Jika penyimpanan gagal, sistem tidak menyimpan perubahan sebagian dan menampilkan pesan gagal.

## 3.5 Redirect dan Navigasi
1. Guest hanya dapat mengakses halaman login.
2. Pengguna aktif yang sudah login diarahkan ke Dashboard.
3. Pengguna yang logout diarahkan kembali ke halaman login.
4. Sesi yang berakhir membuat pengguna harus login ulang.
5. Menu ditampilkan berdasarkan peran pengguna.
6. Manajemen Pengguna hanya tampil untuk Super Admin.
7. Tombol tambah, ubah, hapus, dan persetujuan data hanya tampil untuk peran yang memiliki izin.

# 4. Logika Bisnis

## 4.1 Aturan Bisnis Inti
1. Sistem hanya dapat diakses oleh pengguna yang telah memiliki akun aktif.
2. Pengguna wajib login sebelum mengakses Dashboard, Order Status, Order EDK, Modul Complete, Manajemen Pengguna, atau Profil Pengguna.
3. Hak akses dibedakan berdasarkan tiga peran: Super Admin, Admin/Inputer, dan Account Manager.
4. Super Admin dapat melihat seluruh data monitoring, mengelola pengguna, mengatur peran, mengatur status akun, melakukan operasi CRUD penuh pada data operasional untuk perbaikan data (dengan mewajibkan pemilihan `inputer_id`), dan mengubah status persetujuan pada Modul Complete.
5. Admin/Inputer dapat menambah, mengubah, menghapus, dan memperbarui data Order Status, Order EDK, dan Modul Complete dengan `inputer_id` miliknya.
6. Account Manager hanya dapat melihat data monitoring dengan `account_manager_id` miliknya.
7. Account Manager tidak dapat menambah, mengubah, menghapus, atau menyetujui data operasional.
8. Data monitoring bersumber dari Dashboard NCX dan dikelola di sistem melalui input atau pembaruan oleh Admin/Inputer.
9. Sistem tidak melakukan perubahan data langsung ke aplikasi utama perusahaan seperti CRM/Oracle, SC-One, Carent, atau NCX.
10. Setiap perubahan data penting harus mencatat pengguna yang melakukan perubahan dan waktu perubahan.
11. Data operasional harus memiliki identifier, Admin/Inputer, Account Manager, periode, dan kategori monitoring yang jelas.
12. Data yang tidak lagi digunakan pada monitoring aktif dapat dinonaktifkan atau dihapus secara soft delete agar histori tetap terjaga.
13. Penghapusan data hanya boleh dilakukan oleh peran yang memiliki izin.
14. Dashboard harus menampilkan data sesuai cakupan akses pengguna.
15. Sistem wajib menampilkan empty state apabila data monitoring belum tersedia atau hasil filter tidak ditemukan.

## 4.2 Logika Status

### 4.2.1 Status Order Status
1. Setiap data Order Status harus memiliki satu status aktif pada satu waktu.
2. Status yang dapat digunakan pada Order Status meliputi:
   1. Provisioning
   2. Pending BASO
   3. Pending Billing Approval
   4. Complete
   5. Failed
   6. Cancel / Abandoned
3. Status Provisioning digunakan ketika pekerjaan masih berada dalam proses penyediaan layanan.
4. Status Pending BASO digunakan ketika pekerjaan menunggu proses atau kelengkapan BASO.
5. Status Pending Billing Approval digunakan ketika pekerjaan menunggu persetujuan billing.
6. Status Complete digunakan ketika pekerjaan telah selesai dan dapat dihitung sebagai pekerjaan selesai.
7. Status Failed digunakan ketika pekerjaan gagal diproses atau tidak dapat dilanjutkan karena alasan tertentu.
8. Status Cancel / Abandoned digunakan ketika pekerjaan dibatalkan atau tidak lagi dilanjutkan.

### 4.2.2 Status Order EDK
1. Setiap data Order EDK harus memiliki status progres yang menggambarkan kondisi pekerjaan terbaru.
2. Status yang digunakan pada Order EDK meliputi:
   1. Lanjut
   2. Tidak Lanjut
   3. Belum Input
   4. OGP
   5. Complete
3. Status Lanjut digunakan ketika pekerjaan EDK masih diteruskan ke proses berikutnya.
4. Status Tidak Lanjut digunakan ketika pekerjaan EDK dihentikan atau tidak memenuhi kondisi untuk dilanjutkan.
5. Status Belum Input digunakan ketika data EDK belum diperbarui oleh Admin/Inputer.
6. Status OGP digunakan ketika pekerjaan sedang dalam proses operasional sebelum selesai.
7. Status Complete digunakan ketika pekerjaan EDK telah selesai.

### 4.2.3 Status Persetujuan Modul Complete
1. Data pada Modul Complete digunakan untuk mencatat pekerjaan yang telah masuk tahap penyelesaian.
2. Status persetujuan pada Modul Complete meliputi:
   1. Menunggu Persetujuan
   2. Disetujui
   3. Tidak Disetujui
   4. Revisi
3. Status Menunggu Persetujuan digunakan ketika data sudah diinput tetapi belum diputuskan oleh Super Admin.
4. Status Disetujui digunakan ketika data telah diterima dan dinyatakan valid.
5. Status Tidak Disetujui digunakan ketika data ditolak karena tidak sesuai.
6. Status Revisi digunakan ketika data perlu diperbaiki berdasarkan catatan revisi.

## 4.3 Logika Perhitungan

### 4.3.1 Perhitungan Dashboard
1. Total Order dihitung dari jumlah seluruh data pada tabel `order_statuses` dalam periode atau filter yang dipilih.
2. Pending BASO dihitung dari jumlah order dengan status Pending BASO pada tabel `order_statuses`.
3. Complete dihitung dari jumlah gabungan order dengan status Complete pada `order_statuses` dan `order_edks`.
4. Failed dihitung dari jumlah order dengan status Failed pada tabel `order_statuses`.
5. Sisa Populasi dihitung khusus dari tabel `order_edks` (Total Populasi EDK - Complete - Tidak Lanjut).
6. Rekapitulasi berdasarkan Inputer dihitung dari jumlah data yang dikelola oleh masing-masing Admin/Inputer (hanya ditampilkan untuk Super Admin).
7. Rekapitulasi berdasarkan Account Manager dihitung dari jumlah data yang berkaitan dengan masing-masing Account Manager (hanya ditampilkan untuk Super Admin).
8. Data dashboard harus mengikuti cakupan akses pengguna:
   1. Super Admin melihat seluruh data beserta tabel rekapitulasi.
   2. Admin/Inputer melihat statistik kartu dengan `inputer_id` miliknya (tabel rekapitulasi disembunyikan).
   3. Account Manager melihat statistik kartu dengan `account_manager_id` miliknya (tabel rekapitulasi disembunyikan).

### 4.3.2 Perhitungan Order Status
1. Jumlah setiap tahapan provisioning dihitung berdasarkan status terakhir dari setiap order.
2. Jumlah Pending BASO dihitung dari order yang status terakhirnya Pending BASO.
3. Jumlah Pending Billing Approval dihitung dari order yang status terakhirnya Pending Billing Approval.
4. Jumlah Complete dihitung dari order yang status terakhirnya Complete.
5. Jumlah Failed dihitung dari order yang status terakhirnya Failed.
6. Jumlah Cancel / Abandoned dihitung dari order yang status terakhirnya Cancel / Abandoned.
7. Data yang sama tidak boleh dihitung lebih dari satu kali dalam kategori status aktif.

### 4.3.3 Perhitungan Order EDK
1. Total populasi EDK dihitung dari seluruh data EDK pada periode atau filter yang dipilih.
2. Belum Input dihitung dari data EDK yang belum memiliki pembaruan status dari Admin/Inputer.
3. OGP dihitung dari data EDK yang sedang dalam proses operasional.
4. Complete dihitung dari data EDK yang statusnya Complete.
5. Sisa Populasi dihitung dari total populasi EDK dikurangi data yang berstatus Complete dan Tidak Lanjut.
6. Persentase Achievement dihitung dengan rumus:

   ```text
   Achievement (%) = (Jumlah Complete / Total Populasi) x 100
   ```

7. Jika Total Populasi bernilai 0, maka Achievement ditampilkan sebagai 0% untuk menghindari pembagian dengan nol.

### 4.3.4 Perhitungan Modul Complete
1. Jumlah data pada Modul Complete dihitung dari seluruh pekerjaan yang masuk ke Modul Complete sesuai periode atau filter yang dipilih.
2. Jumlah disetujui dihitung dari data Modul Complete berstatus Disetujui.
3. Jumlah tidak disetujui dihitung dari data Modul Complete berstatus Tidak Disetujui.
4. Jumlah revisi dihitung dari data Modul Complete berstatus Revisi.
5. Statistik penyelesaian berdasarkan Account Manager dihitung dari jumlah data Modul Complete dengan `account_manager_id` masing-masing.

## 4.4 Logika Otomatisasi
1. Setelah login berhasil, sistem otomatis mengarahkan pengguna ke Dashboard.
2. Jika pengguna belum login dan mencoba membuka halaman internal, sistem otomatis mengarahkan pengguna ke halaman login.
3. Jika sesi pengguna berakhir, sistem otomatis meminta pengguna login kembali.
4. Sistem otomatis membatasi menu yang tampil berdasarkan peran pengguna.
5. Sistem otomatis menolak akses apabila pengguna mencoba membuka halaman yang tidak sesuai dengan hak aksesnya.
6. Sistem otomatis memperbarui ringkasan dashboard setelah data Order Status, Order EDK, atau Modul Complete berubah.
7. Sistem otomatis menerapkan filter default berdasarkan periode berjalan apabila pengguna belum memilih filter.
8. Sistem otomatis menampilkan pesan berhasil setelah data tersimpan.
9. Sistem otomatis menampilkan pesan validasi apabila data wajib belum diisi atau format input tidak valid.
10. Sistem otomatis menampilkan empty state apabila data tidak tersedia.
11. Sistem otomatis mencatat waktu pembuatan dan pembaruan data apabila tabel database mendukung field timestamp.
12. Sistem otomatis menjaga password dalam bentuk terenkripsi atau hashed sesuai mekanisme Laravel.

## 4.5 Kondisi Khusus
1. Jika pengguna login dengan akun tidak aktif, sistem menolak akses dan menampilkan pesan bahwa akun tidak aktif.
2. Jika email atau password salah, sistem menolak login dan menampilkan pesan error.
3. Jika pengguna tidak memiliki izin terhadap suatu fitur, sistem menolak akses dan menampilkan halaman atau pesan tidak memiliki izin.
4. Jika data hasil filter kosong, sistem menampilkan empty state tanpa menghapus filter yang dipilih.
5. Jika data monitoring belum tersedia, dashboard tetap tampil dengan nilai 0 pada statistik utama.
6. Jika Account Manager tidak memiliki data pada periode tertentu, sistem menampilkan data kosong hanya untuk Account Manager tersebut.
7. Jika Admin/Inputer mencoba mengakses Manajemen Pengguna, sistem menolak akses.
8. Jika Account Manager mencoba mengakses aksi input, edit, hapus, atau persetujuan data, sistem menolak akses.
9. Jika data yang akan diubah sudah dihapus atau tidak ditemukan, sistem menampilkan pesan data tidak ditemukan.
10. Jika terjadi kegagalan penyimpanan data, sistem tidak menyimpan perubahan sebagian dan menampilkan pesan gagal.
11. Jika terjadi duplikasi data berdasarkan identifier order yang sama, sistem harus mencegah pencatatan ganda atau memperbarui data yang sudah ada sesuai aturan idempotency.
12. Jika catatan revisi diperlukan, data Modul Complete tidak dapat disimpan ke status Revisi tanpa catatan yang jelas.

## 4.6 Aturan Transisi Status

### 4.6.1 Transisi Order Status
1. Status awal Order Status dapat berupa Provisioning, Pending BASO, Pending Billing Approval, Failed, atau Cancel / Abandoned sesuai data operasional yang tersedia.
2. Provisioning dapat berubah menjadi Pending BASO, Pending Billing Approval, Complete, Failed, atau Cancel / Abandoned.
3. Pending BASO dapat berubah menjadi Pending Billing Approval, Complete, Failed, atau Cancel / Abandoned.
4. Pending Billing Approval dapat berubah menjadi Complete, Failed, atau Cancel / Abandoned.
5. Complete merupakan status akhir dan tidak boleh berubah kecuali terdapat koreksi data oleh pengguna berwenang (Hanya Super Admin).
6. Failed merupakan status akhir dan hanya boleh berubah apabila terdapat koreksi data oleh pengguna berwenang (Hanya Super Admin).
7. Cancel / Abandoned merupakan status akhir dan hanya boleh berubah apabila terdapat koreksi data oleh pengguna berwenang (Hanya Super Admin).

### 4.6.2 Transisi Order EDK
1. Belum Input dapat berubah menjadi Lanjut, Tidak Lanjut, OGP, atau Complete.
2. Lanjut dapat berubah menjadi OGP, Complete, atau Tidak Lanjut.
3. OGP dapat berubah menjadi Complete atau Tidak Lanjut.
4. Tidak Lanjut merupakan status akhir dan hanya boleh berubah apabila terdapat koreksi data oleh pengguna berwenang (Hanya Super Admin).
5. Complete merupakan status akhir dan hanya boleh berubah apabila terdapat koreksi data oleh pengguna berwenang (Hanya Super Admin).

### 4.6.3 Transisi Persetujuan Modul Complete
1. Menunggu Persetujuan dapat berubah menjadi Disetujui, Tidak Disetujui, atau Revisi.
2. Revisi dapat berubah kembali menjadi Menunggu Persetujuan setelah data diperbaiki.
3. Tidak Disetujui dapat berubah menjadi Revisi apabila pengguna berwenang meminta perbaikan ulang.
4. Disetujui merupakan status akhir dan hanya boleh berubah apabila terdapat koreksi data oleh pengguna berwenang.
5. Perubahan ke status Revisi wajib disertai catatan revisi.

## 4.7 Aturan Idempotensi dan Concurrency
1. Setiap data operasional harus memiliki identifier unik agar sistem dapat membedakan data baru dan data yang sudah pernah tercatat.
2. Jika Admin/Inputer menginput data dengan identifier yang sudah ada pada periode yang sama, sistem tidak membuat duplikasi data.
3. Untuk data yang sudah ada, sistem dapat memperbarui record lama sesuai perubahan terbaru dari Admin/Inputer.
4. Proses penyimpanan data harus dilakukan dalam transaksi database apabila perubahan melibatkan lebih dari satu tabel.
5. Jika dua pengguna memperbarui data yang sama dalam waktu berdekatan, sistem harus menyimpan perubahan terakhir secara konsisten dan mencegah data rusak.
6. Jika sistem menerapkan optimistic locking, perubahan ditolak ketika data yang diedit sudah lebih dulu diperbarui oleh pengguna lain.
7. Jika perubahan ditolak karena konflik data, sistem menampilkan pesan bahwa data telah diperbarui dan pengguna perlu memuat ulang halaman.
8. Perhitungan statistik dashboard harus menggunakan data terbaru yang sudah berhasil tersimpan di database.
9. Aksi simpan, ubah, atau hapus tidak boleh menghasilkan perubahan ganda ketika pengguna menekan tombol submit lebih dari satu kali.
10. Sistem harus menonaktifkan tombol submit sementara atau menampilkan loading state ketika proses penyimpanan sedang berjalan.
11. Import atau pembaruan data massal harus dapat dijalankan ulang tanpa menghasilkan duplikasi apabila sumber data memiliki identifier yang sama.
12. Log aktivitas disarankan mencatat perubahan penting untuk membantu audit apabila terjadi perbedaan data.
