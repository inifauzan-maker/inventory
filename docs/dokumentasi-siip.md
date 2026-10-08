# Dokumentasi SIIP Inventaris

Diperbarui: 7 Oktober 2026. Dokumentasi ini menjelaskan perilaku aplikasi yang telah diimplementasikan, bukan rencana fitur.

## 1. Konsep data

| Data | Fungsi | Contoh |
| --- | --- | --- |
| Master barang / kode barang | Identitas jenis barang yang digunakan bersama oleh banyak unit | BRG-000001 untuk Laptop |
| Aset / nomor inventaris | Identitas permanen satu unit fisik | INV-2026-000001 |
| Pengajuan barang | Kebutuhan yang dinilai Keuangan dan Direksi | Pengajuan tiga laptop |
| Purchase Order (PO) | Pesanan ke vendor, dapat dibuat bertahap | PO dua laptop, kemudian PO satu laptop |
| Penerimaan | Catatan barang yang diterima terhadap item PO | Satu unit diterima dahulu |
| Mutasi | Riwayat perpindahan aset beserta posisi asal dan tujuan | Bandung / IT → Jakpus |

Kondisi fisik, status penggunaan, dan tahap aktivasi adalah tiga hal berbeda. Harga aset yang belum diketahui tetap kosong/null; nilai nol berarti harga memang dicatat nol.

## 2. Pengajuan barang

1. Buka **Pengajuan Barang** dan buat draf.
2. Isi tujuan, divisi, tanggal, serta barang, jumlah, satuan, spesifikasi, dan estimasi harga.
3. Pilih pengajuan seluruh barang atau alternatif barang.
4. Simpan dan periksa surat. Surat tersedia dalam Word serta halaman cetak/PDF melalui browser.
5. Kirim ke Keuangan.
6. Keuangan menyetujui atau menolak. Untuk alternatif, Keuangan memilih satu barang yang direkomendasikan.
7. Pengajuan yang lolos diteruskan ke Direksi.
8. Direksi menyetujui atau menolak.

Alur status:

Draf → Menunggu Keuangan → Menunggu Direksi → Disetujui Direksi

Penolakan menutup pengajuan. Perubahan dibuat melalui pengajuan baru; riwayat lama tetap tersimpan. Draf hanya dapat diubah pengajunya. Setelah dikirim, isi pengajuan tidak dapat diedit.

Pengaju tidak boleh menyetujui pengajuannya sendiri. Orang yang sama tidak boleh melakukan kedua tingkat persetujuan, termasuk Super Admin.

**Batas implementasi:** penerusan saat ini berupa status dan daftar pekerjaan di aplikasi. Tidak ada pengiriman email/WhatsApp otomatis.

## 3. Membuat PO dari pengajuan

Dilakukan oleh Operasional atau Super Admin.

1. Buka detail pengajuan yang **Disetujui Direksi**, lalu pilih **Buat PO dari Pengajuan**.
2. Alternatifnya, buka **Pengadaan**, pilih pengajuan sumber, lalu **Isi dari Pengajuan**.
3. Periksa daftar barang dan sisa jumlah yang boleh dipesan.
4. Centang barang yang akan dipesan. Barang lain dapat dipesan pada PO berikutnya.
5. Isi jumlah, harga satuan PO, nomor PO, vendor, tanggal, dan catatan.
6. Simpan. PO baru selalu berstatus **draft**.
7. Direksi atau Super Admin menyetujui atau menolak PO melalui tombol persetujuan.

### Aturan alokasi

- Jumlah seluruh PO yang terhubung tidak boleh melebihi jumlah yang disetujui per item pengajuan.
- Draf PO sudah mencadangkan jumlah.
- PO ditolak melepaskan alokasi jumlah.
- PO yang sudah disetujui atau ditolak tidak dapat disetujui/ditolak ulang; hanya draf dapat diproses.
- Nama barang diambil dari pengajuan sumber, meskipun input browser diubah.
- Barang dari pengajuan lain ditolak.
- Untuk mode alternatif, hanya pilihan Keuangan pada versi pengajuan yang disetujui yang dapat dipesan.
- Total PO dihitung di server dari harga satuan dan jumlah menggunakan aritmetika decimal.
- Header, detail, dan total disimpan dalam satu transaksi.

Contoh: persetujuan tiga laptop → PO pertama dua laptop → sisa satu laptop. PO berikutnya dua laptop akan ditolak.

Detail pengajuan menampilkan daftar PO terhubung. Detail PO menampilkan pengajuan sumber dan versi persetujuannya.

**Kompatibilitas:** PO manual tanpa pengajuan masih tersedia. Pembatasan jumlah berdasarkan persetujuan berlaku pada PO yang terhubung ke pengajuan. PO lama tidak otomatis dihubungkan atau diubah.

## 4. Penerimaan dan penerbitan nomor

Penerimaan dilakukan oleh Operasional atau Super Admin terhadap PO yang disetujui.

1. Buka halaman penerimaan barang.
2. Pilih PO, nomor penerimaan, tanggal, item, dan jumlah yang diterima.
3. Simpan penerimaan. Nomor inventaris belum diterbitkan.
4. Dari detail penerimaan, periksa jumlah, kondisi, jenis, dan spesifikasi.
5. Konfirmasikan pemeriksaan barang.
6. Verifikasi dokumen dan isi referensi surat jalan/faktur/bukti penerimaan.
7. Pilih kategori serta lokasi pencatatan untuk setiap item.
8. Pilih **Catat Inventaris dan Generate Nomor**.

Setiap unit fisik memperoleh satu aset dan satu nomor. Penerimaan dapat bertahap; jumlah kumulatif tidak boleh melebihi jumlah PO. Pengulangan pencatatan tahap ditolak agar tidak menggandakan aset.

### Alur lengkap

Diterima → Diperiksa → Dokumen diverifikasi → Inventaris dicatat dan nomor diterbitkan → Label dicetak → Label ditempel → Penempatan → Serah terima → Barang Aktif

Jika pencatatan satu unit gagal, seluruh pencatatan batch beserta nomor dan perubahan tahap dibatalkan dalam transaksi.

## 5. Nomor inventaris dan master barang

Buka **Kode Barang** untuk membuat katalog. Kode otomatis menggunakan format BRG-000001. Memilih master barang pada formulir aset mengisi nama dan kategori dari master tersebut.

Buka **Nomor Inventaris** untuk mengatur prefiks dan penggunaan kode barang dalam nomor. Pengaturan nomor hanya dapat diubah Super Admin.

Format bawaan:

- INV-2026-000001
- Jika kode barang diikutkan: INV-BRG-000001-2026-000001

Nomor menggunakan tahun penerbitan dan urutan enam digit minimal. Urutan dialokasikan menurut prefiks/tahun; format contoh VM-IT-LAP-2026-001 bukan format bawaan aplikasi.

Nomor inventaris:

- unik dan tidak dapat diubah setelah diterbitkan;
- tidak dapat digunakan kembali untuk aset lain;
- tetap dicadangkan dalam register nomor walaupun aset dihapus;
- dapat dicari dan digunakan pada label/QR.

Nomor lama tetap dipertahankan. Nomor manual yang belum digunakan masih diterima pada jalur yang mendukungnya; otomatisasi digunakan ketika nomor kosong.

## 6. Label dan aktivasi aset

1. Buka detail aset yang baru dicatat.
2. Pilih **Buka dan Cetak Label**.
3. Cetak dengan skala 100%, lalu konfirmasikan hasil cetak setelah benar-benar tersedia.
4. Tempel label pada unit yang sesuai, lalu konfirmasikan penempelan.
5. Pilih lokasi dan ruangan penempatan. Ruangan harus sesuai lokasi.
6. Catat nama penerima dan referensi berita acara serah terima.
7. Konfirmasikan serah terima. Aset menjadi **Barang Aktif**.

Label berisi VILLA MERAH, ASET INVENTARIS, nomor, QR Code, serta kode kategori dan nama barang. QR berisi **nomor inventaris saja**, misalnya INV-2026-000001; bukan tautan detail aset.

QR dibuat lokal sebagai SVG, tanpa layanan QR eksternal. Membuka halaman label tidak otomatis mengubah tahap. Cetak, tempel, dan serah terima merupakan konfirmasi petugas; aplikasi tidak membuktikan tindakan fisik tersebut secara otomatis.

Aset lama tetap dianggap aktif untuk kompatibilitas. Ringkasan “Dipakai” menghitung aset dengan status penggunaan dipakai dan tahap aktif.

## 7. Pencatatan aset manual

Buka **Tambah Aset**. Konfirmasikan barang sudah diterima, sudah diperiksa, dan dokumen sudah diverifikasi sebelum menyimpan.

Aset manual baru tetap harus melalui tahapan label, penempatan, dan serah terima sebelum aktif. Untuk barang yang diperoleh melalui PO, gunakan alur penerimaan agar sumber barang dapat ditelusuri.

Pencatatan/edit manual diizinkan untuk Super Admin, Operasional, Staff, dan Akademik. Konfirmasi aktivasi dilakukan Operasional atau Super Admin.

## 8. Impor Excel

Akses melalui **Tambah Aset → Impor Excel**; diizinkan untuk Operasional dan Super Admin.

File referensi:

- [Template data awal](Daftar_Inventaris_Data_Awal.xlsx)
- [Contoh data fiktif](Contoh_Import_Inventaris.xlsx)

Langkah:

1. Siapkan workbook sesuai template, sheet **Inventaris**, satu baris per unit fisik.
2. Unggah file .xlsx, maksimal 5 MB dan 200 aset.
3. Periksa preview. Preview tidak menyimpan aset.
4. Petakan kategori, lokasi, ruangan, serta kode barang ke master yang valid.
5. Perbaiki kesalahan, lalu konfirmasikan pemeriksaan fisik/dokumen dan impor.

Nomor kosong dibuat otomatis saat konfirmasi. Nomor yang sudah atau pernah digunakan ditolak. Formula tidak diperbolehkan. Gunakan format teks untuk nomor/kode/serial agar nol awal tidak hilang. Harga kosong tetap null; tanggal harus mengikuti format template.

Preview bersifat privat per pengguna dan berlaku 30 menit. Setelah berhasil, token preview tidak dapat digunakan ulang. Penyimpanan batch atomik; kegagalan membatalkan seluruh batch.

Aset hasil impor berstatus belum aktif sampai tahap label dan serah terima selesai.

## 9. Ekspor

Buka **Tambah Aset**, lalu pilih **Ekspor Excel** atau **Ekspor CSV**. Endpoint ekspor mendukung filter pencarian, kategori, lokasi, kondisi, dan status penggunaan.

Ekspor Excel memakai struktur template; nilai teks, decimal, dan harga kosong dipertahankan. Ekspor CSV melindungi nilai yang dapat dibaca sebagai formula spreadsheet.

Excel, impor, dan pembuatan surat Word memerlukan ekstensi PHP ZIP. Pastikan ekstensi tersedia pada **PHP web**, bukan hanya PHP terminal.

## 10. Mutasi

Dilakukan Operasional atau Super Admin.

1. Pilih aset dan lokasi tujuan.
2. Pilih ruangan tujuan jika diperlukan.
3. Isi catatan, kemudian simpan.

Posisi asal diambil dari data aset terkini. Input asal yang bertentangan ditolak. Ruangan tujuan diperiksa terhadap lokasi tujuan. Jika tidak memilih ruangan, ruangan tujuan dikosongkan sehingga ruangan lama tidak terbawa ke lokasi lain.

Riwayat mutasi, posisi aset, dan audit disimpan atomik. Nomor inventaris tetap sama.

## 11. Relasi teknis

Rantai sumber:

procurement_requests → procurement_request_items → purchase_order_items → goods_receipt_items → assets

Header PO menyimpan procurement_request_id dan procurement_request_version. Detail PO menyimpan procurement_request_item_id. Aset menyimpan goods_receipt_item_id dan receipt_unit_index.

Register issued_inventory_numbers menyimpan nomor permanen. inventory_number_sequences menyimpan urutan menurut scope. asset_activities menyimpan riwayat aktivitas; asset_mutations menyimpan posisi asal dan tujuan.

Migration hubungan PO: 2026_10_07_005603_link_purchase_orders_to_procurement_requests.php. Kolom sumber nullable untuk kompatibilitas dan menggunakan foreign key yang membatasi penghapusan sumber yang dirujuk.

PurchaseOrderService mengunci pengajuan sebelum menghitung alokasi kumulatif dan menyimpan PO dalam transaksi. ReceiptWorkflowService mengunci PO/detail saat penerimaan. InventoryNumberService mengunci pengaturan serta urutan penomoran. AssetMutationService mengunci aset ketika memindahkan posisi.

## 12. Pengujian dan hasil verifikasi

Jalankan dari folder proyek:

~~~powershell
php artisan test --compact
~~~

Tes standar memakai SQLite :memory:, terpisah dari database aplikasi.

Uji MySQL khusus membuat database sementara bernama siip_concurrency_<nilai acak>, melakukan migrasi di sana, lalu menghapus database uji tersebut:

~~~powershell
$env:SIIP_MYSQL_CONCURRENCY = '1'
php artisan test --compact tests/Feature/MySqlConcurrencyTest.php
Remove-Item Env:SIIP_MYSQL_CONCURRENCY
~~~

Akun database pengujian memerlukan izin membuat/menghapus database uji. Jangan arahkan RefreshDatabase, migrate:fresh, atau reset ke database pengguna.

| Pemeriksaan | Hasil terakhir |
| --- | --- |
| Suite setelah hubungan pengajuan–PO | 126 lulus, 2 tes MySQL dilewati, 579 assertions |
| Kuota bertahap, alternatif, sumber item, akses, pelepasan alokasi PO ditolak | Tes regresi lulus |
| MySQL: delapan alokasi nomor paralel | Lulus |
| MySQL: dua penerimaan untuk sisa satu unit | Satu diterima, satu ditolak; lulus |
| Konkurensi MySQL kuota PO dari pengajuan | Lulus 7 Oktober 2026: dua permintaan untuk sisa satu unit menghasilkan satu PO tersimpan dan satu ditolak |
| Browser: PO → penerimaan → verifikasi → nomor → aktivasi simulasi → mutasi/audit | Berhasil menggunakan data simulasi |
| Browser: tampilan pemilih pengajuan ke PO | Diperiksa sebagai Operasional; belum ada pengajuan disetujui pada database demo |
| QR: kesesuaian matriks dengan encoder referensi | Tes lulus untuk nomor biasa, panjang, dan Unicode |
| Pindai QR dengan ponsel | Belum dikonfirmasi |
| Cetak label di kertas | Belum diuji; printer belum tersedia |
| Impor/ekspor: pengujian otomatis terkait | 25 tes lulus |
| Impor browser | Tertahan izin unggah file ekstensi browser |
| Unduhan ekspor browser | Belum terkonfirmasi; ERR_BLOCKED_BY_CLIENT |

Data browser simulasi tetap tersimpan: PO UJI-BROWSER-20261006-01, penerimaan RCV-UJI-BROWSER-20261006-01, kategori UJI-IT, aset INV-2026-000001. Jangan gunakan sebagai transaksi operasional.

## 13. Troubleshooting

**Class "ZipArchive" not found**

PHP web dapat berbeda dari PHP CLI. Pada pemeriksaan sebelumnya, web memakai PHP 8.5.7, sedangkan CLI memakai PHP 8.4.13. Aktifkan extension=zip pada php.ini runtime web yang benar, kemudian restart layanan PHP/Laragon. Konfigurasi web telah diaktifkan dengan cadangan pada sesi pemeriksaan; unduhan browser tetap membutuhkan verifikasi.

**Chrome ERR_BLOCKED_BY_CLIENT saat ekspor**

Status ini belum terbukti sebagai kesalahan aplikasi. Coba unduhan manual di Chrome dan periksa pemberitahuan unduhan/ekstensi. Jangan menandai ekspor browser berhasil hanya berdasarkan tes CLI.

**Kontrol browser tidak dapat memasukkan file lokal**

Ekstensi ChatGPT memerlukan Allow access to file URLs untuk memasukkan file melalui alat browser. Ini kebutuhan otomasi; pengguna masih dapat memilih file secara manual.

**Nomor atau tahap sudah digunakan**

Periksa riwayat dan tahap terkini. Jangan mengulangi konfirmasi yang sudah berhasil atau mengganti nomor aset lama.

**Jumlah PO/penerimaan melebihi sisa**

Draf PO ikut mengurangi kuota pengajuan. Untuk penerimaan, periksa jumlah yang sudah diterima pada seluruh penerimaan PO.

**Tabel audit tidak tersedia**

Periksa php artisan migrate:status. Migration asset_activities telah diterapkan pada database aplikasi yang diperiksa.

**Tampilan tidak berubah**

Periksa cache Blade dengan php artisan view:clear. Jika mengubah asset frontend Vite, jalankan npm run build sesuai konfigurasi proyek.
