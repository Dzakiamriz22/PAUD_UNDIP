## Kepala Sekolah Permissions & Authorization

Dokumentasi lengkap permission system untuk role **Kepala Sekolah** (Principal) dalam aplikasi PAUD UNDIP.

### 📋 Permissions untuk Kepala Sekolah (Executive Overview + Approvals)

Role **kepala_sekolah** memiliki strategic oversight capabilities sesuai SOP:

#### **Academic Oversight (Oversight Akademik)**

**Academic Year Management**
- `view_academic_year` - Lihat tahun ajaran
- `view_any_academic_year` - Lihat daftar tahun ajaran
- `update_academic_year` - Update tahun ajaran (activate/deactivate semester)

**Class Management**
- `view_school_class` - Lihat kelas
- `view_any_school_class` - Lihat daftar kelas
- `update_school_class` - Edit kelas (assign teachers, etc)

**Student Management & Approvals**
- `view_student` - Lihat siswa
- `view_any_student` - Lihat daftar siswa
- `update_student` - Edit siswa (approve enrollment)

**Student Class History**
- `view_student_class_history` - Lihat riwayat kelas
- `view_any_student_class_history` - Lihat daftar riwayat
- `update_student_class_history` - Update riwayat (approvals)

#### **Financial Oversight (Oversight Keuangan)**

**Invoice Review & Approval**
- `view_invoice` - Lihat invoice
- `view_any_invoice` - Lihat daftar invoice
- `update_invoice` - Update invoice (approve, set status)

**Receipt Verification**
- `view_receipt` - Lihat kwitansi
- `view_any_receipt` - Lihat daftar kwitansi
- `update_receipt` - Verify/approve kwitansi

**Tariff Policy Setting**
- `view_tariff` - Lihat tarif
- `view_any_tariff` - Lihat daftar tarif
- `create_tariff` - Buat tarif baru (set payment policy)
- `update_tariff` - Edit tarif pembayaran

**Virtual Account Configuration**
- `view_virtual_account` - Lihat rekening virtual
- `view_any_virtual_account` - Lihat daftar rekening
- `create_virtual_account` - Buat rekening virtual
- `update_virtual_account` - Edit rekening virtual

#### **Financial Reporting**

**Reports**
- `view_financial::report` - Lihat laporan keuangan
- `view_any_financial::report` - Lihat daftar laporan
- `create_financial::report` - Generate laporan keuangan

#### **School Administration**

**School Settings**
- `view_school` - Lihat data sekolah
- `view_any_school` - Lihat daftar sekolah
- `update_school` - Edit data sekolah (school info, profile)

#### **Compliance & Oversight**

**System Audit**
- `access_log_viewer` - Access activity log (monitor semua aktivitas)

---

### 🔐 Kepala Sekolah Authorization Pattern

```php
// For Academic Resources (Full read + selective update)
public function view(User $user, SchoolClass $class): bool
{
    if (!$user->hasPermissionTo('view_school_class')) {
        return false;
    }
    
    // Kepsek can view all classes
    if ($user->isKepsek()) {
        return true;
    }
    
    return false;
}

public function update(User $user, SchoolClass $class): bool
{
    // Kepsek dapat update classes (e.g., assign teacher)
    if ($user->isKepsek() && $user->hasPermissionTo('update_school_class')) {
        return true;
    }
    
    return false;
}

// For Financial Resources (Full read + approval capability)
public function update(User $user, Invoice $invoice): bool
{
    // Kepsek dapat approve/update invoice
    if ($user->isKepsek() && $user->hasPermissionTo('update_invoice')) {
        return true;
    }
    
    return false;
}

// Cannot delete (no delete permissions)
public function delete(User $user, Resource $resource): bool
{
    return false; // Kepsek has no delete permissions
}
```

---

### 📊 Kepala Sekolah Responsibility Matrix

| Area | Permissions | Purpose |
|------|-------------|---------|
| **Academic** | View All + Update | Oversee curriculum, classes, teachers |
| **Student Enrollment** | View All + Approve | Approve new student enrollments |
| **Financial Policy** | View All + Create/Update | Set tariff policies, payment channels |
| **Invoice Review** | View All + Approve | Review & approve invoices |
| **Receipt Verification** | View All + Verify | Verify payment receipts |
| **Reports** | View & Generate | Create financial/academic reports |
| **Audit Trail** | View Activity Log | Monitor system compliance |

---

### 🎯 Primary SOP Functions

Kepala Sekolah roles and responsibilities:

#### 1. **Academic Leadership**
- Set academic year & semester schedule
- Review class organization & teacher assignments
- Approve student enrollments & transfers
- Oversee student progress tracking

#### 2. **Financial Leadership**
- Approve major financial transactions
- Set tuition tariffs & payment policies
- Configure payment channels (virtual accounts)
- Approve invoices above certain threshold
- Generate & review financial reports

#### 3. **School Operations**
- Update school profile/settings
- Coordinate with admin on operational issues
- Provide strategic direction
- Make approval decisions

#### 4. **Compliance & Oversight**
- Monitor system activity (audit trail)
- Ensure proper procedures are followed
- Track financial transactions
- Review student data accuracy

#### 5. **Collaboration**
- Work with Admin on system management
- Work with Bendahara on financial matters
- Work with Guru on academic matters
- Review Auditor findings

---

### 📊 Access Matrix

| Resource | SuperAdmin | Admin | Bendahara | Kepsek | Guru | Auditor |
|----------|-----------|-------|-----------|--------|------|---------|
| Academic Year | ✅ CRUD | ✅ CRU | ❌ | ✅ R+U | ❌ | ✅ R |
| Classes | ✅ CRUD | ✅ CRUD | ❌ | ✅ R+U | 🔍 R | ✅ R |
| Students | ✅ CRUD | ✅ CRUD | ❌ | ✅ R+U | 🔍 R | ✅ R |
| Invoice | ✅ CRUD | ✅ CRUD | ✅ CRUD | ✅ R+U | 🔍 R | ✅ R |
| Receipt | ✅ CRUD | ✅ CRUD | ✅ CRUD | ✅ R+U | 🔍 R | ✅ R |
| Tariff | ✅ CRUD | ✅ CRUD | ✅ CRU | ✅ CRU | ❌ | ✅ R |
| Reports | ✅ CRUD | ✅ CRU | ✅ CR | ✅ CR | ❌ | ✅ R |
| Activity Log | ✅ R | ✅ R | ❌ | ✅ R | ❌ | ✅ R |

**Legend:**
- ✅ CRUD = Full Create, Read, Update, Delete
- ✅ CRU = Create, Read, Update (no delete)
- ✅ CR = Create, Read (no update/delete)
- ✅ R+U = Read, Update (no create/delete)
- ✅ R = Read-only
- 🔍 R = Limited read (scoped)
- ❌ = No access

---

### 💡 Key Characteristics

1. **Executive Level**
   - Full visibility across all operations
   - Approval authority for major decisions
   - Strategic planning capability

2. **No Delete Powers**
   - Protects data integrity
   - Prevents accidental data loss
   - Maintains audit trail

3. **Approval Authority**
   - Can approve invoices
   - Can approve student enrollments
   - Can verify receipts

4. **Policy Setting**
   - Can create & modify tariffs
   - Can manage payment channels
   - Can set academic year schedule

5. **Oversight**
   - Full access to activity logs
   - Monitor all system activities
   - Ensure compliance

---

### 📊 Summary

Kepala Sekolah Role memiliki **24 permissions** dengan fokus:
- ✅ Full view access ke semua data
- ✅ Update untuk approval decisions (invoice, student, class)
- ✅ Create untuk policy setting (tariff, virtual account)
- ✅ Academic & financial oversight
- ✅ Activity log monitoring untuk compliance
- ❌ Tidak bisa delete apapun
- ❌ Tidak bisa manage users/roles/permissions

