# 📚 Deshio ERP - Complete Documentation Index

**Last Updated:** January 8, 2026  
**Purpose:** Centralized documentation hub for all system features, APIs, guides, and fixes

---

## 📁 Documentation Structure

```
docs/
├── README.md (this file)
├── accounting/          # Financial & accounting system
├── features/            # Feature documentation & API specs
├── product/             # Product management API documentation
├── integrations/        # Third-party integrations
├── guides/              # Implementation & quick start guides
├── fixes/               # Bug fixes & issue resolutions
├── testing/             # Testing guides & procedures
└── archive/             # Historical documentation
```

---

## 🗂️ Quick Navigation

### **By Category**

| Category | Go To | Description |
|----------|-------|-------------|
| 💰 Accounting | [accounting/](accounting/) | Chart of accounts, COGS, financial reports |
| ⚡ Features | [features/](features/) | API documentation for all features |
| � Product | [product/](product/) | Product, variant, and barcode APIs |
| �🔌 Integrations | [integrations/](integrations/) | Pathao courier, payment gateways |
| 📖 Guides | [guides/](guides/) | Frontend integration, quick starts |
| 🔧 Fixes | [fixes/](fixes/) | Bug fixes and issue resolutions |
| 🧪 Testing | [testing/](testing/) | Testing guides and procedures |
| 📦 Archive | [archive/](archive/) | Old/deprecated documentation |

### **By Role**

| Role | Start Here |
|------|------------|
| **New Developer** | [guides/FRONTEND_INTEGRATION_COMPLETE.md](guides/FRONTEND_INTEGRATION_COMPLETE.md) |
| **Frontend Developer** | Browse [features/](features/) and [integrations/](integrations/) |
| **Backend Developer** | [accounting/ACCOUNTING_SYSTEM_COMPLETE_GUIDE.md](accounting/ACCOUNTING_SYSTEM_COMPLETE_GUIDE.md) |
| **QA/Testing** | [testing/TESTING_GUIDE.md](testing/TESTING_GUIDE.md) |
| **DevOps** | Check [integrations/](integrations/) for setup guides |

---

## 📂 Folder Details

### **1. accounting/** 💰
Complete accounting and financial management system

**Contents:**
- Chart of Accounts (COA) structure
- Double-entry bookkeeping
- Financial reports (Balance Sheet, P&L, Trial Balance)
- COGS (Cost of Goods Sold) tracking
- Expense management with receipt handling
- Account ledgers and transactions

**Start:** [accounting/README.md](accounting/README.md)

**Key Files:**
- `ACCOUNTING_SYSTEM_COMPLETE_GUIDE.md` - Full system overview
- `FINANCIAL_METRICS_AND_COGS.md` - COGS & profitability
- `FINANCIAL_REPORTS_API.md` - Report generation APIs
- `EXPENSE_RECEIPT_MANAGEMENT.md` - Receipt uploads

---

### **2. features/** ⚡
API documentation and feature specifications

**Categories:**
- **Orders & Payments**
  - `SERVICE_ORDERS_API.md` - Service order management
  - `INSTALLMENT_EARLY_PAYOFF.md` - Installment payment handling
  - `ORDER_TRACKING_API.md` - Order tracking for customers/employees
  
- **Products & Inventory**
  - `CATEGORY_HARD_DELETE_API.md` - Category permanent deletion
  - `DISPATCH_BARCODE_SYSTEM.md` - Barcode tracking system
  - `VENDOR_OPTIONAL_FIELD.md` - Vendor field configuration
  
- **Customers**
  - `PUBLIC_CUSTOMER_REGISTRATION_API.md` - Customer signup
  - `CUSTOMER_TAGS_API.md` - Customer tagging system
  
- **System Features**
  - `BUSINESS_HISTORY_AUDIT_API.md` - Audit trail & activity logs
  - `LOOKUP_API.md` - Lookup data endpoints
  - `REPORTING_API.md` - Report generation
  - `DASHBOARD_STORES_SUMMARY_API.md` - Dashboard metrics
  
- **Tax System**
  - `TAX_SYSTEM_INCLUSIVE_MODE.md` - Inclusive tax configuration
  - `TAX_MODE_CONFIGURATION.md` - Tax mode settings

**Total:** 15+ feature documents

---
product/** 📦
Comprehensive product management API documentation

**Core APIs:**
- **Product API** - CRUD operations, custom fields, bulk updates
- **Product Variants API** - Size/color matrices, variant management
- **Product Barcodes API** - Barcode generation, scanning, location tracking

**Coverage:**
- Product creation and management
- Custom fields and attributes
- Variant matrix generation
- Barcode scanning workflows
- Location and movement tracking
- Statistics and analytics

**Key Features:**
- Inherited vs variant-specific fields
- Bulk operations (category, vendor updates)
- Matr5x generation for clothing products
- Complete barcode lifecycle tracking
- Integration with inventory and POS

**Start:** [product/README.md](product/README.md)

**Key Files:**
- `2026_01_13_PRODUCT_API.md` - Product management
- `2026_01_13_PRODUCT_VARIANTS_API.md` - Variant operations
- `2026_01_13_PRODUCT_BARCODES_API.md` - Barcode system

---

### **4. dispatch/** 📦🚚
Store-to-store product dispatch and transfer system

**Overview:**
- Inter-store product transfers with barcode tracking
- Mandatory barcode scanning at source and destination
- Real-time inventory updates across stores
- Complete audit trail of product movements

**Workflow:**
1. Source store creates dispatch and scans barcodes
2. Approval and shipment marking
3. Destination store receives and scans barcodes
4. Automatic inventory reconciliation

**Key Files:**
- `2026_01_16_DISPATCH_BARCODE_WORKFLOW.md` - Complete workflow guide with all APIs

---

### **5. integrations/** 🔌
Third-party service integration guides

**Pathao Courier:**
- `PATHAO_API_SETUP_GUIDE.md` - Initial setup & configuration
- `PATHAO_FRONTEND_COMPLETE_GUIDE.md` - Complete frontend guide
- `PATHAO_MULTI_STORE_SYSTEM.md` - Multi-store shipments
- `PATHAO_QUICK_START.md` - Quick reference

**Coverage:**
- API credentials setup
- Shipment creation
- Status tracking
- Multi-store handling
- Frontend implementation

---

### **4. guides/** 📖
Implementation guides and quick starts

**Frontend Integration:**
- `FRONTEND_INTEGRATION_COMPLETE.md` - Complete integration guide
- `SOCIAL_COMMERCE_MULTI_STORE.md` - Social commerce setup
- `MULTI_STORE_QUICK_START.md` - Multi-store quick start
- `MULTI_STORE_FULFILLMENT.md` - Order fulfillment guide

**Best For:**
- Onboarding new team members
- Understanding system workflows
- Step-by-step implementation
- Common use cases

---

### **6. fixes/** 🔧
Bug fixes, issue resolutions, and changelogs

**Recent Fixes:**
- `CSV_EXPORT_FIX.md` - CSV export issues resolved
- `STOCK_VALIDATION_CHANGELOG.md` - Stock validation changes
- `DISPATCH_BARCODE_FRONTEND_FIX.md` - Barcode tracking fix
- `CUSTOMER_ADDRESS_ISSUE_FIX.md` - Address handling fix
- `BARCODE_HISTORY_FIX.md` - Barcode history tracking
- `STORE_ID_ORDER_FIX.md` - Store assignment fix

**Also:**
- `GENERAL_FIXES_SUMMARY.md` - Overview of all fixes
- `FILE_CHANGES_LOG.md` - File-level change tracking
- `DISPATCH_BARCODE_INVESTIGATION.md` - Issue analysis

**Use When:**
- Debugging similar issues
- Understanding past problems
- Reviewing fix history
- QA validation

---

### **6. testing/** 🧪
Testing procedures and guides

**Contents:**
- `TESTING_GUIDE.md` - Comprehensive testing procedures

**Coverage:**
- API endpoint testing
- Feature testing scenarios
- Integration testing
- Test data setup

---

### **7. archive/** 📦
Historical and deprecated documentation

**Contents:**
- Old implementation summaries
- Requirements verification docs
- Migration records
- Deprecated features

**Note:** Reference only, may be outdated

---

## 🔍 Find Documentation

### **By Feature**

<details>
<summary><strong>Order Management</strong></summary>

- Order Tracking: `features/ORDER_TRACKING_API.md`
- Service Orders: `features/SERVICE_ORDERS_API.md`
- Installment Payments: `features/INSTALLMENT_EARLY_PAYOFF.md`
- Multi-Store Fulfillment: `guides/MULTI_STORE_FULFILLMENT.md`
</details>

<details>
<summary><strong>Inventory & Products</strong></summary>

- Barcode System: `features/DISPATCH_BARCODE_SYSTEM.md`
- Category Management: `features/CATEGORY_HARD_DELETE_API.md`
- Vendor Configuration: `features/VENDOR_OPTIONAL_FIELD.md`
- Stock Validation: `fixes/STOCK_VALIDATION_CHANGELOG.md`
</details>

<details>
<summary><strong>Financial Management</strong></summary>

- Accounting System: `accounting/ACCOUNTING_SYSTEM_COMPLETE_GUIDE.md`
- COGS Tracking: `accounting/FINANCIAL_METRICS_AND_COGS.md`
- Financial Reports: `accounting/FINANCIAL_REPORTS_API.md`
- Expense Receipts: `accounting/EXPENSE_RECEIPT_MANAGEMENT.md`
</details>

<details>
<summary><strong>Customer Management</strong></summary>

- Customer Registration: `features/PUBLIC_CUSTOMER_REGISTRATION_API.md`
- Customer Tags: `features/CUSTOMER_TAGS_API.md`
- Address Management: `fixes/CUSTOMER_ADDRESS_ISSUE_FIX.md`
</details>

<details>
<summary><strong>Shipping & Delivery</strong></summary>

- Pathao Setup: `integrations/PATHAO_API_SETUP_GUIDE.md`
- Pathao Frontend: `integrations/PATHAO_FRONTEND_COMPLETE_GUIDE.md`
- Multi-Store Shipping: `integrations/PATHAO_MULTI_STORE_SYSTEM.md`
</details>

<details>
<summary><strong>Tax System</strong></summary>

- Tax Configuration: `features/TAX_MODE_CONFIGURATION.md`
- Inclusive Tax: `features/TAX_SYSTEM_INCLUSIVE_MODE.md`
</details>

<details>
<summary><strong>System Features</strong></summary>

- Audit Logs: `features/BUSINESS_HISTORY_AUDIT_API.md`
- Lookup Data: `features/LOOKUP_API.md`
- Reports: `features/REPORTING_API.md`
- Dashboard: `features/DASHBOARD_STORES_SUMMARY_API.md`
</details>

---

## 📊 Documentation Statistics

- **Total Categories:** 7
- **Total Documents:** 50+
- **API Endpoints Documented:** 100+
- **Integration Guides:** 4
- **Fix Documents:** 9
- **Last Update:** January 8, 2026

---

## 🚀 Getting Started Paths

### **Path 1: New to the Project**
1. Read [guides/FRONTEND_INTEGRATION_COMPLETE.md](guides/FRONTEND_INTEGRATION_COMPLETE.md)
2. Browse [features/](features/) for specific APIs
3. Check [integrations/](integrations/) for external services

### **Path 2: Building a Feature**
1. Find feature doc in [features/](features/)
2. Check [fixes/](fixes/) for known issues
3. Review [guides/](guides/) for implementation patterns

### **Path 3: Debugging Issues**
1. Check [fixes/](fixes/) for similar problems
2. Review feature docs in [features/](features/)
3. Consult [testing/TESTING_GUIDE.md](testing/TESTING_GUIDE.md)

### **Path 4: Financial Features**
1. Start with [accounting/README.md](accounting/README.md)
2. Read [accounting/ACCOUNTING_QUICK_START.md](accounting/ACCOUNTING_QUICK_START.md)
3. Dive into specific docs as needed

---

## 🔗 Related Resources

### **Root Directory Files**
- `README.md` - Project main readme
- Various test files (`test_*.php`) - Test examples

### **Doc/ Folder**
- Contains original documentation (backup)
- May have additional implementation details
- Reference for historical context

---

## 📝 Documentation Standards

All documentation follows these standards:

✅ **Clear Structure:** Organized by purpose/category  
✅ **Descriptive Names:** File names indicate content  
✅ **API Examples:** Include request/response samples  
✅ **Error Handling:** Document error cases  
✅ **Frontend Guides:** Implementation examples  
✅ **Update Dates:** Track when docs were last updated

---

## 🆘 Need Help?

### **Can't Find What You Need?**

1. **Search in this README:** Use Ctrl+F
2. **Check folder READMEs:** Each category has a detailed index
3. **Browse by feature:** Look in [features/](features/)
4. **Check fixes:** Known issues in [fixes/](fixes/)

### **Documentation Issues?**

- Missing documentation? Create an issue
- Found outdated content? Report for update
- Need clarification? Ask the backend team

---

## 📅 Maintenance

**Review Schedule:** Monthly  
**Update Policy:** Update within 48h of major changes  
**Deprecated Docs:** Moved to [archive/](archive/)  
**Version Control:** All docs tracked in Git

---

**Maintained by:** Backend Development Team  
**Contact:** See project maintainers  
**Last Major Reorganization:** January 8, 2026
