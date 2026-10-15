# To Do Pengembangan SIIP

Disusun 7 Oktober 2026. Dokumen ini berisi rekomendasi pekerjaan berikutnya, bukan pernyataan bahwa fitur sudah dibuat atau izin untuk melakukan deploy dan menghapus data.

## Kondisi awal

Fitur dasar telah tersedia: master barang dan penomoran, impor/ekspor, pengajuan Keuangan–Direksi, PO terhubung pengajuan, penerimaan bertahap, aktivasi aset, label QR, mutasi, audit, surat PO, dan BAST.

Pengujian terakhir setelah BAST: 132 tes lulus dan 2 tes MySQL dilewati pada suite standar. Uji MySQL terpisah untuk penomoran dan batas penerimaan sebelumnya lulus. Uji paralel kuota PO dari pengajuan juga sudah lulus pada 7 Oktober 2026: satu PO diterima dan satu ditolak untuk sisa satu unit.

BAST baru menyimpan snapshot di audit serah terima. BAST lama tanpa snapshot ditandai draf rekonstruksi. Dokumentasi Markdown, Word, dan draw.io dibuat sebelum surat PO dan BAST, sehingga perlu diperbarui.

## Tahap 1 Menuntaskan verifikasi fitur yang tersedia

Prioritas: segera, sebelum menambah modul baru.

- [ ] Verifikasi impor Excel melalui browser: preview tidak menyimpan aset, pemetaan valid, konfirmasi berhasil, dan konfirmasi ulang tidak menggandakan aset.
- [ ] Verifikasi unduhan Excel dan CSV secara manual di Chrome untuk membedakan masalah aplikasi dari blokir ekstensi ERR_BLOCKED_BY_CLIENT.
- [ ] Buka hasil ekspor di Excel dan periksa nomor, serial, tanggal, harga kosong, harga nol, serta kesesuaian jumlah baris.
- [ ] Uji alur nyata pengajuan disetujui → PO sebagian → PO berikutnya → penerimaan sebagian → inventaris.
- [x] Tambahkan uji paralel MySQL untuk kuota PO: dua permintaan memesan sisa jumlah yang sama; total tidak boleh melampaui persetujuan. Hasil 7 Oktober 2026: satu PO draf tersimpan, satu ditolak; jumlah kumulatif satu unit.
- [ ] Uji surat PO dan BAST Word dengan banyak baris, nama panjang, catatan panjang, dan karakter khusus.
- [x] Pastikan snapshot BAST tetap sama setelah perubahan lokasi, kondisi, serial, dan nama petugas. Uji 13 Oktober 2026 juga mencakup perubahan nama ruangan, halaman cetak, dan berkas Word.
- [ ] Konfirmasikan pindai QR dari layar menggunakan ponsel; isi harus tepat sama dengan nomor inventaris.
- [ ] Uji cetak label di kertas ketika printer tersedia: ukuran, skala 100%, keterbacaan, dan hasil pindai.
- [ ] Periksa tampilan desktop serta ponsel untuk form, tabel, tombol cetak, dan pesan kegagalan.

Selesai jika: hasil setiap pemeriksaan dicatat, kegagalan yang ditemukan diperbaiki, dan tidak ada duplikasi nomor maupun jumlah berlebih.

## Tahap 2 Memperbarui panduan dan dokumen kerja

Prioritas: setelah tahap 1 atau bersamaan dengan pencatatan hasil uji.

- [ ] Perbarui dokumentasi Markdown untuk surat PO, BAST, snapshot, akses dokumen, dan hasil pengujian terbaru.
- [ ] Sinkronkan versi Word dengan Markdown.
- [ ] Perbarui draw.io: aliran surat PO, BAST, dan penyimpanan snapshot di audit.
- [ ] Tulis panduan singkat per peran: Pengaju, Keuangan, Direksi, Operasional, dan Super Admin.
- [ ] Tambahkan checklist penerimaan, pemeriksaan, penempelan label, dan serah terima yang dapat dipakai petugas.
- [ ] Tentukan prosedur menangani PO ditolak, pengajuan ditolak, data salah, dan kegagalan impor.
- [ ] Sepakati format dokumen resmi serta kebijakan penomoran PO dan BAST.

Selesai jika: panduan sesuai aplikasi dan petugas dapat mengikuti satu alur lengkap tanpa bantuan pengembang.

## Tahap 3 Melengkapi bukti transaksi

Prioritas: sebelum pemakaian rutin jika bukti dokumen diwajibkan organisasi.

- [ ] Tambahkan lampiran pengajuan dan spesifikasi barang.
- [ ] Tambahkan surat jalan, faktur, bukti penerimaan, serta foto kondisi barang.
- [ ] Tambahkan unggahan BAST yang telah ditandatangani.
- [ ] Hubungkan lampiran ke pengajuan, PO, penerimaan, atau aset yang sesuai.
- [ ] Terapkan penyimpanan privat, pembatasan ukuran/jenis file, dan pemeriksaan izin saat unduh.
- [ ] Tetapkan siapa yang boleh mengganti lampiran dan bagaimana riwayat penggantian disimpan.
- [ ] Tetapkan masa penyimpanan bukti dan audit bersama pengelola.

Selesai jika: bukti transaksi dapat ditelusuri melalui aplikasi dan tidak dapat diakses pengguna tanpa izin.

## Tahap 4 Memperjelas kepemilikan dan akses pengguna

Prioritas: sebelum perluasan ke beberapa cabang.

- [ ] Sepakati matriks hak akses per aksi dan cabang/unit kerja.
- [ ] Hubungkan penerima aset ke akun pengguna atau master pegawai; pertahankan nama historis pada snapshot.
- [ ] Tambahkan penanggung jawab aset yang berbeda dari lokasi fisik.
- [ ] Batasi daftar, detail, perubahan aset, dan dokumen sesuai cakupan yang disepakati.
- [ ] Tinjau hak edit aset aktif agar perubahan penempatan mengikuti mutasi dan tidak melewati audit.
- [ ] Tambahkan pengujian manipulasi ID, akses lintas cabang, dan unduhan dokumen tanpa izin.
- [ ] Tinjau perlindungan riwayat audit saat aset/pengguna diarsipkan atau dihapus.

Selesai jika: setiap peran hanya dapat melihat dan mengubah data yang menjadi kewenangannya, serta riwayat tetap dapat ditelusuri.

## Tahap 5 Memperbaiki pemantauan pekerjaan

Prioritas: setelah aturan peran dan akses disepakati.

- [ ] Buat daftar tugas Keuangan dan Direksi yang menunggu persetujuan.
- [ ] Buat daftar PO menunggu keputusan serta PO yang belum diterima penuh.
- [ ] Buat daftar aset menunggu cetak label, penempelan, penempatan, atau serah terima.
- [ ] Tampilkan umur pengajuan dan umur pekerjaan yang belum selesai.
- [ ] Tambahkan notifikasi dalam aplikasi.
- [ ] Evaluasi notifikasi email setelah penerima, isi pesan, pengaturan server, dan aturan pengiriman disepakati.
- [ ] Bedakan total inventaris, aset aktif, dan aset yang belum selesai aktivasi pada ringkasan.

Selesai jika: petugas dapat menemukan pekerjaan berikutnya dan status tertunda tanpa memeriksa setiap transaksi satu per satu.

## Tahap 6 Menambahkan pengelolaan setelah aset aktif

Prioritas: berdasarkan kebutuhan operasional, setelah fitur dasar stabil.

- [ ] Tentukan alur peminjaman dan pengembalian aset.
- [ ] Tambahkan pemeriksaan kondisi berkala serta riwayat perawatan/perbaikan.
- [ ] Catat kerusakan atau kehilangan dengan bukti dan penanggung jawab.
- [ ] Tentukan alur penghapusan/pengarsipan aset dengan persetujuan.
- [ ] Pastikan nomor inventaris tetap dicadangkan setelah aset diarsipkan.
- [ ] Tambahkan stock opname: daftar pemeriksaan, hasil ditemukan/tidak ditemukan, dan tindak lanjut selisih.
- [ ] Evaluasi halaman pencarian nomor hasil pindai QR tanpa mengubah isi QR yang sudah tercetak.

Selesai jika: perubahan keadaan aset setelah aktivasi memiliki transaksi, pelaksana, waktu, dan bukti yang jelas.

## Tahap 7 Menyiapkan pemakaian operasional

Prioritas: sebelum digunakan sebagai sistem utama.

- [ ] Samakan konfigurasi PHP web dan CLI; verifikasi ZIP, BCMath, driver database, dan versi runtime.
- [ ] Tetapkan zona waktu aplikasi Asia/Jakarta dan periksa waktu audit/dokumen terhadap waktu pengguna.
- [ ] Siapkan build frontend produksi agar tidak bergantung pada CDN Tailwind untuk operasional.
- [ ] Tambahkan CI untuk menjalankan tes, formatter, dan uji MySQL yang relevan.
- [ ] Siapkan backup database serta lampiran, jadwal, lokasi simpan, dan masa retensinya.
- [ ] Uji pemulihan backup pada lingkungan terpisah.
- [ ] Konfigurasikan HTTPS, logging, penanganan error produksi, dan pemantauan layanan.
- [ ] Tentukan lingkungan development, staging, dan production.
- [ ] Lakukan UAT per peran dan catat persetujuan penggunaan.
- [ ] Susun prosedur pemindahan data awal serta penanganan data simulasi. Jangan menghapus data pengguna secara otomatis.
- [ ] Lakukan deploy setelah target hosting dan jadwalnya disepakati.

Selesai jika: UAT diterima, pemulihan backup terbukti, konfigurasi produksi siap, dan dukungan operasional memiliki prosedur yang jelas.

## Urutan yang disarankan

Tahap 1 → Tahap 2 → Tahap 3 dan 4 → Tahap 5 → Tahap 7.

Tahap 6 dapat dikerjakan bertahap sesuai kebutuhan, tanpa harus menunggu seluruh modulnya selesai sebelum penggunaan awal.

Pekerjaan paling dekat yang disarankan: **tuntaskan verifikasi impor/ekspor browser**, kemudian perbarui dokumentasi surat PO serta BAST.

## Cara menggunakan daftar ini

- Tandai checkbox hanya setelah implementasi dan pemeriksaan selesai.
- Untuk setiap pekerjaan, catat penanggung jawab, hasil uji, dan kendala yang masih terbuka.
- Estimasi tanggal dibuat setelah prioritas, ruang lingkup, dan penanggung jawab disepakati.
- Pengiriman email, perubahan akses, penghapusan data, dan deploy mengikuti otorisasi yang sesuai; dokumen ini tidak menjalankan tindakan tersebut.

## Hasil tahap pengerjaan 7 Oktober 2026

Uji MySQL terisolasi: 3 tes lulus, 24 assertions. Cakupan: penomoran paralel, batas penerimaan, dan kuota PO dari pengajuan. Database uji dibuat sementara lalu dibersihkan; tidak mengubah transaksi database inventaris. Perilaku layanan kuota PO yang ada memenuhi skenario ini sehingga tidak diperlukan perubahan logika aplikasi.

Pekerjaan berikutnya yang belum selesai: konfirmasi impor/ekspor melalui browser dan pembukaan berkas hasil unduh di Excel.

## Hasil tahap pengerjaan 13 Oktober 2026

Pengujian BAST terisolasi: 3 tes lulus, 35 assertions. Lokasi, ruangan, serial, kondisi, nama barang, dan nama petugas pada dokumen mengikuti snapshot serah terima meskipun data terkini berubah. Formatter lulus. Pengujian menggunakan PHP 8.4 Laragon dengan ekstensi ZIP diaktifkan khusus pada perintah uji; tidak mengubah konfigurasi server atau data operasional.

Akses otomatis browser belum tersedia pada sesi ini. Verifikasi unduhan Chrome, pembukaan Excel, serta pindai QR fisik tetap belum selesai.
