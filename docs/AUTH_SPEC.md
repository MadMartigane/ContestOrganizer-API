# JWT Authentication System - Technical Specification

## Table of Contents

1. [Overview](#overview)
2. [Authentication Flow](#authentication-flow)
3. [API Endpoints](#api-endpoints)
4. [JWT Token Structure](#jwt-token-structure)
5. [Token Validation](#token-validation)
6. [Token Blacklist](#token-blacklist)
7. [Reference Implementation](#reference-implementation)
8. [Security Considerations](#security-considerations)
9. [Error Codes](#error-codes)

---

## Overview

The authentication system uses **JWT (JSON Web Tokens)** with an **access + refresh token** pattern to provide secure, stateless authentication for the Contest Organizer API.

### Key Features

- **Access Token**: Short-lived token (1 hour) for API requests
- **Refresh Token**: Long-lived token (7 days) for obtaining new access tokens
- **Token Blacklisting**: Enables logout functionality by invalidating tokens
- **Role-Based Access Control (RBAC)**: Three user roles: `admin`, `organizer`, `spectator`

### Architecture Components

| Component | File | Responsibility |
|-----------|------|----------------|
| `AuthController` | `src/controllers/AuthController.php` | Handles login/logout HTTP requests |
| `JwtService` | `src/services/JwtService.php` | Token generation and validation |
| `AuthMiddleware` | `src/middleware/AuthMiddleware.php` | Request authentication and role checking |
| Database | SQLite (`token_blacklist`) | Token blacklist storage |

---

## Authentication Flow

### Login Flow

```
┌──────────┐                                    ┌─────────┐
│  Client  │                                    │   API   │
└────┬─────┘                                    └────┬────┘
     │                                                │
     │  1. POST /auth/login                           │
     │  { email, password }                          │
     │───────────────────────────────────────────────>│
     │                                                │
     │                                    2. Validate credentials
     │                                    3. Check user exists
     │                                    4. Verify password
     │                                                │
     │  5. Response: 200 OK                          │
     │  { access_token, refresh_token,               │
     │    expires_in, user }                         │
     │<───────────────────────────────────────────────│
     │                                                │
```

### Logout Flow

```
┌──────────┐                                    ┌─────────┐
│  Client  │                                    │   API   │
└────┬─────┘                                    └────┬────┘
     │                                                │
     │  1. POST /auth/logout                         │
     │  Authorization: Bearer <access_token>        │
     │───────────────────────────────────────────────>│
     │                                                │
     │                                    2. Validate token
     │                                    3. Extract jti
     │                                    4. Insert jti to blacklist
     │                                                │
     │  5. Response: 200 OK                          │
     │  { message }                                  │
     │<───────────────────────────────────────────────│
     │                                                │
```

### Protected Request Flow

```
┌──────────┐                                    ┌─────────┐
│  Client  │                                    │   API   │
└────┬─────┘                                    └────┬────┘
     │                                                │
     │  1. GET /api/protected-resource              │
     │  Authorization: Bearer <access_token>        │
     │───────────────────────────────────────────────>│
     │                                                │
     │                                    2. Extract Bearer token
     │                                    3. Validate signature
     │                                    4. Check expiration
     │                                    5. Check blacklist
     │                                    6. Extract user data
     │                                                │
     │  7. Response: 200 OK / 401 Unauthorized       │
     │<───────────────────────────────────────────────│
     │                                                │
```

---

## API Endpoints

### POST /auth/login

Authenticate user and receive JWT tokens.

**Request**

```http
POST /auth/login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "securepassword123"
}
```

**Success Response (200)**

```json
{
    "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "refresh_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "expires_in": 3600,
    "user": {
        "id": 1,
        "email": "user@example.com",
        "role": "admin"
    }
}
```

**Error Response (400)**

```json
{
    "error": {
        "message": "Email and password are required"
    }
}
```

**Error Response (401)**

```json
{
    "error": {
        "message": "Invalid credentials"
    }
}
```

### POST /auth/logout

Invalidate the current access token (add to blacklist).

**Request**

```http
POST /auth/logout
Authorization: Bearer <access_token>
Content-Type: application/json
```

**Success Response (200)**

```json
{
    "message": "Logged out successfully"
}
```

**Error Response (401)**

```json
{
    "error": {
        "message": "Unauthorized"
    }
}
```

---

## JWT Token Structure

### Algorithm & Configuration

| Parameter | Value |
|-----------|-------|
| Algorithm | HS256 (HMAC SHA-256) |
| Issuer | `contest-api` |
| Access Token TTL | 3600 seconds (1 hour) |
| Refresh Token TTL | 604800 seconds (7 days) |
| JTI Length | 32 hex characters (16 bytes) |

### Access Token Claims

```json
{
    "user_id": 1,
    "email": "user@example.com",
    "role": "admin",
    "jti": "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6",
    "iat": 1700000000,
    "exp": 1700003600,
    "iss": "contest-api"
}
```

| Claim | Type | Description |
|-------|------|-------------|
| `user_id` | integer | Unique user identifier |
| `email` | string | User email address |
| `role` | string | User role: `admin`, `organizer`, or `spectator` |
| `jti` | string | Unique token ID (32 hex chars) |
| `iat` | integer | Issued at timestamp (Unix epoch) |
| `exp` | integer | Expiration timestamp (Unix epoch) |
| `iss` | string | Issuer identifier |

### Refresh Token Claims

```json
{
    "user_id": 1,
    "jti": "p6o5n4m3l2k1j0i9h8g7f6e5d4c3b2a1",
    "type": "refresh",
    "iat": 1700000000,
    "exp": 1700600000,
    "iss": "contest-api"
}
```

| Claim | Type | Description |
|-------|------|-------------|
| `user_id` | integer | Unique user identifier |
| `jti` | string | Unique token ID (32 hex chars) |
| `type` | string | Token type identifier |
| `iat` | integer | Issued at timestamp |
| `exp` | integer | Expiration timestamp |
| `iss` | string | Issuer identifier |

---

## Token Validation

### Validation Steps

1. **Extract Token**
   - Read `Authorization` header
   - Verify `Bearer ` prefix exists
   - Extract token string after `Bearer `

2. **Verify Signature**
   - Decode JWT using `HS256` algorithm
   - Verify signature matches `JWT_SECRET`

3. **Check Expiration**
   - Compare `exp` claim with current timestamp
   - Reject if `exp <= current_time`

4. **Check Blacklist**
   - Query `token_blacklist` table for `jti`
   - Reject if token ID exists in blacklist

### Validation Pseudocode

```
function validateToken(token):
    // Step 1: Decode and verify signature
    decoded = decodeJWT(token, JWT_SECRET, "HS256")
    if decoded is null:
        return null

    // Step 2: Check expiration
    if decoded.exp <= currentTime():
        return null

    // Step 3: Check blacklist
    if isBlacklisted(decoded.jti):
        return null

    return decoded
```

---

## Token Blacklist

### Database Schema (SQLite)

```sql
CREATE TABLE IF NOT EXISTS token_blacklist (
    jti TEXT PRIMARY KEY,
    expires_at INTEGER NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_token_blacklist_expires_at 
ON token_blacklist(expires_at);
```

| Column | Type | Description |
|--------|------|-------------|
| `jti` | TEXT | Token unique identifier (primary key) |
| `expires_at` | INTEGER | Token expiration timestamp |

### Blacklist Operations

#### Add to Blacklist (Logout)

```sql
INSERT INTO token_blacklist (jti, expires_at) VALUES (?, ?)
```

#### Check Blacklist

```sql
SELECT 1 FROM token_blacklist WHERE jti = ?
```

#### Cleanup Expired Entries

```sql
DELETE FROM token_blacklist WHERE expires_at < ?
```

### Blacklist Lifecycle

```
Token Created
     │
     ▼
Token Valid (not expired)
     │
     ▼
User Logs Out ──────────► Insert jti to blacklist
     │                           │
     │                           ▼
     │                    Token Blacklisted
     │                           │
     ▼                           ▼
Token Expires ◄──────────  Validation Fails
                         (blacklist check fails)
     │
     ▼
Cleanup removes entry
(when expires_at < current_time)
```

---

## Reference Implementation

### Token Generation (PHP)

```php
<?php
use Firebase\JWT\JWT;

class JwtService {
    private const ACCESS_TOKEN_TTL = 3600;
    private const REFRESH_TOKEN_TTL = 604800;
    private const ALGORITHM = 'HS256';
    private const ISSUER = 'contest-api';
    
    private string $secret;
    
    public function __construct(string $secret) {
        $this->secret = $secret;
    }
    
    public function generateToken(array $userData): array {
        $now = time();
        
        // Access token
        $accessPayload = [
            'user_id' => $userData['user_id'],
            'email' => $userData['email'],
            'role' => $userData['role'],
            'jti' => bin2hex(random_bytes(16)),
            'iat' => $now,
            'exp' => $now + self::ACCESS_TOKEN_TTL,
            'iss' => self::ISSUER,
        ];
        
        // Refresh token
        $refreshPayload = [
            'user_id' => $userData['user_id'],
            'jti' => bin2hex(random_bytes(16)),
            'type' => 'refresh',
            'iat' => $now,
            'exp' => $now + self::REFRESH_TOKEN_TTL,
            'iss' => self::ISSUER,
        ];
        
        return [
            'access_token' => JWT::encode($accessPayload, $this->secret, self::ALGORITHM),
            'refresh_token' => JWT::encode($refreshPayload, $this->secret, self::ALGORITHM),
        ];
    }
}
```

### Token Validation (PHP)

```php
<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtService {
    private string $secret;
    private const ALGORITHM = 'HS256';
    
    public function validateToken(string $token): ?object {
        try {
            return JWT::decode($token, new Key($this->secret, self::ALGORITHM));
        } catch (Exception $e) {
            return null;
        }
    }
}
```

### Blacklist Check (PHP)

```php
<?php
function isTokenBlacklisted(PDO $db, string $jti): bool {
    $stmt = $db->prepare("SELECT 1 FROM token_blacklist WHERE jti = ?");
    $stmt->execute([$jti]);
    return $stmt->fetch() !== false;
}
```

### Login Endpoint (Pseudocode)

```python
def login(request):
    # 1. Parse request body
    body = json.loads(request.body)
    email = body.get('email')
    password = body.get('password')
    
    # 2. Validate input
    if not email or not password:
        return error(400, "Email and password are required")
    
    if not is_valid_email(email):
        return error(400, "Invalid email format")
    
    # 3. Find user in database
    user = database.find_user_by_email(email)
    if user is None:
        return error(401, "Invalid credentials")
    
    # 4. Verify password
    if not verify_password(password, user.password_hash):
        return error(401, "Invalid credentials")
    
    # 5. Generate tokens
    tokens = jwt_service.generate_token({
        'user_id': user.id,
        'email': user.email,
        'role': user.role,
    })
    
    # 6. Return response
    return json({
        'access_token': tokens['access_token'],
        'refresh_token': tokens['refresh_token'],
        'expires_in': 3600,
        'user': {
            'id': user.id,
            'email': user.email,
            'role': user.role,
        }
    })
```

### Logout Endpoint (Pseudocode)

```python
def logout(request):
    # 1. Extract token from Authorization header
    auth_header = request.headers.get('Authorization')
    if not auth_header.startswith('Bearer '):
        return error(401, "Unauthorized")
    
    token = auth_header[7:]  # Remove "Bearer " prefix
    
    # 2. Validate token
    decoded = jwt_service.validate_token(token)
    if decoded is None:
        return error(401, "Unauthorized")
    
    # 3. Check blacklist
    if database.is_blacklisted(decoded.jti):
        return error(401, "Unauthorized")
    
    # 4. Add to blacklist
    database.insert_blacklist(decoded.jti, decoded.exp)
    
    # 5. Return success
    return json({'message': 'Logged out successfully'})
```

### Protected Middleware (Pseudocode)

```python
def auth_middleware(request, next_handler):
    # 1. Extract token
    auth_header = request.headers.get('Authorization')
    if not auth_header or not auth_header.startswith('Bearer '):
        return error(401, "Invalid or missing authentication token")
    
    token = auth_header[7:]
    
    # 2. Validate token
    decoded = jwt_service.validate_token(token)
    if decoded is None:
        return error(401, "Invalid or missing authentication token")
    
    # 3. Check blacklist
    if database.is_blacklisted(decoded.jti):
        return error(401, "Invalid or missing authentication token")
    
    # 4. Attach user to request
    request.user = decoded
    
    # 5. Continue to handler
    return next_handler(request)
```

### Role-Based Access Control

```python
def require_role(*allowed_roles):
    def decorator(handler):
        def wrapped(request):
            if not hasattr(request, 'user') or request.user is None:
                return error(401, "Unauthorized")
            
            if request.user.role not in allowed_roles:
                return error(403, "Insufficient permissions")
            
            return handler(request)
        return wrapped
    return decorator

# Usage
@require_role('admin')
def delete_user(request):
    # Only admins can delete users
    pass
```

---

## Security Considerations

### JWT_SECRET Management

- **Minimum Length**: 256 bits (32 characters) recommended
- **Storage**: Environment variable or secure secrets manager
- **Rotation**: Rotate periodically and on security incidents
- **Entropy**: Use cryptographically secure random string

```bash
# Generate secure secret
openssl rand -hex 32
```

### Transport Security

- **HTTPS Only**: Always use HTTPS in production
- **HSTS**: Enable HTTP Strict Transport Security
- **Secure Cookies**: Set `Secure` flag for token storage

### Token Storage

| Environment | Recommendation |
|-------------|----------------|
| Web (Browser) | HttpOnly cookies (not localStorage) |
| Mobile | Secure storage (Keychain/Keystore) |
| Server-to-Server | Environment variables |

### Token Rotation

The current implementation uses separate access and refresh tokens. For enhanced security:

1. **Refresh Token Rotation**: Issue new refresh token on each use
2. **Reuse Detection**: Invalidate all tokens if refresh token is reused
3. **Device Binding**: Bind tokens to device/client identifier

### Additional Security Measures

- **Rate Limiting**: Prevent brute force on login endpoint
- **Account Lockout**: Temporarily lock after failed attempts
- **Audit Logging**: Log authentication events
- **Token Size**: Keep JWT claims minimal to reduce size

---

## Error Codes

### HTTP Status Codes

| Status | Name | Usage |
|--------|------|-------|
| 200 | OK | Successful request |
| 400 | Bad Request | Invalid input, missing fields |
| 401 | Unauthorized | Invalid credentials, expired token, blacklisted |
| 403 | Forbidden | Insufficient permissions |
| 500 | Internal Server Error | Server-side failure |

### Error Response Format

```json
{
    "error": {
        "message": "Descriptive error message"
    }
}
```

### Common Error Messages

| Code | Message | Cause |
|------|---------|-------|
| 400 | Email and password are required | Missing fields in request |
| 400 | Invalid email format | Malformed email address |
| 400 | Password is required | Empty password field |
| 401 | Invalid credentials | Wrong email or password |
| 401 | Unauthorized | Missing or invalid token |
| 401 | Invalid or missing authentication token | Token validation failed |
| 401 | Token expired | Access token time exceeded |
| 401 | Token blacklisted | Token in logout blacklist |
| 403 | Insufficient permissions | User role not authorized |

---

## Database Schema Summary

### Users Table

```sql
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL CHECK (role IN ('admin', 'organizer', 'spectator')),
    created_at INTEGER DEFAULT (strftime('%s', 'now'))
);
```

### Token Blacklist Table

```sql
CREATE TABLE IF NOT EXISTS token_blacklist (
    jti TEXT PRIMARY KEY,
    expires_at INTEGER NOT NULL
);
```

---

## Implementation Checklist

When implementing this authentication system:

- [ ] Configure JWT_SECRET environment variable
- [ ] Set up database with users and token_blacklist tables
- [ ] Implement JwtService for token generation/validation
- [ ] Implement AuthMiddleware for request authentication
- [ ] Implement AuthController for login/logout endpoints
- [ ] Add role checking in protected endpoints
- [ ] Set up periodic blacklist cleanup job
- [ ] Enable HTTPS in production
- [ ] Implement rate limiting on auth endpoints
- [ ] Add audit logging for authentication events

---

## Example: Full Authentication Flow

### 1. Login Request

```bash
curl -X POST http://localhost/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@contest.local", "password": "admin123"}'
```

### 2. Response

```json
{
    "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoxLCJlbWFpbCI6ImFkbWluQGNvbnRlc3QubG9jYWwiLCJyb2xlIjoiYWRtaW4iLCJqdGkiOiJhMmNkZWYxMjM0NTY3ODkwMTIzNDU2Nzg5MDEyMzQ1Njc4OTAiLCJpYXQiOjE3MDAwMDAwMDAsImV4cCI6MTcwMDAwMzYwMCwiaXNzIjoiY29udGVzdC1hcGkifQ.example_signature",
    "refresh_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoxLCJqdGkiOiIxMjM0NTY3ODkwYWJjZGVmMTIzNDU2Nzg5MGFiY2RlZjEyMzQ1Njc4OTAiLCJ0eXBlIjoicmVmcmVzaCIsImlhdCI6MTcwMDAwMDAwMCwiZXhwIjoxNzAwNTY4MDAwLCJpc3MiOiJjb250ZXN0LWFwaSJ9.another_signature",
    "expires_in": 3600,
    "user": {
        "id": 1,
        "email": "admin@contest.local",
        "role": "admin"
    }
}
```

### 3. Access Protected Resource

```bash
curl -X GET http://localhost/api/tournaments \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
```

### 4. Logout

```bash
curl -X POST http://localhost/auth/logout \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
```

---

*Document Version: 1.0*  
*Last Updated: 2026-03-07*
