# 🚀 Quick Start Guide: Accounting API for Frontend Developers

## 📋 Table of Contents
- [Essential Endpoints](#essential-endpoints)
- [Common Use Cases](#common-use-cases)
- [Page Components](#page-components)
- [Error Handling](#error-handling)
- [API Response Patterns](#api-response-patterns)

---

## Essential Endpoints

### 🏦 Chart of Accounts

| Action | Method | Endpoint | Use For |
|--------|--------|----------|---------|
| List accounts | GET | `/api/accounts` | Display all accounts |
| Get tree | GET | `/api/accounts/tree` | Hierarchical view |
| Get account | GET | `/api/accounts/{id}` | Account details + balance |
| Create account | POST | `/api/accounts` | Add new account |
| Update account | PUT | `/api/accounts/{id}` | Edit account |
| Delete account | DELETE | `/api/accounts/{id}` | Remove account |
| Get balance | GET | `/api/accounts/{id}/balance` | Check account balance |
| Initialize COA | POST | `/api/accounts/initialize-defaults` | First-time setup |

### 💰 Transactions

| Action | Method | Endpoint | Use For |
|--------|--------|----------|---------|
| List transactions | GET | `/api/transactions` | Transaction history |
| Get transaction | GET | `/api/transactions/{id}` | Transaction details |
| Create transaction | POST | `/api/transactions` | Manual entry |
| Update transaction | PUT | `/api/transactions/{id}` | Edit pending transaction |
| Delete transaction | DELETE | `/api/transactions/{id}` | Remove transaction |
| Complete | POST | `/api/transactions/{id}/complete` | Approve pending |
| Fail | POST | `/api/transactions/{id}/fail` | Mark as failed |
| Cancel | POST | `/api/transactions/{id}/cancel` | Cancel transaction |

### 📊 Reports

| Report | Method | Endpoint | Use For |
|--------|--------|----------|---------|
| Trial Balance | GET | `/api/transactions/trial-balance` | Verify books balance |
| Account Ledger | GET | `/api/transactions/ledger/{id}` | Account statement |
| Account Transactions | GET | `/api/accounts/{id}/transactions` | Account history |
| Statistics | GET | `/api/transactions/statistics` | Dashboard metrics |
| Chart of Accounts | GET | `/api/accounts/chart-of-accounts` | Full COA with balances |

---

## Common Use Cases

### 1️⃣ Dashboard Overview

**Fetch key metrics:**
```javascript
// Get transaction statistics
const stats = await fetch('/api/transactions/statistics?date_from=2025-11-01&date_to=2025-11-30')
  .then(res => res.json());

console.log(stats.data);
// {
//   total: 150,
//   completed: 145,
//   net_balance: 700000.00,
//   total_debits: 1500000.00,
//   total_credits: 800000.00
// }

// Get account statistics
const accountStats = await fetch('/api/accounts/statistics')
  .then(res => res.json());

console.log(accountStats.data.by_type);
// { assets: 7, liabilities: 2, equity: 2, income: 2, expenses: 4 }
```

**Display:**
- Total transactions this month
- Net balance (debits - credits)
- Account counts by type
- Trial balance status

---

### 2️⃣ Chart of Accounts Page

**Fetch accounts tree:**
```javascript
const tree = await fetch('/api/accounts/tree')
  .then(res => res.json());

// Render hierarchical tree
function renderTree(accounts, level = 0) {
  return accounts.map(account => (
    <div style={{ marginLeft: level * 20 }}>
      <span>{account.account_code} - {account.name}</span>
      {account.children && renderTree(account.children, level + 1)}
    </div>
  ));
}
```

**Features to implement:**
- ✅ Tree view with expand/collapse
- ✅ Search by code or name
- ✅ Filter by type (Assets, Liabilities, etc.)
- ✅ Click to view account details
- ✅ Add/Edit/Delete buttons
- ✅ Show active/inactive status

---

### 3️⃣ Account Details Page

**Fetch account with balance:**
```javascript
const accountId = 2;
const account = await fetch(`/api/accounts/${accountId}`)
  .then(res => res.json());

console.log(account.data);
// {
//   id: 2,
//   account_code: "1001",
//   name: "Cash and Cash Equivalents",
//   type: "asset",
//   current_balance: 150000.00,
//   parent: { id: 1, name: "Current Assets" },
//   children: [],
//   transactions: [...]
// }
```

**Display:**
- Account details (code, name, type)
- Current balance (formatted)
- Parent account (breadcrumb)
- Recent transactions (paginated table)
- Edit/Delete buttons

---

### 4️⃣ Transaction List Page

**Fetch with filters:**
```javascript
const params = new URLSearchParams({
  date_from: '2025-11-01',
  date_to: '2025-11-30',
  account_id: 2,
  type: 'debit',
  status: 'completed',
  per_page: 20,
  page: 1
});

const transactions = await fetch(`/api/transactions?${params}`)
  .then(res => res.json());

console.log(transactions.data.data); // Array of transactions
```

**Table columns:**
- Transaction Number
- Date
- Account
- Type (Debit/Credit with color badge)
- Amount
- Description
- Status (with badge)
- Actions (View, Edit, Delete)

**Filters:**
- Date range picker
- Account dropdown
- Type dropdown (Debit/Credit/All)
- Status dropdown
- Search box

---

### 5️⃣ Trial Balance Report

**Fetch and display:**
```javascript
const trialBalance = await fetch('/api/transactions/trial-balance?start_date=2025-11-01&end_date=2025-11-30')
  .then(res => res.json());

const { summary, accounts } = trialBalance.data;

// Display summary
console.log(summary);
// {
//   total_debits: 1500000.00,
//   total_credits: 1500000.00,
//   difference: 0.00,
//   balanced: true
// }

// Display account table
accounts.forEach(acc => {
  console.log(`${acc.account_code} | ${acc.account_name} | Dr: ${acc.debit} | Cr: ${acc.credit} | Bal: ${acc.balance}`);
});
```

**Report layout:**
```
Trial Balance - November 2025

Account Code | Account Name              | Debit       | Credit      | Balance
-------------|---------------------------|-------------|-------------|------------
1001         | Cash                      | 150,000.00  | 0.00        | 150,000.00
1002         | Accounts Receivable       | 100,000.00  | 0.00        | 100,000.00
4001         | Sales Revenue             | 0.00        | 200,000.00  | (200,000.00)
5001         | Operating Expenses        | 50,000.00   | 0.00        | 50,000.00
-------------|---------------------------|-------------|-------------|------------
TOTAL        |                           | 1,500,000.00| 1,500,000.00| 0.00

Status: ✅ BALANCED
```

---

### 6️⃣ Account Ledger Page

**Fetch ledger:**
```javascript
const accountId = 2;
const ledger = await fetch(`/api/transactions/ledger/${accountId}?date_from=2025-11-01&date_to=2025-11-30`)
  .then(res => res.json());

const { account, opening_balance, closing_balance, transactions } = ledger.data;
```

**Ledger layout:**
```
Account Ledger: 1001 - Cash and Cash Equivalents
Period: November 1 - November 30, 2025

Opening Balance: 100,000.00

Date       | Transaction # | Description       | Debit     | Credit    | Balance
-----------|---------------|-------------------|-----------|-----------|------------
2025-11-01 | TXN-001      | Order Payment     | 50,000.00 |           | 150,000.00
2025-11-02 | TXN-002      | Expense Payment   |           | 5,000.00  | 145,000.00
2025-11-03 | TXN-003      | Order Payment     | 10,000.00 |           | 155,000.00

Closing Balance: 155,000.00
```

---

### 7️⃣ Create Account Form

**Form fields:**
```javascript
const formData = {
  account_code: "1004",      // Required, unique
  name: "Prepaid Expenses",  // Required
  description: "Expenses paid in advance",
  type: "asset",             // Required: dropdown
  sub_type: "current_asset", // Required: dropdown based on type
  parent_id: 1,              // Optional: parent account dropdown
  is_active: true            // Checkbox, default true
};

const response = await fetch('/api/accounts', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`
  },
  body: JSON.stringify(formData)
});

const result = await response.json();
if (result.success) {
  alert('Account created successfully!');
} else {
  console.error(result.errors);
}
```

**Type & Sub-type dropdowns:**
```javascript
const typeSubTypeMap = {
  asset: ['current_asset', 'fixed_asset', 'other_asset'],
  liability: ['current_liability', 'long_term_liability'],
  equity: ['owner_equity', 'retained_earnings'],
  income: ['sales_revenue', 'other_income'],
  expense: ['cost_of_goods_sold', 'operating_expenses', 'other_expenses']
};

// When type changes, update sub_type options
const handleTypeChange = (type) => {
  setSubTypeOptions(typeSubTypeMap[type]);
};
```

---

### 8️⃣ Create Manual Transaction

**Use case:** Adjustments, corrections, bank deposits

```javascript
const transactionData = {
  transaction_date: "2025-11-16",
  amount: 10000.00,
  type: "debit",                    // debit or credit
  account_id: 2,                    // Cash account
  description: "Bank deposit - correction",
  store_id: 1,                      // Optional
  reference_type: "Manual",         // Optional
  reference_id: 0,                  // Optional
  metadata: {
    source: "bank_deposit",
    reference: "DEP-123456"
  },
  status: "completed"               // pending, completed, failed, cancelled
};

const response = await fetch('/api/transactions', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`
  },
  body: JSON.stringify(transactionData)
});
```

---

### 9️⃣ Initialize Chart of Accounts (First Time Setup)

**Call once when setting up accounting:**
```javascript
const initializeCOA = async () => {
  try {
    const response = await fetch('/api/accounts/initialize-defaults', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`
      }
    });
    
    const result = await response.json();
    
    if (result.success) {
      alert('Chart of Accounts initialized successfully!');
      // Redirect to accounts page
    } else {
      alert(result.message); // "Chart of accounts already exists"
    }
  } catch (error) {
    console.error('Error:', error);
  }
};

// Call in setup wizard or settings page
initializeCOA();
```

**This creates 17 default accounts:**
- Assets (Current + Fixed)
- Liabilities (Payables)
- Equity (Owner + Retained Earnings)
- Income (Sales + Service)
- Expenses (Operating + COGS)

---

## Page Components

### 📄 Suggested Pages

1. **Accounting Dashboard**
   - Trial balance summary
   - Monthly transaction stats
   - Quick links to reports
   - Recent transactions

2. **Chart of Accounts**
   - Tree view of all accounts
   - Add/Edit/Delete accounts
   - View balances
   - Search and filter

3. **Account Details**
   - Account information
   - Current balance
   - Transaction history
   - Edit button

4. **Transaction List**
   - Filterable table
   - Date range picker
   - Export to CSV
   - Pagination

5. **Trial Balance Report**
   - Date range selector
   - Store filter
   - Printable format
   - Export options

6. **Account Ledger**
   - Account selector
   - Date range
   - Running balance
   - Print view

7. **Manual Transaction Entry**
   - Form for adjustments
   - Account selector
   - Amount validation
   - Description field

---

## Error Handling

### Standard Error Response
```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field_name": ["Validation error message"]
  }
}
```

### Common Errors

**422 Validation Error:**
```javascript
if (response.status === 422) {
  const result = await response.json();
  // Display validation errors
  Object.entries(result.errors).forEach(([field, messages]) => {
    console.error(`${field}: ${messages.join(', ')}`);
  });
}
```

**404 Not Found:**
```javascript
if (response.status === 404) {
  alert('Account or transaction not found');
}
```

**401 Unauthorized:**
```javascript
if (response.status === 401) {
  // Redirect to login
  window.location.href = '/login';
}
```

---

## API Response Patterns

### Success Response
```json
{
  "success": true,
  "data": { ... },
  "message": "Operation successful"
}
```

### Paginated Response
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [...],
    "total": 150,
    "per_page": 15,
    "last_page": 10
  }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error occurred",
  "errors": {
    "field": ["Error message"]
  }
}
```

---

## Color Coding Suggestions

### Account Types
- 🟢 **Assets**: Green (#10B981)
- 🟡 **Liabilities**: Yellow/Orange (#F59E0B)
- 🔵 **Equity**: Blue (#3B82F6)
- 🟣 **Income**: Purple (#8B5CF6)
- 🔴 **Expenses**: Red (#EF4444)

### Transaction Types
- ➕ **Debit**: Green badge
- ➖ **Credit**: Red badge

### Transaction Status
- ✅ **Completed**: Green
- ⏳ **Pending**: Yellow
- ❌ **Failed**: Red
- 🚫 **Cancelled**: Gray

---

## Utility Functions

### Format Currency
```javascript
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'BDT', // or 'USD'
    minimumFractionDigits: 2
  }).format(amount);
};

console.log(formatCurrency(150000)); // "BDT 150,000.00"
```

### Format Date
```javascript
const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

console.log(formatDate('2025-11-16')); // "Nov 16, 2025"
```

### Get Badge Color
```javascript
const getStatusBadge = (status) => {
  const badges = {
    completed: 'bg-green-100 text-green-800',
    pending: 'bg-yellow-100 text-yellow-800',
    failed: 'bg-red-100 text-red-800',
    cancelled: 'bg-gray-100 text-gray-800'
  };
  return badges[status] || badges.pending;
};
```

---

## Authentication

**All endpoints require authentication header:**
```javascript
const headers = {
  'Authorization': `Bearer ${localStorage.getItem('token')}`,
  'Content-Type': 'application/json'
};
```

---

## Testing Tips

1. **Use Postman/Insomnia** to test endpoints before integrating
2. **Initialize default accounts** first using `/api/accounts/initialize-defaults`
3. **Create test transactions** to see how the system works
4. **Check trial balance** after each transaction to verify accuracy
5. **Test filtering** with different date ranges and parameters

---

## Next Steps

1. ✅ Initialize default chart of accounts
2. ✅ Build chart of accounts page
3. ✅ Build transaction list page
4. ✅ Add trial balance report
5. ✅ Add account ledger view
6. ✅ Implement dashboard with metrics

---

**For complete API reference, see:** `ACCOUNTING_API_DOCUMENTATION.md`

**Need help?** Contact the backend development team.
