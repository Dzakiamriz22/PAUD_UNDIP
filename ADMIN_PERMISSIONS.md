## Admin Permissions & Authorization

Dokumentasi lengkap permission system untuk role **Admin** dalam aplikasi PAUD UNDIP.

### 📋 Permissions untuk Admin (Full CRUD - Admin Role)

Role **admin** memiliki full administration capabilities sesuai dengan SOP:

#### **Academic Year (Tahun Ajaran)**
- `view_academic_year` - Lihat tahun ajaran
- `view_any_academic_year` - Lihat daftar tahun ajaran
- `create_academic_year` - Buat tahun ajaran baru
- `update_academic_year` - Edit tahun ajaran

**Access**: Admin dapat manage academic year (tidak bisa delete - hanya SuperAdmin)

#### **Class (Kelas)**
- `view_school_class` - Lihat kelas
- `view_any_school_class` - Lihat daftar kelas
- `create_school_class` - Buat kelas baru
- `update_school_class` - Edit kelas
- `delete_school_class` - Hapus kelas

**Access**: Admin full CRUD untuk classes

#### **Student (Siswa)**
- `view_student` - Lihat siswa
- `view_any_student` - Lihat daftar siswa
- `create_student` - Tambah siswa baru
- `update_student` - Edit siswa
- `delete_student` - Hapus siswa

**Access**: Admin full CRUD untuk students

#### **Student Class History (Riwayat Kelas)**
- `view_student_class_history` - Lihat riwayat kelas
- `view_any_student_class_history` - Lihat daftar riwayat
- `create_student_class_history` - Buat riwayat kelas
- `update_student_class_history` - Edit riwayat kelas

**Access**: Admin dapat manage class history (tidak bisa delete)

#### **Invoice (Tagihan)**
- `view_invoice` - Lihat invoice
- `view_any_invoice` - Lihat daftar invoice
- `create_invoice` - Buat invoice
- `update_invoice` - Edit invoice
- `delete_invoice` - Hapus invoice

**Access**: Admin full CRUD untuk invoice

#### **Receipt (Kwitansi)**
- `view_receipt` - Lihat kwitansi
- `view_any_receipt` - Lihat daftar kwitansi
- `create_receipt` - Buat kwitansi
- `update_receipt` - Edit kwitansi
- `delete_receipt` - Hapus kwitansi

**Access**: Admin full CRUD untuk receipt

#### **Tariff (Tarif)**
- `view_tariff` - Lihat tarif
- `view_any_tariff` - Lihat daftar tarif
- `create_tariff` - Buat tarif baru
- `update_tariff` - Edit tarif
- `delete_tariff` - Hapus tarif

**Access**: Admin full CRUD untuk tarif

#### **Virtual Account (Rekening Virtual)**
- `view_virtual_account` - Lihat rekening virtual
- `view_any_virtual_account` - Lihat daftar
- `create_virtual_account` - Buat rekening virtual
- `update_virtual_account` - Edit rekening virtual
- `delete_virtual_account` - Hapus rekening virtual

**Access**: Admin full CRUD untuk virtual account

#### **Financial Report (Laporan Keuangan)**
- `view_financial::report` - Lihat laporan keuangan
- `view_any_financial::report` - Lihat daftar laporan
- `create_financial::report` - Buat laporan keuangan

**Access**: Admin dapat membuat & melihat laporan keuangan

#### **School (Data Sekolah)**
- `view_school` - Lihat data sekolah
- `view_any_school` - Lihat daftar sekolah
- `update_school` - Edit data sekolah

**Access**: Admin dapat view & update (delete hanya SuperAdmin)

#### **User Management (Manajemen Pengguna)**
- `view_user` - Lihat pengguna
- `view_any_user` - Lihat daftar pengguna
- `create_user` - Buat pengguna baru
- `update_user` - Edit pengguna

**Access**: Admin dapat manage non-admin users

#### **System Access**
- `access_log_viewer` - Akses activity log viewer

**Access**: Admin dapat monitor semua aktivitas sistem

---

### 🔐 Admin Authorization Pattern

```php
public function viewAny(User $user): bool
{
    // Admin dapat view all
    if ($user->is_admin) {
        return true;
    }
    return false;
}

public function view(User $user, Resource $resource): bool
{
    // Admin dapat view all
    if ($user->is_admin) {
        return true;
    }
    return false;
}

public function create(User $user): bool
{
    // Only admin & super_admin
    return $user->isSuperAdmin() || $user->hasRole('admin');
}

public function update(User $user, Resource $resource): bool
{
    // Only admin & super_admin
    return $user->isSuperAdmin() || $user->hasRole('admin');
}

public function delete(User $user, Resource $resource): bool
{
    // Different per resource:
    // - Full CRUD: Admin can delete
    // - Critical data: Only SuperAdmin
    return $user->isSuperAdmin() || ($user->hasRole('admin') && !$isCriticalData);
}
```

---

### 📊 Summary

Admin Role memiliki **28 permissions** dengan fokus pada:
- ✅ Full CRUD untuk academic, student, class data
- ✅ Full CRUD untuk financial data (invoice, receipt, tariff)
- ✅ User management (create non-admin users)
- ✅ System monitoring (activity log)
- ❌ Tidak bisa delete critical data
- ❌ Tidak bisa manage other admins atau super_admin

