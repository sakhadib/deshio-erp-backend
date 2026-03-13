# Return and Exchange API Documentation

Date: 2026-03-13
Audience: PM, Frontend Developers, QA
Scope: Backend APIs for Return, Cross-Store Return, Inventory Reintegration, Exchange Linkage

## 1. Business Rules Implemented

1. Only barcode-tracked sold units are returnable.
2. Return can be received at a different store than purchase store.
3. If receiving store does not have exact batch, backend creates the batch at receiving store using same batch number and pricing metadata.
4. There is no in_return phase for accepted returns. On approval, returned unit is immediately restored to receiving store stock and barcode status becomes sellable.
5. Exchange is implemented as return plus new purchase. Backend provides an explicit endpoint to link a completed/approved return with a replacement order.

## 2. Workflow Summary

1. Create return request.
2. Record quality check.
3. Approve return.
4. Inventory and barcode are auto-restored immediately on approval.
5. Optional process endpoint can still be called for backward compatibility (idempotent inventory behavior).
6. Complete return.
7. Optional refund flow.
8. Optional exchange linking with a new order.

## 3. Endpoints

Base path: /api

### 3.1 Create Return

Method: POST
Path: /returns
Permission: returns.create

Request Body

- order_id: integer, required
- received_at_store_id: integer, optional
- return_reason: enum, required
  - defective_product
  - wrong_item
  - not_as_described
  - customer_dissatisfaction
  - size_issue
  - color_issue
  - quality_issue
  - late_delivery
  - changed_mind
  - duplicate_order
  - other
- return_type: enum, optional
  - customer_return
  - store_return
  - warehouse_return
- items: array, required, min 1
  - order_item_id: integer, required
  - quantity: integer, required, min 1
  - reason: string, optional
- customer_notes: string, optional
- attachments: array, optional

Behavior

1. Validates order and item ownership.
2. Enforces barcode-tracked returnability by resolving sold barcode units for each returned item.
3. Rejects non-barcode return attempts.
4. Stores resolved barcode ids and barcode numbers in return item payload.

Success Response

- success: true
- message: Return created successfully
- data: return object

Failure Response

- success: false
- message: validation or business-rule failure message

### 3.2 Update Return (Quality Input)

Method: PATCH
Path: /returns/{id}
Permission: returns.process

Request Body

- quality_check_passed: boolean, optional
- quality_check_notes: string, optional
- internal_notes: string, optional
- processing_fee: numeric, optional
- total_refund_amount: numeric, optional

Success Response

- success: true
- message: Return updated successfully
- data: return object

### 3.3 Approve Return (Auto Inventory Reintegration)

Method: POST
Path: /returns/{id}/approve
Permission: returns.approve

Request Body

- total_refund_amount: numeric, optional
- processing_fee: numeric, optional
- internal_notes: string, optional

Behavior

1. Requires pending status and successful quality check.
2. Changes status to approved.
3. Immediately restores inventory in received_at_store_id (or original store if not provided).
4. Cross-store handling:
   - If same store: increment original batch quantity.
   - If different store: create/find exact batch in receiving store by product_id + batch_number, then increment quantity.
5. Moves returned barcode(s) to receiving store.
6. Sets barcode status to in_warehouse (sellable lifecycle).
7. Sets barcode is_active to true.
8. Writes product movement records.

Success Response

- success: true
- message: Return approved successfully
- data: return object

Failure Response

- success: false
- message: approval or restore failure message

### 3.4 Process Return (Backward-Compatible)

Method: POST
Path: /returns/{id}/process
Permission: returns.process

Request Body

- restore_inventory: boolean, optional, default true

Behavior

1. Keeps endpoint compatibility with existing FE flows.
2. Inventory restoration is idempotent and does not double-increment if already restored on approval.
3. Transitions approved to processing.

Success Response

- success: true
- message: Return processed successfully
- data: return object

### 3.5 Complete Return

Method: POST
Path: /returns/{id}/complete
Permission: returns.process

Behavior

1. Transitions processing to completed.
2. For defect-related return reasons, auto-marks returned inventory barcode(s) as defective from receiving store inventory.

Success Response

- success: true
- message: Return completed successfully. Ready for refund.
- data: return object
- marked_as_defective: array
- failed_to_mark: array

### 3.6 Link Exchange (Return Plus New Purchase)

Method: POST
Path: /returns/{id}/exchange
Permission: returns.process

Request Body

- new_order_id: integer, required
- notes: string, optional

Behavior

1. Validates return in approved or later status.
2. Validates replacement order belongs to same customer.
3. Rejects cancelled replacement order.
4. Records exchange linkage in return status_history and internal_notes.
5. This endpoint does not mutate return_type database enum to avoid schema incompatibility.

Success Response

- success: true
- message: Exchange linked successfully (return + new purchase).
- data:
  - return_id
  - return_number
  - new_order_id
  - new_order_number

Failure Response

- success: false
- message: linkage failure reason

## 4. Inventory and Barcode State Rules

### 4.1 Sellable Barcode Statuses

A barcode is sellable when all conditions are true:

1. is_active = true
2. is_defective = false
3. current_status in:
   - in_warehouse
   - in_shop
   - on_display

### 4.2 Return Reintegration Status

For accepted return, returned barcode status is set to:

- in_warehouse

No temporary in_return lifecycle is used in new acceptance flow.

### 4.3 Cross-Store Stock Rule

For Store A purchase and Store B return:

1. Determine original batch from return item.
2. If Store B has no same batch_number for that product, create batch in Store B.
3. Increment Store B batch quantity.
4. Move barcode current_store_id to Store B.
5. Rebind barcode batch_id to new Store B batch when cross-store batch was created.

## 5. FE Integration Guidance

### 5.1 Required FE Sequence for Return

1. Create return.
2. Submit quality check through patch endpoint.
3. Approve return.
4. Refresh stock and barcode views immediately after approval.
5. Optional process call for legacy flow compatibility.
6. Complete return.
7. Create/complete refund if required.

### 5.2 Required FE Sequence for Exchange

1. Perform return flow until approved/completed.
2. Create replacement purchase order through existing order APIs.
3. Call exchange-link endpoint with new_order_id.
4. Display exchange linkage from return status_history.

### 5.3 UI Verification Checklist

1. Returned barcode appears as sellable after approval.
2. Barcode scan returns is_available true for non-defective accepted return.
3. Cross-store return creates/updates stock in receiving store.
4. Exchange card shows both return number and replacement order number.

## 6. Edge Cases and Expected Outcomes

1. Non-barcode order item return attempt:
   - Expected: rejected with business error.
2. Cross-store return with missing batch at receiving store:
   - Expected: batch auto-created and quantity incremented.
3. Duplicate inventory restore via process after approve:
   - Expected: no duplicate quantity increase.
4. Defect-related completion:
   - Expected: returned barcode moved into defective flow.
5. Exchange linkage with different-customer order:
   - Expected: rejected.
6. Exchange linkage with cancelled order:
   - Expected: rejected.

## 7. QA Test Notes (Executed)

Validation script executed:

- rouge/test_return_exchange_requirements.php

Result:

- 17 passed
- 0 failed
- test writes rolled back

Test coverage includes:

1. Cross-store return acceptance and stock restoration
2. Auto batch creation in receiving store
3. Immediate sellable barcode state on approval
4. Idempotent process endpoint behavior
5. Exchange linkage endpoint
6. Non-barcode return blocking

## 8. API Error Handling Patterns

All endpoints return JSON with:

1. success: boolean
2. message: human-readable error/success description
3. data: payload when successful

Validation failures use standard 422 format where applicable.
Business and processing failures return success false with detailed message.

## 9. Backward Compatibility Notes

1. Existing process endpoint remains available.
2. Inventory restoration now happens on approve; process is idempotent.
3. Exchange linkage is additive and does not break existing return/refund APIs.

## 10. Changed Backend Components

1. app/Http/Controllers/ProductReturnController.php
2. app/Models/ProductBarcode.php
3. routes/api.php

## 11. PM Sign-off Checklist

1. Store A purchase can be returned at Store B.
2. Accepted return immediately reflects in receiving store stock.
3. Returned barcode is sellable without in_return intermediate state.
4. Exchange linkage is available as return plus replacement purchase.
5. FE has endpoint sequence and payload definitions to proceed.
