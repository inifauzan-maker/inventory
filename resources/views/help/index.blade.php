@extends('layouts.app-shell')

@section('page-title', 'Bantuan SIIP')
@section('header-title', 'Bantuan')

@section('content')
    <div class="space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 md:p-6">
            <h1 class="text-2xl font-bold text-slate-900">Panduan Penggunaan SIIP</h1>
            <p class="mt-2 text-slate-600">Pelajari pencatatan inventaris, pengajuan barang, penerimaan, dan serah terima. Menu dan tindakan yang tersedia mengikuti hak akses akun Anda.</p>
            <nav aria-label="Daftar isi bantuan" class="mt-5 flex flex-wrap gap-3">
                @foreach (['mulai' => 'Mulai menggunakan', 'alur' => 'Alur proses', 'panduan' => 'Panduan per menu', 'peran' => 'Peran pengguna', 'istilah' => 'Istilah', 'kendala' => 'Masalah umum'] as $anchor => $label)
                    <a href="#{{ $anchor }}" class="rounded-lg border border-sky-200 bg-sky-50 px-3 py-2 text-sm font-medium text-sky-800 hover:bg-sky-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-sky-600">{{ $label }}</a>
                @endforeach
            </nav>
        </section>

        <section id="mulai" class="scroll-mt-28 rounded-2xl border border-slate-200 bg-white p-5 md:p-6">
            <h2 class="text-lg font-bold">Mulai menggunakan</h2>
            <ol class="mt-3 list-decimal space-y-2 pl-5 text-slate-700">
                <li>Masuk menggunakan akun Anda dan periksa peran pada bagian bawah menu.</li>
                <li>Petugas menyiapkan master kategori, lokasi, ruangan, dan kode barang sebelum mencatat aset.</li>
                <li>Gunakan Pengajuan Barang untuk kebutuhan pembelian baru. Gunakan Tambah Aset untuk barang yang sudah diterima dan diverifikasi.</li>
                <li>Periksa Dashboard untuk mencari inventaris, lalu buka detail aset untuk melihat identitas dan riwayatnya.</li>
                <li>Keluar melalui tombol Logout setelah selesai, terutama pada perangkat bersama.</li>
            </ol>
        </section>

        <section id="alur" class="scroll-mt-28 rounded-2xl border border-slate-200 bg-white p-5 md:p-6">
            <h2 class="text-lg font-bold">Alur proses</h2>
            <div class="mt-4 grid gap-5 lg:grid-cols-2">
                <div class="rounded-xl bg-slate-50 p-4">
                    <h3 class="font-semibold">Pengajuan dan pengadaan</h3>
                    <ol class="mt-3 list-decimal space-y-2 pl-5">
                        <li>Pengaju membuat draf berisi kebutuhan dan rincian barang.</li>
                        <li>Pengaju mengirim pengajuan untuk pemeriksaan Keuangan.</li>
                        <li>Keuangan memeriksa biaya dan pilihan barang, kemudian meneruskan ke Direksi.</li>
                        <li>Direksi menyetujui atau menolak pengajuan.</li>
                        <li>Operasional membuat PO dari pengajuan yang disetujui, kemudian PO diputuskan oleh pejabat berwenang.</li>
                        <li>Barang diterima berdasarkan PO yang disetujui; penerimaan dapat dilakukan sebagian.</li>
                    </ol>
                    <p class="mt-3 text-sm text-slate-600">PO berikutnya hanya dapat memakai sisa jumlah yang disetujui. Pengajuan yang disetujui belum berarti barang sudah diterima atau aset sudah aktif.</p>
                </div>
                <div class="rounded-xl bg-emerald-50 p-4">
                    <h3 class="font-semibold">Penerimaan hingga aset aktif</h3>
                    <ol class="mt-3 list-decimal space-y-2 pl-5">
                        <li>Terima barang dan catat jumlah aktual.</li>
                        <li>Periksa fisik barang.</li>
                        <li>Verifikasi dokumen penerimaan dan isi referensinya.</li>
                        <li>Daftarkan inventaris untuk menghasilkan aset per unit dan nomor inventaris.</li>
                        <li>Cetak label, kemudian konfirmasikan penempelan label.</li>
                        <li>Tetapkan lokasi dan ruangan penempatan.</li>
                        <li>Catat penerima dan referensi serah terima; aset menjadi aktif.</li>
                        <li>Cetak atau unduh BAST sebagai dokumen serah terima.</li>
                    </ol>
                    <p class="mt-3 text-sm text-slate-600">Konfirmasikan setiap tahap setelah pekerjaan fisiknya benar-benar selesai.</p>
                </div>
            </div>
        </section>

        <section id="panduan" class="scroll-mt-28 rounded-2xl border border-slate-200 bg-white p-5 md:p-6">
            <h2 class="text-lg font-bold">Panduan per menu</h2>
            <div class="mt-4 space-y-3">
                @foreach ([
                    'Dashboard dan pencarian' => 'Cari berdasarkan nomor inventaris, nama barang, atau serial. Gunakan filter kategori, lokasi, kondisi, dan status untuk mempersempit hasil. Buka detail aset untuk menelusuri riwayat.',
                    'Master Data' => 'Siapkan kategori, lokasi, ruangan, serta Kode Barang. Ruangan harus sesuai lokasinya. Pengaturan Nomor Inventaris menentukan format penomoran otomatis; perubahan format tidak mengganti nomor aset lama.',
                    'Tambah Aset' => 'Pilih master barang bila tersedia, lengkapi identitas, kondisi, penempatan, serta data perolehan. Pastikan barang telah diterima, diperiksa, dan dokumennya diverifikasi. Harga yang belum diketahui dibiarkan kosong; isi nol hanya bila nilainya memang nol.',
                    'Impor Excel' => 'Buka Tambah Aset lalu pilih Impor Excel. Unduh template atau contoh, pertahankan judul kolom, dan isi data. Unggah untuk preview, perbaiki kesalahan serta pemetaan master, lalu konfirmasikan satu kali. Preview belum menyimpan aset. Jika sesi preview kedaluwarsa, unggah ulang dan periksa hasilnya sebelum konfirmasi.',
                    'Ekspor Excel dan CSV' => 'Gunakan tombol ekspor pada halaman Tambah Aset. Periksa jumlah baris, nomor inventaris, serial, tanggal, dan harga pada hasil unduhan. Untuk CSV, impor kolom nomor dan serial sebagai teks di aplikasi spreadsheet agar angka nol di awal tetap terjaga.',
                    'Pengajuan Barang' => 'Buat pengajuan, isi alasan dan rincian barang, lalu simpan draf. Periksa kembali sebelum mengirim ke Keuangan. Pengajuan yang sudah dikirim tidak dapat diedit seperti draf. Pantau keputusan dan catatan peninjau pada detail pengajuan; surat tersedia dari halaman detail.',
                    'Pengadaan dan PO' => 'Buka pengajuan yang disetujui untuk membuat PO terkait. Pilih barang dan jumlah yang akan dipesan, isi pemasok serta harga, lalu simpan. Draf PO harus diputuskan sebelum penerimaan. Surat PO dapat dicetak atau diunduh sebagai Word; status draf atau ditolak tetap ditandai pada dokumen.',
                    'Penerimaan Barang' => 'Pilih PO yang disetujui dan masukkan jumlah yang benar-benar datang. Jumlah tidak boleh melebihi sisa pesanan. Lanjutkan pemeriksaan, verifikasi dokumen, dan pendaftaran inventaris melalui detail penerimaan.',
                    'Label QR dan BAST' => 'Buka detail aset untuk melanjutkan aktivasi dan mencetak label. Isi QR adalah nomor inventaris, bukan alamat situs. Pindai dan cocokkan dengan nomor pada label. Setelah serah terima selesai, BAST dapat dicetak atau diunduh sebagai Word. BAST baru mempertahankan data saat serah terima; dokumen lama tanpa snapshot ditandai draf rekonstruksi.',
                    'Mutasi' => 'Gunakan menu Mutasi untuk memindahkan aset. Periksa posisi asal, pilih lokasi dan ruangan tujuan yang sesuai, kemudian isi catatan. Periksa kembali posisi dan riwayat setelah penyimpanan.',
                    'Laporan dan Audit Trail' => 'Gunakan Laporan untuk meninjau data inventaris sesuai filter. Audit Trail membantu menelusuri tindakan yang tercatat beserta pelaksana dan waktunya. Akses mengikuti peran pengguna.'
                ] as $title => $description)
                    <details class="rounded-xl border border-slate-200 p-4">
                        <summary class="cursor-pointer font-semibold text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-sky-600">{{ $title }}</summary>
                        <p class="mt-3 leading-relaxed text-slate-700">{{ $description }}</p>
                    </details>
                @endforeach
            </div>
        </section>

        <section id="peran" class="scroll-mt-28 rounded-2xl border border-slate-200 bg-white p-5 md:p-6">
            <h2 class="text-lg font-bold">Peran pengguna</h2>
            <dl class="mt-4 space-y-4">
                <div><dt class="font-semibold">Pengaju</dt><dd class="text-slate-600">Menyusun kebutuhan, mengirim pengajuan, dan memantau keputusan pada pengajuannya.</dd></div>
                <div><dt class="font-semibold">Keuangan</dt><dd class="text-slate-600">Memeriksa pengajuan pada tahap Keuangan serta meninjau biaya dan pilihan barang.</dd></div>
                <div><dt class="font-semibold">Direksi</dt><dd class="text-slate-600">Memutuskan pengajuan setelah tahap Keuangan dan keputusan PO sesuai kewenangan.</dd></div>
                <div><dt class="font-semibold">Operasional</dt><dd class="text-slate-600">Mengelola master, PO, penerimaan, inventaris, impor, aktivasi, dan mutasi sesuai izin.</dd></div>
                <div><dt class="font-semibold">Super Admin</dt><dd class="text-slate-600">Mengelola pengaturan dan tindakan administratif yang tersedia. Persetujuan tetap mengikuti pemisahan tugas; pengaju tidak menyetujui pengajuannya sendiri.</dd></div>
            </dl>
            <p class="mt-4 text-sm text-slate-500">Jika menu atau tombol tidak tersedia, hubungi pengelola untuk memeriksa peran akun Anda.</p>
        </section>

        <section id="istilah" class="scroll-mt-28 rounded-2xl border border-slate-200 bg-white p-5 md:p-6">
            <h2 class="text-lg font-bold">Istilah yang digunakan</h2>
            <dl class="mt-4 space-y-4">
                <div><dt class="font-semibold">Kode Barang</dt><dd class="text-slate-600">Identitas jenis barang pada master. Beberapa unit aset dapat menggunakan kode barang yang sama.</dd></div>
                <div><dt class="font-semibold">Nomor Inventaris</dt><dd class="text-slate-600">Identitas unik setiap unit aset, memiliki urutan dan tahun. Nomor tidak digunakan kembali untuk aset lain dan menjadi isi QR pada label.</dd></div>
                <div><dt class="font-semibold">PO (Purchase Order)</dt><dd class="text-slate-600">Dokumen pemesanan barang kepada pemasok, berisi barang, jumlah, dan harga pesanan.</dd></div>
                <div><dt class="font-semibold">BAST</dt><dd class="text-slate-600">Berita Acara Serah Terima yang mencatat penyerahan aset kepada penerima.</dd></div>
                <div><dt class="font-semibold">Kondisi dan status penggunaan</dt><dd class="text-slate-600">Kondisi menggambarkan keadaan fisik barang. Status penggunaan menggambarkan pemanfaatannya. Tahap aktivasi menunjukkan kesiapan proses aset hingga serah terima.</dd></div>
            </dl>
        </section>

        <section id="kendala" class="scroll-mt-28 rounded-2xl border border-slate-200 bg-white p-5 md:p-6">
            <h2 class="text-lg font-bold">Masalah umum</h2>
            <dl class="mt-4 space-y-4">
                <div><dt class="font-semibold">Impor ditolak atau nomor sudah digunakan</dt><dd class="text-slate-600">Baca kesalahan pada preview, periksa nomor duplikat dan pemetaan master. Cari aset yang mungkin sudah berhasil masuk sebelum mencoba kembali.</dd></div>
                <div><dt class="font-semibold">Unduhan diblokir browser</dt><dd class="text-slate-600">Periksa pemberitahuan unduhan dan ekstensi browser untuk situs ini. Bila muncul ERR_BLOCKED_BY_CLIENT, catat pesannya dan minta pengelola membantu memeriksa pemblokiran.</dd></div>
                <div><dt class="font-semibold">Penerimaan atau PO melebihi sisa jumlah</dt><dd class="text-slate-600">Muat ulang detail transaksi dan periksa pesanan atau penerimaan lain yang sudah tercatat. Gunakan jumlah sisa terkini.</dd></div>
                <div><dt class="font-semibold">BAST belum tersedia</dt><dd class="text-slate-600">Selesaikan tahapan aktivasi hingga serah terima dengan nama penerima dan referensi dokumen.</dd></div>
                <div><dt class="font-semibold">QR sulit dipindai</dt><dd class="text-slate-600">Pastikan label tidak terpotong, kontras cukup, dan area kosong di sekitar QR terjaga. Cetak pada skala 100% lalu uji kembali dengan ponsel.</dd></div>
                <div><dt class="font-semibold">Login gagal atau akses ditolak</dt><dd class="text-slate-600">Periksa email dan password, lalu hubungi pengelola akun bila tetap gagal. Jangan membagikan password. Saat melaporkan kendala, sertakan menu, nomor transaksi, waktu kejadian, dan pesan kesalahan.</dd></div>
            </dl>
            <a href="#mulai" class="mt-5 inline-block font-medium text-sky-700 underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-sky-600">Kembali ke panduan awal</a>
        </section>
    </div>
@endsection
