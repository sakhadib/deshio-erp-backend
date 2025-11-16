# 📊 Comprehensive Accounting API Documentation

## Table of Contents
1. [Overview](#overview)
2. [Chart of Accounts API](#chart-of-accounts-api)
3. [Transaction Management API](#transaction-management-api)
4. [Financial Reports API](#financial-reports-api)
5. [Account Types & Structure](#account-types--structure)
6. [Database Schema](#database-schema)
7. [Usage Examples](#usage-examples)
8. [Best Practices](#best-practices)

---

## Overview

This ERP system implements **double-entry bookkeeping** with a comprehensive Chart of Accounts (COA) structure. The accounting module automatically tracks all financial transactions through observers, ensuring accurate real-time financial reporting.

### Key Features
- ✅ **Double-Entry Bookkeeping**: Every transaction affects at least two accounts
- ✅ **Hierarchical Chart of Accounts**: Parent-child account relationships
- ✅ **Auto-Transaction Creation**: Observers automatically create transactions
- ✅ **Multi-Store Support**: Track financials per store or globally
- ✅ **Trial Balance**: Real-time verification that debits equal credits
- ✅ **Account Ledgers**: Detailed transaction history per account
- ✅ **Financial Statements**: Balance Sheet, P&L, Cash Flow (coming soon)

### Accounting Equation
```
Assets = Liabilities + Equity
```

### Transaction Types
- **Debit**: Money coming IN (increases Assets/Expenses, decreases Liabilities/Equity/Income)
- **Credit**: Money going OUT (increases Liabilities/Equity/Income, decreases Assets/Expenses)

---

## Chart of Accounts API

### 1. List All Accounts
**GET** `/api/accounts`

Get all accounts with optional filtering and sorting.

**Query Parameters:**
- `type` (optional): Filter by type (asset, liability, equity, income, expense)
- `sub_type` (optional): Filter by sub-type
- `active` (optional): Filter by active status (true/false)
- `level` (optional): Filter by hierarchy level (1, 2, 3...)
- `search` (optional): Search by name or account code
- `sort_by` (optional, default: `account_code`): Sort field
- `sort_order` (optional, default: `asc`): Sort direction (asc/desc)
- `per_page` (optional): Pagination (omit for all records)

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "account_code": "1000",
      "name": "Current Assets",
      "description": "Assets that can be converted to cash within one year",
      "type": "asset",
      "sub_type": "current_asset",
      "parent_id": null,
      "is_active": true,
      "level": 1,
      "path": "1",
      "created_at": "2025-11-16T00:00:00.000000Z",
      "updated_at": "2025-11-16T00:00:00.000000Z",
      "parent": null,
      "children": [
        {
          "id": 2,
          "account_code": "1001",
          "name": "Cash and Cash Equivalents",
          "type": "asset",
          "sub_type": "current_asset",
          "parent_id": 1,
          "level": 2
        }
      ]
    }
  ]
}
```

---

### 2. Create Account
**POST** `/api/accounts`

Create a new account in the chart of accounts.

**Request Body:**
```json
{
  "account_code": "1004",
  "name": "Prepaid Expenses",
  "description": "Expenses paid in advance",
  "type": "asset",
  "sub_type": "current_asset",
  "parent_id": 1,
  "is_active": true
}
```

**Validation Rules:**
- `account_code` (required): Unique code (max 50 chars)
- `name` (required): Account name (max 255 chars)
- `description` (optional): Detailed description
- `type` (required): asset, liability, equity, income, expense
- `sub_type` (required): See [Account Types](#account-types--structure)
- `parent_id` (optional): Parent account ID for hierarchical structure
- `is_active` (optional, default: true): Active status

**Success Response (201):**
```json
{
  "success": true,
  "message": "Account created successfully",
  "data": {
    "id": 18,
    "account_code": "1004",
    "name": "Prepaid Expenses",
    "type": "asset",
    "sub_type": "current_asset",
    "level": 2,
    "path": "1/18",
    "created_at": "2025-11-16T10:30:00.000000Z"
  }
}
```

---

### 3. Get Account Details
**GET** `/api/accounts/{id}`

Get detailed information about a specific account including its balance.

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 2,
    "account_code": "1001",
    "name": "Cash and Cash Equivalents",
    "type": "asset",
    "sub_type": "current_asset",
    "parent_id": 1,
    "level": 2,
    "current_balance": 150000.00,
    "parent": {
      "id": 1,
      "name": "Current Assets"
    },
    "children": [],
    "transactions": [
      {
        "id": 1,
        "transaction_number": "TXN-20251116-001",
        "amount": 50000.00,
        "type": "debit",
        "transaction_date": "2025-11-16"
      }
    ]
  }
}
```

---

### 4. Update Account
**PUT** `/api/accounts/{id}`

Update account details. **Note**: Cannot change type if account has transactions.

**Request Body:**
```json
{
  "name": "Cash on Hand",
  "description": "Physical cash available in stores",
  "is_active": true
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Account updated successfully",
  "data": {
    "id": 2,
    "account_code": "1001",
    "name": "Cash on Hand",
    "updated_at": "2025-11-16T11:00:00.000000Z"
  }
}
```

**Error Response (422) - Has Transactions:**
```json
{
  "success": false,
  "message": "Cannot change account type because it has transactions"
}
```

---

### 5. Delete Account
**DELETE** `/api/accounts/{id}`

Delete an account. **Only allowed if:**
- Account has no sub-accounts
- Account has no transactions

**Success Response (200):**
```json
{
  "success": true,
  "message": "Account deleted successfully"
}
```

**Error Response (422):**
```json
{
  "success": false,
  "message": "Cannot delete account with sub-accounts"
}
```

---

### 6. Get Account Tree
**GET** `/api/accounts/tree`

Get hierarchical tree structure of all accounts.

**Query Parameters:**
- `type` (optional): Filter by account type

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "account_code": "1000",
      "name": "Current Assets",
      "type": "asset",
      "level": 1,
      "children": [
        {
          "id": 2,
          "account_code": "1001",
          "name": "Cash and Cash Equivalents",
          "level": 2,
          "children": []
        },
        {
          "id": 3,
          "account_code": "1002",
          "name": "Accounts Receivable",
          "level": 2,
          "children": []
        }
      ]
    }
  ]
}
```

---

### 7. Get Account Balance
**GET** `/api/accounts/{id}/balance`

Get current balance for an account with optional filters.

**Query Parameters:**
- `store_id` (optional): Filter by specific store
- `end_date` (optional): Calculate balance up to this date

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "account_id": 2,
    "account_name": "Cash and Cash Equivalents",
    "account_code": "1001",
    "balance": 150000.00,
    "children_balance": 0.00,
    "total_balance": 150000.00,
    "store_id": null,
    "end_date": null
  }
}
```

---

### 8. Activate/Deactivate Account
**POST** `/api/accounts/{id}/activate`
**POST** `/api/accounts/{id}/deactivate`

Change account active status.

**Success Response (200):**
```json
{
  "success": true,
  "message": "Account activated successfully",
  "data": {
    "id": 2,
    "is_active": true
  }
}
```

---

### 9. Get Account Statistics
**GET** `/api/accounts/statistics`

Get comprehensive statistics about the chart of accounts.

**Query Parameters:**
- `type` (optional): Filter statistics by account type

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "total": 17,
    "active": 17,
    "inactive": 0,
    "by_type": {
      "assets": 7,
      "liabilities": 2,
      "equity": 2,
      "income": 2,
      "expenses": 4
    },
    "by_sub_type": {
      "current_assets": 4,
      "fixed_assets": 3,
      "current_liabilities": 2,
      "long_term_liabilities": 0,
      "sales_revenue": 2,
      "operating_expenses": 2
    },
    "by_level": {
      "1": 5,
      "2": 12
    }
  }
}
```

---

### 10. Get Chart of Accounts
**GET** `/api/accounts/chart-of-accounts`

Get complete chart of accounts with balances.

**Query Parameters:**
- `store_id` (optional): Filter by store
- `end_date` (optional): Calculate balances up to date

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "account_code": "1000",
      "name": "Current Assets",
      "type": "asset",
      "sub_type": "current_asset",
      "level": 1,
      "parent_id": null,
      "balance": 250000.00
    },
    {
      "id": 2,
      "account_code": "1001",
      "name": "Cash and Cash Equivalents",
      "type": "asset",
      "sub_type": "current_asset",
      "level": 2,
      "parent_id": 1,
      "balance": 150000.00
    }
  ]
}
```

---

### 11. Initialize Default Accounts
**POST** `/api/accounts/initialize-defaults`

Create default chart of accounts structure. **Only works if no accounts exist.**

**Success Response (200):**
```json
{
  "success": true,
  "message": "Default chart of accounts created successfully"
}
```

**Default Accounts Created:**
- Assets (1000-1199)
  - Current Assets (1000-1099)
    - Cash and Cash Equivalents (1001)
    - Accounts Receivable (1002)
    - Inventory (1003)
  - Fixed Assets (1100-1199)
    - Property, Plant and Equipment (1101)
    - Accumulated Depreciation (1102)
- Liabilities (2000-2999)
  - Accounts Payable (2001)
- Equity (3000-3999)
  - Owner Equity (3000)
  - Retained Earnings (3001)
- Income (4000-4999)
  - Sales Revenue (4001)
  - Service Revenue (4002)
- Expenses (5000-5999)
  - Operating Expenses (5001)
  - Cost of Goods Sold (5002)

---

## Transaction Management API

### 12. List Transactions
**GET** `/api/transactions`

Get all transactions with filtering and pagination.

**Query Parameters:**
- `account_id` (optional): Filter by account
- `type` (optional): Filter by type (debit/credit)
- `status` (optional): Filter by status (pending/completed/failed/cancelled)
- `store_id` (optional): Filter by store
- `date_from` (optional): Start date
- `date_to` (optional): End date
- `reference_type` (optional): Filter by reference type (OrderPayment, Expense, etc.)
- `reference_id` (optional): Filter by reference ID
- `search` (optional): Search transaction number or description
- `sort_by` (optional, default: `transaction_date`): Sort field
- `sort_order` (optional, default: `desc`): Sort direction
- `per_page` (optional, default: 15): Items per page

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "transaction_number": "TXN-20251116-001",
        "transaction_date": "2025-11-16",
        "amount": 50000.00,
        "type": "debit",
        "description": "Order Payment - ORD-20251116-001",
        "status": "completed",
        "reference_type": "OrderPayment",
        "reference_id": 1,
        "account": {
          "id": 2,
          "account_code": "1001",
          "name": "Cash and Cash Equivalents"
        },
        "store": {
          "id": 1,
          "name": "Main Store"
        },
        "created_by": {
          "id": 5,
          "name": "John Doe"
        },
        "created_at": "2025-11-16T10:00:00.000000Z"
      }
    ],
    "total": 150,
    "per_page": 15,
    "last_page": 10
  }
}
```

---

### 13. Create Transaction
**POST** `/api/transactions`

Manually create a transaction. **Note**: Most transactions are auto-created by observers.

**Request Body:**
```json
{
  "transaction_date": "2025-11-16",
  "amount": 10000.00,
  "type": "debit",
  "account_id": 2,
  "description": "Manual adjustment - Cash deposit",
  "store_id": 1,
  "reference_type": "Manual",
  "reference_id": 0,
  "metadata": {
    "source": "bank_deposit",
    "bank_reference": "DEP-123456"
  },
  "status": "completed"
}
```

**Validation Rules:**
- `transaction_date` (required): Date of transaction
- `amount` (required): Positive number
- `type` (required): debit or credit
- `account_id` (required): Valid account ID
- `description` (optional): Transaction description
- `store_id` (optional): Store reference
- `reference_type` (optional): Type of source document
- `reference_id` (optional): ID of source document
- `metadata` (optional): Additional JSON data
- `status` (optional): pending, completed, failed, cancelled

**Success Response (201):**
```json
{
  "success": true,
  "message": "Transaction created successfully",
  "data": {
    "id": 151,
    "transaction_number": "TXN-20251116-151",
    "transaction_date": "2025-11-16",
    "amount": 10000.00,
    "type": "debit",
    "status": "completed",
    "account": {
      "id": 2,
      "account_code": "1001",
      "name": "Cash and Cash Equivalents"
    }
  }
}
```

---

### 14. Get Transaction Details
**GET** `/api/transactions/{id}`

Get detailed information about a specific transaction.

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "transaction_number": "TXN-20251116-001",
    "transaction_date": "2025-11-16",
    "amount": 50000.00,
    "type": "debit",
    "description": "Order Payment - ORD-20251116-001",
    "status": "completed",
    "reference_type": "OrderPayment",
    "reference_id": 1,
    "metadata": {
      "payment_method": "cash",
      "order_number": "ORD-20251116-001"
    },
    "account": {
      "id": 2,
      "account_code": "1001",
      "name": "Cash and Cash Equivalents"
    },
    "store": {
      "id": 1,
      "name": "Main Store"
    },
    "created_by": {
      "id": 5,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "reference": {
      "id": 1,
      "order_id": 1,
      "amount": 50000.00,
      "status": "completed"
    }
  }
}
```

---

### 15. Update Transaction
**PUT** `/api/transactions/{id}`

Update a transaction. **Only pending transactions can be updated.**

**Request Body:**
```json
{
  "amount": 10500.00,
  "description": "Updated description",
  "metadata": {
    "updated_reason": "Correction"
  }
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Transaction updated successfully",
  "data": {
    "id": 150,
    "amount": 10500.00,
    "updated_at": "2025-11-16T11:30:00.000000Z"
  }
}
```

**Error Response (422):**
```json
{
  "success": false,
  "message": "Only pending transactions can be updated"
}
```

---

### 16. Delete Transaction
**DELETE** `/api/transactions/{id}`

Delete a transaction. **Only pending or failed transactions can be deleted.**

**Success Response (200):**
```json
{
  "success": true,
  "message": "Transaction deleted successfully"
}
```

---

### 17. Complete Transaction
**POST** `/api/transactions/{id}/complete`

Mark a pending transaction as completed.

**Success Response (200):**
```json
{
  "success": true,
  "message": "Transaction completed successfully",
  "data": {
    "id": 150,
    "status": "completed"
  }
}
```

---

### 18. Fail Transaction
**POST** `/api/transactions/{id}/fail`

Mark a transaction as failed.

**Request Body:**
```json
{
  "reason": "Bank transfer failed"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Transaction marked as failed",
  "data": {
    "id": 150,
    "status": "failed"
  }
}
```

---

### 19. Cancel Transaction
**POST** `/api/transactions/{id}/cancel`

Cancel a transaction.

**Request Body:**
```json
{
  "reason": "Order cancelled"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Transaction cancelled successfully",
  "data": {
    "id": 150,
    "status": "cancelled"
  }
}
```

---

### 20. Get Account Transactions
**GET** `/api/accounts/{accountId}/transactions`

Get all transactions for a specific account (Account Ledger).

**Query Parameters:**
- `date_from` (optional): Start date
- `date_to` (optional): End date
- `store_id` (optional): Filter by store
- `per_page` (optional, default: 15): Items per page

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "transaction_number": "TXN-20251116-001",
        "transaction_date": "2025-11-16",
        "amount": 50000.00,
        "type": "debit",
        "description": "Order Payment",
        "status": "completed"
      }
    ]
  }
}
```

---

### 21. Get Transaction Statistics
**GET** `/api/transactions/statistics`

Get comprehensive transaction statistics.

**Query Parameters:**
- `date_from` (optional, default: start of month): Start date
- `date_to` (optional, default: end of month): End date
- `store_id` (optional): Filter by store

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "total": 150,
    "completed": 145,
    "pending": 3,
    "failed": 2,
    "total_debits": 1500000.00,
    "total_credits": 800000.00,
    "completed_debits": 1450000.00,
    "completed_credits": 750000.00,
    "net_balance": 700000.00,
    "by_type": {
      "debit": 85,
      "credit": 65
    },
    "by_status": {
      "completed": 145,
      "pending": 3,
      "failed": 2
    }
  }
}
```

---

### 22. Bulk Complete Transactions
**POST** `/api/transactions/bulk-complete`

Complete multiple pending transactions at once.

**Request Body:**
```json
{
  "transaction_ids": [150, 151, 152]
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "3 transaction(s) completed successfully",
  "data": {
    "completed_count": 3
  }
}
```

---

## Financial Reports API

### 23. Get Trial Balance
**GET** `/api/transactions/trial-balance`

Get trial balance to verify debits equal credits.

**Query Parameters:**
- `store_id` (optional): Filter by store
- `start_date` (optional, default: start of month): Period start
- `end_date` (optional, default: end of month): Period end

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "summary": {
      "total_debits": 1500000.00,
      "total_credits": 1500000.00,
      "difference": 0.00,
      "balanced": true
    },
    "accounts": [
      {
        "account_code": "1001",
        "account_name": "Cash and Cash Equivalents",
        "type": "asset",
        "balance": 150000.00,
        "debit": 150000.00,
        "credit": 0.00
      },
      {
        "account_code": "4001",
        "account_name": "Sales Revenue",
        "type": "income",
        "balance": -200000.00,
        "debit": 0.00,
        "credit": 200000.00
      }
    ],
    "date_range": {
      "start_date": "2025-11-01",
      "end_date": "2025-11-30"
    },
    "store_id": null
  }
}
```

---

### 24. Get Account Ledger
**GET** `/api/transactions/ledger/{accountId}`

Get detailed ledger for a specific account with running balance.

**Query Parameters:**
- `date_from` (optional, default: start of month): Period start
- `date_to` (optional, default: end of month): Period end
- `store_id` (optional): Filter by store

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "account": {
      "id": 2,
      "account_code": "1001",
      "name": "Cash and Cash Equivalents",
      "type": "asset"
    },
    "opening_balance": 100000.00,
    "closing_balance": 150000.00,
    "transactions": [
      {
        "id": 1,
        "transaction_number": "TXN-20251116-001",
        "transaction_date": "2025-11-16",
        "description": "Order Payment",
        "debit": 50000.00,
        "credit": 0.00,
        "balance": 150000.00,
        "status": "completed"
      },
      {
        "id": 2,
        "transaction_number": "TXN-20251116-002",
        "transaction_date": "2025-11-16",
        "description": "Expense Payment",
        "debit": 0.00,
        "credit": 5000.00,
        "balance": 145000.00,
        "status": "completed"
      }
    ],
    "date_range": {
      "date_from": "2025-11-01",
      "date_to": "2025-11-30"
    }
  }
}
```

---

## Account Types & Structure

### Account Types

| Type | Description | Normal Balance | Examples |
|------|-------------|----------------|----------|
| **asset** | Resources owned | Debit | Cash, Inventory, Equipment |
| **liability** | Obligations owed | Credit | Accounts Payable, Loans |
| **equity** | Owner's stake | Credit | Capital, Retained Earnings |
| **income** | Revenue earned | Credit | Sales, Service Revenue |
| **expense** | Costs incurred | Debit | Rent, Salaries, Utilities |

### Sub-Types

#### Assets
- `current_asset` - Cash, AR, Inventory (converted within 1 year)
- `fixed_asset` - Property, Equipment, Vehicles
- `other_asset` - Intangibles, Long-term investments

#### Liabilities
- `current_liability` - AP, Short-term loans (due within 1 year)
- `long_term_liability` - Long-term loans, Mortgages

#### Equity
- `owner_equity` - Capital contributions
- `retained_earnings` - Accumulated profits

#### Income
- `sales_revenue` - Product sales
- `other_income` - Service fees, Interest income

#### Expenses
- `cost_of_goods_sold` - Direct costs of products sold
- `operating_expenses` - Rent, Salaries, Utilities
- `other_expenses` - Interest, Taxes, Losses

---

## Database Schema

### `accounts` Table
```sql
id                  BIGINT PRIMARY KEY
account_code        VARCHAR(50) UNIQUE
name                VARCHAR(255)
description         TEXT
type                ENUM('asset', 'liability', 'equity', 'income', 'expense')
sub_type            VARCHAR(100)
parent_id           BIGINT FOREIGN KEY → accounts(id)
is_active           BOOLEAN DEFAULT TRUE
level               INT DEFAULT 1
path                VARCHAR(255)
created_at          TIMESTAMP
updated_at          TIMESTAMP
```

### `transactions` Table
```sql
id                  BIGINT PRIMARY KEY
transaction_number  VARCHAR(255) UNIQUE
transaction_date    DATE
amount              DECIMAL(15,2)
type                ENUM('debit', 'credit')
account_id          BIGINT FOREIGN KEY → accounts(id)
reference_type      VARCHAR(255)
reference_id        BIGINT
description         TEXT
store_id            BIGINT FOREIGN KEY → stores(id)
created_by          BIGINT FOREIGN KEY → employees(id)
metadata            JSON
status              ENUM('pending', 'completed', 'failed', 'cancelled')
created_at          TIMESTAMP
updated_at          TIMESTAMP
```

---

## Usage Examples

### Example 1: Create Account Structure

**Step 1: Create Parent Account**
```bash
POST /api/accounts
{
  "account_code": "1200",
  "name": "Investments",
  "type": "asset",
  "sub_type": "other_asset"
}
```

**Step 2: Create Child Account**
```bash
POST /api/accounts
{
  "account_code": "1201",
  "name": "Stocks and Bonds",
  "type": "asset",
  "sub_type": "other_asset",
  "parent_id": 18
}
```

---

### Example 2: View Account Hierarchy

**Get Tree Structure**
```bash
GET /api/accounts/tree?type=asset
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "account_code": "1000",
      "name": "Current Assets",
      "children": [
        {"id": 2, "account_code": "1001", "name": "Cash"},
        {"id": 3, "account_code": "1002", "name": "Accounts Receivable"}
      ]
    },
    {
      "id": 18,
      "account_code": "1200",
      "name": "Investments",
      "children": [
        {"id": 19, "account_code": "1201", "name": "Stocks and Bonds"}
      ]
    }
  ]
}
```

---

### Example 3: Check Account Balance

**Get Current Balance**
```bash
GET /api/accounts/2/balance
```

**Response:**
```json
{
  "success": true,
  "data": {
    "account_id": 2,
    "account_name": "Cash and Cash Equivalents",
    "account_code": "1001",
    "balance": 150000.00,
    "children_balance": 0.00,
    "total_balance": 150000.00
  }
}
```

---

### Example 4: View Monthly Transactions

**Filter by Date Range**
```bash
GET /api/transactions?date_from=2025-11-01&date_to=2025-11-30&account_id=2&per_page=50
```

---

### Example 5: Generate Trial Balance

**Monthly Trial Balance**
```bash
GET /api/transactions/trial-balance?start_date=2025-11-01&end_date=2025-11-30
```

**Response:**
```json
{
  "success": true,
  "data": {
    "summary": {
      "total_debits": 1500000.00,
      "total_credits": 1500000.00,
      "difference": 0.00,
      "balanced": true
    },
    "accounts": [...]
  }
}
```

---

### Example 6: View Account Ledger

**Cash Ledger for November**
```bash
GET /api/transactions/ledger/2?date_from=2025-11-01&date_to=2025-11-30
```

**Response shows:**
- Opening balance
- All transactions with running balance
- Closing balance

---

### Example 7: Store-Specific Reports

**Get Store 1 Trial Balance**
```bash
GET /api/transactions/trial-balance?store_id=1&start_date=2025-11-01&end_date=2025-11-30
```

---

## Best Practices

### 1. Account Naming Conventions
- Use clear, descriptive names
- Follow consistent numbering:
  - 1000-1999: Assets
  - 2000-2999: Liabilities
  - 3000-3999: Equity
  - 4000-4999: Income
  - 5000-5999: Expenses

### 2. Account Hierarchy
- Keep hierarchy 2-3 levels deep maximum
- Parent accounts summarize child accounts
- Leaf accounts (no children) hold actual transactions

### 3. Transaction Management
- Let observers create transactions automatically
- Use manual transactions only for adjustments
- Always include descriptive descriptions
- Tag transactions with metadata for better reporting

### 4. Financial Reporting
- Run trial balance daily to verify accuracy
- Generate ledgers for each account monthly
- Compare store-wise performance using store_id filter
- Use date ranges for period comparisons

### 5. Chart of Accounts Setup
```javascript
// Frontend: Initialize default accounts on first setup
fetch('/api/accounts/initialize-defaults', { method: 'POST' })
  .then(response => response.json())
  .then(data => console.log('COA initialized'));

// Then customize by adding company-specific accounts
fetch('/api/accounts', {
  method: 'POST',
  body: JSON.stringify({
    account_code: '5010',
    name: 'Marketing Expenses',
    type: 'expense',
    sub_type: 'operating_expenses',
    parent_id: 16 // Operating Expenses parent
  })
});
```

### 6. Accounting Verification Checklist
- ✅ Trial balance should always be balanced (debits = credits)
- ✅ All cash receipts should have debit entries in cash account
- ✅ All cash payments should have credit entries in cash account
- ✅ Revenue accounts should have credit balances
- ✅ Expense accounts should have debit balances
- ✅ Asset accounts should have debit balances
- ✅ Liability accounts should have credit balances

### 7. Common Account Mappings
```javascript
// Cash sales
Debit: Cash (1001)
Credit: Sales Revenue (4001)

// Credit sales
Debit: Accounts Receivable (1002)
Credit: Sales Revenue (4001)

// Cash expenses
Debit: Operating Expenses (5001)
Credit: Cash (1001)

// Credit purchases
Debit: Inventory (1003)
Credit: Accounts Payable (2001)
```

---

## Auto-Transaction Triggers

The system automatically creates transactions for:

1. **Order Payments** → Debit Cash/AR, Credit Sales Revenue
2. **Service Payments** → Debit Cash, Credit Service Revenue
3. **Expense Payments** → Debit Expense Account, Credit Cash
4. **Vendor Payments** → Debit AP, Credit Cash
5. **Refunds** → Debit Sales Returns, Credit Cash

See `TRANSACTION_SYSTEM_DOCS.md` for complete details on auto-triggers.

---

## Integration Example (Frontend)

### React/Next.js Dashboard Component
```javascript
import { useState, useEffect } from 'react';

function AccountingDashboard() {
  const [trialBalance, setTrialBalance] = useState(null);
  const [accounts, setAccounts] = useState([]);
  
  useEffect(() => {
    // Fetch trial balance
    fetch('/api/transactions/trial-balance?start_date=2025-11-01&end_date=2025-11-30')
      .then(res => res.json())
      .then(data => setTrialBalance(data.data));
    
    // Fetch accounts tree
    fetch('/api/accounts/tree')
      .then(res => res.json())
      .then(data => setAccounts(data.data));
  }, []);
  
  return (
    <div>
      <h1>Accounting Dashboard</h1>
      
      {/* Trial Balance Summary */}
      {trialBalance && (
        <div className="trial-balance">
          <h2>Trial Balance</h2>
          <p>Total Debits: {trialBalance.summary.total_debits}</p>
          <p>Total Credits: {trialBalance.summary.total_credits}</p>
          <p>Status: {trialBalance.summary.balanced ? '✅ Balanced' : '❌ Not Balanced'}</p>
        </div>
      )}
      
      {/* Accounts Tree */}
      <div className="accounts-tree">
        <h2>Chart of Accounts</h2>
        {accounts.map(account => (
          <AccountNode key={account.id} account={account} />
        ))}
      </div>
    </div>
  );
}

function AccountNode({ account }) {
  return (
    <div style={{ marginLeft: account.level * 20 }}>
      <strong>{account.account_code}</strong> - {account.name}
      {account.children?.map(child => (
        <AccountNode key={child.id} account={child} />
      ))}
    </div>
  );
}
```

---

## Support & Additional Resources

- **Transaction System**: See `TRANSACTION_SYSTEM_DOCS.md`
- **Expense Receipts**: See `EXPENSE_RECEIPT_DOCUMENTATION.md`
- **API Base URL**: `/api/`
- **Authentication**: All endpoints require `Authorization: Bearer {token}` header

---

**Document Version**: 1.0  
**Last Updated**: November 16, 2025  
**Maintained by**: Backend Development Team
