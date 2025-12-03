# Python Worker API Documentation

## Overview

This API is designed for Python automation workers to communicate with the Laravel backend. All endpoints require authentication via the `X-API-Token` header.

**Base URL:** `{APP_URL}/api`

**Authentication:** Header `X-API-Token: {API_TOKEN}`

---

## Authentication

All API requests must include the `X-API-Token` header with a valid API token.

```http
X-API-Token: your-secure-api-token-here
```

The API token is configured in `.env` as `API_TOKEN` or `APP_API_TOKEN`.

---

## Rate Limiting

- **Default:** 60 requests per minute per IP
- **Task endpoints:** 120 requests per minute
- **LLM/Captcha endpoints:** 30 requests per minute

Rate limit headers are included in responses:
- `X-RateLimit-Limit`: Maximum requests allowed
- `X-RateLimit-Remaining`: Remaining requests in current window
- `Retry-After`: Seconds until rate limit resets (if exceeded)

---

## Endpoints

### 1. Get Pending Tasks

Retrieve pending automation tasks for processing.

**Endpoint:** `GET /api/tasks/pending`

**Headers:**
```
X-API-Token: {token}
X-Worker-ID: {worker_id} (optional, for tracking)
```

**Response:**
```json
{
  "tasks": [
    {
      "id": 1,
      "campaign_id": 5,
      "type": "comment",
      "status": "pending",
      "payload": {
        "campaign_id": 5,
        "target_url": "https://example.com/article",
        "keywords": ["seo", "backlinks"],
        "anchor_text_strategy": "variation",
        "content_tone": "professional"
      },
      "max_retries": 3,
      "retry_count": 0,
      "created_at": "2025-12-03T10:00:00Z"
    }
  ]
}
```

**Status Codes:**
- `200`: Success
- `401`: Unauthorized (invalid API token)
- `429`: Too Many Requests (rate limit exceeded)

---

### 2. Lock Task

Lock a task to prevent other workers from processing it.

**Endpoint:** `POST /api/tasks/{id}/lock`

**Headers:**
```
X-API-Token: {token}
X-Worker-ID: {worker_id} (required)
```

**Request Body:**
```json
{
  "worker_id": "python-worker-01"
}
```

**Response:**
```json
{
  "message": "Task locked successfully",
  "task": {
    "id": 1,
    "status": "running",
    "locked_at": "2025-12-03T10:05:00Z",
    "locked_by": "python-worker-01"
  }
}
```

**Status Codes:**
- `200`: Success
- `400`: Task already locked or invalid request
- `401`: Unauthorized
- `404`: Task not found
- `429`: Rate limit exceeded

---

### 3. Unlock Task

Unlock a task (if processing failed or was cancelled).

**Endpoint:** `POST /api/tasks/{id}/unlock`

**Headers:**
```
X-API-Token: {token}
```

**Response:**
```json
{
  "message": "Task unlocked successfully"
}
```

**Status Codes:**
- `200`: Success
- `401`: Unauthorized
- `404`: Task not found

---

### 4. Update Task Status

Update the status of a task after processing.

**Endpoint:** `PUT /api/tasks/{id}/status`

**Headers:**
```
X-API-Token: {token}
```

**Request Body:**
```json
{
  "status": "success",
  "result": {
    "url": "https://example.com/article#comment-123",
    "backlink_id": 42
  },
  "error": null
}
```

**Status Values:**
- `success`: Task completed successfully
- `failed`: Task failed (will retry if retry_count < max_retries)
- `error`: Task error (no retry)

**Response:**
```json
{
  "message": "Task status updated successfully"
}
```

**Status Codes:**
- `200`: Success
- `400`: Invalid status or request
- `401`: Unauthorized
- `404`: Task not found

---

### 5. Create Backlink

Create a new backlink record.

**Endpoint:** `POST /api/backlinks`

**Headers:**
```
X-API-Token: {token}
```

**Request Body:**
```json
{
  "campaign_id": 5,
  "url": "https://example.com/article#comment-123",
  "type": "comment",
  "status": "submitted",
  "keyword": "seo tools",
  "anchor_text": "best seo tools",
  "site_account_id": 10
}
```

**Response:**
```json
{
  "message": "Backlink created successfully",
  "backlink": {
    "id": 42,
    "campaign_id": 5,
    "url": "https://example.com/article#comment-123",
    "type": "comment",
    "status": "submitted",
    "created_at": "2025-12-03T10:10:00Z"
  }
}
```

**Status Codes:**
- `201`: Created
- `400`: Validation error
- `401`: Unauthorized
- `429`: Rate limit exceeded

---

### 6. Update Backlink

Update an existing backlink record.

**Endpoint:** `PUT /api/backlinks/{id}`

**Headers:**
```
X-API-Token: {token}
```

**Request Body:**
```json
{
  "status": "verified",
  "verified_at": "2025-12-03T10:15:00Z"
}
```

**Response:**
```json
{
  "message": "Backlink updated successfully"
}
```

**Status Codes:**
- `200`: Success
- `400`: Validation error
- `401`: Unauthorized
- `404`: Backlink not found

---

### 7. Create Site Account

Create a new site account record (for tracking accounts created on target sites).

**Endpoint:** `POST /api/site-accounts`

**Headers:**
```
X-API-Token: {token}
```

**Request Body:**
```json
{
  "campaign_id": 5,
  "site_domain": "example.com",
  "username": "john_doe",
  "email": "john@example.com",
  "status": "pending_verification",
  "verification_link": "https://example.com/verify?token=abc123"
}
```

**Response:**
```json
{
  "message": "Site account created successfully",
  "site_account": {
    "id": 10,
    "campaign_id": 5,
    "site_domain": "example.com",
    "status": "pending_verification"
  }
}
```

**Status Codes:**
- `201`: Created
- `400`: Validation error
- `401`: Unauthorized

---

### 8. Update Site Account

Update a site account record (e.g., after email verification).

**Endpoint:** `PUT /api/site-accounts/{id}`

**Headers:**
```
X-API-Token: {token}
```

**Request Body:**
```json
{
  "status": "verified",
  "verified_at": "2025-12-03T10:20:00Z"
}
```

**Response:**
```json
{
  "message": "Site account updated successfully"
}
```

---

### 9. Get Proxies

Retrieve available proxies for automation tasks.

**Endpoint:** `GET /api/proxies`

**Headers:**
```
X-API-Token: {token}
```

**Query Parameters:**
- `country` (optional): Filter by country code (e.g., "US", "GB")
- `active_only` (optional): Only return active proxies (default: true)

**Response:**
```json
{
  "proxies": [
    {
      "id": 1,
      "host": "proxy.example.com",
      "port": 8080,
      "username": "user",
      "password": "pass",
      "type": "http",
      "country": "US",
      "is_active": true,
      "error_count": 0
    }
  ]
}
```

**Status Codes:**
- `200`: Success
- `401`: Unauthorized

---

### 10. Generate LLM Content

Generate content using LLM (OpenAI/DeepSeek).

**Endpoint:** `POST /api/llm/generate`

**Headers:**
```
X-API-Token: {token}
```

**Request Body:**
```json
{
  "prompt": "Write a thoughtful comment for a blog post about SEO.",
  "options": {
    "max_tokens": 200,
    "temperature": 0.7,
    "model": "gpt-3.5-turbo"
  }
}
```

**Response:**
```json
{
  "success": true,
  "content": "This is a great article about SEO! I found the section on keyword research particularly helpful..."
}
```

**Status Codes:**
- `200`: Success
- `400`: Invalid request
- `401`: Unauthorized
- `500`: LLM service error
- `429`: Rate limit exceeded

**Rate Limit:** 30 requests per minute

---

### 11. Solve Captcha

Solve a captcha using 2Captcha or AntiCaptcha service.

**Endpoint:** `POST /api/captcha/solve`

**Headers:**
```
X-API-Token: {token}
```

**Request Body:**
```json
{
  "captcha_type": "recaptcha_v2",
  "data": {
    "site_key": "6Le-wvkSAAAAAPBMRTvw0Q4Muexq9bi0DJwx_mJ-",
    "page_url": "https://example.com/register",
    "campaign_id": 5,
    "site_domain": "example.com"
  }
}
```

**Captcha Types:**
- `recaptcha_v2`: Google reCAPTCHA v2
- `hcaptcha`: hCaptcha

**Response:**
```json
{
  "success": true,
  "solution": "03AGdBq24Pj...",
  "task_id": "12345678",
  "cost": 0.0025
}
```

**Status Codes:**
- `200`: Success
- `400`: Invalid request or unsupported captcha type
- `401`: Unauthorized
- `500`: Captcha service error
- `429`: Rate limit exceeded

**Rate Limit:** 30 requests per minute

---

## Error Responses

All endpoints return errors in a consistent format:

```json
{
  "error": "Error message here",
  "errors": {
    "field_name": ["Validation error message"]
  }
}
```

**Common Status Codes:**
- `400`: Bad Request (validation errors)
- `401`: Unauthorized (invalid or missing API token)
- `404`: Not Found (resource doesn't exist)
- `429`: Too Many Requests (rate limit exceeded)
- `500`: Internal Server Error

---

## Example Python Usage

```python
import requests

API_URL = "http://localhost:8000/api"
API_TOKEN = "your-api-token-here"

headers = {
    "X-API-Token": API_TOKEN,
    "X-Worker-ID": "python-worker-01",
    "Content-Type": "application/json"
}

# Get pending tasks
response = requests.get(f"{API_URL}/tasks/pending", headers=headers)
tasks = response.json()["tasks"]

# Lock a task
task_id = tasks[0]["id"]
requests.post(f"{API_URL}/tasks/{task_id}/lock", 
              json={"worker_id": "python-worker-01"}, 
              headers=headers)

# Process task...
# ...

# Update task status
requests.put(f"{API_URL}/tasks/{task_id}/status",
             json={
                 "status": "success",
                 "result": {"url": "https://example.com/backlink"}
             },
             headers=headers)
```

---

## Best Practices

1. **Always include X-Worker-ID header** for better tracking and debugging
2. **Lock tasks before processing** to prevent duplicate work
3. **Handle rate limits** - check `X-RateLimit-Remaining` header
4. **Retry failed requests** with exponential backoff
5. **Log all API interactions** for debugging
6. **Validate responses** before processing
7. **Update task status promptly** after completion or failure

---

## Support

For issues or questions:
- Check application logs: `storage/logs/laravel.log`
- Review API rate limit headers
- Verify API token is correct in `.env`
- Ensure database and Redis are running

