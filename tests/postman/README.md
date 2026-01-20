# Article API V2 - Postman Collections

This directory contains Postman collections and environment files for testing the Article microservice API across different runtimes and configurations.

## Architecture

This microservice implements **Event Sourcing + CQRS** with **DDD layers**:

```
Command → CommandHandler → ArticleEntity (Aggregate) → DomainEvent → EventStore (PostgreSQL)
                                                           ↓
                                                    EventBus (RabbitMQ) → Projectors (Read Models)
```

## Files Overview

### Environment File

| File | Description |
|------|-------------|
| `Article API V2 - Local.postman_environment.json` | Shared environment with all base URLs and JWT tokens |

### Collections by Runtime

| File | Runtime | Port | Auth |
|------|---------|------|------|
| `Article API V2 APISIX.postman_collection.json` | APISIX Gateway | 9080 | JWT (RS256) |
| `Article API V2 Nginx-Fpm.postman_collection.json` | Nginx + PHP-FPM | 8080 | None |
| `Article API V2 RoadRunner.postman_collection.json` | RoadRunner | 8081 | None |
| `Article API V2 Franken.postman_collection.json` | FrankenPHP | 8082 | None |

### Runtime Architecture

```
                         ┌─────────────────────────────────────────────────────────┐
                         │                    APISIX Gateway                       │
                         │                      (Port 9080)                        │
                         │  ┌─────────────────────────────────────────────────┐    │
   Client Request ──────►│  │ JWT Auth → Rate Limiting → Load Balancing       │    │
                         │  └─────────────────────────────────────────────────┘    │
                         └────────────────────────┬────────────────────────────────┘
                                                  │
                    ┌─────────────────────────────┼─────────────────────────────┐
                    │                             │                             │
                    ▼                             ▼                             ▼
         ┌──────────────────┐          ┌──────────────────┐          ┌──────────────────┐
         │   Nginx + FPM    │          │    RoadRunner    │          │   FrankenPHP     │
         │   (Port 8080)    │          │   (Port 8081)    │          │   (Port 8082)    │
         │  Traditional     │          │  High-perf PHP   │          │   Modern PHP     │
         │  PHP-FPM pools   │          │  worker mode     │          │   native HTTP    │
         └──────────────────┘          └──────────────────┘          └──────────────────┘
```

## Quick Start

### 1. Import into Postman

1. Open Postman Desktop
2. Click **Import** (top-left, or `Ctrl+O` / `Cmd+O`)
3. Import these files:
   - `Article API V2 - Local.postman_environment.json` (environment)
   - One or more collection files based on your testing needs

### 2. Select Environment

1. Click the environment dropdown (top-right)
2. Select **"Article API V2 - Local"**

### 3. Choose Collection Based on Testing Goal

| Goal | Collection |
|------|------------|
| Test API Gateway with JWT authentication | `Article API V2 APISIX` |
| Test standard PHP-FPM deployment | `Article API V2 Nginx-Fpm` |
| Test high-performance RoadRunner runtime | `Article API V2 RoadRunner` |
| Test FrankenPHP modern runtime | `Article API V2 Franken` |

## APISIX Collection (JWT Authentication)

The APISIX collection includes comprehensive JWT authentication testing:

### Test Categories

| Folder | Description |
|--------|-------------|
| 0. Setup & Health | Gateway health checks and connectivity |
| 1. JWT Authentication Tests | JWT validation scenarios |
| 2. Article API - Public Operations | GET endpoints (no auth required) |
| 3. Article API - Protected Operations | CRUD with JWT (auth required) |
| 4. User API | User management endpoints |
| 5. Workflow Tests | Full lifecycle automation |

### JWT Configuration

| Parameter | Value |
|-----------|-------|
| Algorithm | RS256 |
| Consumer Key | `article-api-key` |
| Token Expiry | Jan 16, 2026 |
| Header | `Authorization: Bearer <token>` |

### Protected vs Public Endpoints

```
Protected (JWT Required):     Public (No Auth):
├── POST /api/v2/article/        ├── GET /api/v2/article
├── PUT /api/v2/article/{uuid}   ├── GET /api/v2/article/{uuid}
├── PUT .../publish           ├── GET /api/v2/article/slug/{slug}
├── PUT .../unpublish         ├── GET /api/v2/article/event/{id}
├── PUT .../archive           ├── GET /api/v2/article/published
└── DELETE /api/v2/article/{uuid}└── GET /api/v2/article/archived
```

## Environment Variables

### Base URLs

| Variable | Value | Description |
|----------|-------|-------------|
| `base_url` | `http://localhost:8080/api/v2` | Direct backend (PHP-FPM) |
| `apisix_base_url` | `http://localhost:9080` | APISIX Gateway root |
| `apisix_base_url_v1` | `http://localhost:9080/api/v1` | APISIX V1 API |
| `apisix_base_url_v2` | `http://localhost:9080/api/v2` | APISIX V2 API |
| `base_url_80` | `http://localhost:8080/api/v2` | PHP-FPM direct |
| `base_url_81` | `http://localhost:8081/api/v2` | RoadRunner direct |

### Authentication

| Variable | Description |
|----------|-------------|
| `jwt_token` | Valid JWT token for authenticated requests |
| `jwt_key` | JWT consumer key (`article-api-key`) |
| `expired_jwt_token` | Expired token for negative testing |

### Test Data

| Variable | Default | Description |
|----------|---------|-------------|
| `event_id` | `1` | Default event ID |
| `article_uuid` | *(auto-set)* | Stored after create/list |
| `article_slug` | *(auto-set)* | Stored after create/list |
| `article_title` | `Breaking Article...` | Default title for create |
| `article_short_description` | `A brief summary...` | Default summary |
| `article_description` | `Full article content...` | Default content |
| `search_status` | `published` | Status filter for searches |

## Running Tests

### Postman Collection Runner

1. Click **Runner** button (or `Ctrl+Shift+R`)
2. Select desired collection
3. Select "Article API V2 - Local" environment
4. Click **Run**

### Newman CLI

```bash
# Run APISIX collection
npx newman run "tests/postman/Article API V2 APISIX.postman_collection.json" \
    -e "tests/postman/Article API V2 - Local.postman_environment.json"

# Run with HTML report
npx newman run "tests/postman/Article API V2 APISIX.postman_collection.json" \
    -e "tests/postman/Article API V2 - Local.postman_environment.json" \
    -r htmlextra --reporter-htmlextra-export ./newman-report.html

# Run specific folder
npx newman run "tests/postman/Article API V2 APISIX.postman_collection.json" \
    -e "tests/postman/Article API V2 - Local.postman_environment.json" \
    --folder "1. JWT Authentication Tests"
```

### Compare Runtimes

```bash
# Test all runtimes and compare
for runtime in "Nginx-Fpm" "RoadRunner" "Franken"; do
    echo "=== Testing $runtime ==="
    npx newman run "tests/postman/Article API V2 $runtime.postman_collection.json" \
        -e "tests/postman/Article API V2 - Local.postman_environment.json" \
        --reporters cli
done
```

## API Endpoints Reference

### Query Endpoints (GET) - CQRS Read Side

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/article` | List all article with optional filters |
| GET | `/article/{uuid}` | Get article by UUID |
| GET | `/article/slug/{slug}` | Get article by URL slug |
| GET | `/article/event/{event_id}` | Get article by event |
| GET | `/article/published` | Get all published article |
| GET | `/article/archived` | Get all archived article |

### Command Endpoints (POST/PUT/DELETE) - CQRS Write Side

| Method | Endpoint | Description | Domain Event |
|--------|----------|-------------|--------------|
| POST | `/article/` | Create article | `ArticleWasCreated` |
| PUT | `/article/{uuid}` | Update article | `ArticleWasUpdated` |
| PUT | `/article/{uuid}/publish` | Publish article | `ArticleWasPublished` |
| PUT | `/article/{uuid}/unpublish` | Unpublish article | `ArticleWasUnpublished` |
| PUT | `/article/{uuid}/archive` | Archive article | `ArticleWasArchived` |
| DELETE | `/article/{uuid}` | Delete article | `ArticleWasDeleted` |

**Note:** POST endpoints require trailing slash (`/article/`), PUT/DELETE on UUID do not.

## Article States & Transitions

```
                    ┌─────────────────────────────────────────┐
                    │                                         │
                    ▼                                         │
┌─────────┐   publish   ┌───────────┐   archive   ┌──────────┐
│  draft  │ ──────────► │ published │ ──────────► │ archived │
└─────────┘             └───────────┘             └──────────┘
     ▲                        │                        │
     │                        │                        │
     │        unpublish       │                        │
     └────────────────────────┘                        │
                                                       │
                    ┌──────────────────────────────────┘
                    │
                    ▼
              ┌──────────┐
              │ deleted  │
              └──────────┘
```

## Request Body Examples

### Create Article (POST /article/)

```json
{
    "title": "Breaking Article: New Feature Released",
    "shortDescription": "A brief summary of the new feature (must be 50+ chars).",
    "description": "Full article content with all details (must be 50-50000 chars)...",
    "slug": "breaking-article-new-feature",
    "eventId": 1
}
```

**Note:** New articles are created with `draft` status. Description fields have minimum length requirements.

### Update Article (PUT /article/{uuid})

```json
{
    "title": "Updated: Breaking Article",
    "shortDescription": "Updated summary with additional details.",
    "description": "Updated full content with comprehensive information."
}
```

## Troubleshooting

### 401 Unauthorized (APISIX)

- JWT token missing or expired
- Regenerate token using `apisix/jwt-keys/private.pem`
- Check `jwt_token` environment variable

### 400 Bad Request

- Check request body JSON syntax
- Verify required fields are present
- Ensure description fields meet minimum length (50+ chars)

### 404 Not Found

- POST endpoints need trailing slash: `/api/v2/article/`
- Verify the `article_uuid` is valid
- Run 'Create Article' or 'List All Article' first

### 422 Unprocessable Entity

- Validation errors in request body
- Check `violations` field for details
- Common: duplicate slug, invalid status transition

### 500 Internal Server Error

- Check server logs: `make logs-server`
- Verify database connection
- Check RabbitMQ is running

### Connection Refused

- Ensure Docker containers are running: `make start`
- Verify correct port for runtime
- Check backend logs: `make logs-server`

## Development Commands

```bash
# Start all services
make start

# View logs
make logs-server

# Run tests
make composer-test

# Database shell
make database-shell

# Console commands
make console

# Check APISIX health
curl http://localhost:9080/api/v2/article
```

## Regenerating JWT Token

If the JWT token expires, regenerate using:

```bash
# Generate new JWT with private key
EXP=$(date -d "+1 year" +%s)
JWT_HEADER=$(echo -n '{"alg":"RS256","typ":"JWT"}' | base64 -w0 | tr '+/' '-_' | tr -d '=')
JWT_PAYLOAD=$(echo -n "{\"key\":\"article-api-key\",\"exp\":$EXP}" | base64 -w0 | tr '+/' '-_' | tr -d '=')
JWT_SIGNATURE=$(echo -n "${JWT_HEADER}.${JWT_PAYLOAD}" | \
    openssl dgst -sha256 -sign apisix/jwt-keys/private.pem | \
    base64 -w0 | tr '+/' '-_' | tr -d '=')
echo "${JWT_HEADER}.${JWT_PAYLOAD}.${JWT_SIGNATURE}"
```

Update the `jwt_token` value in the environment file.

## Event Sourcing Notes

All command operations trigger domain events that are:
1. Stored in the PostgreSQL event store
2. Published to RabbitMQ event bus
3. Consumed by projectors to update read models

To inspect events:
```bash
# Access database
make database-shell

# Query event store
SELECT * FROM event_store ORDER BY created_at DESC LIMIT 10;
```

## Need Help?

- API documentation: `http://localhost:8080/api/v2/docs`
- Backend logs: `make logs-server`
- Container access: `make php-shell`
- Unit tests: `make composer-test`
