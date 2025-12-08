# pemrograman-dasar

# 💰 BudgetKu — Aplikasi Pengatur Keuangan Pribadi

> Website budgeting sederhana berbasis PHP native, **tanpa database**, menggunakan file JSON untuk penyimpanan data per pengguna.  
> Responsif untuk mobile & desktop. Inspirasi: Goodbudget & Rocket Money.

---

## ✅ Ketentuan Tugas Terpenuhi

| No | Ketentuan | File & Implementasi |
|----|-----------|---------------------|
| 1 | **Validasi server-side dengan `filter_var()`** | `add-transaction.php`: validasi `amount` (`FILTER_VALIDATE_FLOAT`), `date`, `description` (`FILTER_SANITIZE_FULL_SPECIAL_CHARS`). |
| 2 | **Manipulasi string & data dengan fungsi PHP** | `functions.php`: `stripos()` (auto-kategori), `json_encode/decode`, `array_filter`, `array_map`, `substr`, `date()`, `number_format()`. |
| 3 | **File handling (`fopen`, `fwrite`, dll)** | `functions.php`: `file_get_contents()`, `file_put_contents()` untuk baca/tulis `data/*.json`. `backup.php`: `fopen('php://output')`, `fputcsv()`. |
| 4 | **Mengembangkan logika bisnis aplikasi** | - Update saldo akun saat transaksi/hapus<br>- Progres goal otomatis dari transaksi bertipe "tabungan"<br>- Auto-kategorisasi berbasis keyword<br>- Ringkasan bulanan & YTD |
| 5 | **Debugging & error handling** | `functions.php`: cek `file_exists()`, `@file_get_contents`, `json_last_error()`, fallback ke data default, `error_log()`. Semua form punya validasi & pesan error user-friendly. |

---

## 🚀 Cara Menjalankan

### Prasyarat
- PHP 7.4+ (atau XAMPP/Laragon/PHP built-in server)

### Langkah
1. Clone/salin folder `budgetku-final/` ke server lokal:
   ```bash
   # Contoh: pakai PHP built-in server
   cd budgetku-final
   php -S localhost:8000