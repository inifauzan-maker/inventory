---
name: siip-database
description: Rancang atau ubah migration dan relasi database SIIP untuk master barang, inventaris, lokasi, mutasi dan pengadaan.
---

# Database SIIP

Periksa migration, model dan engine database sebelum merancang perubahan. Usulan dalam percakapan adalah kebutuhan, bukan bukti tabel sudah tersedia.

- Pisahkan katalog barang dari aset per unit. Terapkan unique constraint nomor inventaris dan foreign key relasi.
- Pastikan ruangan berada di lokasi yang dipilih; hindari dua kolom lokasi yang dapat bertentangan tanpa validasi.
- Simpan uang dengan DECIMAL dan tanggal dengan tipe date/datetime. Harga tidak diketahui nullable; jangan menggantinya dengan nol.
- Riwayat mutasi menyimpan asal/tujuan dan PIC pada waktu transaksi, bukan hanya membaca posisi terkini aset.
- Rencana, pengajuan, PO dan penerimaan memiliki header-detail. Detail penerimaan merujuk detail PO; aset merujuk detail penerimaan.
- Rancang constraint dan kunci idempotensi yang mencegah nomor ganda serta pembuatan aset ganda saat retry.
- Jangan memakai cascade delete yang menghilangkan jejak transaksi atau audit tanpa dasar bisnis.
- Untuk tabel berisi data, gunakan perubahan bertahap: tambah kolom nullable, backfill terkontrol, lalu constraint. Jelaskan dampak sebelum perubahan destruktif.

Uji migration pada database pengujian. Jangan menjalankan migrate:fresh, refresh, reset atau drop pada database pengguna. Laporkan keterbatasan bila pengujian hanya menggunakan SQLite sedangkan targetnya MySQL/MariaDB.
