## Role & Permission Architecture Overview

Dokumentasi lengkap arsitektur Role & Permission system untuk aplikasi PAUD UNDIP.

### 🏗️ Architecture Overview

Sistem menggunakan **Spatie Laravel Permission** dengan extension custom untuk role-based authorization:

```
┌─────────────────────────────────────┐
│   User Authentication & Authorization │
└─────────────────┬───────────────────┘
                  │
        ┌─────────┴─────────┐
        │                   │
    ┌───▼───────┐  ┌──────▼─────┐
    │   Roles   │  │ Permissions │
    └───────────┘  └─────────────┘
        │                   │
        └─────────┬─────────┘
                  │
           ┌──────▼──────┐
           │  Policies   │
           │ (CRUD Rules)│
           └─────────────┘
                  │
           ┌──────▼──────┐
           │ Resources   │
           │  (Filament) │
           └─────────────┘
```

---

### 👥 Roles Hierarchy

#### **1. Super Admin**
- **Purpose**: Full system control
- **Created By**: SuperadminSeeder
- **Permissions**: All (super_admin role)
- **Features**: Manage users, roles, permissions, all data
- **Access**: Unrestricted

#### **2. Admin**
- **Purpose**: Daily system administration
- **Created By**: UserSeeder
- **Permissions**: CRUD for academic, financial, student data
- **Features**: Manage classes, students, invoices, receipts, users
- **Access**: Full CRUD on most resources

#### **3. Guru (Teacher)**
- **Purpose**: Classroom management & monitoring
- **Created By**: TeacherSeeder (automatically per class)
- **Permissions**: 8 read-only view permissions
- **Features**: View own class, siswa, payment history
- **Scope**: Limited to assigned class
- **Access**: Read-only

#### **4. Bendahara (Treasurer)**
- **Purpose**: Financial management
- **Created By**: UserSeeder/Manual
- **Permissions**: CRUD for invoices, receipts, payments, tariffs
- **Features**: Create/manage invoices, process payments, generate reports
- **Access**: Full CRUD on financial resources

#### **5. Kepala Sekolah (Principal/Headmaster)**
- **Purpose**: School management & approvals
- **Created By**: UserSeeder/Manual
- **Permissions**: Full view + approval for invoices, tariffs
- **Features**: View all data, approve financial transactions
- **Access**: View all + selective update/create

#### **6. Auditor**
- **Purpose**: Compliance monitoring & verification
- **Created By**: Manual or AuditorRoleSeeder
- **Permissions**: 20 read-only view permissions
- **Features**: Monitor all activities, verify transactions, generate reports
- **Scope**: Unrestricted view access
- **Access**: Read-only to all features

#### **7. Operator (Optional)**
- **Purpose**: Data entry & basic operations
- **Created By**: Manual
- **Permissions**: Limited CRUD
- **Features**: Data entry support
- **Access**: Limited CRUD

---

### 🔐 Permission Structure

#### **Academic Year**
- `view_academic_year` - View single academic year
- `view_any_academic_year` - View list

#### **Classes**
- `view_school_class` - View single class
- `view_any_school_class` - View list

#### **Students**
- `view_student` - View single student
- `view_any_student` - View list

#### **Student Class History**
- `view_student_class_history` - View single history record
- `view_any_student_class_history` - View list

#### **Invoices**
- `view_invoice` - View single invoice
- `view_any_invoice` - View list

#### **Receipts**
- `view_receipt` - View single receipt
- `view_any_receipt` - View list

#### **Tariffs**
- `view_tariff` - View single tariff
- `view_any_tariff` - View list

#### **Virtual Accounts**
- `view_virtual_account` - View single account
- `view_any_virtual_account` - View list

#### **Financial Reports**
- `view_financial::report` - View single report
- `view_any_financial::report` - View list

#### **Schools**
- `view_school` - View single school
- `view_any_school` - View list

#### **Audit & System**
- `access_log_viewer` - Access activity log viewer

---

### 📋 Seeders Execution Order

```
1. RolesAndPermissionsSeeder
   ├─ Create base roles (super_admin, admin, guru, bendahara, kepala_sekolah, auditor, operator)
   └─ Create access_log_viewer permission

2. GuruPermissionsSeeder
   ├─ Create 10 view permissions for guru
   └─ Assign to guru role (read-only, scoped by class)

3. AdminPermissionsSeeder
   ├─ Create 28 CRUD permissions for admin
   └─ Assign to admin role (full admin access)

4. BendaharaPermissionsSeeder
   ├─ Create 16 permissions for bendahara (financial focus)
   └─ Assign to bendahara role (invoice/receipt CRUD)

5. KepsekPermissionsSeeder
   ├─ Create 24 permissions for kepsek (oversight + approval)
   └─ Assign to kepsek role (full view + selective update)

6. AuditorPermissionsSeeder
   ├─ Create 21 read-only permissions for auditor
   └─ Assign to auditor role (view all, no CRUD)

4. SuperadminSeeder
   └─ Create super_admin user

5. SchoolSeeder
   └─ Create school data

6. AcademicYearSeeder
   └─ Create academic year data

7. ClassSeeder
   └─ Create classes for academic year

8. StudentSeeder
   └─ Create student data

9. StudentClassHistorySeeder
   └─ Assign students to classes

10. UserSeeder
    ├─ Create admin, guru, bendahara, kepsek basic users
    └─ Assign roles

11. TeacherSeeder
    ├─ Create teacher user per class
    ├─ Assign guru role
    ├─ Assign role scope per class
    └─ Assign class to teacher

12. IncomeTypeSeeder
    └─ Create income types

13. TariffSeeder
    └─ Create tariff data

14. VirtualAccountSeeder
    └─ Create virtual accounts

15. AuditorRoleSeeder
    └─ Create additional auditor if needed

16. VerifyAllUsersSeeder
    └─ Set email_verified_at for all users
```

---

### 🛡️ Authorization Flow

#### **User Access Check:**

```
1. User Login
   └─ Create authenticated session

2. Access Resource (e.g., Student List)
   ├─ Check route authorization
   ├─ Call Resource.canViewAny()
   │  └─ Check Policy.viewAny(user)
   │     ├─ Check permission
   │     ├─ Check role
   │     └─ Return true/false
   │
   ├─ If false → Forbidden error
   └─ If true → Continue

3. Get Eloquent Query
   └─ Resource.getEloquentQuery()
      ├─ Apply scope filters (guru: own class, etc)
      ├─ Build query
      └─ Return query

4. Render List/Detail
   ├─ Display data
   └─ For each record:
      └─ Check individual Policy.view(user, record)
         ├─ Validate access to this specific record
         └─ Show/hide actions based on Policy methods
```

---

### 📊 RBAC Matrix

| Action | SuperAdmin | Admin | Guru | Bendahara | Kepsek | Auditor | Operator |
|--------|-----------|-------|------|-----------|--------|---------|----------|
| **Academic Year** |
| View | ✅ | ✅ | ❌ | ✅ | ✅ | ✅ | ✅ |
| Create | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Update | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Delete | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Classes** |
| View | ✅ | ✅ | 🔍 | ✅ | ✅ | ✅ | ✅ |
| Create | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Update | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Delete | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Students** |
| View | ✅ | ✅ | 🔍 | ✅ | ✅ | ✅ | ✅ |
| Create | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ |
| Update | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ |
| Delete | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Invoices** |
| View | ✅ | ✅ | 🔍 | ✅ | ✅ | ✅ | ✅ |
| Create | ✅ | ❌ | ❌ | ✅ | ✅ | ❌ | ❌ |
| Update | ✅ | ❌ | ❌ | ✅ | ✅ | ❌ | ❌ |
| Delete | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ |
| **Receipts** |
| View | ✅ | ✅ | 🔍 | ✅ | ✅ | ✅ | ✅ |
| Create | ✅ | ❌ | ❌ | ✅ | ✅ | ❌ | ❌ |
| Update | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ |
| Delete | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Financial Report** |
| View | ✅ | ✅ | ❌ | ✅ | ✅ | ✅ | ❌ |
| Create | ✅ | ✅ | ❌ | ✅ | ✅ | ❌ | ❌ |
| **Activity Log** |
| View | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ | ❌ |

**Legend:**
- ✅ = Full/allowed access
- 🔍 = Scoped access (limited to own context)
- ❌ = No access

---

### 🔒 Security Principles

#### **1. Principle of Least Privilege**
- Setiap role hanya dapat akses yang dibutuhkan
- Default: deny access, whitelist what's allowed
- No excessive permissions

#### **2. Role-Based Access Control (RBAC)**
- Permissions assigned to roles
- Users get permissions through roles
- Easy to manage permissions collectively

#### **3. Scope-Based Access**
- Teachers limited to own class scope
- Students limited to their teacher's class
- Prevents cross-boundary data access

#### **4. Explicit Authorization**
- Permission checks di setiap method
- No implicit allow
- Policies validate at multiple levels

#### **5. Audit Trail**
- Activity logging untuk semua changes
- Auditor dapat monitor sistem
- Compliance dengan requirements

---

### 🛠️ Policy Implementation Pattern

Setiap policy mengikuti pattern yang sama:

```php
class ResourcePolicy
{
    // Check permission + role/scope
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_any_resource');
    }

    // Check permission + specific record access
    public function view(User $user, Resource $resource): bool
    {
        // Step 1: Check permission
        if (!$user->hasPermissionTo('view_resource')) {
            return false;
        }

        // Step 2: Super roles always allow
        if ($user->isSuperAdmin() || $user->hasRole('admin')) {
            return true;
        }

        // Step 3: Role-specific logic
        if ($user->isGuru()) {
            // Check if owned by this teacher's class
        }

        // Step 4: Return result
        return false;
    }

    // Only specific roles can create
    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasRole('admin');
    }

    // Similar for update, delete, restore, forceDelete
}
```

---

### 📝 Files Overview

#### **Seeders**
- `RolesAndPermissionsSeeder.php` - Base roles & permissions
- `GuruPermissionsSeeder.php` - Guru-specific permissions
- `AuditorPermissionsSeeder.php` - Auditor-specific permissions
- `UserSeeder.php` - Basic users (admin, bendahara, kepsek)
- `SuperadminSeeder.php` - Super admin user
- `TeacherSeeder.php` - Teacher users per class
- `AuditorRoleSeeder.php` - Additional auditor setup
- `VerifyAllUsersSeeder.php` - Verify all emails
- `DatabaseSeeder.php` - Master seeder

#### **Policies**
- `StudentPolicy.php` - Student authorization
- `SchoolClassPolicy.php` - Class authorization
- `AcademicYearPolicy.php` - Academic year authorization
- `StudentClassHistoryPolicy.php` - History authorization
- `InvoicePolicy.php` - Invoice authorization
- `ReceiptPolicy.php` - Receipt authorization
- `TariffPolicy.php` - Tariff authorization
- `VirtualAccountPolicy.php` - Virtual account authorization
- `SchoolPolicy.php` - School authorization

#### **Service Providers**
- `AuthServiceProvider.php` - Register all policies

#### **Documentation**
- `GURU_PERMISSIONS.md` - Guru permission details
- `AUDITOR_PERMISSIONS.md` - Auditor permission details
- `ROLE_PERMISSION_ARCHITECTURE.md` - This file

---

### 🚀 Getting Started

#### **1. Run All Seeders**
```bash
php artisan db:seed
```

#### **2. Verify Roles**
```bash
php artisan tinker
> App\Models\User::with('roles')->get();
```

#### **3. Check Permissions**
```bash
php artisan tinker
> Spatie\Permission\Models\Role::with('permissions')->find(1);
```

#### **4. Test Authorization**
```bash
php artisan tinker
> $user = App\Models\User::find('user-id');
> $user->hasPermissionTo('view_student');
> Auth::loginAs($user);
```

---

### 🔄 Common Tasks

#### **Add New Permission**
```php
// In a seeder or migration
Permission::create([
    'name' => 'view_new_feature',
    'guard_name' => 'web',
]);

$role = Role::find('guru');
$role->givePermissionTo('view_new_feature');
```

#### **Add New Role**
```php
$role = Role::create([
    'name' => 'new_role',
    'guard_name' => 'web',
]);

$role->givePermissionTo(['permission1', 'permission2']);
```

#### **Assign Role to User**
```php
$user = User::find('user-id');
$user->assignRole('guru');
// or
$user->syncRoles(['guru', 'another_role']);
```

#### **Check Permission in Code**
```php
// In blade
@can('view_student')
    <button>View Student</button>
@endcan

// In controller/service
if ($user->hasPermissionTo('view_student')) {
    // Do something
}

// In Resource via Policy
public function canViewAny(): bool
{
    return auth()->user()->hasPermissionTo('view_any_student');
}
```

---

## 📋 SOP Reference - Role Permissions Overview

### Quick SOP Summary Tabel

| Role | Key Permissions | Primary Focus | Restrictions |
|------|-----------------|----------------|--------------|
| **SuperAdmin** | All | Full system control | None |
| **Admin** | 28 (Full CRUD except delete critical) | System administration | No delete critical data |
| **Guru** | 10 (Read-only) | Classroom management | Scoped by class, read-only |
| **Bendahara** | 16 (Invoice/Receipt CRUD) | Financial management | Delete restricted, no user mgmt |
| **Kepsek** | 24 (Read + Selective Update) | School oversight | No delete, approval authority |
| **Auditor** | 21 (Read-only) | Compliance monitoring | Read-only, full visibility |

### Detailed Role SOP Breakdown

#### **🔑 Admin Role SOP**
- **File**: `AdminPermissionsSeeder.php` | **Doc**: `ADMIN_PERMISSIONS.md`
- **Key Powers**:
  - Full CRUD untuk Classes, Students, Invoices, Receipts
  - Create & Update Academic Year (tidak bisa delete)
  - User management (create & manage non-admin users)
  - Activity log access
- **When to Use**: Daily system administration, general management
- **Restrictions**: Cannot delete critical data, cannot manage other admins

#### **👨‍🏫 Guru Role SOP**
- **File**: `GuruPermissionsSeeder.php` | **Doc**: `GURU_PERMISSIONS.md`
- **Key Powers**:
  - View own class & students (via scope)
  - View student payment history
  - View student class history
- **When to Use**: Classroom monitoring, student management within own class
- **Restrictions**: Read-only, scoped by assigned class only

#### **💰 Bendahara Role SOP**
- **File**: `BendaharaPermissionsSeeder.php` | **Doc**: `BENDAHARA_PERMISSIONS.md`
- **Key Powers**:
  - Full CRUD untuk Invoice & Receipt (primary responsibility)
  - Create & Update Tariff & Virtual Account
  - Generate Financial Reports
  - View student/class data for billing context
- **When to Use**: Financial transactions, payment processing, billing management
- **Restrictions**: Cannot delete non-draft invoices, no user management

#### **🎓 Kepsek (Principal) Role SOP**
- **File**: `KepsekPermissionsSeeder.php` | **Doc**: `KEPSEK_PERMISSIONS.md`
- **Key Powers**:
  - Full view (all academic & financial data)
  - Update Academic Year (set active semester)
  - Approve Invoices & Student enrollments
  - Create & Update Tariffs & Virtual Accounts
  - Activity log access (school oversight)
- **When to Use**: Strategic decisions, approvals, school management
- **Restrictions**: No delete permissions, no system/user management

#### **📊 Auditor Role SOP**
- **File**: `AuditorPermissionsSeeder.php` | **Doc**: `AUDITOR_PERMISSIONS.md`
- **Key Powers**:
  - Full read access (all features)
  - Activity log viewer (audit trail)
  - Financial report generation
  - Student & payment data access
- **When to Use**: Compliance monitoring, verification, audit trail review
- **Restrictions**: Read-only (no create, update, delete)

---

### 📄 Documentation Files by Role

| Role | Documentation File | Permissions Count | Focus Area |
|------|-------------------|------------------|-----------|
| Admin | `ADMIN_PERMISSIONS.md` | 28 | System administration |
| Guru | `GURU_PERMISSIONS.md` | 10 | Classroom management |
| Bendahara | `BENDAHARA_PERMISSIONS.md` | 16 | Financial management |
| Kepsek | `KEPSEK_PERMISSIONS.md` | 24 | School oversight |
| Auditor | `AUDITOR_PERMISSIONS.md` | 21 | Compliance monitoring |
| **Architecture** | `ROLE_PERMISSION_ARCHITECTURE.md` | - | Complete system overview |

---

### ✅ Implementation Checklist

- [x] RolesAndPermissionsSeeder - Base roles created
- [x] GuruPermissionsSeeder - 10 read-only permissions
- [x] AdminPermissionsSeeder - 28 admin permissions
- [x] BendaharaPermissionsSeeder - 16 financial permissions
- [x] KepsekPermissionsSeeder - 24 oversight permissions
- [x] AuditorPermissionsSeeder - 21 audit permissions
- [x] StudentPolicy - Student authorization
- [x] SchoolClassPolicy - Class authorization
- [x] StudentClassHistoryPolicy - History authorization
- [x] InvoicePolicy - Invoice authorization
- [x] ReceiptPolicy - Receipt authorization
- [x] TariffPolicy - Tariff authorization
- [x] VirtualAccountPolicy - Virtual account authorization
- [x] AcademicYearPolicy - Academic year authorization
- [x] SchoolPolicy - School authorization
- [x] AuthServiceProvider - All policies registered
- [x] DatabaseSeeder - All seeders in execution order
- [x] Documentation - Complete SOP documentation

---

### 📚 References

- **Spatie Laravel Permission**: https://spatie.be/docs/laravel-permission/
- **Laravel Authorization**: https://laravel.com/docs/authorization
- **Filament Authorization**: https://filamentphp.com/docs/authorization
- **RBAC Best Practices**: https://cheatsheetseries.owasp.org/cheatsheets/Authorization_Cheat_Sheet.html

---

## 🚀 Ready to Deploy

Sistem permission lengkap sesuai SOP sudah siap digunakan:

```bash
# Jalankan semua seeders
php artisan db:seed

# atau jalankan individual seeders
php artisan db:seed --class=AdminPermissionsSeeder
php artisan db:seed --class=BendaharaPermissionsSeeder
php artisan db:seed --class=KepsekPermissionsSeeder
```

Setiap role sudah memiliki permission yang sesuai dengan SOP dan tanggung jawab mereka! 🎯

```

---

### 📚 References

- **Spatie Laravel Permission**: https://spatie.be/docs/laravel-permission/
- **Laravel Authorization**: https://laravel.com/docs/authorization
- **Filament Authorization**: https://filamentphp.com/docs/authorization
- **RBAC Best Practices**: https://cheatsheetseries.owasp.org/cheatsheets/Authorization_Cheat_Sheet.html

