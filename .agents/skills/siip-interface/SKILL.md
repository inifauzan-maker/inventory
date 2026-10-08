---
name: siip-interface
description: Bangun halaman Blade dan Tailwind SIIP untuk tabel inventaris, formulir, filter, preview impor serta detail transaksi.
---

# Antarmuka SIIP

Periksa Blade, layout, komponen dan package.json yang tersedia. Ikuti sistem tampilan proyek; jangan mengganti framework frontend atau menambah paket UI hanya untuk satu formulir.

- Gunakan bahasa Indonesia pada label, validasi, empty state dan tombol.
- Tabel inventaris menyediakan pencarian/paginasi dan filter yang sesuai data tersedia. Pertahankan filter saat berpindah halaman dan mengekspor.
- Form memisahkan identitas barang, penempatan/PIC, kondisi/status dan perolehan. Gunakan label eksplisit dan error dekat input; simpan input ketika validasi gagal.
- Lokasi membatasi pilihan ruangan. Pilihan UI tetap harus divalidasi di server.
- Tampilkan uang dalam rupiah dan tanggal hari/bulan/tahun, dengan nilai mentah bertipe benar saat dikirim ke backend. Tampilkan harga belum diketahui sebagai belum tersedia.
- Preview impor menampilkan baris sumber serta kesalahan dan menyediakan aksi konfirmasi yang jelas.
- Tombol disesuaikan izin, tetapi penyembunyian tombol bukan pengganti Policy. Gunakan CSRF dan escaping Blade.
- Tabel lebar boleh scroll horizontal di ponsel. Pastikan label terbaca, fokus keyboard terlihat, dan status tidak dibedakan dengan warna saja.

Jalankan build frontend bila asset berubah. Jika browser tersedia, periksa desktop/ponsel serta error/empty state. Jika tidak, sebutkan bahwa pemeriksaan visual belum dilakukan.
