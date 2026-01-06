# Public Customer Registration API

**Date**: January 7, 2026  
**Endpoint**: `POST /api/customer-registration`  
**Authentication**: None (Public endpoint)  
**Status**: ✅ Production Ready

## Overview

Public API endpoint for customer self-registration. No authentication required. Supports both minimal (name + phone) and complete registration with all customer fields including preferences, social profiles, and tags.

## Endpoint Details

```
POST http://localhost:8000/api/customer-registration
Content-Type: application/json
```

## Request Body

### Required Fields

| Field | Type | Validation | Description |
|-------|------|------------|-------------|
| `name` | string | max:255 | Customer full name |
| `phone` | string | unique | Phone number (must be unique) |

### Optional Fields

| Field | Type | Validation | Description |
|-------|------|------------|-------------|
| `email` | string | email, unique | Email address (must be unique if provided) |
| `password` | string | min:6 | Password (will be hashed with bcrypt) |
| `customer_type` | string | in:counter,social_commerce,ecommerce | Customer type (default: `ecommerce`) |
| `address` | string | - | Full address |
| `city` | string | max:100 | City name |
| `state` | string | max:100 | State/Division |
| `postal_code` | string | max:20 | Postal/ZIP code |
| `country` | string | max:100 | Country (default: `Bangladesh`) |
| `date_of_birth` | date | format:Y-m-d | Date of birth (e.g., 1995-05-15) |
| `gender` | string | in:male,female,other | Gender |
| `preferences` | object | JSON | Customer preferences (see below) |
| `social_profiles` | object | JSON | Social media profiles (see below) |
| `tags` | array | JSON | Customer tags array |
| `notes` | text | - | Additional notes |

### Automatic Fields

These fields are automatically generated and **should not be sent** in the request:

- `customer_code`: Auto-generated (format: CUST-XXXXXXXX)
- `status`: Automatically set to `active`
- `created_at`, `updated_at`: Timestamp fields

## Request Examples

### Minimal Registration

```json
{
  "name": "John Doe",
  "phone": "01712345678"
}
```

### Complete Registration

```json
{
  "name": "Jane Smith",
  "phone": "01812345678",
  "email": "jane.smith@example.com",
  "password": "securePass123",
  "customer_type": "ecommerce",
  "address": "House 42, Road 12, Dhanmondi",
  "city": "Dhaka",
  "state": "Dhaka Division",
  "postal_code": "1209",
  "country": "Bangladesh",
  "date_of_birth": "1990-08-15",
  "gender": "female",
  "preferences": {
    "newsletter": true,
    "sms_notifications": false,
    "preferred_language": "bn",
    "preferred_contact_method": "email"
  },
  "social_profiles": {
    "facebook": "facebook.com/janesmith",
    "instagram": "@janesmith",
    "twitter": "@janesmith"
  },
  "tags": ["premium", "early-adopter", "newsletter-subscriber"],
  "notes": "Registered from promotional campaign"
}
```

## Response Format

### Success Response (HTTP 201)

```json
{
  "id": 15,
  "name": "Jane Smith",
  "phone": "01812345678",
  "email": "jane.smith@example.com",
  "customer_code": "CUST-A1B2C3D4",
  "customer_type": "ecommerce",
  "status": "active",
  "address": "House 42, Road 12, Dhanmondi",
  "city": "Dhaka",
  "state": "Dhaka Division",
  "postal_code": "1209",
  "country": "Bangladesh",
  "date_of_birth": "1990-08-15",
  "gender": "female",
  "preferences": {
    "newsletter": true,
    "sms_notifications": false,
    "preferred_language": "bn",
    "preferred_contact_method": "email"
  },
  "social_profiles": {
    "facebook": "facebook.com/janesmith",
    "instagram": "@janesmith",
    "twitter": "@janesmith"
  },
  "tags": ["premium", "early-adopter", "newsletter-subscriber"],
  "notes": "Registered from promotional campaign",
  "total_purchases": 0.00,
  "total_orders": 0,
  "last_purchase_at": null,
  "first_purchase_at": null,
  "created_at": "2026-01-07T21:30:45.000000Z",
  "updated_at": "2026-01-07T21:30:45.000000Z"
}
```

**Note**: `password` and `remember_token` are hidden in responses for security.

### Validation Error Response (HTTP 422)

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "phone": ["The phone has already been taken."],
    "email": ["The email has already been taken."]
  }
}
```

### Server Error Response (HTTP 500)

```json
{
  "success": false,
  "message": "Registration failed",
  "error": "Detailed error message"
}
```

## Field Details

### Customer Type Options

- `counter`: Counter/in-store customer
- `social_commerce`: Social commerce customer
- `ecommerce`: E-commerce customer (default)

### Gender Options

- `male`
- `female`
- `other`

### Preferences Structure (Example)

```json
{
  "newsletter": true,
  "sms_notifications": false,
  "preferred_language": "bn",
  "preferred_contact_method": "email",
  "marketing_consent": true,
  "notification_frequency": "weekly"
}
```

### Social Profiles Structure (Example)

```json
{
  "facebook": "facebook.com/username",
  "instagram": "@username",
  "twitter": "@username",
  "linkedin": "linkedin.com/in/username",
  "whatsapp": "01712345678"
}
```

### Tags (Example)

```json
["vip", "premium", "early-adopter", "newsletter-subscriber", "event-registration"]
```

## Frontend Implementation Guide

### React/JavaScript Example

```javascript
const registerCustomer = async (formData) => {
  try {
    const response = await fetch('http://localhost:8000/api/customer-registration', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(formData)
    });

    const data = await response.json();

    if (response.status === 201) {
      console.log('Registration successful:', data);
      return { success: true, customer: data };
    } else if (response.status === 422) {
      console.error('Validation errors:', data.errors);
      return { success: false, errors: data.errors };
    } else {
      console.error('Registration failed:', data.message);
      return { success: false, message: data.message };
    }
  } catch (error) {
    console.error('Network error:', error);
    return { success: false, message: 'Network error occurred' };
  }
};

// Usage - Minimal
const result = await registerCustomer({
  name: "John Doe",
  phone: "01712345678"
});

// Usage - With all fields
const result = await registerCustomer({
  name: "Jane Smith",
  phone: "01812345678",
  email: "jane@example.com",
  password: "securePass123",
  customer_type: "ecommerce",
  address: "House 42, Road 12, Dhanmondi",
  city: "Dhaka",
  preferences: {
    newsletter: true,
    preferred_language: "bn"
  },
  tags: ["premium", "newsletter-subscriber"]
});
```

### Axios Example

```javascript
import axios from 'axios';

const API_BASE_URL = 'http://localhost:8000/api';

export const registerCustomer = async (customerData) => {
  try {
    const response = await axios.post(`${API_BASE_URL}/customer-registration`, customerData);
    return { success: true, customer: response.data };
  } catch (error) {
    if (error.response?.status === 422) {
      return { success: false, errors: error.response.data.errors };
    }
    return { 
      success: false, 
      message: error.response?.data?.message || 'Registration failed' 
    };
  }
};
```

## Validation Rules Summary

| Field | Rules |
|-------|-------|
| name | Required, max 255 characters |
| phone | Required, unique in database |
| email | Must be valid email format, unique if provided |
| password | Min 6 characters if provided |
| customer_type | Must be: counter, social_commerce, or ecommerce |
| gender | Must be: male, female, or other |
| date_of_birth | Must be valid date format (Y-m-d) |
| city, state | Max 100 characters |
| postal_code | Max 20 characters |
| country | Max 100 characters |

## Error Handling

### Common Validation Errors

1. **Duplicate Phone Number**
   ```json
   {
     "phone": ["The phone has already been taken."]
   }
   ```

2. **Duplicate Email**
   ```json
   {
     "email": ["The email has already been taken."]
   }
   ```

3. **Invalid Customer Type**
   ```json
   {
     "customer_type": ["The selected customer type is invalid."]
   }
   ```

4. **Short Password**
   ```json
   {
     "password": ["The password must be at least 6 characters."]
   }
   ```

### Form Validation Tips

1. **Phone Number**: Check for uniqueness on blur
2. **Email**: Validate format before submission
3. **Password**: Show strength indicator
4. **Customer Type**: Provide dropdown with valid options
5. **JSON Fields**: Validate structure before sending

## Testing

### Test with cURL

```bash
# Minimal registration
curl -X POST http://localhost:8000/api/customer-registration \
  -H "Content-Type: application/json" \
  -d '{"name":"Test User","phone":"01712345678"}'

# Complete registration
curl -X POST http://localhost:8000/api/customer-registration \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "phone": "01812345678",
    "email": "test@example.com",
    "password": "password123",
    "customer_type": "ecommerce",
    "city": "Dhaka",
    "preferences": {"newsletter": true},
    "tags": ["test", "new-customer"]
  }'
```

### Test with PowerShell

```powershell
$body = @{
    name = "Test User"
    phone = "01712345678"
    email = "test@example.com"
    password = "password123"
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://localhost:8000/api/customer-registration" `
  -Method POST `
  -Body $body `
  -ContentType "application/json"
```

## Security Features

✅ **Password Hashing**: All passwords are hashed using bcrypt ($2y$10$...)  
✅ **Unique Constraints**: Phone and email must be unique  
✅ **Input Validation**: All inputs validated before processing  
✅ **SQL Injection Protection**: Laravel Eloquent ORM prevents SQL injection  
✅ **Hidden Sensitive Fields**: Password never returned in responses  

## Notes for Frontend Team

1. **No Authentication Required**: This is a public endpoint, no JWT token needed
2. **Phone Uniqueness**: Consider adding real-time validation for phone number availability
3. **Customer Code**: Generated automatically, do not send in request
4. **JSON Fields**: `preferences`, `social_profiles`, and `tags` should be sent as proper JSON objects/arrays
5. **Password**: Optional during registration but recommended for full account functionality
6. **Default Values**: `customer_type` defaults to `ecommerce`, `country` defaults to `Bangladesh`
7. **Date Format**: Use YYYY-MM-DD format for `date_of_birth`

## Related APIs

- **Customer Login**: `POST /api/auth/login` (coming soon)
- **Customer Profile**: `GET /api/customer/profile` (requires auth)
- **Update Profile**: `PUT /api/customer/profile` (requires auth)

## Changelog

- **2026-01-07**: Initial release - Public customer registration endpoint created

## Support

For issues or questions, contact the backend team.
