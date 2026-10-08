---
name: siip-excel-import
description: Bangun atau perbaiki impor Excel data awal inventaris SIIP, dengan preview, pemetaan master, validasi dan pencegahan duplikasi.
---

# Impor inventaris Excel

Baca [kontrak kolom](references/columns.md) sebelum mengubah importir atau template. Skill ini untuk kode impor aplikasi; tidak mengharuskan library tertentu. Periksa kompatibilitas Laravel 13 sebelum menambah paket.

1. Baca sheet Inventaris; abaikan Panduan dan baris kosong. Satu baris adalah satu aset fisik.
2. Pertahankan nomor inventaris, kode barang dan serial number sebagai teks, termasuk nol awal. Normalisasi whitespace dan header secara terkontrol.
3. Terima tanggal Excel sesuai kalender workbook dan teks dd/mm/yyyy yang valid. Tolak tanggal ambigu/rusak dengan pesan per baris. Jangan menebak harga kosong sebagai nol.
4. Deteksi duplikasi nomor inventaris dalam file serta database. Tampilkan master yang tidak dikenal untuk pemetaan atau pembuatan eksplisit oleh pengguna berwenang.
5. Preview harus menunjukkan nomor baris sumber, nilai, kesalahan dan keputusan pemetaan. Jangan menulis aset atau master saat preview.
6. Saat konfirmasi, validasi ulang otorisasi, data dan keunikan. Gunakan transaksi serta unique constraint untuk mencegah race condition. Tentukan atomisitas impor secara eksplisit; jangan menyimpan sebagian tanpa laporan jelas.
7. Jangan menimpa aset yang sudah ada tanpa mode update yang secara eksplisit diminta. Retry konfirmasi tidak boleh menggandakan data.
8. Batasi ukuran file/baris, gunakan chunk bila perlu, dan jangan menjalankan macro, formula atau mengunduh tautan dari file. Lindungi ekspor CSV dari formula injection bila format itu disediakan.

Uji tanggal numerik/teks, nol awal, baris kosong, harga kosong vs nol, duplikat, master tidak dikenal, hubungan lokasi-ruangan, retry dan akses lintas unit. Laporkan baris yang diterima/ditolak tanpa menampilkan data sensitif berlebihan.
