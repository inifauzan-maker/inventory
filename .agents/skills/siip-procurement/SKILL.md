---
name: siip-procurement
description: Implementasikan alur rencana, pengajuan, persetujuan, PO dan penerimaan barang SIIP dengan penerimaan parsial serta pelacakan aset.
---

# Pengadaan SIIP

Petakan state dan izin transisi sebelum implementasi. Pisahkan status rencana/pengajuan, approval, PO dan penerimaan; jangan memakai satu status global untuk semuanya.

- Pengajuan berasal dari kebutuhan/rencana. Approval merujuk dokumen dan revisi yang dinilai, bukan PO yang belum dibuat.
- Rekam level, approver, keputusan, catatan dan waktu. Jangan mengizinkan approver di luar kewenangannya; jangan mengasumsikan pengaju boleh menyetujui sendiri.
- Tetapkan alur revisi sesuai kebutuhan. Perubahan material pada jumlah, harga atau spesifikasi yang telah disetujui memerlukan persetujuan ulang dan mempertahankan riwayat lama.
- Hitung subtotal dan total di server. Estimasi, nilai pesanan dan realisasi adalah nilai berbeda.
- Penerimaan dapat bertahap. Jumlah kumulatif diterima tidak boleh melebihi jumlah dipesan; kunci detail PO saat memeriksa dan menyimpan.
- Pembuatan aset dari penerimaan dilakukan atomik dan idempoten. Simpan hubungan detail PO -> detail penerimaan -> aset.
- Selesai hanya jika prasyarat dokumen terpenuhi. Barang belum diterima tidak boleh muncul sebagai aset yang dimiliki.

Uji happy path, transisi ilegal, approval tanpa izin, revisi setelah approval, penerimaan parsial, over-receipt, retry dan request bersamaan. Batasi implementasi pada tahap yang diminta pengguna.
