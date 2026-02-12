
# Sistem Informasi PAUD UNDIP

Sistem Informasi Manajemen Pembayaran dan Keuangan untuk Pendidikan Anak Usia Dini di Universitas Diponegoro.

## 📋 Daftar Isi

- [Tentang Sistem](#tentang-sistem)
- [Fitur Utama](#fitur-utama)
- [Tech Stack](#tech-stack)
- [Instalasi & Setup](#instalasi--setup)
- [Arsitektur Sistem](#arsitektur-sistem)
- [Best Practices](#best-practices)
- [Panduan Pengguna](#panduan-pengguna)
- [Troubleshooting](#troubleshooting)
- [Support](#support)

---

## 🎯 Tentang Sistem

Sistem Informasi PAUD UNDIP adalah aplikasi berbasis web yang dirancang untuk mengelola:
- **Pembayaran Siswa**: Sistem invoicing dan receipt otomatis
- **Laporan Keuangan**: Dashboard analitik real-time
- **Manajemen Siswa & Kelas**: Database siswa dan kelas
- **Virtual Account BNI**: Integrasi pembayaran otomatis
- **Kontrol Akses**: Role-based permission system (Super Admin, Bendahara, Auditor, Kepala Sekolah, Guru)

---

## ✨ Fitur Utama

### 1. Invoice & Pembayaran
- ✅ Generate invoice otomatis untuk setiap siswa
- ✅ Multiple item per invoice (SPP, Pendaftaran, dll)
- ✅ Virtual Account BNI untuk pembayaran online
- ✅ Tracking status pembayaran (Paid/Unpaid)
- ✅ Export invoice ke PDF

### 2. Kwitansi (Receipt)
- ✅ Automatic receipt generation setelah pembayaran
- ✅ Receipt PDF dengan styling professional
- ✅ Receipt numbering otomatis
- ✅ VA reference pada setiap receipt

### 3. Laporan Keuangan
- ✅ Dashboard dengan metrik real-time:
  - Total Pembayaran
  - Total Tagihan
  - Tunggakan
  - Diskon
  - Tingkat Koleksi
- ✅ Filter periode (bulanan/tahunan)
- ✅ Breakdown per sumber pendapatan
- ✅ Summary koleksi per kelas
- ✅ Export PDF, Excel, Print

### 4. Manajemen Data Akademik
- ✅ Master Data Siswa
- ✅ Master Data Kelas
- ✅ Master Tarif per Kelas
- ✅ Academic Year Management
- ✅ Student Class History

### 5. Role-Based Access Control
- ✅ Super Admin: Full access
- ✅ Bendahara: Kelola keuangan, invoice, receipt
- ✅ Auditor: View laporan & analisis
- ✅ Kepala Sekolah: View dashboard & laporan
- ✅ Guru: View siswa & kelas mereka

---

## 🛠 Tech Stack

### Backend
- **Laravel 11.45.1** - PHP Web Framework
- **PHP 8.3.30** - Programming Language
- **MySQL 8.0** - Database
- **Livewire** - Real-time components
- **Filament 3.x** - Admin Panel

### Frontend
- **Blade Templates** - Server-side templating
- **Tailwind CSS 3** - Utility-first CSS
- **Alpine.js** - Lightweight JS framework
- **Dark Mode Support** - Built-in theme switching

### Payment Integration
- **Virtual Account BNI** - Payment gateway
- **Barryvdh/DomPDF** - PDF generation

### Additional Tools
- **Spatie Permissions** - Role & Permission management
- **Spatie Activity Log** - Audit trail
- **Spatie Media Library** - File management

---

## 📦 Instalasi & Setup

### Prerequisites
- Docker & Docker Compose
- Git
- PHP 8.3+
- Composer

### Quick Start

```bash
# 1. Clone repository
git clone <repository-url>
cd PAUD_UNDIP

# 2. Build & start Docker containers
docker-compose up -d

# 3. Install dependencies
docker-compose exec app-filament composer install

# 4. Generate app key
docker-compose exec app-filament php artisan key:generate

# 5. Run migrations
docker-compose exec app-filament php artisan migrate

# 6. Seed database (optional)
docker-compose exec app-filament php artisan db:seed

# 7. Access aplikasi
# Admin Panel: http://localhost:8080/admin
# Default User: superadmin@local.com / password
```

### Environment Setup
Buat file `.env` dari `.env.example`:
```bash
cp .env.example .env
```

Konfigurasi penting:
```
DB_DATABASE=db_paud
DB_USERNAME=root
DB_PASSWORD=root

FILAMENT_ADMIN_PATH=admin
APP_NAME="PAUD UNDIP"
```

---

## 🏗 Arsitektur Sistem

### Folder Structure
```
PAUD_UNDIP/
├── app/
│   ├── Filament/          # Admin panel resources
│   ├── Http/              # Controllers & middleware
│   ├── Models/            # Eloquent models
│   ├── Services/          # Business logic
│   └── Traits/            # Reusable traits
├── database/
│   ├── migrations/        # Database schemas
│   ├── seeders/           # Sample data
│   └── factories/         # Model factories
├── resources/
│   ├── views/
│   │   ├── pdf/          # PDF templates
│   │   ├── partials/     # Shared components
│   │   └── filament/     # Filament views
│   └── css/              # Tailwind CSS
├── routes/               # API & web routes
├── config/               # Configuration files
└── public/               # Static assets
```

### Database Schema

**Core Tables:**
- `schools` - Data sekolah
- `academic_years` - Tahun ajaran (ganjil/genap)
- `classes` - Data kelas (TK, KB, TPA_PAUD, dll)
- `students` - Data siswa
- `student_class_histories` - Riwayat kelas siswa

**Financial Tables:**
- `invoices` - Invoice siswa
- `invoice_items` - Item dalam invoice
- `receipts` - Bukti pembayaran
- `income_types` - Jenis pemasukan (SPP, Pendaftaran, dll)
- `tariffs` - Tarif per kelas & jenis

**System Tables:**
- `users` - User accounts
- `roles` - Role definitions
- `permissions` - Permission definitions
- `activity_log` - Audit trail

---

## 📚 Best Practices

### 1. **Data Integrity**
✅ **Constraint & Foreign Keys**
- Semua relasi menggunakan foreign keys
- Cascade delete untuk data yang dependent
- Soft deletes untuk data penting

✅ **Transactions**
```php
DB::transaction(function () {
    // Multiple operations di dalam transaction
    Invoice::create($data);
    Receipt::create($receiptData);
});
```

✅ **Validation**
- Server-side validation di controller
- Client-side validation di form
- Custom validation rules ketika perlu

### 2. **Performance Optimization**
✅ **Query Optimization**
- Eager loading dengan `with()` untuk relationships
- Avoid N+1 queries
- Use `select()` untuk columns yang dibutuhkan saja

✅ **Caching**
```php
Cache::remember('financial_summary', 3600, function () {
    return FinancialReport::getSummary();
});
```

✅ **Indexing**
- Index pada foreign keys
- Index pada frequently searched columns
- Index pada date fields untuk filtering

### 3. **Security**
✅ **Authentication & Authorization**
- Semua route dilindungi middleware
- Role-based access control (Spatie Permissions)
- Email verification untuk user baru

✅ **Data Protection**
- Encrypt sensitive data (VI BNI numbers)
- No hardcoded credentials
- Use environment variables

✅ **CSRF & XSS Protection**
- CSRF tokens di semua forms
- Blade escaping otomatis
- Content Security Policy headers

### 4. **Code Quality**
✅ **Naming Conventions**
- Model singular: `Student`, `Invoice`
- Table plural: `students`, `invoices`
- Methods verb-first: `getBalance()`, `calculateTotal()`

✅ **Comments & Documentation**
```php
/**
 * Calculate total collection for a period
 * 
 * @param AcademicYear|null $academicYear
 * @param int|null $month
 * @return float
 */
public function getCollectionTotal($academicYear = null, $month = null)
{
    // Implementation
}
```

✅ **DRY Principle**
- Extract repeating logic ke methods
- Use traits untuk shared functionality
- Create reusable Blade components

### 5. **Database Management**
✅ **Migrations**
- Semua schema changes via migrations
- Buat dedicated migration untuk setiap perubahan
- Reversible migrations dengan down() method

✅ **Seeders**
- Use seeders untuk data awal
- Separate seeders untuk different data types
- Seed dalam environment development/testing saja

### 6. **API & Integration**
✅ **Consistent Response Format**
```php
return response()->json([
    'success' => true,
    'data' => $data,
    'message' => 'Operation successful'
]);
```

✅ **Error Handling**
```php
try {
    // Process payment
} catch (PaymentException $e) {
    Log::error('Payment failed', ['error' => $e->getMessage()]);
    return back()->with('error', 'Payment processing failed');
}
```

### 7. **Testing**
✅ **Unit Tests**
- Test business logic secara isolated
- Mock external dependencies

✅ **Feature Tests**
- Test user workflows
- Test authorization/permissions

```bash
# Run tests
php artisan test

# Run specific test
php artisan test tests/Feature/InvoiceTest.php
```

### 8. **Monitoring & Logging**
✅ **Activity Log**
- Semua aksi penting dilog (create, update, delete)
- Audit trail untuk compliance

✅ **Error Tracking**
- Use Flare atau Sentry untuk error tracking
- Monitor exception dari production

---

## 📖 Panduan Pengguna

### Super Admin
- Akses full ke seluruh sistem
- Kelola users dan roles
- View semua laporan
- Change system settings

### Bendahara
- Kelola invoice & pembayaran
- Generate receipt
- Input tarif
- View financial reports

### Auditor
- View-only access ke laporan
- Download reports (PDF, Excel)
- Analisis data keuangan
- No edit permissions

### Kepala Sekolah
- Dashboard overview
- View laporan keuangan
- See student management
- No transaction access

### Guru
- View siswa di kelasnya
- See student information
- View class management
- Limited to own classes

---

## 🔧 Troubleshooting

### Database Connection Error
```bash
# Ensure database container is running
docker-compose ps

# Check database logs
docker-compose logs db-filament

# Recreate database
docker-compose exec app-filament php artisan migrate:fresh
```

### Permission Denied
- Check user role & permissions
- Verify middleware configuration
- Check policy files di `app/Policies/`

### PDF Generation Failed
- Ensure DomPDF dependencies installed
- Check storage permissions
- Verify font files available

### Class Not Found
- Run composer autoload
```bash
composer dump-autoload
```

---

## 📞 Support

### Dokumentasi
- [Laravel Docs](https://laravel.com/docs)
- [Filament Docs](https://filamentphp.com/docs)
- [Tailwind Docs](https://tailwindcss.com/docs)

### Kontak Support
- **Email**: support@paud-undip.ac.id
- **WhatsApp**: [Contact Number]
- **Jam Operasional**: Senin-Jumat 08:00-16:00 WIB

### Issue Reporting
Jika menemukan bug atau issue, silakan:
1. Check existing issues
2. Provide detailed description
3. Include error logs & screenshots
4. Submit ke issue tracker

---

## 📝 Changelog

### v1.0.0 (12 Februari 2026)
- ✅ Initial release
- ✅ Invoice management
- ✅ Receipt generation
- ✅ Financial reports
- ✅ Role-based access control
- ✅ Virtual Account BNI integration

---

## 📄 License

Proprietary - PAUD UNDIP 2026

---

**Last Updated**: February 12, 2026 | **Version**: 1.0.0
