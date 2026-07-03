# 1. UI, UX, dan Design System

## 1.1 Struktur Layout

### 1.1.1 Auth Layout
Struktur:
1. Area utama berada di tengah layar.
2. Form login ditampilkan dalam panel sederhana.
3. Identitas sistem ditampilkan sebagai judul utama.
4. Field email dan password mudah ditemukan.
5. Pesan error login ditampilkan dekat form.
6. Tidak menampilkan menu aplikasi sebelum pengguna login.

Elemen utama:
1. Nama sistem: Sistem Monitoring Operasional.
2. Keterangan unit: Divisi Government Service Regional Sulbagteng.
3. Field email.
4. Field password.
5. Tombol login.
6. Pesan validasi atau error.

### 1.1.2 App Layout
Struktur:
1. Sidebar navigasi di sisi kiri untuk desktop.
2. Top bar di bagian atas area konten.
3. Area konten utama untuk dashboard, tabel, form, dan grafik.
4. Breadcrumb atau judul halaman di bagian atas konten.
5. Menu pengguna di top bar untuk profil pengguna termasuk penggantian password, dan logout.

Prinsip layout:
1. Sidebar tetap konsisten pada semua halaman internal.
2. Konten utama menggunakan lebar penuh dengan padding yang cukup.
3. Data penting diletakkan pada bagian atas halaman.
4. Filter diletakkan dekat data yang dipengaruhi oleh filter tersebut.
5. Aksi utama seperti tambah data diletakkan di kanan atas area konten.
6. Tidak menggunakan elemen dekoratif berlebihan karena sistem berfokus pada pekerjaan operasional.

### 1.1.3 Content Layout

1. Header halaman berisi judul, deskripsi singkat, dan aksi utama apabila ada.
2. Area filter berada setelah header halaman.
3. Ringkasan statistik ditampilkan sebelum tabel apabila relevan.
4. Tabel data menjadi area utama untuk halaman monitoring.
5. Pagination diletakkan di bawah tabel.
6. Empty state ditampilkan di area tabel apabila data tidak tersedia.

## 1.2 Daftar Halaman

| Halaman | Tujuan | Peran yang Dapat Mengakses |
| --- | --- | --- |
| Login | Autentikasi pengguna sebelum masuk sistem. | Guest |
| Dashboard | Menampilkan ringkasan operasional, grafik, dan rekapitulasi. | Super Admin, Admin/Inputer, Account Manager |
| Order Status | Monitoring status pekerjaan provisioning. | Super Admin, Admin/Inputer, Account Manager |
| Order EDK | Monitoring progres pekerjaan EDK. | Super Admin, Admin/Inputer, Account Manager |
| Modul Complete | Monitoring hasil penyelesaian pekerjaan dan status persetujuan. | Super Admin, Admin/Inputer, Account Manager |
| Manajemen Pengguna | Mengelola akun, peran, dan status aktif pengguna. | Super Admin |
| Profil Pengguna | Melihat dan memperbarui profil pengguna sendiri serta mengubah password akun. | Super Admin, Admin/Inputer, Account Manager |

### 1.2.1 Halaman Login

Konten utama:
1. Judul sistem.
2. Form email dan password.
3. Tombol login.
4. Pesan error apabila login gagal.
5. Tidak ada link atau fitur forgot password.

UX utama:
1. Fokus awal berada pada field email.
2. Tombol login masuk loading state saat form dikirim.
3. Password dapat ditampilkan atau disembunyikan melalui tombol icon.
4. Pesan error tidak mengungkap detail keamanan yang sensitif.

### 1.2.2 Halaman Dashboard

Konten utama:
1. Kartu statistik Total Order.
2. Kartu statistik Pending BASO.
3. Kartu statistik Complete.
4. Kartu statistik Failed.
5. Kartu statistik Sisa Populasi.
6. Grafik monitoring operasional.
7. Tabel rekapitulasi berdasarkan Inputer.
8. Tabel rekapitulasi berdasarkan Account Manager.

UX utama:
1. Statistik utama ditampilkan paling atas.
2. Grafik digunakan untuk membaca tren atau komposisi status.
3. Filter periode tersedia dan mudah dijangkau.
4. Data dashboard mengikuti hak akses peran pengguna.

### 1.2.3 Halaman Order Status

Konten utama:
1. Ringkasan jumlah status.
2. Filter Inputer, Account Manager, status, dan periode.
3. Tabel data Order Status.
4. Aksi tambah, ubah, hapus, dan detail sesuai hak akses.
5. Aksi tambah, ubah, hapus, dan detail dijalankan melalui modal/dialog.

Kolom tabel utama:
1. Nomor order.
2. Nama pelanggan atau instansi.
3. Layanan.
4. Inputer.
5. Account Manager.
6. Status.
7. Periode.
8. Update terakhir.
9. Aksi.

### 1.2.4 Halaman Order EDK

Konten utama:
1. Ringkasan Lanjut, Tidak Lanjut, Belum Input, OGP, Complete, Achievement, dan Sisa Populasi.
2. Filter Inputer, Account Manager, status, dan periode.
3. Tabel data Order EDK.
4. Aksi tambah, ubah, hapus, dan detail sesuai hak akses.
5. Aksi tambah, ubah, hapus, dan detail dijalankan melalui modal/dialog.

Kolom tabel utama:
1. Referensi EDK.
2. Nama pelanggan atau instansi.
3. Inputer.
4. Account Manager.
5. Status.
6. Periode.
7. Update terakhir.
8. Aksi.

### 1.2.5 Halaman Modul Complete

Konten utama:
1. Ringkasan total data Modul Complete, disetujui, tidak disetujui, dan revisi.
2. Filter Account Manager, Inputer, status persetujuan, dan periode.
3. Tabel data Modul Complete.
4. Catatan revisi apabila tersedia.
5. Aksi tambah, ubah, hapus, dan detail sesuai hak akses.
6. Aksi tambah, ubah, hapus, dan detail dijalankan melalui modal/dialog.

Kolom tabel utama:
1. Nomor Complete.
2. Inputer.
3. Account Manager.
4. Status persetujuan.
5. Tanggal Complete.
6. Catatan revisi.
7. Periode.
8. Update terakhir.
9. Aksi.

### 1.2.6 Halaman Manajemen Pengguna

Konten utama:
1. Daftar pengguna.
2. Filter peran dan status akun.
3. Aksi tambah pengguna.
4. Aksi ubah pengguna.
5. Aksi aktifkan atau nonaktifkan akun.
6. Aksi hapus atau soft delete sesuai aturan sistem.
7. Aksi tambah, ubah, detail, aktif/nonaktif, dan hapus pengguna dijalankan melalui modal/dialog.

Kolom tabel utama:
1. Nama.
2. Email.
3. Peran.
4. Nomor telepon.
5. Status akun.
6. Dibuat pada.
7. Aksi.

### 1.2.7 Halaman Profil Pengguna

Konten utama:
1. Section informasi profil.
2. Foto profil.
3. Nama.
4. Email.
5. Nomor telepon.
6. Biodata.
7. Tombol simpan perubahan profil.
8. Section ubah password.
9. Password saat ini.
10. Password baru.
11. Konfirmasi password baru.
12. Tombol simpan password.

UX utama:
1. Informasi profil dan penggantian password berada dalam satu halaman Profil Pengguna, dapat dipisahkan menggunakan section atau tab.
2. Peran dan status akun dapat ditampilkan sebagai informasi read-only.
3. Pengguna tidak dapat mengubah peran atau status aktif miliknya sendiri.
4. Upload foto menampilkan preview sebelum disimpan.
5. Field password memiliki tombol show/hide.
6. Pesan validasi ditampilkan per field.
7. Setelah perubahan profil atau password berhasil, sistem menampilkan notifikasi berhasil.
8. Tombol simpan profil dan tombol simpan password dipisahkan agar validasi password tidak mengganggu penyimpanan profil.

## 1.3 Navigasi

### 1.3.1 Struktur Menu

Menu sidebar menggunakan pengelompokan visual agar pengguna memahami konteks setiap menu.

Struktur sidebar:
1. Dashboard.
2. Group label: Monitoring (label grup, tidak dapat diklik).
   1. Order Status.
   2. Order EDK.
   3. Modul Complete.
3. Manajemen Pengguna.

Menu di luar sidebar:
1. Profil Pengguna (diakses melalui menu pengguna di top bar).
2. Logout (diakses melalui menu pengguna di top bar).

Aturan pengelompokan:
1. Group label Monitoring ditampilkan sebagai teks kecil atau divider berteks di sidebar, bukan sebagai menu yang dapat diklik.
2. Order Status, Order EDK, dan Modul Complete ditampilkan sebagai sub-item di bawah group label Monitoring dengan indentasi ringan.
3. Pengelompokan ini membantu pengguna memahami bahwa ketiga menu tersebut merupakan satu kelompok fitur monitoring dengan konteks data yang berbeda.
4. Group label tetap tampil untuk semua peran karena ketiga menu monitoring dapat diakses oleh semua peran.

### 1.3.2 Navigasi Berdasarkan Peran

| Menu | Super Admin | Admin/Inputer | Account Manager |
| --- | --- | --- | --- |
| Dashboard | Tampil | Tampil | Tampil |
| Group: Monitoring | Tampil | Tampil | Tampil |
| — Order Status | Tampil | Tampil | Tampil |
| — Order EDK | Tampil | Tampil | Tampil |
| — Modul Complete | Tampil | Tampil | Tampil |
| Manajemen Pengguna | Tampil | Tidak tampil | Tidak tampil |
| Profil Pengguna (top bar) | Tampil | Tampil | Tampil |
| Logout (top bar) | Tampil | Tampil | Tampil |

### 1.3.3 Perilaku Navigasi

1. Menu aktif harus terlihat jelas, termasuk sub-item di bawah group label Monitoring.
2. Jika salah satu sub-item Monitoring aktif, group label Monitoring juga dapat diberi indikator visual ringan.
3. Menu yang tidak sesuai peran tidak ditampilkan.
4. Jika pengguna mengakses URL yang tidak sesuai izin, sistem menampilkan pesan tidak memiliki izin atau mengarahkan ke halaman yang diizinkan.
5. Sidebar desktop dapat dibuat collapsible apabila ruang layar terbatas.
6. Pada mobile, navigasi ditampilkan melalui drawer atau sheet dengan struktur pengelompokan yang sama.
7. Logout dan Profil Pengguna berada pada menu pengguna di top bar.

## 1.4 Komponen UI

### 1.4.1 Komponen Dasar

| Komponen | Penggunaan |
| --- | --- |
| Button | Aksi utama, sekunder, destructive, dan ghost. |
| Input | Field teks seperti email, nama, nomor order, dan referensi EDK. |
| Select | Pilihan peran, status, Inputer, Account Manager, dan periode. |
| Textarea | Catatan, biodata, dan catatan revisi. |
| Checkbox | Pengaturan boolean seperti status aktif apabila diperlukan. |
| Switch | Status aktif/nonaktif akun. |
| Badge | Peran, status order, status EDK, status Modul Complete, dan status akun. |
| Card | Kartu statistik dan ringkasan data. |
| Table | Data monitoring dan daftar pengguna. |
| Dialog | Form tambah, ubah, konfirmasi hapus, dan detail data. |
| Dropdown Menu | Menu pengguna, aksi baris tabel, dan pilihan cepat. |
| Tabs | Pemisahan tampilan ringkasan apabila halaman membutuhkan tab. |
| Skeleton | Loading state pada kartu, grafik, dan tabel. |
| Toast | Notifikasi berhasil, gagal, atau peringatan. |

### 1.4.2 Button

Variant button:
1. Primary: aksi utama seperti Tambah Data, Simpan, Login.
2. Secondary: aksi pendukung seperti Reset Filter atau Batal.
3. Outline: aksi netral seperti Detail.
4. Ghost: aksi icon ringan di tabel atau toolbar.
5. Destructive: aksi hapus atau nonaktifkan.

Aturan button:
1. Satu area kerja hanya memiliki satu primary action utama.
2. Button yang sedang memproses request masuk loading state.
3. Button destructive harus meminta konfirmasi sebelum menjalankan aksi.
4. Button icon harus memiliki tooltip atau label aksesibilitas.

### 1.4.3 Icon

Icon menggunakan lucide-vue-next apabila tersedia.

Contoh penggunaan:
1. Dashboard: `LayoutDashboard`.
2. Order Status: `ClipboardList`.
3. Order EDK: `FileCheck`.
4. Modul Complete: `CheckCircle`.
5. Manajemen Pengguna: `Users`.
6. Profil: `User`.
7. Password di halaman profil: `KeyRound`.
8. Logout: `LogOut`.
9. Filter: `SlidersHorizontal`.
10. Tambah: `Plus`.
11. Ubah: `Pencil`.
12. Hapus: `Trash2`.
13. Detail: `Eye`.

## 1.5 Form dan Input

### 1.5.1 Prinsip Form

1. Label harus jelas dan berada dekat input.
2. Field wajib diberi indikator visual.
3. Placeholder hanya menjadi contoh format, bukan pengganti label.
4. Error validation ditampilkan di bawah field yang bermasalah.
5. Form panjang dapat dibagi menjadi beberapa grup.
6. Tombol Simpan dan Batal diletakkan di bagian bawah form.
7. Form harus tetap dapat digunakan dengan keyboard.

### 1.5.2 Tipe Input

| Data | Komponen |
| --- | --- |
| Email | Input type email |
| Password | Password input dengan show/hide |
| Nama pengguna | Input text |
| Nomor telepon | Input text |
| Peran | Select |
| Status akun | Switch atau Select |
| Periode | Month picker atau Select periode |
| Status monitoring | Select |
| Catatan | Textarea |
| Foto profil | File input dengan preview |

### 1.5.3 Form Filter

Filter digunakan pada Dashboard, Order Status, Order EDK, Modul Complete, dan Manajemen Pengguna.

Aturan filter:
1. Filter utama adalah periode (menggunakan Month Picker format YYYY-MM).
2. Filter Inputer hanya tampil jika sesuai dengan peran pengguna.
3. Filter Account Manager hanya tampil jika sesuai dengan peran pengguna.
4. Filter status tersedia pada halaman monitoring.
5. Semua filter menggunakan mekanisme auto-submit (data langsung berubah saat filter dipilih, tanpa tombol terapkan).
6. Filter aktif dapat ditampilkan sebagai badge ringkas di atas tabel.

## 1.6 Tabel dan Data List

### 1.6.1 Prinsip Tabel

1. Tabel digunakan untuk data operasional yang perlu dipindai cepat.
2. Header tabel harus jelas dan singkat.
3. Status ditampilkan sebagai badge.
4. Kolom angka menggunakan alignment kanan apabila berisi nilai numerik.
5. Kolom aksi diletakkan di sisi kanan.
6. Tabel harus memiliki pagination.
7. Tabel harus memiliki empty state.
8. Baris tabel dapat memiliki hover state ringan.

### 1.6.2 Fitur Tabel

Fitur tabel yang disarankan:
1. Search berdasarkan identifier atau nama pelanggan.
2. Filter berdasarkan periode, status, Inputer, dan Account Manager.
3. Sorting pada kolom tanggal update atau nama.
4. Pagination dengan pilihan jumlah baris per halaman.
5. Dropdown action pada setiap baris.
6. Responsive horizontal scroll pada layar kecil.

### 1.6.3 Aksi Baris Tabel

| Aksi | Super Admin | Admin/Inputer | Account Manager |
| --- | --- | --- | --- |
| Detail | Ya | Ya | Ya |
| Tambah | Ya | Ya | Tidak |
| Ubah | Ya | Ya, sesuai hak data | Tidak |
| Hapus | Ya | Ya, sesuai hak data | Tidak |
| Nonaktifkan pengguna | Ya | Tidak | Tidak |

Aturan aksi:
1. Aksi Detail selalu membuka modal detail.
2. Aksi Tambah dan Ubah menggunakan modal form.
3. Aksi Hapus dan Nonaktifkan pengguna menggunakan confirmation dialog.

## 1.7 Grafik dan Statistik

### 1.7.1 Kartu Statistik
Elemen kartu:
1. Label metrik.
2. Nilai utama.
3. Status atau konteks periode.
4. Icon pendukung.
5. Indikator warna sesuai jenis metrik.

Metrik dashboard:
1. Total Order.
2. Pending BASO.
3. Complete.
4. Failed.
5. Sisa Populasi.

Metrik Order EDK:
1. Lanjut.
2. Tidak Lanjut.
3. Belum Input.
4. OGP.
5. Complete.
6. Achievement.
7. Sisa Populasi.

### 1.7.2 Grafik

Jenis grafik yang disarankan:
1. Bar chart untuk rekapitulasi berdasarkan Inputer atau Account Manager.
2. Donut chart untuk komposisi status.
3. Line chart untuk tren jumlah data per periode apabila tersedia data historis.

Aturan grafik:
1. Grafik harus memiliki judul yang jelas.
2. Tooltip menampilkan nilai dan label status.
3. Warna grafik harus konsisten dengan status badge.
4. Grafik tetap menampilkan empty state apabila data kosong.
5. Grafik tidak boleh menjadi satu-satunya sumber informasi; angka ringkasan tetap perlu tersedia.

## 1.8 Modal / Dialog

### 1.8.1 Jenis Dialog

| Dialog | Penggunaan |
| --- | --- |
| Form dialog | Tambah atau ubah data CRUD sederhana. |
| Detail dialog | Melihat detail data tanpa pindah halaman. |
| Confirmation dialog | Konfirmasi hapus, logout, atau nonaktifkan akun. |
| Alert dialog | Peringatan akses atau aksi penting. |

### 1.8.2 Aturan Dialog

1. Dialog memiliki judul yang jelas.
2. Dialog form memiliki tombol Simpan dan Batal.
3. Dialog destructive menggunakan tombol berwarna destructive.
4. Dialog dapat ditutup dengan tombol close atau tombol Batal.
5. Saat proses simpan berjalan, tombol aksi masuk loading state.
6. Dialog tidak boleh menutup otomatis apabila validasi gagal.

### 1.8.3 Aturan CRUD dan Detail

1. Seluruh operasi CRUD pada sistem ini menggunakan modal/dialog. Tidak ada form CRUD yang menggunakan halaman terpisah karena seluruh form pada sistem ini memiliki jumlah field yang ringkas dan tidak membutuhkan alur bertahap.
2. Detail data menggunakan modal detail, bukan halaman terpisah.
3. Create dan update menggunakan form dialog agar pengguna tetap berada pada halaman daftar dan dapat langsung melihat hasil perubahan pada tabel.
4. Delete, soft delete, aktif/nonaktif akun, dan aksi destructive lain menggunakan confirmation dialog.
5. Detail modal harus bersifat read-only dan menampilkan aksi lanjutan sesuai hak akses, seperti Ubah atau Hapus, apabila pengguna memiliki izin.
6. Modal CRUD harus menampilkan pesan validasi per field dan tidak menutup otomatis saat validasi gagal.
7. Setelah data berhasil disimpan, modal ditutup otomatis dan tabel diperbarui tanpa memuat ulang halaman.

## 1.9 Alert dan Notification

### 1.9.1 Toast Notification

Jenis toast:
1. Success: data berhasil disimpan, diperbarui, atau dihapus.
2. Error: data gagal disimpan atau validasi gagal secara umum.
3. Warning: aksi membutuhkan perhatian.
4. Info: informasi umum seperti sesi berakhir.

Contoh pesan:
1. Data berhasil disimpan.
2. Data berhasil diperbarui.
3. Data berhasil dihapus.
4. Anda tidak memiliki izin untuk mengakses fitur ini.
5. Data sudah diperbarui oleh pengguna lain. Muat ulang halaman untuk melihat data terbaru.

### 1.9.2 Inline Alert
Penggunaan:
1. Error login.
2. Akun tidak aktif.
3. Tidak memiliki izin.
4. Kegagalan memuat data.
5. Informasi filter aktif atau data kosong.

## 1.10 Loading State

Loading state harus memberi tanda bahwa sistem sedang memproses data.

Aturan:
1. Halaman dashboard menggunakan skeleton pada kartu statistik dan grafik.
2. Tabel menggunakan skeleton row saat data dimuat.
3. Button menggunakan spinner kecil saat form disubmit.
4. Filter dapat menampilkan loading ringan saat data diperbarui.
5. Submit button dinonaktifkan sementara saat request berlangsung.
6. Loading state tidak boleh menghapus nilai form yang sudah diisi.

Komponen:
1. Skeleton card.
2. Skeleton table row.
3. Button spinner.
4. Progress indicator sederhana apabila import data massal ditambahkan pada pengembangan berikutnya.

## 1.11 Empty State

Empty state digunakan saat data tidak tersedia.

Kondisi empty state:
1. Belum ada data monitoring.
2. Hasil filter tidak ditemukan.
3. Account Manager tidak memiliki data pada periode tertentu.
4. Data soft delete tidak ditampilkan pada view aktif.

Isi empty state:
1. Judul singkat.
2. Deskripsi penyebab data kosong.
3. Aksi yang relevan sesuai peran.

Contoh:
1. Untuk Admin/Inputer: tampilkan tombol Tambah Data apabila memiliki izin.
2. Untuk Account Manager: tampilkan informasi bahwa data belum tersedia.
3. Untuk Super Admin: tampilkan opsi reset filter atau tambah data apabila relevan.

## 1.12 Error State

Error state digunakan saat sistem gagal memuat atau memproses data.

Jenis error state:
1. Error validasi form.
2. Error tidak memiliki izin.
3. Error data tidak ditemukan.
4. Error server.
5. Error sesi berakhir.
6. Error konflik pembaruan data.

Aturan UI:
1. Error validasi ditampilkan di bawah field terkait.
2. Error halaman ditampilkan dalam panel ringkas.
3. Error akses menampilkan pesan tidak memiliki izin.
4. Error sesi berakhir mengarahkan pengguna ke login.
5. Error konflik data memberi instruksi untuk memuat ulang halaman.

## 1.13 Responsive Design

Sistem harus nyaman digunakan pada desktop, laptop, tablet, dan mobile.

### 1.13.1 Desktop

Aturan desktop:
1. Sidebar tampil tetap di sisi kiri.
2. Konten menggunakan grid yang rapat dan mudah dipindai.
3. Kartu statistik dapat tampil dalam 4 sampai 5 kolom.
4. Tabel menggunakan lebar penuh.
5. Filter dapat tampil satu baris apabila ruang cukup.

### 1.13.2 Tablet

Aturan tablet:
1. Sidebar dapat dibuat collapsible.
2. Kartu statistik tampil dalam 2 kolom.
3. Filter dapat berpindah menjadi 2 baris.
4. Tabel dapat menggunakan horizontal scroll.

### 1.13.3 Mobile

Aturan mobile:
1. Sidebar diganti menjadi drawer atau sheet.
2. Kartu statistik tampil satu kolom atau dua kolom sesuai ruang.
3. Filter disusun vertikal.
4. Tabel menggunakan horizontal scroll atau list card ringkas.
5. Aksi baris tabel masuk ke dropdown menu.
6. Tombol utama tetap mudah dijangkau.

Breakpoint yang disarankan mengikuti Tailwind CSS:
1. `sm`: 640px.
2. `md`: 768px.
3. `lg`: 1024px.
4. `xl`: 1280px.
5. `2xl`: 1536px.

## 1.14 Warna, Font, dan Style

### 1.14.1 Prinsip Visual

1. Tampilan harus bersih, profesional, dan fokus pada data.
2. Tema visual harus mengikuti identitas Telkom Indonesia, yaitu merah, putih, hitam, dan abu-abu.
3. Merah Telkom digunakan sebagai warna utama untuk brand accent dan aksi utama.
4. Putih, hitam, dan abu-abu digunakan sebagai dasar antarmuka agar dashboard tetap nyaman dibaca.
5. Warna status seperti hijau, biru, dan kuning hanya digunakan sebagai warna semantik pendukung, bukan warna dominan brand.
6. Kontras teks harus cukup agar mudah dibaca.
7. Elemen dekoratif tidak boleh mengganggu data operasional.

### 1.14.2 Palet Warna

Palet utama mengikuti warna identitas Telkom Indonesia. Nilai HEX digunakan sebagai panduan implementasi digital agar konsisten di Tailwind CSS dan komponen shadcn-vue.

| Token | Warna | Penggunaan |
| --- | --- | --- |
| `telkom-red` | `#E42313` | Warna utama Telkom untuk primary button, active menu, link aktif, dan aksen penting. |
| `telkom-red-dark` | `#B91C1C` | Hover dan pressed state untuk elemen primary. |
| `telkom-red-soft` | `#FEE2E2` | Background lembut untuk badge, alert ringan, dan highlight non-destruktif. |
| `telkom-black` | `#1D1D1B` | Teks utama, logo-safe text, dan elemen dengan prioritas tinggi. |
| `telkom-grey` | `#706F6F` | Teks sekunder, metadata, border kuat, dan elemen netral. |
| `telkom-grey-soft` | `#F3F4F6` | Background area sekunder, table header, dan hover row. |
| `telkom-white` | `#FFFFFF` | Surface utama, card, dialog, dan area konten. |
| `background` | `#F8FAFC` | Latar aplikasi agar tidak terlalu putih pada layar kerja panjang. |
| `surface` | `#FFFFFF` | Card, dialog, tabel, dan panel. |
| `border` | `#E2E8F0` | Garis batas komponen. |
| `text-primary` | `#1D1D1B` | Teks utama mengikuti hitam Telkom. |
| `text-secondary` | `#475569` | Teks pendukung. |
| `text-muted` | `#64748B` | Metadata dan hint. |
| `primary` | `#E42313` | Aksi utama dan aksen Telkom. |
| `primary-dark` | `#B91C1C` | Hover primary. |
| `success` | `#16A34A` | Complete dan Disetujui. |
| `warning` | `#D97706` | Pending, Belum Input, dan Revisi. |
| `danger` | `#DC2626` | Failed, error, dan destructive action. |
| `info` | `#2563EB` | Provisioning, OGP, dan informasi. |
| `neutral` | `#64748B` | Cancel, Abandoned, dan status netral. |

Aturan penggunaan warna:
1. Primary action menggunakan `telkom-red`.
2. Sidebar active state menggunakan `telkom-red` atau border kiri merah.
3. Header tabel dan background aplikasi menggunakan abu-abu muda agar data tetap mudah dipindai.
4. Teks utama menggunakan `telkom-black`, bukan hitam pekat default browser.
5. Tombol destructive tetap menggunakan warna bahaya yang jelas, tetapi tidak boleh terlihat sama dengan primary action.
6. Grafik boleh memakai warna semantik, tetapi merah Telkom tetap menjadi warna pertama untuk seri utama.
7. Background aplikasi tidak menggunakan gradien dominan agar tetap sesuai karakter dashboard operasional.

### 1.14.3 Typography

Font yang disarankan:
1. `Gotham Rounded` sebagai font utama apabila lisensi dan file font tersedia.
2. `Gotham` sebagai font sekunder apabila tersedia.
3. `Inter`, `Nunito Sans`, atau font sans-serif sistem sebagai fallback implementasi.
4. Body text menggunakan ukuran 14px sampai 16px.
5. Heading halaman menggunakan ukuran 20px sampai 24px.
6. Label tabel dan form menggunakan ukuran 12px sampai 14px.
7. Angka statistik menggunakan ukuran 24px sampai 32px.

Aturan typography:
1. Tidak menggunakan letter spacing negatif.
2. Teks harus tetap terbaca pada mobile.
3. Heading tidak dibuat terlalu besar karena aplikasi bersifat operasional.
4. Label status harus singkat dan konsisten.

### 1.14.4 Spacing dan Radius

Aturan:
1. Radius card dan panel maksimal 8px.
2. Spacing antar section menggunakan 24px sampai 32px.
3. Spacing dalam card menggunakan 16px sampai 24px.
4. Tinggi button standar 36px sampai 40px.
5. Tinggi input standar 36px sampai 40px.
6. Border digunakan tipis dan konsisten.

## 1.15 Design System / Component Library

### 1.15.1 Library Utama

1. Tailwind CSS digunakan untuk styling utility-first.
2. shadcn-vue digunakan sebagai komponen dasar.
3. Inertia.js digunakan untuk perpindahan halaman tanpa REST API terpisah.
4. lucide-vue-next digunakan untuk icon.

### 1.15.2 Token Design

Token yang perlu disiapkan:
1. Color token.
2. Typography token.
3. Spacing token.
4. Border radius token.
5. Shadow token.
6. Status token.

### 1.15.3 Komponen yang Perlu Distandarkan

Komponen aplikasi:
1. `AppLayout`.
2. `AuthLayout`.
3. `SidebarNav`.
4. `TopBar`.
5. `PageHeader`.
6. `FilterBar`.
7. `StatCard`.
8. `StatusBadge`.
9. `DataTable`.
10. `Pagination`.
11. `EmptyState`.
12. `ConfirmDialog`.
13. `FormError`.
14. `UserMenu`.

### 1.15.4 Standar Interaksi

1. Hover state digunakan pada button, menu, dan baris tabel.
2. Focus ring wajib terlihat untuk navigasi keyboard.
3. Disabled state harus jelas dan tidak dapat diklik.
4. Active state digunakan pada menu aktif.
5. Loading state ditampilkan ketika request berlangsung.
6. Toast digunakan setelah aksi berhasil atau gagal.

## 1.16 Status Badge

Status badge harus konsisten antara Dashboard, tabel, grafik, dan detail data.

### 1.16.1 Badge Order Status

| Status | Warna | Keterangan |
| --- | --- | --- |
| Provisioning | Info | Pekerjaan masih berjalan. |
| Pending BASO | Warning | Menunggu proses atau kelengkapan BASO. |
| Pending Billing Approval | Warning | Menunggu persetujuan billing. |
| Complete | Success | Pekerjaan selesai. |
| Failed | Danger | Pekerjaan gagal diproses. |
| Cancel / Abandoned | Neutral | Pekerjaan dibatalkan atau tidak dilanjutkan. |

### 1.16.2 Badge Order EDK

| Status | Warna | Keterangan |
| --- | --- | --- |
| Lanjut | Info | Pekerjaan diteruskan ke proses berikutnya. |
| Tidak Lanjut | Neutral | Pekerjaan tidak diteruskan. |
| Belum Input | Warning | Data belum diperbarui oleh Inputer. |
| OGP | Info | Pekerjaan sedang dalam proses operasional. |
| Complete | Success | Pekerjaan EDK selesai. |

### 1.16.3 Badge Modul Complete

| Status | Warna | Keterangan |
| --- | --- | --- |
| Menunggu Persetujuan | Warning | Data Modul Complete belum diputuskan. |
| Disetujui | Success | Data Modul Complete diterima. |
| Tidak Disetujui | Danger | Data Modul Complete ditolak. |
| Revisi | Warning | Data membutuhkan perbaikan. |

### 1.16.4 Badge Peran dan Akun

| Status | Warna | Keterangan |
| --- | --- | --- |
| Super Admin | Primary | Peran akses penuh. |
| Admin/Inputer | Info | Peran pengelola data operasional. |
| Account Manager | Neutral | Peran monitoring data miliknya. |
| Aktif | Success | Akun dapat login. |
| Tidak Aktif | Neutral | Akun tidak dapat login. |

## 1.17 Dashboard Berdasarkan Peran

Dashboard harus menampilkan data dan aksi sesuai peran pengguna.

### 1.17.1 Super Admin

Tampilan:
1. Melihat seluruh statistik operasional.
2. Melihat rekapitulasi seluruh Inputer.
3. Melihat rekapitulasi seluruh Account Manager.
4. Dapat menggunakan filter Inputer dan Account Manager.
5. Dapat mengakses Manajemen Pengguna.

Aksi:
1. Melihat detail data.
2. Mengelola pengguna.
3. Mengakses seluruh halaman monitoring.

### 1.17.2 Admin/Inputer

Tampilan:
1. Melihat statistik data dengan `inputer_id` miliknya.
2. Melihat daftar Order Status, Order EDK, dan Modul Complete sesuai hak akses.
3. Melihat filter yang tersedia untuk cakupan data Admin/Inputer.
4. Tidak melihat menu Manajemen Pengguna.
5. Tidak melihat tabel rekapitulasi (disembunyikan khusus role ini).

Aksi:
1. Menambah data monitoring.
2. Mengubah data monitoring.
3. Menghapus data monitoring sesuai hak akses.
4. Memperbarui profil dan password sendiri.

### 1.17.3 Account Manager

Tampilan:
1. Melihat statistik data yang berkaitan dengan `account_manager_id` miliknya.
2. Melihat Order Status, Order EDK, dan Modul Complete miliknya.
3. Tidak melihat tombol tambah, ubah, hapus, atau persetujuan data.
4. Tidak melihat menu Manajemen Pengguna.
5. Tidak melihat tabel rekapitulasi (disembunyikan khusus role ini).

Aksi:
1. Melihat detail data monitoring.
2. Menggunakan filter yang tersedia.
3. Memperbarui profil dan password sendiri.

## 1.18 Privasi Data di UI

### 1.18.1 Prinsip Privasi

1. Pengguna hanya melihat data sesuai hak akses.
2. Account Manager hanya melihat data dengan `account_manager_id` miliknya.
3. Data operasional tidak boleh ditampilkan kepada guest atau pengguna belum login.
4. Data sensitif seperti password tidak pernah ditampilkan di UI.
5. Pesan error tidak boleh mengungkap informasi keamanan yang detail.

### 1.18.2 Perlindungan Data pada Tampilan

Aturan:
1. Menu yang tidak sesuai peran tidak ditampilkan.
2. Tombol aksi yang tidak sesuai peran tidak ditampilkan.
3. Query data tetap harus dibatasi di backend meskipun tombol disembunyikan di UI.
4. Field peran dan status akun hanya dapat dikelola oleh Super Admin.
5. Email dan nomor telepon pengguna ditampilkan hanya pada konteks yang diperlukan.
6. Log aktivitas tidak perlu ditampilkan kepada peran selain Super Admin apabila fitur audit disediakan.

### 1.18.3 Tampilan Data Kosong Karena Batasan Akses

Jika data tidak muncul karena batasan peran, UI harus menampilkan pesan netral seperti:
1. Data belum tersedia untuk periode ini.
2. Tidak ada data yang sesuai dengan filter.
3. Anda tidak memiliki izin untuk mengakses fitur ini.

UI tidak perlu menjelaskan detail data milik pengguna lain yang tidak dapat diakses.
