---
name: siip-laravel
description: Implementasikan fitur backend Laravel untuk inventaris SIIP, termasuk CRUD, otorisasi, mutasi aset dan layanan bisnis.
---

# Backend SIIP

Baca AGENTS.md dan composer.json proyek. Ikuti Laravel yang benar-benar terpasang; target proyek ini Laravel 13 dengan PHP minimal 8.3. Periksa dokumentasi versi terkait sebelum memakai API yang belum dikenal.

- Satu inventaris mewakili satu barang fisik. Master barang dapat memiliki banyak inventaris. Nomor inventaris unik dan tidak berubah saat mutasi.
- Pisahkan kondisi fisik dari status penggunaan. Jangan menambahkan modul stok habis pakai tanpa permintaan.
- Validasi input melalui Form Request dan gunakan Policy untuk aksi serta cakupan unit. Ambil pengaju/pelaksana dari identitas login, bukan input klien.
- Controller mengatur request/response. Tempatkan proses multi-langkah seperti mutasi dan penerimaan di Service, dalam transaksi database.
- Pada mutasi, simpan posisi asal/tujuan dan PIC, lalu perbarui posisi terkini secara atomik. Gunakan penguncian bila ada kemungkinan perubahan bersamaan.
- Harga yang tidak diketahui tetap null. Hitung total di server dengan decimal, bukan float atau total dari browser.
- Jangan menghapus permanen aset yang sudah memiliki transaksi. Gunakan pengarsipan sesuai aturan bisnis.
- Simpan unggahan di lokasi yang sesuai visibilitasnya; validasi ukuran, MIME, dan otorisasi saat mengakses dokumen.

Validasi hasil dengan tes fitur relevan: izin akses, perubahan data, penolakan input, dan rollback ketika proses gagal. Laporkan perubahan dan tes yang benar-benar dijalankan.
