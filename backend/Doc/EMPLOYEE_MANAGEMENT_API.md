# Employee Management API - Complete Documentation

## Overview

Comprehensive Employee Management system for ERP with full support for:
- Employee CRUD operations
- Role & Department management
- Hierarchical management (Manager-Subordinate relationships)
- Session tracking & management
- Multi-Factor Authentication (MFA)
- Salary management with history
- Activity logging
- Bulk operations

## Database Tables

### Core Tables:
1. **employees** - Main employee records
2. **employee_sessions** - Session tracking
3. **employee_m_f_a_s** - MFA settings (TOTP, SMS, Email)
4. **employee_m_f_a_backup_codes** - Backup codes for MFA
5. **email_verification_tokens** - Email verification
6. **password_reset_tokens** - Password reset tracking

## API Endpoints

### 1. Employee CRUD Operations

#### Get All Employees
**GET** `/api/employees`

Query Parameters:
- `store_id` - Filter by store
- `role_id` - Filter by role
- `department` - Filter by department
- `is_active` - Filter by active status (true/false)
- `is_in_service` - Filter by service status (true/false)
- `search` - Search by name, email, code, or phone
- `sort_by` - Field to sort by (name, email, hire_date, salary, etc.)
- `sort_direction` - asc or desc
- `per_page` - Results per page (default: 15)

**Response:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "employee_code": "EMP-20251104-ABC123",
        "phone": "+1234567890",
        "department": "Sales",
        "salary": "5000.00",
        "is_active": true,
        "hire_date": "2025-01-15",
        "store": {...},
        "role": {...},
        "manager": {...}
      }
    ],
    "total": 50
  }
}
```

#### Get Single Employee
**GET** `/api/employees/{id}`

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "store": {...},
    "role": {...},
    "manager": {...},
    "subordinates": [...],
    "sessions": [...]
  }
}
```

#### Create Employee
**POST** `/api/employees`

**Request:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "SecurePass123",
  "store_id": 1,
  "role_id": 2,
  "phone": "+1234567890",
  "address": "123 Main St",
  "department": "Sales",
  "salary": 5000.00,
  "manager_id": 5,
  "hire_date": "2025-11-04"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Employee created successfully",
  "data": {...}
}
```

#### Update Employee
**PUT** `/api/employees/{id}`

**Request:** (all fields optional)
```json
{
  "name": "John Doe Updated",
  "email": "john.new@example.com",
  "store_id": 2,
  "role_id": 3,
  "phone": "+9876543210",
  "department": "Marketing",
  "salary": 6000.00
}
```

#### Delete/Deactivate Employee
**DELETE** `/api/employees/{id}`

Soft deletes by setting `is_active` and `is_in_service` to false.

---

### 2. Employee Management Actions

#### Change Employee Role
**PATCH** `/api/employees/{id}/role`

**Request:**
```json
{
  "role_id": 3
}
```

**Response:**
```json
{
  "success": true,
  "message": "Employee role changed from 'Sales Rep' to 'Manager'",
  "data": {...}
}
```

#### Transfer Employee to Another Store
**PATCH** `/api/employees/{id}/transfer`

**Request:**
```json
{
  "new_store_id": 2
}
```

**Response:**
```json
{
  "success": true,
  "message": "Employee transferred from 'Store A' to 'Store B'",
  "data": {...}
}
```

#### Activate Employee
**PATCH** `/api/employees/{id}/activate`

**Response:**
```json
{
  "success": true,
  "message": "Employee activated successfully",
  "data": {...}
}
```

#### Deactivate Employee
**PATCH** `/api/employees/{id}/deactivate`

**Response:**
```json
{
  "success": true,
  "message": "Employee deactivated successfully"
}
```

#### Change Password
**PATCH** `/api/employees/{id}/password`

**Request:**
```json
{
  "current_password": "OldPassword123",
  "new_password": "NewPassword123",
  "new_password_confirmation": "NewPassword123"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Password changed successfully"
}
```

#### Update Salary
**PATCH** `/api/employees/{id}/salary`

**Request:**
```json
{
  "salary": 7000.00,
  "effective_date": "2025-12-01",
  "reason": "Annual increment"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Salary updated successfully",
  "data": {
    "old_salary": "5000.00",
    "new_salary": "7000.00",
    "employee": {...}
  }
}
```

---

### 3. Manager & Hierarchy Management

#### Get Subordinates
**GET** `/api/employees/{id}/subordinates`

Returns direct reports of an employee.

**Response:**
```json
{
  "success": true,
  "data": [...],
  "manager": {
    "id": 5,
    "name": "Manager Name",
    "employee_code": "EMP-123"
  }
}
```

#### Get Employee Hierarchy
**GET** `/api/employees/{id}/hierarchy`

Returns complete organizational hierarchy for an employee.

**Response:**
```json
{
  "success": true,
  "data": {
    "employee": {...},
    "chain_of_command": [
      {"id": 10, "name": "CEO", "department": "Executive"},
      {"id": 5, "name": "Director", "department": "Sales"}
    ],
    "direct_reports": [...],
    "all_subordinates": [...]
  }
}
```

#### Assign Manager
**POST** `/api/employees/{id}/assign-manager`

**Request:**
```json
{
  "manager_id": 5
}
```

**Response:**
```json
{
  "success": true,
  "message": "Manager assigned successfully",
  "data": {...}
}
```

#### Remove Manager
**DELETE** `/api/employees/{id}/remove-manager`

**Response:**
```json
{
  "success": true,
  "message": "Manager removed successfully",
  "data": {...}
}
```

#### Get Employees by Manager
**GET** `/api/employees/by-manager/{managerId}`

**Response:**
```json
{
  "success": true,
  "data": [...],
  "manager": {...}
}
```

#### Get Employees by Department
**GET** `/api/employees/by-department/{department}`

**Response:**
```json
{
  "success": true,
  "data": [...],
  "department": "Sales",
  "stats": {
    "total": 25,
    "active": 23,
    "in_service": 22
  }
}
```

---

### 4. Session Management

#### Get Employee Sessions
**GET** `/api/employees/{id}/sessions`

**Response:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "token": "...",
        "ip_address": "192.168.1.1",
        "user_agent": "Mozilla/5.0...",
        "last_activity_at": "2025-11-04 10:30:00",
        "expires_at": "2025-11-05 10:30:00",
        "revoked_at": null
      }
    ]
  },
  "active_sessions_count": 3
}
```

#### Revoke Session
**DELETE** `/api/employees/{id}/sessions/{sessionId}`

**Response:**
```json
{
  "success": true,
  "message": "Session revoked successfully"
}
```

#### Revoke All Sessions
**DELETE** `/api/employees/{id}/sessions/revoke-all`

**Response:**
```json
{
  "success": true,
  "message": "Revoked 5 active sessions"
}
```

---

### 5. Multi-Factor Authentication (MFA)

#### Get MFA Settings
**GET** `/api/employees/{id}/mfa`

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "type": "totp",
      "is_enabled": true,
      "verified_at": "2025-11-01 10:00:00",
      "last_used_at": "2025-11-04 09:30:00",
      "backup_codes": [...]
    }
  ],
  "has_mfa_enabled": true
}
```

#### Enable MFA
**POST** `/api/employees/{id}/mfa/enable`

**Request:**
```json
{
  "type": "totp",
  "secret": "BASE32ENCODEDSECRET",
  "generate_backup_codes": true,
  "settings": {
    "phone": "+1234567890"
  }
}
```

Supported types: `totp`, `sms`, `email`, `backup_codes`

**Response:**
```json
{
  "success": true,
  "message": "MFA enabled successfully",
  "data": {
    "id": 1,
    "type": "totp",
    "is_enabled": true,
    "backup_codes": [
      {"code": "ABC12345", "expires_at": "2026-05-04"},
      ...
    ]
  }
}
```

#### Disable MFA
**DELETE** `/api/employees/{id}/mfa/{mfaId}/disable`

**Response:**
```json
{
  "success": true,
  "message": "MFA disabled successfully"
}
```

#### Regenerate Backup Codes
**POST** `/api/employees/{id}/mfa/{mfaId}/backup-codes/regenerate`

**Response:**
```json
{
  "success": true,
  "message": "Backup codes regenerated successfully",
  "data": [
    {"code": "XYZ98765", "expires_at": "2026-05-04"},
    ...
  ]
}
```

---

### 6. Filtering & Searching

#### Get Employees by Store
**GET** `/api/employees/by-store/{storeId}`

**Response:**
```json
{
  "success": true,
  "data": [...],
  "store": {...}
}
```

#### Get Employees by Role
**GET** `/api/employees/by-role/{roleId}`

**Response:**
```json
{
  "success": true,
  "data": [...],
  "role": {...}
}
```

#### Get Employee Stats
**GET** `/api/employees/stats`

**Response:**
```json
{
  "success": true,
  "data": {
    "total_employees": 150,
    "active_employees": 142,
    "inactive_employees": 8,
    "in_service": 140,
    "by_department": [
      {"department": "Sales", "count": 45},
      {"department": "Marketing", "count": 30}
    ],
    "by_role": [
      {"role": "Manager", "count": 15},
      {"role": "Sales Rep", "count": 60}
    ],
    "recent_hires": [...]
  }
}
```

---

### 7. Activity Tracking

#### Get Activity Log
**GET** `/api/employees/{id}/activity-log`

**Response:**
```json
{
  "success": true,
  "data": {
    "last_login": "2025-11-04 09:00:00",
    "recent_sessions": [
      {
        "id": 1,
        "ip_address": "192.168.1.1",
        "last_activity_at": "2025-11-04 10:30:00"
      }
    ],
    "password_resets": [
      {
        "created_at": "2025-10-15 14:20:00",
        "used_at": "2025-10-15 14:25:00"
      }
    ],
    "mfa_usage": [
      {
        "type": "totp",
        "last_used_at": "2025-11-04 09:00:00",
        "verified_at": "2025-11-01 10:00:00"
      }
    ]
  }
}
```

---

### 8. Bulk Operations

#### Bulk Update Status
**PATCH** `/api/employees/bulk/status`

**Request:**
```json
{
  "employee_ids": [1, 2, 3, 4, 5],
  "is_active": false,
  "is_in_service": false
}
```

**Response:**
```json
{
  "success": true,
  "message": "Updated 5 employees successfully"
}
```

---

## Employee Model Features

### Automatic Features:
- **Employee Code Generation**: Auto-generates unique codes (EMP-YYYYMMDD-XXXXXX)
- **Password Hashing**: Automatically bcrypts passwords
- **JWT Support**: Implements JWTSubject for authentication
- **Soft Deletes**: Via is_active flag
- **Last Login Tracking**: Updates on each login

### Relationships:
- `store` - Belongs to Store
- `role` - Belongs to Role
- `manager` - Belongs to Employee (self-referencing)
- `subordinates` - Has many Employees
- `sessions` - Has many EmployeeSession
- `mfa` - Has many EmployeeMFA
- `mfaBackupCodes` - Has many through EmployeeMFABackupCode
- `emailVerificationTokens` - Has many
- `passwordResetTokens` - Has many

### Scopes:
- `active()` - Only active employees
- `inService()` - Only in-service employees
- `byStore($storeId)` - Filter by store
- `byRole($roleId)` - Filter by role

### Methods:
- `hasPermission($permission)` - Check if has permission
- `updateLastLogin()` - Update last login timestamp
- `generateEmployeeCode()` - Static method to generate codes

---

## Security Features

✅ **Password Protection**: Bcrypt hashing
✅ **Session Tracking**: IP, user agent, device info
✅ **MFA Support**: TOTP, SMS, Email, Backup codes
✅ **Session Revocation**: Individual or bulk
✅ **Circular Hierarchy Prevention**: Cannot be own manager
✅ **Self-Action Prevention**: Cannot delete/deactivate self
✅ **Audit Trail**: Activity logs, salary history

---

## Use Cases

### 1. Onboard New Employee
```bash
POST /api/employees
{
  "name": "Jane Smith",
  "email": "jane@company.com",
  "password": "SecurePass123",
  "store_id": 1,
  "role_id": 2,
  "department": "Sales",
  "salary": 5000,
  "manager_id": 10,
  "hire_date": "2025-11-05"
}
```

### 2. Promote Employee
```bash
# Change role
PATCH /api/employees/5/role
{ "role_id": 3 }

# Update salary
PATCH /api/employees/5/salary
{
  "salary": 7000,
  "effective_date": "2025-12-01",
  "reason": "Promotion to Senior Sales Rep"
}
```

### 3. Transfer Employee
```bash
PATCH /api/employees/5/transfer
{ "new_store_id": 2 }
```

### 4. Setup MFA for Employee
```bash
# Enable TOTP
POST /api/employees/5/mfa/enable
{
  "type": "totp",
  "secret": "BASE32SECRET",
  "generate_backup_codes": true
}
```

### 5. View Organization Hierarchy
```bash
GET /api/employees/1/hierarchy
```

### 6. Manage Sessions
```bash
# View all sessions
GET /api/employees/5/sessions

# Revoke specific session
DELETE /api/employees/5/sessions/123

# Revoke all sessions (force re-login)
DELETE /api/employees/5/sessions/revoke-all
```

### 7. Deactivate Employee (Offboarding)
```bash
# Remove manager assignment
DELETE /api/employees/5/remove-manager

# Revoke all sessions
DELETE /api/employees/5/sessions/revoke-all

# Deactivate
PATCH /api/employees/5/deactivate
```

---

## Complete Feature List

### ✅ Implemented:
1. Employee CRUD (Create, Read, Update, Delete/Deactivate)
2. Role management (Change role)
3. Store transfer
4. Department filtering
5. Manager-subordinate hierarchy
6. Salary management with history
7. Password change with verification
8. Session tracking & management
9. MFA setup (TOTP, SMS, Email)
10. MFA backup codes generation
11. Activity logging
12. Bulk status updates
13. Search & filtering
14. Sorting & pagination
15. Organization hierarchy view
16. Chain of command tracking
17. Stats & analytics
18. Recent hires tracking
19. Prevent circular hierarchy
20. Self-action prevention

### Database Support:
✅ employees table
✅ employee_sessions table
✅ employee_m_f_a_s table
✅ employee_m_f_a_backup_codes table
✅ email_verification_tokens table
✅ password_reset_tokens table

---

## 🎉 Complete Employee Management System Ready!

All endpoints are implemented and ready for use in your ERP system.
