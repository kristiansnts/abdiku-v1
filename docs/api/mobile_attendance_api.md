# Mobile Attendance API Documentation

## Overview

REST API for mobile attendance application with device management, clock-in/out with geofence validation, and attendance correction requests.

**Base URL**: `https://your-domain.com/api/v1`

**Content-Type**: `application/json`

---

## Authentication

All endpoints (except login) require Bearer token authentication.

```
Authorization: Bearer {token}
```

---

## Response Format

### Success Response
```json
{
  "success": true,
  "data": { ... },
  "message": "Operation successful"
}
```

### Error Response
```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Human readable message",
    "data": { ... }  // Optional additional data
  }
}
```

### Pagination Response
```json
{
  "success": true,
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "last_page": 10,
    "per_page": 15,
    "total": 150
  }
}
```

---

## 1. Authentication Endpoints

### 1.1 Login

Authenticate user and register device. Implements one-user-one-device policy.

**Endpoint**: `POST /auth/login`

**Rate Limit**: 5 requests per minute per IP

**Request Body**:
```json
{
  "email": "user@example.com",
  "password": "password123",
  "device_id": "unique-device-uuid-from-mobile",
  "device_name": "iPhone 14 Pro",
  "device_model": "iPhone14,3",
  "device_os": "iOS 17.2",
  "app_version": "1.0.0",
  "force_switch": false
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| email | string | Yes | User's email address |
| password | string | Yes | User's password |
| device_id | string | Yes | Unique device identifier (UUID) |
| device_name | string | Yes | User-friendly device name |
| device_model | string | No | Device model identifier |
| device_os | string | No | Operating system and version |
| app_version | string | No | Mobile app version |
| force_switch | boolean | No | Set `true` to force switch to this device (logs out other devices) |

**Success Response** (200):
```json
{
  "success": true,
  "data": {
    "token": "1|abcdefghijklmnop...",
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "role": "EMPLOYEE",
      "company": {
        "id": 1,
        "name": "PT Example Company"
      },
      "employee": {
        "id": 1,
        "name": "John Doe",
        "join_date": "2024-01-15",
        "status": "ACTIVE"
      }
    },
    "device": {
      "id": 1,
      "device_id": "unique-device-uuid",
      "device_name": "iPhone 14 Pro",
      "is_active": true
    }
  },
  "message": "Login berhasil."
}
```

**Error Responses**:

| Code | Status | Description |
|------|--------|-------------|
| INVALID_CREDENTIALS | 401 | Wrong email or password |
| DEVICE_BLOCKED | 403 | Device has been blocked by admin |
| DIFFERENT_DEVICE_ACTIVE | 403 | User has another active device |

**Device Blocked Error**:
```json
{
  "success": false,
  "error": {
    "code": "DEVICE_BLOCKED",
    "message": "Perangkat Anda telah diblokir. Alasan: Perangkat hilang"
  }
}
```

**Different Device Active Error**:
```json
{
  "success": false,
  "error": {
    "code": "DIFFERENT_DEVICE_ACTIVE",
    "message": "Akun Anda sudah aktif di perangkat lain (iPhone 14). Silakan logout dari perangkat tersebut atau hubungi HR.",
    "data": {
      "active_device": "iPhone 14"
    }
  }
}
```

> **Note**: To switch devices, user can either:
> 1. Logout from the other device first
> 2. Send login request with `force_switch: true`
> 3. Contact HR to deactivate the old device

---

### 1.2 Logout

Logout from current device.

**Endpoint**: `POST /auth/logout`

**Headers**: `Authorization: Bearer {token}`

**Success Response** (200):
```json
{
  "success": true,
  "message": "Logout berhasil."
}
```

---

### 1.3 Logout All Devices

Logout from all registered devices.

**Endpoint**: `POST /auth/logout-all`

**Headers**: `Authorization: Bearer {token}`

**Success Response** (200):
```json
{
  "success": true,
  "message": "Logout dari semua perangkat berhasil."
}
```

---

### 1.4 Get Current User

Get authenticated user information.

**Endpoint**: `GET /auth/me`

**Headers**: `Authorization: Bearer {token}`

**Success Response** (200):
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "role": "EMPLOYEE",
      "company": {
        "id": 1,
        "name": "PT Example Company"
      },
      "employee": {
        "id": 1,
        "name": "John Doe",
        "join_date": "2024-01-15",
        "status": "ACTIVE"
      }
    },
    "active_device": {
      "id": 1,
      "device_id": "unique-device-uuid",
      "device_name": "iPhone 14 Pro",
      "last_login_at": "2026-02-02 08:00:00"
    }
  }
}
```

---

### 1.5 Get User Devices

List all registered devices for the user.

**Endpoint**: `GET /auth/devices`

**Headers**: `Authorization: Bearer {token}`

**Success Response** (200):
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "device_id": "uuid-device-1",
      "device_name": "iPhone 14 Pro",
      "device_model": "iPhone14,3",
      "device_os": "iOS 17.2",
      "is_active": true,
      "is_blocked": false,
      "block_reason": null,
      "last_login_at": "2026-02-02 08:00:00"
    },
    {
      "id": 2,
      "device_id": "uuid-device-2",
      "device_name": "Samsung Galaxy S23",
      "device_model": "SM-S911B",
      "device_os": "Android 14",
      "is_active": false,
      "is_blocked": true,
      "block_reason": "Perangkat hilang",
      "last_login_at": "2026-01-15 10:30:00"
    }
  ]
}
```

---

## 2. Attendance Endpoints

### 2.1 Get Today's Status

Check current attendance status for today.

**Endpoint**: `GET /attendance/status`

**Headers**: `Authorization: Bearer {token}`

**Success Response** (200):
```json
{
  "success": true,
  "data": {
    "can_clock_in": false,
    "can_clock_out": true,
    "has_clocked_in": true,
    "has_clocked_out": false,
    "today_attendance": {
      "id": 123,
      "date": "2026-02-02",
      "clock_in": "08:05:30",
      "clock_out": null,
      "status": "APPROVED"
    },
    "message": "Sudah clock in, silakan clock out"
  }
}
```

**Status Values**:
- `can_clock_in: true, can_clock_out: false` → User hasn't clocked in
- `can_clock_in: false, can_clock_out: true` → User has clocked in, waiting for clock out
- `can_clock_in: false, can_clock_out: false` → User has completed attendance for today

---

### 2.2 Clock In

Submit clock-in with location and device evidence.

**Endpoint**: `POST /attendance/clock-in`

**Headers**: `Authorization: Bearer {token}`

**Content-Type**: `multipart/form-data` (if uploading photo) or `application/json`

**Request Body**:
```json
{
  "clock_in_at": "2026-02-02T08:05:30+07:00",
  "evidence": {
    "geolocation": {
      "lat": -6.2088,
      "lng": 106.8456,
      "accuracy": 10.5
    },
    "device": {
      "device_id": "unique-device-uuid",
      "model": "iPhone 14 Pro",
      "os": "iOS 17.2",
      "app_version": "1.0.0"
    }
  }
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| clock_in_at | datetime | Yes | ISO 8601 datetime, must be ≤ now |
| evidence.geolocation.lat | number | Yes | Latitude (-90 to 90) |
| evidence.geolocation.lng | number | Yes | Longitude (-180 to 180) |
| evidence.geolocation.accuracy | number | No | GPS accuracy in meters |
| evidence.device.device_id | string | Yes | Unique device identifier |
| evidence.device.model | string | Yes | Device model |
| evidence.device.os | string | Yes | Operating system |
| evidence.device.app_version | string | Yes | App version |
| evidence.photo | file | No | Selfie photo (max 5MB, jpg/jpeg/png) |

**With Photo (multipart/form-data)**:
```
clock_in_at: 2026-02-02T08:05:30+07:00
evidence[geolocation][lat]: -6.2088
evidence[geolocation][lng]: 106.8456
evidence[geolocation][accuracy]: 10.5
evidence[device][device_id]: unique-device-uuid
evidence[device][model]: iPhone 14 Pro
evidence[device][os]: iOS 17.2
evidence[device][app_version]: 1.0.0
evidence[photo]: (binary file)
```

**Success Response - Within Geofence** (201):
```json
{
  "success": true,
  "data": {
    "id": 123,
    "date": "2026-02-02",
    "clock_in": "2026-02-02 08:05:30",
    "clock_out": null,
    "source": "MOBILE",
    "status": "APPROVED",
    "status_label": "Disetujui",
    "evidences": [
      {
        "id": 1,
        "type": "GEOLOCATION",
        "type_label": "Lokasi GPS",
        "payload": {
          "lat": -6.2088,
          "lng": 106.8456,
          "accuracy": 10.5,
          "validated": true,
          "within_geofence": true,
          "nearest_location_id": 1,
          "distance_meters": 25.5
        },
        "captured_at": "2026-02-02 08:05:30"
      },
      {
        "id": 2,
        "type": "DEVICE",
        "type_label": "Info Perangkat",
        "payload": {
          "device_id": "unique-device-uuid",
          "model": "iPhone 14 Pro",
          "os": "iOS 17.2",
          "app_version": "1.0.0"
        },
        "captured_at": "2026-02-02 08:05:30"
      }
    ],
    "location": {
      "id": 1,
      "name": "Kantor Pusat",
      "address": "Jl. Sudirman No. 1",
      "latitude": -6.2087,
      "longitude": 106.8455,
      "geofence_radius_meters": 100
    }
  },
  "message": "Clock in berhasil."
}
```

**Success Response - Outside Geofence** (201):
```json
{
  "success": true,
  "data": {
    "id": 124,
    "date": "2026-02-02",
    "clock_in": "2026-02-02 08:05:30",
    "clock_out": null,
    "source": "MOBILE",
    "status": "PENDING",
    "status_label": "Menunggu Review",
    "evidences": [...],
    "location": null
  },
  "message": "Clock in berhasil. Menunggu verifikasi karena lokasi di luar area."
}
```

**Error Responses**:

| Code | Status | Description |
|------|--------|-------------|
| ALREADY_CLOCKED_IN | 422 | Already clocked in today |
| VALIDATION_ERROR | 422 | Invalid request data |

---

### 2.3 Clock Out

Submit clock-out.

**Endpoint**: `POST /attendance/clock-out`

**Headers**: `Authorization: Bearer {token}`

**Request Body**:
```json
{
  "clock_out_at": "2026-02-02T17:30:00+07:00",
  "evidence": {
    "geolocation": {
      "lat": -6.2088,
      "lng": 106.8456,
      "accuracy": 10.5
    }
  }
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| clock_out_at | datetime | Yes | ISO 8601 datetime, must be ≤ now |
| evidence.geolocation | object | No | Optional geolocation for clock out |

**Success Response** (200):
```json
{
  "success": true,
  "data": {
    "id": 123,
    "date": "2026-02-02",
    "clock_in": "2026-02-02 08:05:30",
    "clock_out": "2026-02-02 17:30:00",
    "source": "MOBILE",
    "status": "APPROVED",
    "status_label": "Disetujui",
    "evidences": [...],
    "location": {...}
  },
  "message": "Clock out berhasil."
}
```

**Error Responses**:

| Code | Status | Description |
|------|--------|-------------|
| NOT_CLOCKED_IN | 422 | Haven't clocked in today |
| ALREADY_CLOCKED_OUT | 422 | Already clocked out today |
| ATTENDANCE_LOCKED | 403 | Attendance is locked (payroll processed) |

---

### 2.4 Attendance History

Get paginated attendance history.

**Endpoint**: `GET /attendance/history`

**Headers**: `Authorization: Bearer {token}`

**Query Parameters**:
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| per_page | integer | 15 | Items per page (max 100) |
| page | integer | 1 | Page number |

**Example**: `GET /attendance/history?per_page=10&page=1`

**Success Response** (200):
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "date": "2026-02-02",
      "clock_in": "2026-02-02 08:05:30",
      "clock_out": "2026-02-02 17:30:00",
      "source": "MOBILE",
      "status": "APPROVED",
      "status_label": "Disetujui",
      "evidences": [...],
      "location": {...}
    },
    {
      "id": 122,
      "date": "2026-02-01",
      "clock_in": "2026-02-01 08:00:00",
      "clock_out": "2026-02-01 17:00:00",
      "source": "MOBILE",
      "status": "APPROVED",
      "status_label": "Disetujui",
      "evidences": [...],
      "location": {...}
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 10,
    "total": 45
  }
}
```

---

## 3. Attendance Correction Requests

### 3.1 List Correction Requests

Get user's correction requests.

**Endpoint**: `GET /attendance/requests`

**Headers**: `Authorization: Bearer {token}`

**Query Parameters**:
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| per_page | integer | 15 | Items per page |
| page | integer | 1 | Page number |
| status | string | null | Filter by status: PENDING, APPROVED, REJECTED |

**Example**: `GET /attendance/requests?status=PENDING&per_page=10`

**Success Response** (200):
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "request_type": "LATE",
      "request_type_label": "Keterlambatan",
      "requested_clock_in_at": "2026-02-02 08:30:00",
      "requested_clock_out_at": null,
      "reason": "Macet di jalan tol karena kecelakaan",
      "status": "PENDING",
      "status_label": "Menunggu Review",
      "requested_at": "2026-02-02 09:00:00",
      "reviewed_at": null,
      "review_note": null,
      "reviewer": null,
      "attendance": {
        "id": 123,
        "date": "2026-02-02",
        "clock_in": "2026-02-02 09:00:00",
        "clock_out": null,
        "status": "PENDING"
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1
  }
}
```

---

### 3.2 Create Correction Request

Submit a new correction request.

**Endpoint**: `POST /attendance/requests`

**Headers**: `Authorization: Bearer {token}`

**Request Body for LATE request**:
```json
{
  "request_type": "LATE",
  "attendance_raw_id": 123,
  "requested_clock_in_at": "2026-02-02T08:00:00+07:00",
  "reason": "Macet di jalan tol karena kecelakaan"
}
```

**Request Body for CORRECTION request**:
```json
{
  "request_type": "CORRECTION",
  "attendance_raw_id": 123,
  "requested_clock_in_at": "2026-02-02T08:00:00+07:00",
  "requested_clock_out_at": "2026-02-02T17:00:00+07:00",
  "reason": "Lupa clock out, keluar kantor jam 17:00"
}
```

**Request Body for MISSING request** (no attendance record):
```json
{
  "request_type": "MISSING",
  "date": "2026-02-01",
  "requested_clock_in_at": "2026-02-01T08:00:00+07:00",
  "requested_clock_out_at": "2026-02-01T17:00:00+07:00",
  "reason": "Lupa clock in dan clock out karena ada meeting di luar kantor"
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| request_type | string | Yes | LATE, CORRECTION, or MISSING |
| attendance_raw_id | integer | Conditional | Required for LATE and CORRECTION |
| date | date | Conditional | Required for MISSING (format: YYYY-MM-DD) |
| requested_clock_in_at | datetime | No | Requested clock in time |
| requested_clock_out_at | datetime | No | Requested clock out time |
| reason | string | Yes | Reason for the request (max 1000 chars) |

**Request Types**:
| Type | Description |
|------|-------------|
| LATE | Request for late arrival approval |
| CORRECTION | Request to correct clock-in/out times |
| MISSING | Request for missing attendance (forgot to clock) |

**Success Response** (201):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "request_type": "LATE",
    "request_type_label": "Keterlambatan",
    "requested_clock_in_at": "2026-02-02 08:00:00",
    "requested_clock_out_at": null,
    "reason": "Macet di jalan tol karena kecelakaan",
    "status": "PENDING",
    "status_label": "Menunggu Review",
    "requested_at": "2026-02-02 09:00:00",
    "reviewed_at": null,
    "review_note": null,
    "reviewer": null,
    "attendance": {...}
  },
  "message": "Permintaan koreksi berhasil diajukan."
}
```

**Error Responses**:

| Code | Status | Description |
|------|--------|-------------|
| INVALID_ATTENDANCE | 422 | Attendance record not found or doesn't belong to user |
| ATTENDANCE_LOCKED | 422 | Attendance is locked (payroll processed) |
| VALIDATION_ERROR | 422 | Invalid request data |

---

### 3.3 Get Request Detail

Get specific correction request.

**Endpoint**: `GET /attendance/requests/{id}`

**Headers**: `Authorization: Bearer {token}`

**Success Response** (200):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "request_type": "CORRECTION",
    "request_type_label": "Koreksi Waktu",
    "requested_clock_in_at": "2026-02-02 08:00:00",
    "requested_clock_out_at": "2026-02-02 17:00:00",
    "reason": "Lupa clock out",
    "status": "APPROVED",
    "status_label": "Disetujui",
    "requested_at": "2026-02-02 18:00:00",
    "reviewed_at": "2026-02-02 18:30:00",
    "review_note": "Disetujui berdasarkan data CCTV",
    "reviewer": {
      "id": 5,
      "name": "HR Manager"
    },
    "attendance": {
      "id": 123,
      "date": "2026-02-02",
      "clock_in": "2026-02-02 08:00:00",
      "clock_out": "2026-02-02 17:00:00",
      "status": "APPROVED",
      "evidences": [...]
    }
  }
}
```

**Error Response** (404):
```json
{
  "success": false,
  "error": {
    "code": "NOT_FOUND",
    "message": "Permintaan tidak ditemukan."
  }
}
```

---

### 3.4 Cancel Request

Cancel a pending correction request.

**Endpoint**: `DELETE /attendance/requests/{id}`

**Headers**: `Authorization: Bearer {token}`

**Success Response** (200):
```json
{
  "success": true,
  "message": "Permintaan berhasil dibatalkan."
}
```

**Error Responses**:

| Code | Status | Description |
|------|--------|-------------|
| NOT_FOUND | 404 | Request not found |
| CANNOT_CANCEL | 422 | Can only cancel PENDING requests |

---

## 4. Company Endpoints

### 4.1 Get Company Locations

Get list of company locations for geofence validation.

**Endpoint**: `GET /company/locations`

**Headers**: `Authorization: Bearer {token}`

**Success Response** (200):
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Kantor Pusat",
      "address": "Jl. Sudirman No. 1, Jakarta",
      "latitude": -6.2087,
      "longitude": 106.8455,
      "geofence_radius_meters": 100,
      "is_default": true
    },
    {
      "id": 2,
      "name": "Kantor Cabang Bandung",
      "address": "Jl. Asia Afrika No. 10, Bandung",
      "latitude": -6.9175,
      "longitude": 107.6191,
      "geofence_radius_meters": 150,
      "is_default": false
    }
  ]
}
```

---

## 5. Error Codes Reference

### Authentication Errors
| Code | Status | Description |
|------|--------|-------------|
| INVALID_CREDENTIALS | 401 | Wrong email or password |
| UNAUTHENTICATED | 401 | Token is invalid or expired |

### Device Errors
| Code | Status | Description |
|------|--------|-------------|
| DEVICE_BLOCKED | 403 | Device has been blocked by admin |
| DIFFERENT_DEVICE_ACTIVE | 403 | User has another active device |
| DEVICE_NOT_REGISTERED | 403 | Device is not registered |

### Employee Errors
| Code | Status | Description |
|------|--------|-------------|
| EMPLOYEE_NOT_FOUND | 403 | User doesn't have associated employee record |

### Attendance Errors
| Code | Status | Description |
|------|--------|-------------|
| ALREADY_CLOCKED_IN | 422 | Already clocked in today |
| NOT_CLOCKED_IN | 422 | Haven't clocked in today |
| ALREADY_CLOCKED_OUT | 422 | Already clocked out today |
| ATTENDANCE_LOCKED | 403 | Attendance is locked (payroll processed) |
| INVALID_ATTENDANCE | 422 | Invalid attendance record |

### General Errors
| Code | Status | Description |
|------|--------|-------------|
| VALIDATION_ERROR | 422 | Request validation failed |
| NOT_FOUND | 404 | Resource not found |
| CANNOT_CANCEL | 422 | Cannot cancel non-pending request |

---

## 6. Status Values

### Attendance Status
| Value | Label | Description |
|-------|-------|-------------|
| PENDING | Menunggu Review | Requires HR review (outside geofence) |
| APPROVED | Disetujui | Approved attendance |
| REJECTED | Ditolak | Rejected by HR |
| LOCKED | Terkunci | Locked for payroll processing |

### Attendance Source
| Value | Label | Description |
|-------|-------|-------------|
| MACHINE | Mesin | From biometric machine |
| REQUEST | Permintaan | Manual request |
| IMPORT | Impor | Imported data |
| MOBILE | Aplikasi Mobile | From mobile app |

### Request Type
| Value | Label | Description |
|-------|-------|-------------|
| LATE | Keterlambatan | Late arrival request |
| CORRECTION | Koreksi Waktu | Time correction request |
| MISSING | Absen Hilang | Missing attendance request |

---

## 7. Implementation Notes

### Device ID
Generate a unique device identifier that persists across app reinstalls:
- **iOS**: Use `identifierForVendor` or Keychain storage
- **Android**: Use `ANDROID_ID` or generate UUID and store in SharedPreferences

### Geolocation
- Request location permission with "while using" mode
- Use high accuracy mode for best results
- Include accuracy value in meters
- Handle location permission denied gracefully

### Photo Evidence
- Compress images before upload (recommended max 1MB)
- Use JPEG format for smaller file size
- Consider using front camera for selfie verification

### Token Storage
- Store token securely (Keychain on iOS, EncryptedSharedPreferences on Android)
- Clear token on logout
- Handle 401 responses by redirecting to login

### Offline Support
- Cache company locations locally
- Queue clock-in/out requests when offline
- Sync when connection is restored
- Show appropriate offline indicators

### Time Zones
- Always send datetime in ISO 8601 format with timezone
- Example: `2026-02-02T08:00:00+07:00`
- Server will convert to appropriate timezone

---

## 8. Sample Flow

### Login Flow
```
1. User opens app
2. Check if token exists in storage
   - Yes: Call GET /auth/me to validate
   - No: Show login screen
3. User enters credentials and device info
4. Call POST /auth/login
5. Store token securely
6. Navigate to home screen
```

### Clock In Flow
```
1. User taps "Clock In" button
2. Request location permission (if not granted)
3. Get current location with high accuracy
4. Fetch company locations (GET /company/locations)
5. Display nearest location and distance
6. Optionally capture selfie
7. Call POST /attendance/clock-in
8. Show success/pending message
9. Update UI to show "Clock Out" button
```

### Clock Out Flow
```
1. Check current status (GET /attendance/status)
2. Verify can_clock_out is true
3. Optionally get location
4. Call POST /attendance/clock-out
5. Show success message
6. Update UI
```

---

## 9. Testing

### Test Credentials
Contact your system administrator for test account credentials.

### Postman Collection
Import the Postman collection from:
`/docs/api/postman_collection.json` (if available)

### cURL Examples

**Login**:
```bash
curl -X POST https://your-domain.com/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password",
    "device_id": "test-device-001",
    "device_name": "Test Device",
    "device_model": "Postman",
    "device_os": "Test",
    "app_version": "1.0.0"
  }'
```

**Clock In**:
```bash
curl -X POST https://your-domain.com/api/v1/attendance/clock-in \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "clock_in_at": "2026-02-02T08:00:00+07:00",
    "evidence": {
      "geolocation": {
        "lat": -6.2088,
        "lng": 106.8456,
        "accuracy": 10
      },
      "device": {
        "device_id": "test-device-001",
        "model": "Test Device",
        "os": "Test",
        "app_version": "1.0.0"
      }
    }
  }'
```

---

## 10. Changelog

### Version 1.0.0 (2026-02-02)
- Initial release
- Authentication with device management
- One-user-one-device policy
- Clock in/out with geofence validation
- Evidence storage (geolocation, device, photo)
- Attendance correction requests
- Company locations endpoint
