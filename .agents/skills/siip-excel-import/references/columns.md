# Kontrak data awal

Sheet: Inventaris. Satu baris per barang fisik. Sheet Panduan bukan data impor.

Urutan template saat ini:
1. Nomor Inventaris
2. Kode Barang
3. Nama Barang
4. Kategori
5. Merk
6. Tipe / Model
7. Spesifikasi
8. Serial Number
9. Satuan
10. Cabang / Lokasi
11. Ruangan
12. Unit Kerja
13. Penanggung Jawab
14. Kondisi
15. Status Penggunaan
16. Tanggal Perolehan
17. Harga Perolehan (Rp)
18. Sumber Perolehan
19. Vendor / Pemberi
20. Nomor Dokumen
21. Tanggal Pemeriksaan
22. Tautan Foto / Dokumen
23. Keterangan

Petakan berdasarkan nama header, bukan posisi saja. Laporkan header wajib yang hilang atau ganda.
Kolom utama: nomor inventaris, kode/nama barang, kategori, lokasi, ruangan, unit kerja, PIC, kondisi dan status. Sesuaikan kewajiban ruangan/PIC dengan aturan bisnis yang ditetapkan, jangan membuat nilai palsu.

- Kondisi: Baik, Rusak Ringan, Rusak Berat, Hilang, Belum Diperiksa.
- Status Penggunaan: Digunakan, Cadangan, Dipinjamkan, Dalam Perbaikan, Tidak Digunakan, Dihapuskan.
- Sumber Perolehan: Pembelian, Hibah, Transfer, Saldo Awal, Lainnya.
- Kategori awal: Elektronik, Furniture, Peralatan Kantor, Kendaraan, Mesin, Peralatan Kerja, Lainnya. Ini kandidat master, bukan pembatas permanen.
- Satuan awal: Unit, Set, Buah.

Nomor inventaris unik; kode barang boleh berulang. Tanggal ditampilkan dd/mm/yyyy. Harga adalah rupiah per aset, nullable jika tidak diketahui. Link foto/dokumen hanya referensi; tidak boleh diambil otomatis saat impor.
