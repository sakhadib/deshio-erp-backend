# Accounting & Financial Management Documentation

**Last Updated:** January 8, 2026  
**Location:** `/docs/accounting`

---

## 📁 Documentation Index

This folder contains all accounting, financial tracking, and bookkeeping documentation for the Deshio ERP system.

---

## 📚 Core Documentation

### **1. ACCOUNTING_SYSTEM_COMPLETE_GUIDE.md**
**Purpose:** Comprehensive guide to the entire accounting system  
**Contents:**
- Chart of Accounts (COA) structure
- Double-entry bookkeeping implementation
- Transaction management API
- Financial reports API
- Account types and hierarchy
- Database schema
- Best practices

**Use When:** Setting up accounting module, understanding system architecture

---

### **2. ACCOUNTING_QUICK_START.md**
**Purpose:** Quick reference for getting started with accounting features  
**Contents:**
- Basic setup steps
- Common operations
- Quick API references
- Essential endpoints

**Use When:** Need fast overview, onboarding new developers

---

### **3. FINANCIAL_REPORTS_API.md**
**Purpose:** API endpoints for generating financial reports  
**Contents:**
- Balance Sheet API
- Profit & Loss (Income Statement) API
- Trial Balance API
- Account Ledger API
- Cash Flow reports
- Report filtering and parameters

**Use When:** Building financial reporting dashboards, generating statements

---

### **4. FINANCIAL_METRICS_AND_COGS.md**
**Purpose:** Cost of Goods Sold (COGS) and financial metrics tracking  
**Contents:**
- COGS calculation and tracking
- Gross margin calculations
- Net profit metrics
- Order financial data
- Dashboard metrics
- Expense tracking
- Accounts receivable/payable

**Use When:** Building financial dashboards, calculating profitability

---

## 🔧 Implementation & Fixes

### **5. COGS_IMPLEMENTATION_FIXES.md**
**Purpose:** Documentation of COGS calculation fixes and improvements  
**Contents:**
- COGS calculation logic
- Bug fixes and patches
- Implementation history
- Testing scenarios

**Use When:** Debugging COGS issues, understanding calculation changes

---

### **6. DOUBLE_ENTRY_BOOKKEEPING_FIX.md**
**Purpose:** Fixes and improvements to double-entry bookkeeping system  
**Contents:**
- Transaction balancing fixes
- Debit/credit validation
- Observer improvements
- Data integrity fixes

**Use When:** Debugging accounting transactions, ensuring balance

---

## 📄 Expense Management

### **7. EXPENSE_RECEIPT_MANAGEMENT.md**
**Purpose:** Expense receipt upload and management system  
**Contents:**
- Receipt upload API
- Multiple receipt handling
- Primary receipt designation
- File management
- Storage structure

**Use When:** Building expense submission forms, managing receipts

---

## 🗂️ Document Organization

### By Purpose:
- **System Overview:** `ACCOUNTING_SYSTEM_COMPLETE_GUIDE.md`
- **Quick Reference:** `ACCOUNTING_QUICK_START.md`
- **Reports:** `FINANCIAL_REPORTS_API.md`
- **Metrics:** `FINANCIAL_METRICS_AND_COGS.md`
- **Expenses:** `EXPENSE_RECEIPT_MANAGEMENT.md`
- **Fixes:** `COGS_IMPLEMENTATION_FIXES.md`, `DOUBLE_ENTRY_BOOKKEEPING_FIX.md`

### By Audience:
- **New Developers:** Start with `ACCOUNTING_QUICK_START.md`
- **Frontend Developers:** Focus on `FINANCIAL_METRICS_AND_COGS.md`, `FINANCIAL_REPORTS_API.md`
- **Backend Developers:** Read `ACCOUNTING_SYSTEM_COMPLETE_GUIDE.md`, fixes documentation
- **QA/Testing:** Review all fix documents, use quick start for testing

---

## 🔑 Key Concepts

### **Chart of Accounts (COA)**
Hierarchical structure of all financial accounts:
- Assets (1000-1999)
- Liabilities (2000-2999)
- Equity (3000-3999)
- Revenue (4000-4999)
- Expenses (5000-5999)

### **Double-Entry Bookkeeping**
Every transaction has equal debits and credits:
```
Debit Total = Credit Total (always)
```

### **COGS (Cost of Goods Sold)**
Cost to acquire/produce products sold:
```
Gross Profit = Revenue - COGS
```

### **Financial Statements**
1. **Balance Sheet:** Assets = Liabilities + Equity
2. **Income Statement:** Revenue - Expenses = Net Profit
3. **Trial Balance:** Verify Debits = Credits

---

## 📊 Common Use Cases

### **1. Display Financial Dashboard**
```
→ Read: FINANCIAL_METRICS_AND_COGS.md (Dashboard section)
→ API: GET /api/accounting/dashboard
```

### **2. Generate Balance Sheet**
```
→ Read: FINANCIAL_REPORTS_API.md (Balance Sheet section)
→ API: GET /api/accounting/reports/balance-sheet
```

### **3. Track Order Profitability**
```
→ Read: FINANCIAL_METRICS_AND_COGS.md (Order Financial Data)
→ API: GET /api/orders/{id} (includes COGS data)
```

### **4. Manage Expense Receipts**
```
→ Read: EXPENSE_RECEIPT_MANAGEMENT.md
→ API: POST /api/expenses/{id}/receipts
```

### **5. View Account Ledger**
```
→ Read: FINANCIAL_REPORTS_API.md (Account Ledger)
→ API: GET /api/accounting/accounts/{id}/ledger
```

---

## 🚀 Getting Started

### **Step 1: Understand the System**
Read `ACCOUNTING_SYSTEM_COMPLETE_GUIDE.md` for complete overview

### **Step 2: Quick Setup**
Follow `ACCOUNTING_QUICK_START.md` for basic setup

### **Step 3: Build Features**
- Reports → `FINANCIAL_REPORTS_API.md`
- Metrics → `FINANCIAL_METRICS_AND_COGS.md`
- Expenses → `EXPENSE_RECEIPT_MANAGEMENT.md`

### **Step 4: Debug Issues**
Check fix documents:
- `COGS_IMPLEMENTATION_FIXES.md`
- `DOUBLE_ENTRY_BOOKKEEPING_FIX.md`

---

## 🔗 Related Documentation

### Other Relevant Docs (in main /docs folder):
- `reporting_api.md` - General reporting endpoints
- `23_12_25_BUSINESS_HISTORY_API.md` - Audit trail for financial changes
- `27_12_25_DASHBOARD_STORES_SUMMARY_API.md` - Store-level financial metrics

### In /Doc folder:
- `DASHBOARD_API.md` - Dashboard with financial metrics
- Various expense and payment related docs

---

## 📝 Documentation Standards

All accounting documents should include:
- ✅ API endpoint examples
- ✅ Request/response samples
- ✅ Error handling
- ✅ Business logic explanation
- ✅ Database schema (if applicable)
- ✅ Frontend implementation examples

---

## ⚠️ Important Notes

### **Data Integrity**
- All transactions must balance (debits = credits)
- COGS is stored at time of sale (not real-time calculated)
- Soft deletes preserve financial history

### **Multi-Store Support**
- Accounts can be store-specific or company-wide
- Reports can filter by store
- Transactions track source store

### **Audit Trail**
- All financial changes are logged
- Use Business History API for audit trails
- Changes show WHO, WHEN, and WHAT

---

## 🆘 Need Help?

1. **Find the right doc:** Use this README index
2. **Search keywords:** Use Ctrl+F in relevant document
3. **Check examples:** All docs include practical examples
4. **Review fixes:** Check fix docs for known issues
5. **Contact backend team:** For questions not covered in docs

---

**Maintained by:** Backend Development Team  
**Review Frequency:** Monthly or after major accounting changes  
**Last Major Update:** January 8, 2026
