## Auditor Permission & Authorization System

Dokumentasi lengkap permission system untuk role Auditor dalam aplikasi PAUD UNDIP.

### 📋 Permissions untuk Auditor (Read-Only - Full Access)

Role **auditor** memiliki permissions berikut untuk akses read-only ke semua fitur sistem:

#### 1. **Academic Year (Tahun Ajaran)**
- `view_academic_year` - Lihat data tahun ajaran
- `view_any_academic_year` - Lihat daftar tahun ajaran

**Akses**: Auditor dapat melihat semua tahun ajaran (read-only)

#### 2. **Class Access (Kelas)**
- `view_school_class` - Lihat data kelas individual
- `view_any_school_class` - Lihat daftar semua kelas

**Akses**: Auditor dapat melihat semua kelas tanpa batasan (read-only)

#### 3. **Student Access (Siswa)**
- `view_student` - Lihat data siswa individual
- `view_any_student` - Lihat daftar siswa

**Akses**: Auditor dapat melihat semua siswa tanpa batasan (read-only)

#### 4. **Student Class History (Riwayat Kelas Siswa)**
- `view_student_class_history` - Lihat riwayat kelas siswa
- `view_any_student_class_history` - Lihat daftar riwayat kelas

**Akses**: Auditor dapat melihat riwayat kelas semua siswa (read-only)

#### 5. **Invoice/Payment History (Riwayat Pembayaran)**
- `view_invoice` - Lihat invoice individual
- `view_any_invoice` - Lihat daftar invoice

**Akses**: Auditor dapat melihat semua invoice tanpa batasan (read-only)

#### 6. **Receipt/Kwitansi (Kwitansi Pembayaran)**
- `view_receipt` - Lihat kwitansi individual
- `view_any_receipt` - Lihat daftar kwitansi

**Akses**: Auditor dapat melihat semua kwitansi tanpa batasan (read-only)

#### 7. **Tariff (Tarif Pembayaran)**
- `view_tariff` - Lihat tarif
- `view_any_tariff` - Lihat daftar tarif

**Akses**: Auditor dapat melihat semua tarif (read-only)

#### 8. **Virtual Account (Rekening Virtual)**
- `view_virtual_account` - Lihat rekening virtual
- `view_any_virtual_account` - Lihat daftar rekening virtual

**Akses**: Auditor dapat melihat semua rekening virtual (read-only)

#### 9. **Financial Report (Laporan Keuangan)**
- `view_financial::report` - Lihat laporan keuangan individual
- `view_any_financial::report` - Lihat daftar laporan keuangan

**Akses**: Auditor dapat melihat semua laporan keuangan (read-only)

#### 10. **School (Data Sekolah)**
- `view_school` - Lihat data sekolah
- `view_any_school` - Lihat daftar sekolah

**Akses**: Auditor dapat melihat semua data sekolah (read-only)

#### 11. **Activity Log & Audit Trail**
- `access_log_viewer` - Akses Activity Log Viewer

**Akses**: Auditor dapat mengakses activity log untuk melihat semua aktivitas sistem

---

### 🔐 Authorization Logic (Policies)

#### **AcademicYearPolicy**
```php
// viewAny() - Auditor lihat semua tahun ajaran
// view(AcademicYear) - Auditor lihat semua tahun ajaran
// create() - BLOCKED untuk Auditor (hanya Admin)
// update() - BLOCKED untuk Auditor (hanya Admin)
// delete() - BLOCKED untuk Auditor (hanya SuperAdmin)
```

#### **StudentPolicy**
```php
// viewAny() - Auditor lihat semua siswa
// view(Student) - Auditor lihat semua siswa
// create() - BLOCKED untuk Auditor (hanya Admin)
// update() - BLOCKED untuk Auditor (hanya Admin)
// delete() - BLOCKED untuk Auditor (hanya SuperAdmin)
```

#### **SchoolClassPolicy**
```php
// viewAny() - Auditor lihat semua kelas
// view(SchoolClass) - Auditor lihat semua kelas
// create() - BLOCKED untuk Auditor (hanya Admin)
// update() - BLOCKED untuk Auditor (hanya Admin)
// delete() - BLOCKED untuk Auditor (hanya SuperAdmin)
```

#### **StudentClassHistoryPolicy**
```php
// viewAny() - Auditor lihat semua riwayat kelas
// view(StudentClassHistory) - Auditor lihat semua riwayat kelas
// create() - BLOCKED untuk Auditor (hanya Admin)
// update() - BLOCKED untuk Auditor (hanya Admin)
// delete() - BLOCKED untuk Auditor (hanya SuperAdmin)
```

#### **InvoicePolicy**
```php
// viewAny() - Auditor lihat semua invoice
// view(Invoice) - Auditor lihat semua invoice
// create() - BLOCKED untuk Auditor (hanya Bendahara/Admin/Kepsek)
// update() - BLOCKED untuk Auditor (hanya Bendahara/Admin)
// delete() - BLOCKED untuk Auditor (hanya Bendahara/Admin)
```

#### **ReceiptPolicy**
```php
// viewAny() - Auditor lihat semua receipt
// view(Receipt) - Auditor lihat semua receipt
// create() - BLOCKED untuk Auditor (hanya Bendahara/Admin/Kepsek)
// update() - BLOCKED untuk Auditor (hanya Bendahara/Admin)
// delete() - BLOCKED untuk Auditor (hanya Admin)
```

#### **TariffPolicy**
```php
// viewAny() - Auditor lihat semua tarif
// view(Tariff) - Auditor lihat semua tarif
// create() - BLOCKED untuk Auditor (hanya Bendahara/Admin/Kepsek)
// update() - BLOCKED untuk Auditor (hanya Bendahara/Admin/Kepsek)
// delete() - BLOCKED untuk Auditor (hanya Admin)
```

#### **VirtualAccountPolicy**
```php
// viewAny() - Auditor lihat semua rekening virtual
// view(VirtualAccount) - Auditor lihat semua rekening virtual
// create() - BLOCKED untuk Auditor (hanya Bendahara/Admin/Kepsek)
// update() - BLOCKED untuk Auditor (hanya Bendahara/Admin/Kepsek)
// delete() - BLOCKED untuk Auditor (hanya Admin)
```

#### **SchoolPolicy**
```php
// viewAny() - Auditor lihat semua sekolah
// view(School) - Auditor lihat semua sekolah
// create() - BLOCKED untuk Auditor (hanya Admin)
// update() - BLOCKED untuk Auditor (hanya Admin)
// delete() - BLOCKED untuk Auditor (hanya SuperAdmin)
```

---

### 📊 Comprehensive Access Matrix

| Feature | Guru | Auditor | Bendahara | Kepsek | Admin |
|---------|------|---------|-----------|--------|-------|
| **View Tahun Ajaran** | ❌ | ✅ Read | ✅ Full | ✅ Full | ✅ Full |
| **View Kelas** | 🔍 Own | ✅ Read | ✅ Full | ✅ Full | ✅ Full |
| **View Siswa** | 🔍 Own | ✅ Read | ✅ Full | ✅ Full | ✅ Full |
| **View Riwayat Kelas** | 🔍 Own | ✅ Read | ✅ Full | ✅ Full | ✅ Full |
| **View Invoice** | 🔍 Own | ✅ Read | ✅ Full | ✅ Full | ✅ Full |
| **Create Invoice** | ❌ | ❌ | ✅ | ✅ | ❌ |
| **View Receipt** | 🔍 Own | ✅ Read | ✅ Full | ✅ Full | ✅ Full |
| **Create Receipt** | ❌ | ❌ | ✅ | ✅ | ❌ |
| **View Tarif** | ❌ | ✅ Read | ✅ Full | ✅ Full | ✅ Full |
| **View Virtual Account** | ❌ | ✅ Read | ✅ Full | ✅ Full | ✅ Full |
| **View Financial Report** | ❌ | ✅ Read | ✅ Full | ✅ Full | ✅ Full |
| **View School** | ❌ | ✅ Read | ✅ Full | ✅ Full | ✅ Full |
| **View Activity Log** | ❌ | ✅ | ❌ | ❌ | ✅ |

**Legend:**
- ✅ Read = Read-only access to all
- ✅ Full = Full CRUD access
- 🔍 Own = Limited to own scope (kelas guru, siswa guru, etc)
- ❌ = No access

---

### 🔧 Implementation Details

#### **Files yang Dibuat/Diupdate:**

**New Seeder:**
1. **AuditorPermissionsSeeder** (`database/seeders/AuditorPermissionsSeeder.php`)
   - Membuat 20 permissions untuk auditor
   - Assign semua permissions ke role auditor

**New Policies:**
2. **AcademicYearPolicy** (`app/Policies/AcademicYearPolicy.php`) - NEW
3. **StudentClassHistoryPolicy** (`app/Policies/StudentClassHistoryPolicy.php`) - NEW
4. **TariffPolicy** (`app/Policies/TariffPolicy.php`) - NEW
5. **VirtualAccountPolicy** (`app/Policies/VirtualAccountPolicy.php`) - NEW
6. **SchoolPolicy** (`app/Policies/SchoolPolicy.php`) - NEW

**Updated Policies (Added Auditor Support):**
7. **StudentPolicy** - Added auditor view all support
8. **SchoolClassPolicy** - Added auditor view all support
9. **InvoicePolicy** - Added auditor view all support
10. **ReceiptPolicy** - Added auditor view all support

**Service Provider:**
11. **AuthServiceProvider** - Register 9 policies

**Database:**
12. **DatabaseSeeder** - Add AuditorPermissionsSeeder ke call sequence

---

### 🚀 Cara Menggunakan

#### **Jalankan All Seeders:**
```bash
php artisan db:seed
```

#### **Atau Jalankan Hanya Auditor Permissions Seeder:**
```bash
php artisan db:seed --class=AuditorPermissionsSeeder
```

#### **Verifikasi di Database:**
```sql
-- Check auditor permissions
SELECT p.name, p.guard_name 
FROM permissions p
JOIN role_has_permissions rhp ON p.id = rhp.permission_id
JOIN roles r ON rhp.role_id = r.id
WHERE r.name = 'auditor'
ORDER BY p.name;

-- Check auditor user
SELECT u.id, u.username, u.firstname, u.lastname
FROM users u
JOIN model_has_roles mhr ON u.id = mhr.model_id
JOIN roles r ON mhr.role_id = r.id
WHERE r.name = 'auditor'
  AND mhr.model_type = 'App\\Models\\User';
```

---

### 📌 Best Practices Implemented

1. **Comprehensive Read-Only Access**
   - Auditor dapat akses semua fitur untuk monitoring & auditing
   - Tidak ada create, update, atau delete permissions

2. **Separation of Concerns**
   - Permission check di `viewAny()` / `view()`
   - Query filtering di `getEloquentQuery()`
   - Authorization logic terpusat di Policies

3. **Consistency**
   - Pattern sama untuk semua resources
   - Auditor selalu get all, Guru get filtered by scope
   - Bendahara/Kepsek get full access untuk keuangan

4. **Security**
   - Explicit permission check, bukan implicit
   - No hardcoded role checks di queries
   - Scope validation at multiple levels

5. **Maintainability**
   - Easy to add new resource policies
   - Easy to modify access rules
   - Minimal code duplication

6. **Audit Trail**
   - Auditor dapat akses activity log
   - Dapat memantau siapa yang mengubah apa dan kapan
   - Complete visibility untuk compliance

---

### 🔄 Use Cases untuk Auditor

#### **Monitoring & Verification**
- Verifikasi semua transaksi keuangan (Invoice, Receipt)
- Monitor data siswa dan riwayat kelas
- Check tarif dan virtual account configuration
- Review financial reports tanpa bisa mengubah

#### **Compliance & Audit**
- Generate reports berdasarkan data yang dilihat
- Verify consistency antara invoice dan receipt
- Check activity log untuk audit trail
- Ensure proper procedures diikuti

#### **Read-Only Investigation**
- Investigate discrepancies tanpa risk mengubah data
- Trace student payment history
- Review teacher-class assignments
- Analyze financial trends

---

### 🔐 Security Considerations

1. **No Write Capability**
   - Auditor hanya bisa READ, tidak bisa CREATE/UPDATE/DELETE
   - Protects data integrity
   - Prevents accidental modifications

2. **Full Visibility**
   - Dapat melihat semua data tanpa batasan scope
   - Diperlukan untuk audit yang comprehensive
   - Controlled melalui permission system

3. **Activity Logging**
   - Semua akses auditor dicatat di activity log
   - Dapat di-audit sendiri (meta-audit)
   - Compliance dengan requirements

4. **Role Isolation**
   - Auditor tidak bisa assign permissions
   - Tidak bisa manage users/roles
   - Strictly limited ke read-only operations

---

### 📈 Future Enhancements

- [ ] Time-based access restrictions (audit hanya dalam jam tertentu)
- [ ] Scope-based filtering untuk audit (by school, by academic year)
- [ ] Export/Report generation permissions
- [ ] Notification alerts untuk suspicious activities
- [ ] Audit report template system
- [ ] Compliance check automated reports

