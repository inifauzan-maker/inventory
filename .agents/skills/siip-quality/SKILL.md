---
name: siip-quality
description: Uji atau review fitur SIIP untuk regresi, otorisasi, integritas inventaris, keamanan impor dan penerimaan barang.
---

# Pengujian dan review SIIP

Bedakan permintaan review dari perbaikan: pada review, laporkan temuan dengan lokasi dan dampak tanpa mengedit aplikasi. Untuk perbaikan yang diminta, tambahkan regresi yang relevan dan ubah kode dalam cakupan.

- Baca phpunit.xml dan test setup sebelum menjalankan tes. Pastikan koneksi test terisolasi; jangan memakai database produksi/pengguna untuk RefreshDatabase.
- Pilih tes berdasarkan perilaku yang berubah, bukan menyalin implementasi. Dua tes contoh bawaan tidak membuktikan modul inventaris berfungsi.
- Uji Policy per aksi dan unit, termasuk manipulasi ID request, IDOR, mass assignment dan pengunduhan dokumen.
- Uji keunikan nomor aset, lokasi-ruangan, mutasi atomik, harga null vs nol dan perhitungan total server.
- Untuk impor, uji preview tanpa efek samping, file invalid, duplikat dan konfirmasi ulang. Untuk penerimaan, uji parsial, over-receipt dan retry.
- Uji penguncian/concurrency pada engine yang sesuai jika perubahan bergantung padanya; jangan menganggap tes SQLite membuktikan perilaku MySQL.
- Audit log tidak memuat password, token atau isi .env. Jangan mencetak rahasia ketika debugging.

Gunakan php artisan test untuk suite relevan; composer validate dan composer check-platform-reqs saat dependensi berubah. Jalankan build bila frontend berubah. Laporkan command, hasil, temuan, dan batas cakupan secara jujur. Jangan memperbaiki isu di luar cakupan tanpa dasar dari permintaan pengguna.
