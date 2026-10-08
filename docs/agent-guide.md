# Agent dan skill SIIP

Folder proyek: C:\laragon\www\inventaris.

## Struktur

- `.github/agents/`: empat profil custom agent untuk VS Code.
- `.agents/skills/`: enam skill proyek, masing-masing memiliki SKILL.md.
- `AGENTS.md`: instruksi utama yang tetap berlaku; bagian Laravel Boost sebelumnya dipertahankan.

## Penggunaan di VS Code

Buka folder inventaris sebagai workspace. Pada ekstensi/harness yang mendukung custom agents, pilih SIIP Developer, SIIP Database, SIIP Import atau SIIP Reviewer dari pilihan agent di Chat.

Contoh permintaan:
- SIIP Developer: Buat CRUD master barang dengan Policy dan pengujian.
- SIIP Database: Rancang relasi inventaris, ruangan dan riwayat mutasi.
- SIIP Import: Buat preview impor Excel sesuai kontrak data awal.
- SIIP Reviewer: Review perubahan fitur penerimaan untuk duplikasi dan over-receipt.

Jika profil belum terlihat, periksa Agent Customizations di VS Code dan buka ulang sesi. Dukungan UI bergantung pada ekstensi/harness. Alternatifnya, minta agent membaca file `.github/agents/siip-developer.agent.md` dan SKILL.md terkait secara eksplisit.

Skill tidak memasang paket, menjalankan subagent, atau membangun aplikasi otomatis. File-file ini memberikan panduan ketika ada tugas pengguna yang sesuai. Untuk bootstrap Laravel Boost sebelum perubahan aplikasi, ikuti AGENTS.md yang sudah ada. Pembuatan paket instruksi ini tidak mengubah dependensi aplikasi.

## Skill yang tersedia

| Skill | Lingkup |
| --- | --- |
| siip-laravel | Backend, Policy, Service dan mutasi |
| siip-database | Migration, relasi dan constraint |
| siip-excel-import | Template 23 kolom, preview dan konfirmasi impor |
| siip-procurement | Persetujuan, PO dan penerimaan parsial |
| siip-interface | Blade, Tailwind dan formulir responsif |
| siip-quality | Pengujian, keamanan dan review |

Dokumentasi format:
- https://code.visualstudio.com/docs/agent-customization/custom-agents
- https://code.visualstudio.com/docs/agent-customization/agent-skills
