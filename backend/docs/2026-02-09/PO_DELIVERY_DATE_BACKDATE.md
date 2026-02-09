# PO Expected Delivery Date - Backdate Allowed

## Overview
`expected_delivery_date` on PO create and edit now accepts any date (including past dates).

## API Changes

### Create PO
`POST /api/purchase-orders`

| Field | Validation Change |
|-------|-------------------|
| `expected_delivery_date` | `nullable|date` (was `nullable|date|after_or_equal:today`) |

### Update PO
`PUT /api/purchase-orders/{id}`

| Field | Validation |
|-------|------------|
| `expected_delivery_date` | `nullable|date` (no date restriction) |

## FE Notes
- Date picker for "Expected Delivery Date" should allow past dates
- No validation needed on FE to block past dates
