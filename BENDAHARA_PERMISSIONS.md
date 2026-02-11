## Bendahara Permissions & Authorization

Dokumentasi lengkap permission system untuk role **Bendahara** (Treasurer) dalam aplikasi PAUD UNDIP.

### 📋 Permissions untuk Bendahara (Financial Focus)

Role **bendahara** fokus pada financial management sesuai SOP:

#### **Financial Operations (Operasi Keuangan)**

**Invoice (Primary Responsibility)**
- `view_invoice` - Lihat invoice
- `view_any_invoice` - Lihat daftar invoice
- `create_invoice` - Buat invoice BARU
- `update_invoice` - Edit invoice (status, amount, etc)
- `delete_invoice` - Hapus invoice (hanya draft)

**Receipt (Kuitansi Pembayaran)**
- `view_receipt` - Lihat kwitansi
- `view_any_receipt` - Lihat daftar kwitansi
- `create_receipt` - Buat kwitansi pembayaran
- `update_receipt` - Edit kwitansi
- `delete_receipt` - Hapus kwitansi (restricted)

**Tariff Management (Tarif Pembayaran)**
- `view_tariff` - Lihat tarif
- `view_any_tariff` - Lihat daftar tarif
- `create_tariff` - Buat tarif baru
- `update_tariff` - Edit tarif pembayaran

**Virtual Account Management**
- `view_virtual_account` - Lihat rekening virtual
- `view_any_virtual_account` - Lihat daftar rekening
- `create_virtual_account` - Buat rekening virtual
- `update_virtual_account` - Edit rekening virtual

#### **Data Access for Billing Context (Read-Only)**

**Student Data**
- `view_student` - Lihat data siswa (untuk billing)
- `view_any_student` - Lihat daftar siswa

**Class Data**
- `view_school_class` - Lihat kelas (untuk organizing billing)
- `view_any_school_class` - Lihat daftar kelas

**Student Class History**
- `view_student_class_history` - Lihat riwayat kelas (untuk reference)
- `view_any_student_class_history` - Lihat daftar riwayat

**Academic Year**
- `view_academic_year` - Lihat tahun ajaran
- `view_any_academic_year` - Lihat daftar tahun ajaran

**School**
- `view_school` - Lihat data sekolah
- `view_any_school` - Lihat daftar sekolah

#### **Reporting**

**Financial Report**
- `view_financial::report` - Lihat laporan keuangan
- `view_any_financial::report` - Lihat daftar laporan
- `create_financial::report` - Generate laporan keuangan

---

### 🔐 Bendahara Authorization Pattern

```php
// For Invoice (Primary responsibility)
public function viewAny(User $user): bool
{
    return $user->is_admin || $user->hasRole('bendahara');
}

public function create(User $user): bool
{
    // Bendahara primary creator of invoices
    return $user->hasRole('bendahara') 
        || $user->is_admin 
        || $user->isKepsek();
}

public function update(User $user, Invoice $invoice): bool
{
    return ($user->hasRole('bendahara') || $user->is_admin) 
        && $invoice->status === 'draft';
}

public function delete(User $user, Invoice $invoice): bool
{
    // Only bendahara & admin, and only draft
    return ($user->hasRole('bendahara') || $user->is_admin) 
        && $invoice->status === 'draft';
}

// For Student Data (Read-only context)
public function view(User $user, Student $student): bool
{
    if (!$user->hasPermissionTo('view_student')) {
        return false;
    }
    
    // Bendahara dapat view semua siswa untuk billing
    if ($user->hasRole('bendahara')) {
        return true;
    }
    
    return false;
}
```

---

### 📊 Bendahara Responsibility Matrix

| Feature | Access | Purpose |
|---------|--------|---------|
| **Invoice** | Full CRUD | Primary financial document |
| **Receipt** | Full CRUD | Payment confirmation |
| **Tariff** | CRUD (no delete) | Payment rate management |
| **Virtual Account** | CRUD (no delete) | Payment channel configuration |
| **Student** | Read | View for billing reference |
| **Class** | Read | Organize billing by class |
| **Financial Report** | Create & Read | Generate financial reports |
| **Other Data** | Read-only | Context for financial operations |

---

### 🚧 Restrictions & Limitations

1. **Cannot Delete Critical Data**
   - Tidak bisa delete invoice/receipt terisi
   - Hanya bisa delete draft invoice

2. **No User Management**
   - Tidak bisa create/manage users
   - Tidak bisa manage roles/permissions

3. **No Academic Management**
   - Tidak bisa create/modify academic year
   - Tidak bisa edit student enrollment

4. **Student Data Read-Only**
   - Lihat untuk billing reference only
   - Tidak bisa modify student data

5. **No System Settings**
   - Tidak bisa change school settings
   - Tidak bisa modify core system data

---

### 📊 Summary

Bendahara Role memiliki **16 permissions** dengan fokus:
- ✅ Full CRUD untuk Invoice & Receipt
- ✅ Create & Update Tariff & Virtual Account
- ✅ Read-Only untuk Student, Class, Academic data
- ✅ Generate Financial Reports
- ❌ Tidak bisa delete critical financial records
- ❌ Tidak bisa manage users atau system settings
- ❌ Data access read-only untuk non-financial entities

---

### 🎯 Primary SOP Functions

Bendahara roles and responsibilities:
1. **Invoice Management**
   - Create new invoices for students
   - Update invoice status (draft → issued → paid)
   - Track invoice payments

2. **Receipt Management**
   - Create payment receipts
   - Record payment details
   - Archive receipts

3. **Tariff Management**
   - Set or update payment rates
   - Manage different tariff types
   - Configure payment amounts

4. **Payment Processing**
   - Monitor student payments
   - Track unpaid invoices
   - Generate payment reminders

5. **Financial Reporting**
   - Generate income reports
   - Create financial statements
   - Track payment collections

6. **Data Verification**
   - Verify student enrollment for billing
   - Check class assignments
   - Cross-reference payment history

