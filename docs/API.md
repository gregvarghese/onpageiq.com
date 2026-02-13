# OnPageIQ API Documentation

## Authentication

All API requests require authentication using Laravel Sanctum API tokens.

### Creating a Token

```bash
POST /api/v1/auth/token
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "your-password",
  "token_name": "My API Token"
}
```

**Response:**
```json
{
  "token": "1|abc123...",
  "message": "Token created successfully."
}
```

### Using the Token

Include the token in the Authorization header:

```
Authorization: Bearer 1|abc123...
```

---

## Projects

### List Projects

```
GET /api/v1/projects
```

**Query Parameters:**
- `per_page` (optional): Number of results per page (default: 15)

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "My Project",
      "description": "Project description",
      "language": "en",
      "check_config": {
        "spelling": true,
        "grammar": true,
        "seo": true,
        "readability": true
      },
      "urls_count": 5,
      "created_at": "2026-02-13T12:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 10
  }
}
```

### Create Project

```
POST /api/v1/projects
Content-Type: application/json

{
  "name": "New Project",
  "description": "Optional description",
  "language": "en",
  "check_config": {
    "spelling": true,
    "grammar": true
  }
}
```

### Get Project

```
GET /api/v1/projects/{id}
```

### Update Project

```
PUT /api/v1/projects/{id}
Content-Type: application/json

{
  "name": "Updated Name"
}
```

### Delete Project

```
DELETE /api/v1/projects/{id}
```

---

## URLs

### List URLs for Project

```
GET /api/v1/projects/{projectId}/urls
```

### Add URL to Project

```
POST /api/v1/projects/{projectId}/urls
Content-Type: application/json

{
  "url": "https://example.com/page"
}
```

### Bulk Add URLs

```
POST /api/v1/projects/{projectId}/urls/bulk
Content-Type: application/json

{
  "urls": [
    "https://example.com/page1",
    "https://example.com/page2"
  ]
}
```

### Delete URL

```
DELETE /api/v1/urls/{id}
```

---

## Scans

### Trigger Scan

```
POST /api/v1/urls/{urlId}/scan
Content-Type: application/json

{
  "scan_type": "quick"
}
```

**Scan Types:**
- `quick`: Uses GPT-4o-mini (1 credit)
- `deep`: Uses GPT-4o (3 credits)

### Get Scan Status

```
GET /api/v1/scans/{id}
```

**Response:**
```json
{
  "data": {
    "id": 1,
    "url_id": 1,
    "status": "completed",
    "scan_type": "quick",
    "credits_charged": 1,
    "started_at": "2026-02-13T12:00:00Z",
    "completed_at": "2026-02-13T12:01:00Z"
  }
}
```

### Get Scan Results

```
GET /api/v1/scans/{id}/results
```

---

## Credits

### Get Balance

```
GET /api/v1/credits/balance
```

**Response:**
```json
{
  "data": {
    "credit_balance": 150,
    "overdraft_balance": 0
  }
}
```

### Get Transaction History

```
GET /api/v1/credits/transactions
```

---

## Webhooks

### List Webhook Endpoints

```
GET /api/v1/webhooks
```

### Create Webhook Endpoint

```
POST /api/v1/webhooks
Content-Type: application/json

{
  "url": "https://your-server.com/webhook",
  "events": ["scan.completed", "scan.failed", "credits.low"]
}
```

**Available Events:**
- `scan.started`
- `scan.completed`
- `scan.failed`
- `credits.low`
- `credits.depleted`

### Update Webhook

```
PUT /api/v1/webhooks/{id}
```

### Delete Webhook

```
DELETE /api/v1/webhooks/{id}
```

### Webhook Payload Format

```json
{
  "event": "scan.completed",
  "timestamp": "2026-02-13T12:00:00Z",
  "data": {
    "scan_id": 1,
    "url": "https://example.com/page",
    "status": "completed",
    "issue_count": 5
  }
}
```

### Webhook Signature

All webhook requests include an `X-Webhook-Signature` header containing an HMAC-SHA256 signature of the payload using your webhook secret.

---

## Rate Limits

API requests are rate limited based on subscription tier:
- Free: 60 requests/minute
- Pro: 120 requests/minute
- Team: 300 requests/minute
- Enterprise: Custom

Rate limit headers are included in responses:
- `X-RateLimit-Limit`
- `X-RateLimit-Remaining`
- `X-RateLimit-Reset`

---

## Error Responses

```json
{
  "message": "Error description",
  "errors": {
    "field": ["Validation error message"]
  }
}
```

**HTTP Status Codes:**
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `429` - Rate Limited
- `500` - Server Error
