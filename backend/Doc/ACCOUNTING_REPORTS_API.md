# Accounting Reports API - Textbook Style Financial Statements

## Overview
এই API গুলো **standard accounting textbook** অনুযায়ী financial statements provide করে। সব reports double-entry bookkeeping principles follow করে।

---

## 1. T-Account (Ledger Account)

**Textbook-style debit/credit ledger** for individual accounts.

### Endpoint
```
GET /api/accounting/t-account/{accountId}
```

### Query Parameters
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| date_from | date | No | Current month start | Period start date (YYYY-MM-DD) |
| date_to | date | No | Today | Period end date (YYYY-MM-DD) |

### Example Request
```
GET /api/accounting/t-account/1?date_from=2025-06-01&date_to=2025-06-30
```

### Example Response
```json
{
  "success": true,
  "data": {
    "account": {
      "id": 1,
      "code": "1010",
      "name": "Cash in Hand",
      "type": "asset",
      "category": "cash"
    },
    "period": {
      "from": "2025-06-01",
      "to": "2025-06-30"
    },
    "opening_balance": "50000.00",
    "debit_side": [
      {
        "date": "2025-06-01",
        "reference": "ORD-001",
        "description": "Cash sale",
        "amount": "5000.00",
        "balance": "55000.00"
      },
      {
        "date": "2025-06-05",
        "reference": "ORD-002",
        "description": "Cash sale",
        "amount": "3000.00",
        "balance": "58000.00"
      }
    ],
    "credit_side": [
      {
        "date": "2025-06-03",
        "reference": "EXP-001",
        "description": "Office rent payment",
        "amount": "15000.00",
        "balance": "43000.00"
      },
      {
        "date": "2025-06-10",
        "reference": "PO-001",
        "description": "Payment to vendor",
        "amount": "25000.00",
        "balance": "18000.00"
      }
    ],
    "totals": {
      "total_debits": "8000.00",
      "total_credits": "40000.00",
      "closing_balance": "18000.00"
    }
  }
}
```

### T-Account Format (Textbook Style)
```
┌─────────────────────────────────────────────────────────────────┐
│                    Cash in Hand (1010)                          │
│                   Period: 2025-06-01 to 2025-06-30              │
├──────────────────────────────┬──────────────────────────────────┤
│  DEBIT SIDE                  │  CREDIT SIDE                     │
├──────────────────────────────┼──────────────────────────────────┤
│ Opening Balance  50,000.00   │                                  │
│                              │                                  │
│ 2025-06-01 (ORD-001)         │ 2025-06-03 (EXP-001)             │
│ Cash sale        5,000.00    │ Office rent      15,000.00       │
│ Balance         55,000.00    │ Balance          43,000.00       │
│                              │                                  │
│ 2025-06-05 (ORD-002)         │ 2025-06-10 (PO-001)              │
│ Cash sale        3,000.00    │ Vendor payment   25,000.00       │
│ Balance         58,000.00    │ Balance          18,000.00       │
├──────────────────────────────┼──────────────────────────────────┤
│ Total           8,000.00     │ Total            40,000.00       │
│ Closing Balance 18,000.00    │                                  │
└──────────────────────────────┴──────────────────────────────────┘
```

---

## 2. Trial Balance

**Lists all accounts with their debit and credit balances** to verify accounting equation.

### Endpoint
```
GET /api/accounting/trial-balance
```

### Query Parameters
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| as_of_date | date | No | Today | Balance as of date (YYYY-MM-DD) |

### Example Request
```
GET /api/accounting/trial-balance?as_of_date=2025-06-30
```

### Example Response
```json
{
  "success": true,
  "data": {
    "title": "Trial Balance",
    "as_of_date": "2025-06-30",
    "accounts": [
      {
        "account_code": "1010",
        "account_name": "Cash in Hand",
        "account_type": "asset",
        "debit_balance": "50000.00",
        "credit_balance": "-",
        "raw_balance": 50000
      },
      {
        "account_code": "1020",
        "account_name": "Bank Account",
        "account_type": "asset",
        "debit_balance": "150000.00",
        "credit_balance": "-",
        "raw_balance": 150000
      },
      {
        "account_code": "2010",
        "account_name": "Accounts Payable",
        "account_type": "liability",
        "debit_balance": "-",
        "credit_balance": "75000.00",
        "raw_balance": -75000
      },
      {
        "account_code": "3010",
        "account_name": "Owner's Capital",
        "account_type": "equity",
        "debit_balance": "-",
        "credit_balance": "100000.00",
        "raw_balance": -100000
      },
      {
        "account_code": "4010",
        "account_name": "Sales Revenue",
        "account_type": "revenue",
        "debit_balance": "-",
        "credit_balance": "250000.00",
        "raw_balance": -250000
      },
      {
        "account_code": "5010",
        "account_name": "Cost of Goods Sold",
        "account_type": "expense",
        "debit_balance": "180000.00",
        "credit_balance": "-",
        "raw_balance": 180000
      },
      {
        "account_code": "5020",
        "account_name": "Rent Expense",
        "account_type": "expense",
        "debit_balance": "45000.00",
        "credit_balance": "-",
        "raw_balance": 45000
      }
    ],
    "totals": {
      "total_debits": "425000.00",
      "total_credits": "425000.00",
      "difference": "0.00",
      "is_balanced": true
    }
  }
}
```

### Trial Balance Format (Textbook Style)
```
┌─────────────────────────────────────────────────────────────────┐
│                      TRIAL BALANCE                              │
│                   As of June 30, 2025                           │
├───────┬─────────────────────────┬────────────┬─────────────────┤
│ Code  │ Account Name            │ Debit (৳)  │ Credit (৳)      │
├───────┼─────────────────────────┼────────────┼─────────────────┤
│ 1010  │ Cash in Hand            │ 50,000.00  │ -               │
│ 1020  │ Bank Account            │ 150,000.00 │ -               │
│ 2010  │ Accounts Payable        │ -          │ 75,000.00       │
│ 3010  │ Owner's Capital         │ -          │ 100,000.00      │
│ 4010  │ Sales Revenue           │ -          │ 250,000.00      │
│ 5010  │ Cost of Goods Sold      │ 180,000.00 │ -               │
│ 5020  │ Rent Expense            │ 45,000.00  │ -               │
├───────┴─────────────────────────┼────────────┼─────────────────┤
│ TOTAL                           │ 425,000.00 │ 425,000.00      │
└─────────────────────────────────┴────────────┴─────────────────┘
✓ Trial Balance is BALANCED
```

---

## 3. Income Statement (Profit & Loss)

**Shows revenue, expenses, and profit** for a period.

### Endpoint
```
GET /api/accounting/income-statement
```

### Query Parameters
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| date_from | date | No | Current month start | Period start date |
| date_to | date | No | Today | Period end date |

### Example Request
```
GET /api/accounting/income-statement?date_from=2025-06-01&date_to=2025-06-30
```

### Example Response
```json
{
  "success": true,
  "data": {
    "title": "Income Statement (Profit & Loss)",
    "period": {
      "from": "2025-06-01",
      "to": "2025-06-30"
    },
    "revenue": {
      "sales_revenue": "250000.00",
      "sales_count": 45
    },
    "cost_of_goods_sold": "180000.00",
    "gross_profit": {
      "amount": "70000.00",
      "margin_percentage": "28.00"
    },
    "operating_expenses": {
      "by_category": [
        {
          "category": "Rent",
          "total": 15000,
          "count": 1,
          "formatted_total": "15000.00"
        },
        {
          "category": "Salaries",
          "total": 30000,
          "count": 3,
          "formatted_total": "30000.00"
        },
        {
          "category": "Utilities",
          "total": 5000,
          "count": 2,
          "formatted_total": "5000.00"
        }
      ],
      "total": "50000.00"
    },
    "net_profit": {
      "amount": "20000.00",
      "margin_percentage": "8.00",
      "is_profit": true
    }
  }
}
```

### Income Statement Format (Textbook Style)
```
┌─────────────────────────────────────────────────────────────────┐
│                   INCOME STATEMENT                              │
│            For the Month Ended June 30, 2025                    │
├─────────────────────────────────────────────────────────────────┤
│ Revenue:                                                        │
│   Sales Revenue                                   250,000.00    │
│                                                                 │
│ Less: Cost of Goods Sold                         (180,000.00)   │
│                                                   ───────────    │
│ Gross Profit                                       70,000.00    │
│ Gross Profit Margin: 28.00%                                     │
│                                                                 │
│ Operating Expenses:                                             │
│   Rent Expense                                     15,000.00    │
│   Salaries Expense                                 30,000.00    │
│   Utilities Expense                                 5,000.00    │
│                                                   ───────────    │
│ Total Operating Expenses                          (50,000.00)   │
│                                                   ───────────    │
│ Net Profit                                         20,000.00    │
│ Net Profit Margin: 8.00%                          ═══════════   │
└─────────────────────────────────────────────────────────────────┘
```

---

## 4. Balance Sheet

**Shows Assets = Liabilities + Equity** at a specific date.

### Endpoint
```
GET /api/accounting/balance-sheet
```

### Query Parameters
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| as_of_date | date | No | Today | Balance sheet date |

### Example Request
```
GET /api/accounting/balance-sheet?as_of_date=2025-06-30
```

### Example Response
```json
{
  "success": true,
  "data": {
    "title": "Balance Sheet",
    "as_of_date": "2025-06-30",
    "assets": {
      "current_assets": {
        "cash_and_bank": {
          "breakdown": [
            {
              "account": "Cash in Hand",
              "balance": "50000.00"
            },
            {
              "account": "Bank Account - Brac",
              "balance": "150000.00"
            }
          ],
          "total": "200000.00"
        },
        "inventory": "180000.00",
        "accounts_receivable": "75000.00",
        "total_current_assets": "455000.00"
      },
      "total_assets": "455000.00"
    },
    "liabilities": {
      "current_liabilities": {
        "accounts_payable": "125000.00",
        "other_liabilities": {
          "breakdown": [
            {
              "account": "Short-term Loan",
              "balance": "50000.00"
            }
          ],
          "total": "50000.00"
        },
        "total_current_liabilities": "175000.00"
      },
      "total_liabilities": "175000.00"
    },
    "equity": {
      "owner_equity": {
        "breakdown": [
          {
            "account": "Owner's Capital",
            "balance": "200000.00"
          }
        ],
        "total": "200000.00"
      },
      "retained_earnings": "80000.00",
      "total_equity": "280000.00"
    },
    "total_liabilities_and_equity": "455000.00",
    "accounting_equation": {
      "assets": "455000.00",
      "liabilities_plus_equity": "455000.00",
      "difference": "0.00",
      "is_balanced": true
    }
  }
}
```

### Balance Sheet Format (Textbook Style)
```
┌─────────────────────────────────────────────────────────────────┐
│                      BALANCE SHEET                              │
│                   As of June 30, 2025                           │
├─────────────────────────────────────────────────────────────────┤
│ ASSETS                                                          │
│                                                                 │
│ Current Assets:                                                 │
│   Cash in Hand                                     50,000.00    │
│   Bank Account - Brac                            150,000.00    │
│                                                   ───────────    │
│   Total Cash & Bank                              200,000.00    │
│   Inventory                                      180,000.00    │
│   Accounts Receivable                             75,000.00    │
│                                                   ───────────    │
│ Total Current Assets                             455,000.00    │
│                                                   ───────────    │
│ TOTAL ASSETS                                     455,000.00    │
│                                                   ═══════════    │
│                                                                 │
│ LIABILITIES & EQUITY                                            │
│                                                                 │
│ Current Liabilities:                                            │
│   Accounts Payable                               125,000.00    │
│   Short-term Loan                                 50,000.00    │
│                                                   ───────────    │
│ Total Current Liabilities                        175,000.00    │
│                                                   ───────────    │
│ TOTAL LIABILITIES                                175,000.00    │
│                                                                 │
│ Equity:                                                         │
│   Owner's Capital                                200,000.00    │
│   Retained Earnings                               80,000.00    │
│                                                   ───────────    │
│ TOTAL EQUITY                                     280,000.00    │
│                                                   ───────────    │
│ TOTAL LIABILITIES & EQUITY                       455,000.00    │
│                                                   ═══════════    │
│                                                                 │
│ ✓ Accounting Equation Balanced:                                │
│   Assets (455,000) = Liabilities (175,000) + Equity (280,000)  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 5. Cash Flow Statement

**Shows cash inflows and outflows** by operating, investing, and financing activities.

### Endpoint
```
GET /api/accounting/cash-flow-statement
```

### Query Parameters
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| date_from | date | No | Current month start | Period start |
| date_to | date | No | Today | Period end |

### Example Request
```
GET /api/accounting/cash-flow-statement?date_from=2025-06-01&date_to=2025-06-30
```

### Example Response
```json
{
  "success": true,
  "data": {
    "title": "Cash Flow Statement",
    "period": {
      "from": "2025-06-01",
      "to": "2025-06-30"
    },
    "cash_flow_from_operating_activities": {
      "cash_received_from_customers": "250000.00",
      "cash_paid_to_vendors": "-150000.00",
      "cash_paid_for_expenses": "-50000.00",
      "net_cash_from_operations": "50000.00"
    },
    "cash_flow_from_investing_activities": {
      "net_cash_from_investing": "0.00"
    },
    "cash_flow_from_financing_activities": {
      "net_cash_from_financing": "0.00"
    },
    "net_increase_decrease_in_cash": "50000.00",
    "cash_summary": {
      "opening_cash": "150000.00",
      "net_change": "50000.00",
      "closing_cash": "200000.00"
    }
  }
}
```

### Cash Flow Statement Format (Textbook Style)
```
┌─────────────────────────────────────────────────────────────────┐
│                   CASH FLOW STATEMENT                           │
│            For the Month Ended June 30, 2025                    │
├─────────────────────────────────────────────────────────────────┤
│ Cash Flow from Operating Activities:                           │
│   Cash Received from Customers               250,000.00        │
│   Cash Paid to Vendors                      (150,000.00)       │
│   Cash Paid for Expenses                     (50,000.00)       │
│                                              ───────────        │
│   Net Cash from Operations                    50,000.00        │
│                                                                 │
│ Cash Flow from Investing Activities:                           │
│   Net Cash from Investing                          0.00        │
│                                                                 │
│ Cash Flow from Financing Activities:                           │
│   Net Cash from Financing                          0.00        │
│                                              ───────────        │
│ Net Increase in Cash                          50,000.00        │
│                                                                 │
│ Cash at Beginning of Period                  150,000.00        │
│ Net Change in Cash                            50,000.00        │
│                                              ───────────        │
│ Cash at End of Period                        200,000.00        │
│                                              ═══════════        │
└─────────────────────────────────────────────────────────────────┘
```

---

## 6. Cost Sheet

**Manufacturing/trading cost analysis** showing prime cost, works cost, and total cost.

### Endpoint
```
GET /api/accounting/cost-sheet
```

### Query Parameters
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| date_from | date | No | Current month start | Period start |
| date_to | date | No | Today | Period end |
| product_id | int | No | null | Specific product (optional) |

### Example Request
```
GET /api/accounting/cost-sheet?date_from=2025-06-01&date_to=2025-06-30
```

### Example Response
```json
{
  "success": true,
  "data": {
    "title": "Cost Sheet",
    "period": {
      "from": "2025-06-01",
      "to": "2025-06-30"
    },
    "units_sold": 450,
    "direct_costs": {
      "direct_material_cost": "180000.00",
      "direct_labor_cost": "0.00",
      "prime_cost": "180000.00"
    },
    "factory_overheads": "10000.00",
    "works_cost": "190000.00",
    "administrative_overheads": "30000.00",
    "cost_of_production": "220000.00",
    "selling_distribution_overheads": "10000.00",
    "total_cost_of_sales": "230000.00",
    "sales_revenue": "250000.00",
    "profit_loss": {
      "amount": "20000.00",
      "margin_percentage": "8.00",
      "is_profit": true
    },
    "per_unit_analysis": {
      "cost_per_unit": "511.11",
      "selling_price_per_unit": "555.56",
      "profit_per_unit": "44.44"
    }
  }
}
```

### Cost Sheet Format (Textbook Style)
```
┌─────────────────────────────────────────────────────────────────┐
│                        COST SHEET                               │
│            For the Month Ended June 30, 2025                    │
│                    Units Sold: 450                              │
├─────────────────────────────────────────────────────────────────┤
│ Direct Material Cost                         180,000.00        │
│ Direct Labor Cost                                  0.00        │
│                                              ───────────        │
│ PRIME COST                                   180,000.00        │
│                                                                 │
│ Add: Factory Overheads                        10,000.00        │
│                                              ───────────        │
│ WORKS COST                                   190,000.00        │
│                                                                 │
│ Add: Administrative Overheads                 30,000.00        │
│                                              ───────────        │
│ COST OF PRODUCTION                           220,000.00        │
│                                                                 │
│ Add: Selling & Distribution Overheads         10,000.00        │
│                                              ───────────        │
│ TOTAL COST OF SALES                          230,000.00        │
│                                                                 │
│ Sales Revenue                                250,000.00        │
│                                              ───────────        │
│ PROFIT                                        20,000.00        │
│ Profit Margin: 8.00%                         ═══════════        │
│                                                                 │
│ Per Unit Analysis:                                              │
│   Cost per Unit:          ৳511.11                               │
│   Selling Price per Unit: ৳555.56                               │
│   Profit per Unit:        ৳44.44                                │
└─────────────────────────────────────────────────────────────────┘
```

---

## 7. Journal Entries

**Double-entry journal entries** showing all transactions.

### Endpoint
```
GET /api/accounting/journal-entries
```

### Query Parameters
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| date_from | date | No | Current month start | Period start |
| date_to | date | No | Today | Period end |

### Example Request
```
GET /api/accounting/journal-entries?date_from=2025-06-01&date_to=2025-06-30
```

### Example Response
```json
{
  "success": true,
  "data": {
    "title": "Journal Entries",
    "period": {
      "from": "2025-06-01",
      "to": "2025-06-30"
    },
    "entries": [
      {
        "date": "2025-06-01",
        "reference": "ORD-001",
        "description": "Cash sale",
        "entries": [
          {
            "account_code": "1010",
            "account_name": "Cash in Hand",
            "debit": "5000.00",
            "credit": "-"
          },
          {
            "account_code": "4010",
            "account_name": "Sales Revenue",
            "debit": "-",
            "credit": "5000.00"
          }
        ],
        "totals": {
          "debit": "5000.00",
          "credit": "5000.00",
          "is_balanced": true
        }
      },
      {
        "date": "2025-06-03",
        "reference": "EXP-001",
        "description": "Office rent payment",
        "entries": [
          {
            "account_code": "5020",
            "account_name": "Rent Expense",
            "debit": "15000.00",
            "credit": "-"
          },
          {
            "account_code": "1010",
            "account_name": "Cash in Hand",
            "debit": "-",
            "credit": "15000.00"
          }
        ],
        "totals": {
          "debit": "15000.00",
          "credit": "15000.00",
          "is_balanced": true
        }
      }
    ],
    "total_entries": 2
  }
}
```

### Journal Entry Format (Textbook Style)
```
┌─────────────────────────────────────────────────────────────────┐
│                      JOURNAL ENTRIES                            │
│                Period: June 1-30, 2025                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│ Date: June 1, 2025                      Ref: ORD-001            │
│ Description: Cash sale                                          │
│ ┌─────────────────────────────────┬──────────┬────────────┐    │
│ │ Account                         │ Debit    │ Credit     │    │
│ ├─────────────────────────────────┼──────────┼────────────┤    │
│ │ Cash in Hand (1010)             │ 5,000.00 │            │    │
│ │   Sales Revenue (4010)          │          │ 5,000.00   │    │
│ └─────────────────────────────────┴──────────┴────────────┘    │
│                                      5,000.00   5,000.00  ✓     │
│                                                                 │
│ Date: June 3, 2025                      Ref: EXP-001            │
│ Description: Office rent payment                                │
│ ┌─────────────────────────────────┬──────────┬────────────┐    │
│ │ Account                         │ Debit    │ Credit     │    │
│ ├─────────────────────────────────┼──────────┼────────────┤    │
│ │ Rent Expense (5020)             │ 15,000.00│            │    │
│ │   Cash in Hand (1010)           │          │ 15,000.00  │    │
│ └─────────────────────────────────┴──────────┴────────────┘    │
│                                     15,000.00  15,000.00  ✓     │
└─────────────────────────────────────────────────────────────────┘
```

---

## Common Response Codes

| Code | Meaning | Description |
|------|---------|-------------|
| 200 | Success | Report generated successfully |
| 404 | Not Found | Account not found (for T-Account) |
| 422 | Validation Error | Invalid date format or parameters |
| 500 | Server Error | Internal error generating report |

---

## Notes for Frontend Developer

### 1. **Date Format**
- All dates should be in `YYYY-MM-DD` format
- Default period is current month if not specified

### 2. **Number Formatting**
- All amounts are returned as strings with 2 decimal places
- Use these directly in UI without reformatting
- Negative amounts shown with minus sign (not parentheses)

### 3. **Balanced Checks**
- `is_balanced` field indicates if accounting equation is satisfied
- Trial Balance: Debits must equal Credits
- Balance Sheet: Assets must equal Liabilities + Equity
- Journal Entries: Each entry must have equal debits and credits

### 4. **Raw vs Formatted**
- Most amounts are pre-formatted strings
- Some endpoints provide `raw_balance` for calculations
- Use formatted values for display, raw for computations

### 5. **Empty Results**
- If no data for period, arrays will be empty but response is still 200
- Check array length before rendering tables

### 6. **Textbook Styling**
- Responses follow standard accounting textbook format
- Debit always on left, Credit always on right
- Use dashes (-) for zero/empty amounts
- Show totals with separating lines (horizontal rules)

### 7. **Performance**
- Large date ranges may take longer
- Consider pagination for journal entries if needed
- Cache results for frequently accessed reports

---

## Example Frontend Usage (React/Vue)

### Fetching Trial Balance
```javascript
async function getTrialBalance(asOfDate = null) {
  const params = new URLSearchParams();
  if (asOfDate) params.append('as_of_date', asOfDate);
  
  const response = await fetch(`/api/accounting/trial-balance?${params}`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  });
  
  const result = await response.json();
  
  if (result.success) {
    console.log('Total Debits:', result.data.totals.total_debits);
    console.log('Total Credits:', result.data.totals.total_credits);
    console.log('Is Balanced:', result.data.totals.is_balanced);
    
    return result.data;
  }
}
```

### Rendering Income Statement
```javascript
function IncomeStatementTable({ data }) {
  return (
    <table className="income-statement">
      <thead>
        <tr>
          <th colSpan="2">Income Statement</th>
        </tr>
        <tr>
          <th colSpan="2">
            Period: {data.period.from} to {data.period.to}
          </th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Sales Revenue</td>
          <td className="amount">{data.revenue.sales_revenue}</td>
        </tr>
        <tr>
          <td>Less: Cost of Goods Sold</td>
          <td className="amount">({data.cost_of_goods_sold})</td>
        </tr>
        <tr className="subtotal">
          <td>Gross Profit</td>
          <td className="amount">{data.gross_profit.amount}</td>
        </tr>
        <tr>
          <td>Operating Expenses</td>
          <td className="amount">({data.operating_expenses.total})</td>
        </tr>
        <tr className="total">
          <td><strong>Net Profit</strong></td>
          <td className="amount">
            <strong>{data.net_profit.amount}</strong>
          </td>
        </tr>
      </tbody>
    </table>
  );
}
```

---

## Troubleshooting

### Q: Trial Balance not balanced?
**A:** Check if all transactions have proper double-entry (equal debit and credit). Use journal entries endpoint to verify.

### Q: Balance Sheet assets ≠ liabilities + equity?
**A:** Ensure all transactions are completed status. Pending transactions are not included.

### Q: Negative cash balance?
**A:** Check if expense/payment transactions are recorded correctly. Credits should reduce cash.

### Q: Missing accounts in Trial Balance?
**A:** Accounts with zero balance are excluded. Set `include_zero_balances=true` query parameter (if implemented).

---

## Summary

এই APIs সব **standard accounting principles** follow করে:
- ✅ Double-entry bookkeeping
- ✅ Debit = Credit balance
- ✅ Assets = Liabilities + Equity
- ✅ Textbook formatting
- ✅ Proper date handling
- ✅ Accurate calculations

**Frontend developer কে বলো যে এখন সব data correct format এ আছে!** 🎉
