## Guru Permission & Authorization System

Dokumentasi lengkap permission system untuk role Guru dalam aplikasi PAUD UNDIP.

### 📋 Permissions untuk Guru (Read-Only)

Role **guru** memiliki permissions berikut:

#### 1. **Class Access (Kelas)**
- `view_school_class` - Lihat data kelas individual
- `view_any_school_class` - Lihat daftar semua kelas

**Akses**: Guru hanya bisa melihat kelas yang ditugaskan sebagai homeroom teacher mereka.

#### 2. **Student Access (Siswa)**
- `view_student` - Lihat data siswa individual
- `view_any_student` - Lihat daftar siswa

**Akses**: Guru hanya bisa melihat siswa yang terdaftar di kelas mereka dengan status `is_active = true`.

#### 3. **Invoice/Payment History (Riwayat Pembayaran)**
- `view_invoice` - Lihat invoice individual
- `view_any_invoice` - Lihat daftar invoice

**Akses**: Guru hanya bisa melihat invoice dari siswa di kelas mereka. Invoice dari siswa kelas lain tidak terlihat.

#### 4. **Receipt/Kwitansi (Kwitansi Pembayaran)**
- `view_receipt` - Lihat kwitansi individual
- `view_any_receipt` - Lihat daftar kwitansi

**Akses**: Guru hanya bisa melihat kwitansi dari siswa di kelas mereka.

---

### 🔐 Authorization Logic (Policies)

#### **StudentPolicy**
```php
// viewAny() - Guru hanya melihat list siswa di kelasnya
// view(Student) - Guru hanya bisa view siswa di kelasnya
// create() - BLOCKED untuk Guru (hanya Admin/SuperAdmin)
// update() - BLOCKED untuk Guru (hanya Admin/SuperAdmin)
// delete() - BLOCKED untuk Guru (hanya SuperAdmin)
```

#### **SchoolClassPolicy**
```php
// viewAny() - Guru hanya melihat list kelas yang mereka ajar
// view(SchoolClass) - Guru hanya bisa view kelas mereka sendiri
// create() - BLOCKED untuk Guru (hanya Admin/SuperAdmin)
// update() - BLOCKED untuk Guru (hanya Admin/SuperAdmin)
// delete() - BLOCKED untuk Guru (hanya SuperAdmin)
```

#### **InvoicePolicy**
```php
// viewAny() - Guru hanya melihat invoice siswa di kelasnya
// view(Invoice) - Guru hanya melihat invoice siswa di kelasnya
// create() - BLOCKED untuk Guru (hanya Bendahara/Admin/Kepsek)
// update() - BLOCKED untuk Guru (hanya Bendahara/Admin dengan status draft)
// delete() - BLOCKED untuk Guru (hanya Bendahara/Admin dengan status draft)
```

#### **ReceiptPolicy**
```php
// viewAny() - Guru hanya melihat receipt siswa di kelasnya
// view(Receipt) - Guru hanya melihat receipt siswa di kelasnya
// create() - BLOCKED untuk Guru (hanya Bendahara/Admin/Kepsek)
// update() - BLOCKED untuk Guru (hanya Bendahara/Admin)
// delete() - BLOCKED untuk Guru (hanya Admin)
```

---

### 🔍 Query Logic (Scope-based Access)

Selain permission check, aplikasi menggunakan database query filtering melalui `getEloquentQuery()`:

#### **StudentResource.getEloquentQuery()**
Guru: Filter hanya siswa yang `is_active = true` di kelas mereka

#### **SchoolClassResource.getEloquentQuery()**
Guru: Filter hanya kelas dengan `homeroom_teacher_id = user_id`

#### **InvoiceResource.getEloquentQuery()**
Guru: Filter invoice milik siswa dari kelas mereka

#### **ReceiptResource.getEloquentQuery()**
Guru: Filter receipt milik siswa dari kelas mereka

---

### 📊 Access Matrix

| Feature | Guru | Admin | Bendahara | Kepsek | Auditor |
|---------|------|-------|-----------|--------|---------|
| **View Kelas Sendiri** | ✅ Read | ✅ Full | ✅ Full | ✅ Full | ✅ Read |
| **View Siswa di Kelas** | ✅ Read | ✅ Full | ✅ Full | ✅ Full | ✅ Read |
| **View Invoice** | ✅ Read | ✅ Full | ✅ Full | ✅ Full | ✅ Read |
| **Create Invoice** | ❌ | ❌ | ✅ | ✅ | ❌ |
| **View Receipt** | ✅ Read | ✅ Full | ✅ Full | ✅ Full | ✅ Read |
| **Create Receipt** | ❌ | ❌ | ✅ | ✅ | ❌ |

---

### 🔧 Implementation Details

#### **File yang Dibuat/Diupdate:**

1. **GuruPermissionsSeeder** (`database/seeders/GuruPermissionsSeeder.php`)
   - Membuat 8 permissions untuk guru
   - Assign permissions ke role guru

2. **StudentPolicy** (`app/Policies/StudentPolicy.php`) - NEW
   - Mengatur akses view untuk siswa
   - Guru hanya bisa lihat siswa di kelasnya

3. **SchoolClassPolicy** (`app/Policies/SchoolClassPolicy.php`) - NEW
   - Mengatur akses view untuk kelas
   - Guru hanya bisa lihat kelas mereka sendiri

4. **InvoicePolicy** (`app/Policies/InvoicePolicy.php`) - UPDATED
   - Menambah support untuk guru (read-only)
   - Guru hanya bisa lihat invoice siswa di kelasnya

5. **ReceiptPolicy** (`app/Policies/ReceiptPolicy.php`) - UPDATED
   - Mengatur akses untuk kwitansi
   - Guru hanya bisa lihat receipt siswa di kelasnya

6. **AuthServiceProvider** (`app/Providers/AuthServiceProvider.php`) - UPDATED
   - Register 4 policies: Student, SchoolClass, Invoice, Receipt

7. **DatabaseSeeder** (`database/seeders/DatabaseSeeder.php`) - UPDATED
   - Menambah `GuruPermissionsSeeder` ke seeder list

---

### 🚀 Cara Menggunakan

#### **Jalankan All Seeders:**
```bash
php artisan db:seed
```

#### **Atau Jalankan Hanya Guru Permissions Seeder:**
```bash
php artisan db:seed --class=GuruPermissionsSeeder
```

#### **Verifikasi di Database:**
```sql
-- Check guru permissions
SELECT p.name, p.guard_name 
FROM permissions p
JOIN role_has_permissions rhp ON p.id = rhp.permission_id
JOIN roles r ON rhp.role_id = r.id
WHERE r.name = 'guru'
ORDER BY p.name;

-- Check teacher assignments
SELECT u.username, u.firstname, u.lastname, sc.code, sc.category
FROM users u
JOIN classes sc ON u.id = sc.homeroom_teacher_id
WHERE u.deleted_at IS NULL
ORDER BY sc.category;
```

---

### 📌 Best Practices Implemented

1. **Separation of Concerns**
   - Permission check di `viewAny()` / `view()`
   - Scope filtering di `getEloquentQuery()`
   - Tidak ada hardcoded role check di queries

2. **Consistency**
   - Semua read operations untuk guru menggunakan same pattern
   - Semua CUD (Create, Update, Delete) blocked untuk guru

3. **Query Efficiency**
   - Menggunakan relationship loading optimal
   - Scope filtering di database level, bukan aplikasi

4. **Security**
   - Guru hanya melihat studentnya sendiri di kelas mereka
   - Tidak ada cross-class data leakage
   - Permission explicit diminta, bukan implicit

5. **Maintainability**
   - Mudah menambah permission baru
   - Mudah mengubah access rules
   - Centralized authorization logic di Policies

---

### 🔄 Future Enhancement Ideas

- [ ] Add `edit_student_notes` permission untuk guru menulis catatan siswa
- [ ] Add `export_payment_report` permission untuk download laporan pembayaran
- [ ] Add scope-based permissions (menggunakan `RoleHasScope`)
- [ ] Add time-based access (hanya akses saat tahun ajaran aktif)
- [ ] Add audit logging untuk setiap akses guru

