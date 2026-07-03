# 1. Ringkasan Proyek

## 1.1 Deskripsi
Sistem Monitoring Operasional adalah aplikasi web internal untuk Divisi Government Service PT Telkom Indonesia Regional Sulawesi Bagian Tengah. Sistem ini membantu pemantauan aktivitas operasional, evaluasi kinerja, dan penyusunan laporan data Order Status, Order EDK, serta Modul Complete secara lebih cepat, terstruktur, dan mudah dipahami.

Sistem ini berfungsi sebagai dashboard pendukung monitoring, bukan pengganti aplikasi operasional utama perusahaan seperti CRM/Oracle, SC-One, Carent, atau NCX. Data operasional bersumber dari Dashboard NCX, kemudian diinput atau diperbarui oleh Admin/Inputer ke dalam sistem agar dapat disajikan kembali dalam bentuk statistik, grafik, tabel monitoring, dan indikator kinerja.

Sistem memiliki tiga peran pengguna: Super Admin, Admin/Inputer, dan Account Manager. Super Admin digunakan oleh Manager atau Mentor untuk memantau seluruh aktivitas dan mengelola pengguna. Admin/Inputer mengelola data monitoring operasional. Account Manager memantau perkembangan pekerjaan yang menjadi tanggung jawabnya berdasarkan data yang sudah diperbarui oleh Admin/Inputer.

## 1.2 Tujuan Proyek
1. Menyediakan dashboard monitoring khusus bagi Divisi Government Service Regional Sulbagteng.
2. Menyajikan data operasional dari Dashboard NCX dalam bentuk informasi yang mudah dipahami.
3. Membantu pemantauan kinerja Admin/Inputer dan Account Manager berdasarkan pembaruan data terakhir.
4. Mempermudah Mentor dan Manager dalam mengevaluasi aktivitas operasional.
5. Mengurangi ketergantungan pada monitoring manual menggunakan Google Spreadsheet.
6. Menyediakan laporan operasional yang lebih cepat, akurat, dan terstruktur.
7. Mendukung pengambilan keputusan berdasarkan data operasional yang sudah divisualisasikan.
8. Menyediakan pengelolaan pengguna dan hak akses sesuai struktur kerja Divisi Government Service.

## 1.3 Target Pengguna
1. Super Admin
   Super Admin adalah pengguna dengan hak akses tertinggi. Peran ini digunakan oleh Manager atau Mentor untuk mengawasi seluruh aktivitas operasional, melihat seluruh data monitoring, dan mengelola akun pengguna.

2. Admin/Inputer
   Admin/Inputer adalah pengguna yang bertugas menginput, memperbarui, dan mengelola data monitoring operasional berdasarkan data dari Dashboard NCX.

3. Account Manager
   Account Manager adalah pengguna yang memantau pekerjaan yang menjadi tanggung jawabnya. Account Manager tidak melakukan input, perubahan, atau penghapusan data operasional.

## 1.4 Istilah Baku
| Istilah | Definisi |
| --- | --- |
| Sistem | Sistem Monitoring Operasional berbasis web untuk Divisi Government Service Regional Sulbagteng. |
| Dashboard NCX | Sumber data operasional yang menjadi acuan input dan pembaruan data monitoring. |
| Order Status | Modul monitoring status pekerjaan provisioning, termasuk Pending BASO, Pending Billing Approval, Complete, Failed, dan Cancel/Abandoned. |
| Order EDK | Modul monitoring progres pekerjaan EDK, termasuk Lanjut, Tidak Lanjut, Belum Input, OGP, Complete, Achievement, dan Sisa Populasi. |
| Modul Complete | Modul untuk mencatat dan memantau pekerjaan yang sudah masuk tahap penyelesaian serta status persetujuannya. |
| Status Complete | Status yang menandakan pekerjaan sudah selesai pada modul Order Status atau Order EDK. |
| Admin/Inputer | Peran pengguna yang bertanggung jawab mengelola data monitoring operasional. |
| Account Manager | Peran pengguna yang hanya memantau data pekerjaan miliknya. |
| Super Admin | Peran pengguna yang memiliki akses penuh untuk monitoring, pengelolaan pengguna, dan pengaturan hak akses. |

# 2. Ruang Lingkup

## 2.1 Termasuk dalam Ruang Lingkup
1. Autentikasi pengguna
   Sistem menyediakan fitur login agar hanya pengguna terdaftar dan aktif yang dapat mengakses aplikasi.

2. Dashboard monitoring operasional
   Sistem menampilkan ringkasan informasi seperti Total Order, Pending BASO, Complete, Failed, Sisa Populasi, grafik monitoring, serta rekapitulasi berdasarkan Admin/Inputer dan Account Manager.

3. Monitoring Order Status
   Sistem menampilkan data Order Status, termasuk tahapan provisioning, Pending BASO, Pending Billing Approval, Complete, Failed, serta Cancel/Abandoned. Data dapat difilter berdasarkan Admin/Inputer, Account Manager, status, dan periode.

4. Monitoring Order EDK
   Sistem menampilkan progres pekerjaan EDK berdasarkan status Lanjut, Tidak Lanjut, Belum Input, OGP, Complete, Persentase Achievement, dan Sisa Populasi.

5. Modul Complete
   Sistem mencatat dan menampilkan hasil penyelesaian pekerjaan, termasuk jumlah data pada Modul Complete, status persetujuan, catatan revisi, dan statistik penyelesaian berdasarkan Account Manager.

6. Manajemen Pengguna
   Sistem menyediakan fitur untuk menambah, mengubah, menonaktifkan, menghapus, menentukan peran, dan mengatur status akun pengguna. Fitur ini hanya dapat diakses oleh Super Admin.

7. Pengelolaan profil dan password
   Setiap pengguna dapat melihat dan memperbarui profilnya sendiri serta mengganti password akun masing-masing.

8. Pengaturan hak akses
   Sistem membedakan akses berdasarkan tiga peran pengguna: Super Admin, Admin/Inputer, dan Account Manager.

## 2.2 Di Luar Ruang Lingkup
1. Sistem tidak menggantikan aplikasi utama perusahaan seperti CRM/Oracle, SC-One, Carent, atau NCX.
2. Sistem tidak mengubah data langsung pada server NCX atau aplikasi operasional utama perusahaan.
3. Sistem tidak ditujukan untuk seluruh divisi PT Telkom Indonesia; ruang lingkupnya hanya Divisi Government Service Regional Sulbagteng.
4. Account Manager tidak dapat menambah, mengubah, menghapus, atau menyetujui data operasional.
5. Admin/Inputer tidak dapat mengelola pengguna, peran, atau hak akses.
6. Sistem tidak menjamin sinkronisasi otomatis langsung dari NCX; ketepatan data mengikuti waktu input atau pembaruan terakhir oleh Admin/Inputer.

## 2.3 Batasan Proyek
1. Sistem dikembangkan sebagai dashboard monitoring internal untuk Divisi Government Service PT Telkom Indonesia Regional Sulawesi Bagian Tengah.
2. Data operasional berasal dari Dashboard NCX dan disajikan kembali dalam bentuk yang lebih mudah dianalisis.
3. Fokus monitoring terbatas pada Order Status, Order EDK, dan Modul Complete.
4. Sistem hanya memiliki tiga peran pengguna: Super Admin, Admin/Inputer, dan Account Manager.
5. Super Admin memiliki akses penuh terhadap seluruh menu, termasuk Manajemen Pengguna dan pengaturan hak akses.
6. Admin/Inputer dapat mengelola data operasional sesuai tanggung jawabnya, tetapi tidak dapat mengakses Manajemen Pengguna.
7. Account Manager hanya dapat melihat data monitoring yang berkaitan dengan pekerjaannya.
8. Sistem tidak melakukan integrasi perubahan data ke CRM/Oracle, SC-One, Carent, atau NCX.

# 3. Masalah dan Solusi

## 3.1 Masalah Utama
Divisi Government Service PT Telkom Indonesia Regional Sulawesi Bagian Tengah membutuhkan media monitoring internal yang lebih terpusat untuk memantau, menganalisis, dan mengevaluasi data operasional. Data Order Status, Order EDK, dan Modul Complete perlu disajikan dalam bentuk yang lebih cepat dibaca, terstruktur, dan mudah dipahami oleh Manager, Mentor, Admin/Inputer, dan Account Manager.

Proses monitoring sebelumnya masih bergantung pada media manual seperti Google Spreadsheet. Kondisi ini membuat pemantauan dan pelaporan berpotensi kurang efisien, kurang terpusat, dan membutuhkan waktu lebih lama saat data perlu diolah atau dievaluasi.

## 3.2 Dampak Masalah
1. Evaluasi kinerja Admin/Inputer dan Account Manager membutuhkan waktu lebih lama.
2. Monitoring aktivitas operasional harian belum tersaji dalam satu dashboard khusus.
3. Penyajian laporan operasional kurang terstruktur.
4. Data monitoring sulit dipahami apabila hanya ditampilkan sebagai data mentah.
5. Manager dan Mentor membutuhkan waktu lebih banyak untuk melihat kondisi pekerjaan secara keseluruhan.
6. Account Manager belum memiliki media ringkas untuk memantau perkembangan pekerjaan miliknya.

## 3.3 Solusi yang Ditawarkan
Solusi yang ditawarkan adalah pengembangan Sistem Monitoring Operasional berbasis web sebagai dashboard internal Divisi Government Service Regional Sulbagteng. Sistem menyajikan data operasional dalam bentuk statistik, grafik, tabel monitoring, dan indikator kinerja agar informasi lebih mudah dipahami dan dianalisis.

Sistem menyediakan fitur Dashboard, Order Status, Order EDK, Modul Complete, Manajemen Pengguna, Profil Pengguna termasuk penggantian password, dan Logout. Setiap fitur mendukung proses monitoring aktivitas operasional, pengelolaan data, serta evaluasi kinerja Admin/Inputer dan Account Manager.

Setiap pengguna memiliki hak akses sesuai perannya. Super Admin dapat mengelola seluruh sistem dan pengguna. Admin/Inputer dapat mengelola data monitoring operasional. Account Manager dapat memantau perkembangan pekerjaan berdasarkan data yang diperbarui oleh Admin/Inputer.

## 3.4 Nilai Utama Produk
1. Monitoring lebih terpusat
   Seluruh informasi operasional dapat dipantau melalui satu dashboard internal Divisi Government Service Regional Sulbagteng.

2. Data lebih mudah dipahami
   Data operasional disajikan dalam bentuk statistik, grafik, tabel, dan indikator kinerja.

3. Evaluasi kinerja lebih cepat
   Mentor dan Manager dapat memantau aktivitas Admin/Inputer dan Account Manager secara lebih mudah.

4. Pelaporan lebih terstruktur
   Data Order Status, Order EDK, dan Modul Complete dapat disajikan dalam laporan yang rapi dan informatif.

5. Proses manual berkurang
   Sistem membantu mengurangi ketergantungan pada monitoring manual menggunakan Google Spreadsheet.

6. Hak akses lebih jelas
   Setiap pengguna memiliki akses sesuai perannya, sehingga pengelolaan data dan keamanan sistem lebih terkontrol.
