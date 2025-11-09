# JWT Authentication Test Guide

## Prerequisites
Ensure you have:
1. JWT_SECRET set in your `.env` file
2. Database migrated with employees table
3. At least one employee/store in the database

## Generate JWT Secret (if not done)
```bash
php artisan jwt:secret
```

This will add `JWT_SECRET=...` to your `.env` file.

## Test Endpoints

### 1. Register/Signup (POST /api/signup)
```bash
# PowerShell
$headers = @{
    "Content-Type" = "application/json"
    "Accept" = "application/json"
}

$body = @{
    name = "Test Employee"
    email = "test@example.com"
    password = "password123"
    password_confirmation = "password123"
    store_id = 1
    phone = "+1234567890"
    department = "Sales"
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://localhost:8000/api/signup" -Method Post -Headers $headers -Body $body
```

**Expected Response:**
```json
{
  "message": "Employee registered successfully",
  "employee": {
    "id": 1,
    "name": "Test Employee",
    "email": "test@example.com",
    "store_id": 1,
    ...
  },
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "bearer",
  "expires_in": 3600
}
```

### 2. Login (POST /api/login)
```bash
# PowerShell
$headers = @{
    "Content-Type" = "application/json"
    "Accept" = "application/json"
}

$body = @{
    email = "test@example.com"
    password = "password123"
} | ConvertTo-Json

$response = Invoke-RestMethod -Uri "http://localhost:8000/api/login" -Method Post -Headers $headers -Body $body
$token = $response.access_token
Write-Host "Token: $token"
```

**Expected Response:**
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "bearer",
  "expires_in": 3600
}
```

### 3. Get Current User (GET /api/me)
```bash
# PowerShell (use token from login)
$headers = @{
    "Content-Type" = "application/json"
    "Accept" = "application/json"
    "Authorization" = "Bearer $token"
}

Invoke-RestMethod -Uri "http://localhost:8000/api/me" -Method Get -Headers $headers
```

**Expected Response:**
```json
{
  "id": 1,
  "name": "Test Employee",
  "email": "test@example.com",
  "store_id": 1,
  "role_id": null,
  "is_active": true,
  ...
}
```

### 4. Refresh Token (POST /api/refresh)
```bash
# PowerShell
$headers = @{
    "Content-Type" = "application/json"
    "Accept" = "application/json"
    "Authorization" = "Bearer $token"
}

$response = Invoke-RestMethod -Uri "http://localhost:8000/api/refresh" -Method Post -Headers $headers
$newToken = $response.access_token
Write-Host "New Token: $newToken"
```

**Expected Response:**
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "bearer",
  "expires_in": 3600
}
```

### 5. Logout (POST /api/logout)
```bash
# PowerShell
$headers = @{
    "Content-Type" = "application/json"
    "Accept" = "application/json"
    "Authorization" = "Bearer $token"
}

Invoke-RestMethod -Uri "http://localhost:8000/api/logout" -Method Post -Headers $headers
```

**Expected Response:**
```json
{
  "message": "Successfully logged out"
}
```

### 6. Test Protected Route (GET /api/employees)
```bash
# PowerShell - Without token (should fail)
$headers = @{
    "Content-Type" = "application/json"
    "Accept" = "application/json"
}

Invoke-RestMethod -Uri "http://localhost:8000/api/employees" -Method Get -Headers $headers
```

**Expected Response:** 401 Unauthorized

```bash
# PowerShell - With token (should succeed)
$headers = @{
    "Content-Type" = "application/json"
    "Accept" = "application/json"
    "Authorization" = "Bearer $token"
}

Invoke-RestMethod -Uri "http://localhost:8000/api/employees" -Method Get -Headers $headers
```

**Expected Response:** List of employees

## Common Issues & Solutions

### Issue 1: "JWT_SECRET not set"
**Solution:**
```bash
php artisan jwt:secret
```

### Issue 2: "Unauthenticated" on protected routes
**Causes:**
- Token not included in Authorization header
- Token expired (default: 60 minutes)
- Invalid token format

**Solution:**
```bash
# Get new token
$response = Invoke-RestMethod -Uri "http://localhost:8000/api/login" -Method Post -Headers $headers -Body $body
$token = $response.access_token

# Use Bearer prefix
"Authorization" = "Bearer $token"
```

### Issue 3: "Token has expired"
**Solution:**
```bash
# Refresh the token
$headers = @{
    "Authorization" = "Bearer $token"
}
$response = Invoke-RestMethod -Uri "http://localhost:8000/api/refresh" -Method Post -Headers $headers
$token = $response.access_token
```

### Issue 4: "The token has been blacklisted"
**Solution:**
Login again to get a new token:
```bash
$response = Invoke-RestMethod -Uri "http://localhost:8000/api/login" -Method Post -Headers $headers -Body $body
```

## Full Test Script

Save this as `test-jwt-auth.ps1`:

```powershell
# JWT Authentication Test Script

$baseUrl = "http://localhost:8000/api"

# Test 1: Login
Write-Host "`n=== Testing Login ===" -ForegroundColor Green
$loginBody = @{
    email = "test@example.com"
    password = "password123"
} | ConvertTo-Json

try {
    $loginResponse = Invoke-RestMethod -Uri "$baseUrl/login" -Method Post `
        -Headers @{"Content-Type"="application/json"; "Accept"="application/json"} `
        -Body $loginBody
    
    $token = $loginResponse.access_token
    Write-Host "✓ Login successful" -ForegroundColor Green
    Write-Host "Token: $($token.Substring(0, 20))..." -ForegroundColor Cyan
} catch {
    Write-Host "✗ Login failed: $($_.Exception.Message)" -ForegroundColor Red
    exit
}

# Test 2: Get current user
Write-Host "`n=== Testing Get Current User ===" -ForegroundColor Green
try {
    $meResponse = Invoke-RestMethod -Uri "$baseUrl/me" -Method Get `
        -Headers @{
            "Content-Type"="application/json"
            "Accept"="application/json"
            "Authorization"="Bearer $token"
        }
    
    Write-Host "✓ User info retrieved successfully" -ForegroundColor Green
    Write-Host "User: $($meResponse.name) ($($meResponse.email))" -ForegroundColor Cyan
} catch {
    Write-Host "✗ Failed to get user info: $($_.Exception.Message)" -ForegroundColor Red
}

# Test 3: Access protected route
Write-Host "`n=== Testing Protected Route ===" -ForegroundColor Green
try {
    $employeesResponse = Invoke-RestMethod -Uri "$baseUrl/employees" -Method Get `
        -Headers @{
            "Content-Type"="application/json"
            "Accept"="application/json"
            "Authorization"="Bearer $token"
        }
    
    Write-Host "✓ Protected route accessed successfully" -ForegroundColor Green
} catch {
    Write-Host "✗ Failed to access protected route: $($_.Exception.Message)" -ForegroundColor Red
}

# Test 4: Refresh token
Write-Host "`n=== Testing Token Refresh ===" -ForegroundColor Green
try {
    $refreshResponse = Invoke-RestMethod -Uri "$baseUrl/refresh" -Method Post `
        -Headers @{
            "Content-Type"="application/json"
            "Accept"="application/json"
            "Authorization"="Bearer $token"
        }
    
    $newToken = $refreshResponse.access_token
    Write-Host "✓ Token refreshed successfully" -ForegroundColor Green
    Write-Host "New Token: $($newToken.Substring(0, 20))..." -ForegroundColor Cyan
    $token = $newToken
} catch {
    Write-Host "✗ Failed to refresh token: $($_.Exception.Message)" -ForegroundColor Red
}

# Test 5: Logout
Write-Host "`n=== Testing Logout ===" -ForegroundColor Green
try {
    $logoutResponse = Invoke-RestMethod -Uri "$baseUrl/logout" -Method Post `
        -Headers @{
            "Content-Type"="application/json"
            "Accept"="application/json"
            "Authorization"="Bearer $token"
        }
    
    Write-Host "✓ Logout successful" -ForegroundColor Green
    Write-Host "Message: $($logoutResponse.message)" -ForegroundColor Cyan
} catch {
    Write-Host "✗ Logout failed: $($_.Exception.Message)" -ForegroundColor Red
}

# Test 6: Try to use token after logout (should fail)
Write-Host "`n=== Testing Blacklisted Token ===" -ForegroundColor Green
try {
    Invoke-RestMethod -Uri "$baseUrl/me" -Method Get `
        -Headers @{
            "Content-Type"="application/json"
            "Accept"="application/json"
            "Authorization"="Bearer $token"
        }
    
    Write-Host "✗ Token still works after logout (unexpected)" -ForegroundColor Red
} catch {
    Write-Host "✓ Token correctly blacklisted after logout" -ForegroundColor Green
}

Write-Host "`n=== All Tests Completed ===" -ForegroundColor Green
```

Run with:
```bash
.\test-jwt-auth.ps1
```

## Configuration Summary

### Files Modified:
1. ✅ `config/auth.php` - Uses JWT driver for 'api' guard
2. ✅ `config/jwt.php` - JWT configuration
3. ✅ `app/Models/Employee.php` - Implements JWTSubject
4. ✅ `app/Http/Controllers/AuthController.php` - Login, logout, refresh, me endpoints
5. ✅ `app/Http/Kernel.php` - JWT middleware registered
6. ✅ `routes/api.php` - Uses auth:api middleware

### Environment Variables:
Add to `.env`:
```env
JWT_SECRET=your-secret-key-here
JWT_TTL=60
JWT_REFRESH_TTL=20160
JWT_BLACKLIST_ENABLED=true
```
