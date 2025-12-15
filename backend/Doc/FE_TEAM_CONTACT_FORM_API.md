# Contact Form API Documentation for Frontend Team

## Overview
This document describes the Contact Form API endpoints for managing visitor contact messages. The system supports:
- **Public submission** - Visitors can submit messages without authentication
- **Admin management** - Staff can view, reply to, filter, and manage messages
- **Phone-based lookup** - View all messages from the same phone number
- **Status tracking** - Messages flow through: new → read → replied → archived

---

## Table of Contents
1. [Public Endpoints](#public-endpoints)
2. [Admin Endpoints](#admin-endpoints)
3. [Phone Lookup](#phone-lookup)
4. [Filtering & Search](#filtering--search)
5. [Response Formats](#response-formats)
6. [Status Flow](#status-flow)

---

## Public Endpoints

### 1. Submit Contact Message (No Auth Required)

**Endpoint:** `POST /api/contact-messages`

**Description:** Allows visitors to submit contact messages without authentication. Phone numbers are automatically cleaned (digits only).

**Request Body:**
```json
{
  "phone": "+880 1712-345678",
  "name": "John Doe",
  "message": "I'm interested in your products. Please contact me."
}
```

**Validation Rules:**
- `phone`: required, string, max 20 characters
- `name`: required, string, max 255 characters
- `message`: required, string, max 5000 characters

**Success Response (201):**
```json
{
  "success": true,
  "message": "Your message has been sent successfully. We will contact you soon.",
  "data": {
    "id": 1,
    "phone": "8801712345678",
    "name": "John Doe",
    "message": "I'm interested in your products. Please contact me.",
    "status": "new",
    "created_at": "2025-12-15T10:30:00.000000Z",
    "updated_at": "2025-12-15T10:30:00.000000Z"
  }
}
```

**Error Response (422):**
```json
{
  "success": false,
  "errors": {
    "phone": ["The phone field is required."],
    "name": ["The name field is required."],
    "message": ["The message field is required."]
  }
}
```

---

## Admin Endpoints

> **Note:** All admin endpoints require authentication. Include the JWT token in the Authorization header:
> ```
> Authorization: Bearer {token}
> ```

### 2. List All Messages (Admin)

**Endpoint:** `GET /api/contact-messages`

**Query Parameters:**
- `status` - Filter by status (new, read, replied, archived)
- `search` - Search in name, phone, or message
- `date_from` - Filter from date (YYYY-MM-DD)
- `date_to` - Filter to date (YYYY-MM-DD)
- `sort_by` - Sort field (created_at, status, phone) - default: created_at
- `sort_direction` - Sort direction (asc, desc) - default: desc
- `per_page` - Items per page - default: 15
- `page` - Page number

**Example Request:**
```
GET /api/contact-messages?status=new&search=john&sort_by=created_at&sort_direction=desc&per_page=20&page=1
```

**Success Response (200):**
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "phone": "8801712345678",
      "name": "John Doe",
      "message": "I'm interested in your products.",
      "status": "new",
      "admin_reply": null,
      "replied_at": null,
      "replied_by": null,
      "created_at": "2025-12-15T10:30:00.000000Z",
      "updated_at": "2025-12-15T10:30:00.000000Z",
      "deleted_at": null,
      "replied_by_employee": null
    }
  ],
  "first_page_url": "http://localhost/api/contact-messages?page=1",
  "from": 1,
  "last_page": 1,
  "last_page_url": "http://localhost/api/contact-messages?page=1",
  "links": [...],
  "next_page_url": null,
  "path": "http://localhost/api/contact-messages",
  "per_page": 15,
  "prev_page_url": null,
  "to": 1,
  "total": 1
}
```

### 3. View Single Message (Admin)

**Endpoint:** `GET /api/contact-messages/{id}`

**Description:** View a specific message. Automatically marks "new" messages as "read".

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "phone": "8801712345678",
    "name": "John Doe",
    "message": "I'm interested in your products.",
    "status": "read",
    "admin_reply": null,
    "replied_at": null,
    "replied_by": null,
    "created_at": "2025-12-15T10:30:00.000000Z",
    "updated_at": "2025-12-15T10:35:00.000000Z",
    "deleted_at": null,
    "replied_by_employee": null
  }
}
```

### 4. Update Message / Add Reply (Admin)

**Endpoint:** `PUT /api/contact-messages/{id}`

**Description:** Update message status or add admin reply. When `admin_reply` is provided, status is automatically set to "replied".

**Request Body:**
```json
{
  "status": "archived",
  "admin_reply": "Thank you for contacting us. We will get back to you shortly."
}
```

**Validation Rules:**
- `status`: optional, must be one of: new, read, replied, archived
- `admin_reply`: optional, string, max 5000 characters

**Success Response (200):**
```json
{
  "success": true,
  "message": "Message updated successfully",
  "data": {
    "id": 1,
    "phone": "8801712345678",
    "name": "John Doe",
    "message": "I'm interested in your products.",
    "status": "replied",
    "admin_reply": "Thank you for contacting us. We will get back to you shortly.",
    "replied_at": "2025-12-15T11:00:00.000000Z",
    "replied_by": 5,
    "created_at": "2025-12-15T10:30:00.000000Z",
    "updated_at": "2025-12-15T11:00:00.000000Z",
    "deleted_at": null,
    "replied_by_employee": {
      "id": 5,
      "name": "Admin User",
      "email": "admin@example.com"
    }
  }
}
```

### 5. Delete Message (Admin)

**Endpoint:** `DELETE /api/contact-messages/{id}`

**Description:** Soft delete a message (can be recovered from recycle bin).

**Success Response (200):**
```json
{
  "success": true,
  "message": "Message deleted successfully"
}
```

---

## Phone Lookup

### 6. Get Messages by Phone Number

**Endpoint:** `GET /api/contact-messages/by-phone`

**Query Parameters:**
- `phone` - Phone number (required) - supports any format
- `per_page` - Items per page - default: 15
- `page` - Page number

**Example Request:**
```
GET /api/contact-messages/by-phone?phone=+880-1712-345678
```

**Description:** Retrieves all messages from a specific phone number. Handles phone number formatting variations (e.g., "+880 1712-345678" or "8801712345678").

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 3,
        "phone": "8801712345678",
        "name": "John Doe",
        "message": "Follow-up question about pricing.",
        "status": "replied",
        "admin_reply": "Please check your email for pricing details.",
        "replied_at": "2025-12-15T12:00:00.000000Z",
        "replied_by": 5,
        "created_at": "2025-12-15T11:30:00.000000Z",
        "updated_at": "2025-12-15T12:00:00.000000Z",
        "deleted_at": null,
        "replied_by_employee": {
          "id": 5,
          "name": "Admin User"
        }
      },
      {
        "id": 1,
        "phone": "8801712345678",
        "name": "John Doe",
        "message": "I'm interested in your products.",
        "status": "replied",
        "admin_reply": "Thank you for contacting us.",
        "replied_at": "2025-12-15T11:00:00.000000Z",
        "replied_by": 5,
        "created_at": "2025-12-15T10:30:00.000000Z",
        "updated_at": "2025-12-15T11:00:00.000000Z",
        "deleted_at": null,
        "replied_by_employee": {
          "id": 5,
          "name": "Admin User"
        }
      }
    ],
    "total": 2
  }
}
```

---

## Statistics & Bulk Operations

### 7. Get Statistics (Admin)

**Endpoint:** `GET /api/contact-messages/statistics`

**Description:** Get message statistics for dashboard/analytics.

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "total_messages": 150,
    "new_messages": 12,
    "read_messages": 8,
    "replied_messages": 115,
    "archived_messages": 15,
    "today_messages": 5,
    "this_week_messages": 23,
    "this_month_messages": 87
  }
}
```

### 8. Bulk Update Status (Admin)

**Endpoint:** `POST /api/contact-messages/bulk-update-status`

**Description:** Update status for multiple messages at once.

**Request Body:**
```json
{
  "message_ids": [1, 2, 3, 4, 5],
  "status": "archived"
}
```

**Validation Rules:**
- `message_ids`: required, array of existing message IDs
- `status`: required, must be one of: new, read, replied, archived

**Success Response (200):**
```json
{
  "success": true,
  "message": "Messages updated successfully"
}
```

---

## Filtering & Search

### Search Capabilities

The `search` parameter in the list endpoint searches across:
- **Name** - Visitor's name
- **Phone** - Phone number
- **Message** - Message content

**Example:**
```
GET /api/contact-messages?search=product inquiry
```
This will return messages where "product inquiry" appears in name, phone, or message.

### Date Range Filtering

**Example:**
```
GET /api/contact-messages?date_from=2025-12-01&date_to=2025-12-15
```

### Combined Filters

**Example:**
```
GET /api/contact-messages?status=new&search=urgent&date_from=2025-12-10&sort_by=created_at&sort_direction=desc
```

---

## Response Formats

### Standard Success Response
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

### Pagination Response
```json
{
  "current_page": 1,
  "data": [...],
  "first_page_url": "...",
  "from": 1,
  "last_page": 5,
  "last_page_url": "...",
  "links": [...],
  "next_page_url": "...",
  "path": "...",
  "per_page": 15,
  "prev_page_url": null,
  "to": 15,
  "total": 75
}
```

### Error Response
```json
{
  "success": false,
  "errors": {
    "field_name": ["Error message"]
  }
}
```

---

## Status Flow

### Message Lifecycle

```
new → read → replied → archived
 ↓      ↓       ↓         ↓
 └──────┴───────┴─────────┘
    (Can transition to any status)
```

**Status Definitions:**
- **new** - Message just submitted, not yet viewed by admin
- **read** - Admin has opened/viewed the message
- **replied** - Admin has added a reply (auto-set when admin_reply is provided)
- **archived** - Message is completed/closed

**Automatic Status Changes:**
- Viewing a "new" message → automatically becomes "read"
- Adding admin_reply → automatically becomes "replied"

---

## Frontend Implementation Tips

### 1. Public Contact Form

```javascript
async function submitContactForm(formData) {
  const response = await fetch('http://your-api.com/api/contact-messages', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(formData)
  });
  
  const result = await response.json();
  return result;
}

// Usage
const formData = {
  phone: phoneInput.value,
  name: nameInput.value,
  message: messageInput.value
};

submitContactForm(formData)
  .then(result => {
    if (result.success) {
      alert(result.message);
    }
  });
```

### 2. Admin Message List with Filters

```javascript
async function getMessages(filters = {}) {
  const params = new URLSearchParams(filters);
  const token = localStorage.getItem('auth_token');
  
  const response = await fetch(`http://your-api.com/api/contact-messages?${params}`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });
  
  return await response.json();
}

// Usage
getMessages({
  status: 'new',
  search: 'urgent',
  per_page: 20,
  page: 1
}).then(data => {
  console.log(data.data); // Array of messages
  console.log(data.total); // Total count
});
```

### 3. Reply to Message

```javascript
async function replyToMessage(messageId, replyText) {
  const token = localStorage.getItem('auth_token');
  
  const response = await fetch(`http://your-api.com/api/contact-messages/${messageId}`, {
    method: 'PUT',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      admin_reply: replyText
    })
  });
  
  return await response.json();
}
```

### 4. Phone Lookup

```javascript
async function getMessagesByPhone(phone) {
  const token = localStorage.getItem('auth_token');
  
  const response = await fetch(`http://your-api.com/api/contact-messages/by-phone?phone=${encodeURIComponent(phone)}`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });
  
  return await response.json();
}

// Usage - Shows all messages from this customer
getMessagesByPhone('+880 1712-345678').then(result => {
  console.log(`Found ${result.data.total} messages from this number`);
  result.data.data.forEach(msg => {
    console.log(`${msg.created_at}: ${msg.message}`);
  });
});
```

### 5. Statistics Dashboard

```javascript
async function getStatistics() {
  const token = localStorage.getItem('auth_token');
  
  const response = await fetch('http://your-api.com/api/contact-messages/statistics', {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });
  
  return await response.json();
}

// Usage
getStatistics().then(result => {
  const stats = result.data;
  console.log(`New: ${stats.new_messages}`);
  console.log(`Today: ${stats.today_messages}`);
  console.log(`This Week: ${stats.this_week_messages}`);
});
```

---

## Common Use Cases

### Use Case 1: Display New Messages Badge
```javascript
getStatistics().then(result => {
  const newCount = result.data.new_messages;
  document.getElementById('new-messages-badge').textContent = newCount;
  if (newCount > 0) {
    document.getElementById('new-messages-badge').style.display = 'block';
  }
});
```

### Use Case 2: Message Inbox with Filters
```javascript
// Admin inbox with status tabs
function loadMessages(status) {
  getMessages({ 
    status: status, 
    sort_by: 'created_at', 
    sort_direction: 'desc' 
  }).then(data => {
    renderMessageList(data.data);
    renderPagination(data);
  });
}

// Tabs: All, New, Read, Replied, Archived
loadMessages('new'); // Show only new messages
```

### Use Case 3: View Customer History
```javascript
// When viewing a message, show all messages from same phone
function viewMessageWithHistory(messageId) {
  // Get the message
  fetch(`/api/contact-messages/${messageId}`)
    .then(res => res.json())
    .then(result => {
      const message = result.data;
      renderMessage(message);
      
      // Get all messages from this phone
      getMessagesByPhone(message.phone).then(history => {
        renderCustomerHistory(history.data.data);
      });
    });
}
```

### Use Case 4: Quick Reply Modal
```javascript
function showReplyModal(messageId) {
  const replyText = prompt('Enter your reply:');
  if (replyText) {
    replyToMessage(messageId, replyText).then(result => {
      if (result.success) {
        alert('Reply sent successfully!');
        refreshMessageList();
      }
    });
  }
}
```

---

## Testing Guide

### Test Public Submission
```bash
curl -X POST http://localhost/api/contact-messages \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "+880 1712-345678",
    "name": "Test User",
    "message": "This is a test message"
  }'
```

### Test Admin List (with Auth)
```bash
curl -X GET "http://localhost/api/contact-messages?status=new" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Test Phone Lookup
```bash
curl -X GET "http://localhost/api/contact-messages/by-phone?phone=8801712345678" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Test Reply
```bash
curl -X PUT http://localhost/api/contact-messages/1 \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "admin_reply": "Thank you for your message. We will contact you soon."
  }'
```

---

## Security Notes

1. **Public Endpoint** - The `POST /api/contact-messages` endpoint does NOT require authentication. Implement rate limiting on your frontend/server to prevent spam.

2. **Phone Number Privacy** - Phone numbers are stored as digits only (cleaned). Display formatting should be handled on the frontend.

3. **Soft Deletes** - Deleted messages can be recovered from the recycle bin for 7 days before permanent deletion.

4. **Admin Actions** - All admin actions (view, update, delete) require authentication with valid JWT token.

---

## Error Handling

### Common HTTP Status Codes
- **200** - Success
- **201** - Created (message submitted)
- **401** - Unauthorized (missing/invalid token)
- **404** - Not Found (message doesn't exist)
- **422** - Validation Error (invalid input)
- **500** - Server Error

### Handle Errors in Frontend
```javascript
async function submitContactForm(formData) {
  try {
    const response = await fetch('/api/contact-messages', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(formData)
    });
    
    const result = await response.json();
    
    if (!response.ok) {
      // Handle validation errors
      if (response.status === 422 && result.errors) {
        Object.keys(result.errors).forEach(field => {
          showFieldError(field, result.errors[field][0]);
        });
      }
      return;
    }
    
    // Success
    showSuccessMessage(result.message);
    
  } catch (error) {
    console.error('Network error:', error);
    showErrorMessage('Failed to submit message. Please try again.');
  }
}
```

---

## Database Schema Reference

**Table:** `contact_messages`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| phone | string | Phone number (digits only, indexed) |
| name | string | Visitor's name |
| message | text | Message content |
| status | enum | Status (new/read/replied/archived) |
| admin_reply | text | Admin's reply (nullable) |
| replied_at | timestamp | When admin replied (nullable) |
| replied_by | bigint | Employee ID who replied (nullable, FK to employees) |
| created_at | timestamp | When message was created |
| updated_at | timestamp | When message was last updated |
| deleted_at | timestamp | Soft delete timestamp (nullable) |

**Indexes:**
- `phone` - For quick phone lookup
- `status, created_at` - For efficient filtering and sorting

---

## Questions or Issues?

If you encounter any issues or have questions about the API:

1. Check the HTTP status code and error message
2. Verify your authentication token is valid
3. Ensure request body matches validation rules
4. Check API endpoint URL is correct

**Migration Status:** ✅ Completed on 2025-12-15

**Last Updated:** December 15, 2025
