# Expense Receipt Management Feature

## Overview
This feature allows users to upload, manage, and track receipt images for expenses. Each expense can have multiple receipts, with one designated as the primary receipt.

## Database Schema

### `expense_receipts` Table
| Field | Type | Description |
|-------|------|-------------|
| `id` | bigint | Primary key |
| `expense_id` | bigint | Foreign key to expenses table |
| `file_name` | string | Stored filename |
| `file_path` | string | Storage path |
| `file_extension` | string(10) | File extension (jpg, png, pdf) |
| `mime_type` | string(100) | MIME type |
| `file_size` | bigint | File size in bytes |
| `original_name` | string | Original uploaded filename |
| `uploaded_by` | bigint | Foreign key to employees table |
| `description` | text | Optional description |
| `is_primary` | boolean | Whether this is the primary receipt |
| `metadata` | json | Additional metadata |
| `created_at` | timestamp | Upload timestamp |
| `updated_at` | timestamp | Last update timestamp |
| `deleted_at` | timestamp | Soft delete timestamp |

## API Endpoints

### 1. Upload Receipt
**POST** `/api/expenses/{id}/receipts`

Upload a receipt image for an expense.

**Headers:**
- `Authorization: Bearer {token}`
- `Content-Type: multipart/form-data`

**Request Body:**
```
receipt: File (required) - Image or PDF file (max 5MB)
  - Allowed types: jpeg, jpg, png, pdf
description: String (optional) - Receipt description
is_primary: Boolean (optional, default: false) - Set as primary receipt
```

**Success Response (201):**
```json
{
  "success": true,
  "message": "Receipt uploaded successfully",
  "data": {
    "id": 1,
    "expense_id": 10,
    "file_name": "expense_10_1700140000_abc123.jpg",
    "file_path": "expense-receipts/expense_10_1700140000_abc123.jpg",
    "file_extension": "jpg",
    "mime_type": "image/jpeg",
    "file_size": 245678,
    "file_size_formatted": "239.92 KB",
    "original_name": "receipt_scan.jpg",
    "uploaded_by": 5,
    "description": "Vendor invoice receipt",
    "is_primary": true,
    "url": "/storage/expense-receipts/expense_10_1700140000_abc123.jpg",
    "created_at": "2025-11-16T10:30:00.000000Z",
    "uploaded_by_user": {
      "id": 5,
      "name": "John Doe",
      "email": "john@example.com"
    }
  }
}
```

**Error Response (422):**
```json
{
  "success": false,
  "errors": {
    "receipt": ["The receipt must be a file of type: jpeg, jpg, png, pdf."]
  }
}
```

---

### 2. Get All Receipts
**GET** `/api/expenses/{id}/receipts`

Get all receipts for an expense (primary receipt listed first).

**Headers:**
- `Authorization: Bearer {token}`

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "expense_id": 10,
      "file_name": "expense_10_1700140000_abc123.jpg",
      "is_primary": true,
      "file_size_formatted": "239.92 KB",
      "url": "/storage/expense-receipts/expense_10_1700140000_abc123.jpg",
      "description": "Vendor invoice receipt",
      "created_at": "2025-11-16T10:30:00.000000Z"
    },
    {
      "id": 2,
      "expense_id": 10,
      "file_name": "expense_10_1700141000_def456.pdf",
      "is_primary": false,
      "file_size_formatted": "1.2 MB",
      "url": "/storage/expense-receipts/expense_10_1700141000_def456.pdf",
      "description": "Supporting document",
      "created_at": "2025-11-16T10:45:00.000000Z"
    }
  ]
}
```

---

### 3. Delete Receipt
**DELETE** `/api/expenses/{id}/receipts/{receiptId}`

Delete a receipt and its file from storage.

**Headers:**
- `Authorization: Bearer {token}`

**Success Response (200):**
```json
{
  "success": true,
  "message": "Receipt deleted successfully"
}
```

**Error Response (404):**
```json
{
  "success": false,
  "message": "Receipt not found"
}
```

---

### 4. Set Primary Receipt
**PATCH** `/api/expenses/{id}/receipts/{receiptId}/set-primary`

Set a receipt as the primary receipt (unsets other primary receipts).

**Headers:**
- `Authorization: Bearer {token}`

**Success Response (200):**
```json
{
  "success": true,
  "message": "Receipt set as primary",
  "data": {
    "id": 2,
    "expense_id": 10,
    "is_primary": true,
    "updated_at": "2025-11-16T11:00:00.000000Z"
  }
}
```

---

### 5. Download Receipt
**GET** `/api/expenses/{id}/receipts/{receiptId}/download`

Download the original receipt file.

**Headers:**
- `Authorization: Bearer {token}`

**Success Response (200):**
- Returns file download with original filename
- Content-Type matches the file's MIME type
- Content-Disposition: attachment

**Error Response (404):**
```json
{
  "success": false,
  "message": "File not found"
}
```

---

## Model Relationships

### Expense Model
```php
// Get all receipts
$expense->receipts

// Get only primary receipt
$expense->primaryReceipt

// Example: Load expense with receipts
$expense = Expense::with('receipts')->find(1);
```

### ExpenseReceipt Model
```php
// Get parent expense
$receipt->expense

// Get uploader
$receipt->uploadedBy

// Get file URL
$receipt->url

// Get formatted file size
$receipt->file_size_formatted
```

## Usage Examples

### Frontend Upload Example (JavaScript/Axios)
```javascript
const formData = new FormData();
formData.append('receipt', fileInput.files[0]);
formData.append('description', 'Vendor invoice');
formData.append('is_primary', true);

axios.post(`/api/expenses/${expenseId}/receipts`, formData, {
  headers: {
    'Content-Type': 'multipart/form-data',
    'Authorization': `Bearer ${token}`
  }
})
.then(response => {
  console.log('Receipt uploaded:', response.data);
})
.catch(error => {
  console.error('Upload failed:', error.response.data);
});
```

### Backend PHP Example
```php
// Upload receipt programmatically
$expense = Expense::find(1);

// Create receipt record
$receipt = ExpenseReceipt::create([
    'expense_id' => $expense->id,
    'file_name' => 'receipt.jpg',
    'file_path' => 'expense-receipts/receipt.jpg',
    'file_extension' => 'jpg',
    'mime_type' => 'image/jpeg',
    'file_size' => 250000,
    'original_name' => 'original_receipt.jpg',
    'uploaded_by' => auth()->id(),
    'is_primary' => true,
]);

// Set as primary
$receipt->setPrimary();

// Delete file and record
$receipt->deleteFile();
$receipt->delete();
```

## File Storage

### Storage Location
- Files are stored in: `storage/app/public/expense-receipts/`
- Public URL: `/storage/expense-receipts/{filename}`

### Storage Configuration
Ensure the storage link exists:
```bash
php artisan storage:link
```

### File Naming Convention
```
expense_{expense_id}_{timestamp}_{unique_id}.{extension}
```
Example: `expense_10_1700140000_abc123.jpg`

## Validation Rules

### File Validation
- **Max size:** 5MB (5120 KB)
- **Allowed types:** jpeg, jpg, png, pdf
- **MIME types:** image/jpeg, image/png, application/pdf

### Field Validation
- `receipt` (required): File meeting above criteria
- `description` (optional): String, max 500 characters
- `is_primary` (optional): Boolean

## Features

✅ **Multiple Receipts**: Upload multiple receipt images per expense  
✅ **Primary Receipt**: Designate one receipt as primary  
✅ **Soft Delete**: Deleted receipts can be recovered (7 days)  
✅ **Auto Cleanup**: Physical files deleted when receipt record deleted  
✅ **File Metadata**: Track size, type, uploader, upload date  
✅ **Download Support**: Download original files  
✅ **Security**: Only authenticated users can upload/access  
✅ **Audit Trail**: Track who uploaded each receipt

## Security Considerations

1. **Authentication Required**: All endpoints require valid JWT token
2. **File Type Validation**: Only images and PDFs allowed
3. **Size Limits**: 5MB maximum to prevent abuse
4. **Path Traversal Protection**: Files stored with secure naming
5. **Access Control**: Users can only access receipts for expenses they have permission to view

## Error Handling

### Common Errors
| Error Code | Cause | Solution |
|------------|-------|----------|
| 422 | Invalid file type | Upload jpeg, jpg, png, or pdf only |
| 422 | File too large | Reduce file size to under 5MB |
| 404 | Expense not found | Verify expense ID exists |
| 404 | Receipt not found | Verify receipt belongs to expense |
| 500 | Storage failure | Check storage permissions |

## Testing Checklist

- [ ] Upload receipt with valid file
- [ ] Upload with invalid file type (should fail)
- [ ] Upload file over 5MB (should fail)
- [ ] Set receipt as primary
- [ ] Upload multiple receipts
- [ ] Download receipt
- [ ] Delete receipt (verify file deleted from storage)
- [ ] Get all receipts for expense
- [ ] Primary receipt shows first in list

## Database Migration

Run migration:
```bash
php artisan migrate
```

Rollback:
```bash
php artisan migrate:rollback
```

## Notes
- Receipts are automatically deleted from storage when the record is deleted
- Primary receipts are displayed first in the list
- File storage uses Laravel's public disk
- Soft deletes allow recovery within 7 days
