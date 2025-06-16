# 📝 Laravel CMS Application

A modern, feature-rich Content Management System built with Laravel, implementing Domain-Driven Design (DDD) architecture with comprehensive user management, content publishing, and role-based access control.

## Overview

This project implements a scalable CMS application with core functionality to:
- Manage users with role-based permissions (Admin, Editor, Author)
- Create, publish, and manage content (Articles, Pages, Media)
- Handle media uploads and file storage
- Implement content categorization and tagging
- Provide RESTful API endpoints with authentication
- Maintain content caching for optimal performance

## 🚀 Features

### User Management
- Complete user CRUD operations with role-based access control
- Authentication via Laravel Sanctum with token management
- Role management (Admin, Editor, Author) with specific permissions
- User profile management with secure password updates
- Rate limiting based on user roles

### Content Management System
- Multi-type content support (Articles, Pages, Media)
- Content workflow states (Draft, Published, Archived)
- Rich content editing with featured images and media attachments
- Content categorization and tagging system
- Advanced content filtering and search capabilities
- Content caching with automatic cache invalidation

### Media & File Handling
- Secure file upload with AWS S3 integration
- Featured image management for content
- Media library with content associations
- File storage service with configurable storage drivers
- Media validation and processing

### System Architecture
- Domain-Driven Design (DDD) implementation
- Clean separation between Domain and Infrastructure layers
- Repository pattern with interface-based design
- Event-driven architecture for content lifecycle
- Comprehensive unit and feature testing
- Database migrations with proper indexing for performance

### Security & Performance
- Role-based authorization policies
- API rate limiting by user role
- Full-text search capabilities (MySQL FULLTEXT indexes)
- Content caching with tag-based invalidation
- Input validation and sanitization

## Prerequisites

- PHP 8.2+
- Composer 2.0+
- Laravel 12.0+
- MySQL 8.0+ (for full-text search support)
- Redis (for caching and queues)
- AWS S3 (for file storage)

## 📦 Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/Amro404/cms.git
   cd cms
   ```

2. **Install composer dependencies**
   ```bash
   composer install
   ```

3. **Create your configuration file**
   ```bash
   cp .env.example .env
   ```

4. **Generate application key**
   ```bash
   php artisan key:generate
   ```

5. **Configure the database and services in `.env`**
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=cms_database
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   # Cache Configuration
   CACHE_DRIVER=redis
   REDIS_HOST=127.0.0.1
   REDIS_PASSWORD=null
   REDIS_PORT=6379
   ```

6. **Run database migrations and seeders**
   ```bash
   php artisan migrate --seed
   ```

7. **Start the application**
   ```bash
   php artisan serve
   ```

8. **Start the queue worker** (if using queues)
   ```bash
   php artisan queue:work
   ```

## 📡 API Endpoints

### Authentication Endpoints

#### 🔐 POST `/api/v1/auth/register`
Register a new user account.

**Request Body:**
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

**Success Response:**
```json
{
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "created_at": "2025-06-16T10:00:00.000000Z",
        "updated_at": "2025-06-16T10:00:00.000000Z"
    },
    "token": "1|abc123def456..."
}
```

#### 🔐 POST `/api/v1/auth/login`
Authenticate user and receive access token.

**Request Body:**
```json
{
    "email": "john@example.com",
    "password": "password123"
}
```

**Success Response:**
```json
{
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com"
    },
    "token": "1|abc123def456..."
}
```

#### 🔐 POST `/api/v1/auth/logout`
**Headers:** `Authorization: Bearer {token}`

Logout and invalidate current token.

#### 🔐 GET `/api/v1/auth/user`
**Headers:** `Authorization: Bearer {token}`

Get authenticated user profile.

#### 🔐 PUT `/api/v1/auth/profile`
**Headers:** `Authorization: Bearer {token}`

Update user profile information.

---

### User Management Endpoints

#### 👥 GET `/api/v1/users`
**Headers:** `Authorization: Bearer {token}`
**Permissions:** Admin only

Get paginated list of all users.

#### 👥 POST `/api/v1/users`
**Headers:** `Authorization: Bearer {token}`
**Permissions:** Admin only

Create a new user.

**Request Body:**
```json
{
    "name": "Jane Smith",
    "email": "jane@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

#### 👥 GET `/api/v1/users/{id}`
**Headers:** `Authorization: Bearer {token}`

Get specific user details.

#### 👥 PUT `/api/v1/users/{id}`
**Headers:** `Authorization: Bearer {token}`

Update user information.

#### 👥 DELETE `/api/v1/users/{id}`
**Headers:** `Authorization: Bearer {token}`
**Permissions:** Admin only

Delete a user.

---

### Content Management Endpoints

#### 📄 GET `/api/v1/contents`
**Headers:** `Authorization: Bearer {token}`

Get paginated content list with advanced filtering.

**Query Parameters:**
- `search` - Full-text search in title and body
- `status` - Filter by content status (DRAFT, PUBLISHED, ARCHIVED)
- `type` - Filter by content type (ARTICLE, PAGE, MEDIA)
- `category_id` - Filter by category ID
- `tag_id` - Filter by tag ID
- `author_id` - Filter by author ID
- `sort_by` - Sort field (default: published_at)
- `sort_direction` - Sort direction (asc, desc)
- `per_page` - Items per page (default: 15)

#### 📄 POST `/api/v1/contents`
**Headers:** `Authorization: Bearer {token}`
**Permissions:** Author, Editor, Admin

Create new content.

**Request Body:**
```json
{
    "title": "Getting Started with Laravel",
    "body": "Laravel is a powerful PHP framework...",
    "excerpt": "A comprehensive guide to Laravel basics",
    "type": "ARTICLE",
    "status": "DRAFT",
    "categories": [1, 2],
    "tags": [1, 3, 5],
    "featured_image": "file upload",
    "media": ["file uploads"]
}
```

#### 📄 GET `/api/v1/contents/{id}`
**Headers:** `Authorization: Bearer {token}`

Get content by ID with relationships.

#### 📄 PUT `/api/v1/contents/{id}`
**Headers:** `Authorization: Bearer {token}`
**Permissions:** Content owner or Admin

Update existing content.

#### 📄 DELETE `/api/v1/contents/{id}`
**Headers:** `Authorization: Bearer {token}`
**Permissions:** Content owner or Admin

Delete content.

#### 📄 POST `/api/v1/contents/{id}/publish`
**Headers:** `Authorization: Bearer {token}`
**Permissions:** Editor, Admin

Publish content (change status to PUBLISHED).

#### 📄 POST `/api/v1/contents/{id}/draft`
**Headers:** `Authorization: Bearer {token}`
**Permissions:** Content owner, Editor, Admin

Change content status to DRAFT.

#### 📄 POST `/api/v1/contents/{id}/archive`
**Headers:** `Authorization: Bearer {token}`
**Permissions:** Admin only

Archive content.

#### 📄 GET `/api/v1/contents/category/{slug}`
Get contents by category slug.

#### 📄 GET `/api/v1/contents/tag/{slug}`
Get contents by tag slug.

---

## 🏗️ Project Structure

This project follows Domain-Driven Design (DDD) principles with clear separation between `Domain` and `Infrastructure` layers.

```
src/
├── Domain/
│   ├── Content/
│   │   ├── DTOs/
│   │   │   ├── CreateContentData.php
│   │   │   └── UpdateContentData.php
│   │   ├── Enums/
│   │   │   ├── ContentStatus.php
│   │   │   └── ContentType.php
│   │   ├── Events/
│   │   │   ├── ContentCreated.php
│   │   │   ├── ContentPublished.php
│   │   │   └── ContentUpdated.php
│   │   ├── Listeners/
│   │   │   └── SendContentPublishedNotification.php
│   │   ├── Repositories/
│   │   │   ├── ContentRepositoryInterface.php
│   │   │   └── MediaRepositoryInterface.php
│   │   ├── Services/
│   │   │   ├── ContentService.php
│   │   │   ├── MediaService.php
│   │   │   ├── FileStorageService.php
│   │   │   └── CacheService.php
│   │   └── ValueObjects/
│   │       └── ContentSlug.php
│   └── User/
│       ├── DTOs/
│       │   ├── CreateUserData.php
│       │   └── UpdateUserData.php
│       ├── Repositories/
│       │   └── UserRepositoryInterface.php
│       └── Services/
│           └── UserService.php
└── Infrastructure/
    └── Repositories/
        └── Eloquent/
            ├── Content/
            │   ├── EloquentContentRepository.php
            │   └── EloquentMediaRepository.php
            └── User/
                └── EloquentUserRepository.php
```

### Laravel Application Structure
```
app/
├── Http/
│   ├── Controllers/
│   │   └── API/V1/
│   │       ├── AuthController.php
│   │       ├── ContentController.php
│   │       └── UserController.php
│   ├── Middleware/
│   │   └── RateLimitByRole.php
│   ├── Requests/
│   │   ├── CreateContentRequest.php
│   │   ├── UpdateContentRequest.php
│   │   └── IndexContentRequest.php
│   └── Resources/
│       ├── ContentResource.php
│       ├── UserResource.php
│       ├── CategoryResource.php
│       ├── TagResource.php
│       └── MediaResource.php
├── Models/
│   ├── Content.php
│   ├── User.php
│   ├── Category.php
│   ├── Tag.php
│   └── Media.php
└── Policies/
    └── UserPolicy.php
```

---

## 🧩 Domain Overview

### 🔹 Content Domain

Handles all content-related operations including creation, publishing, media management, and caching.

**Key Components:**
- **DTOs:** `CreateContentData`, `UpdateContentData` - Data transfer objects for content operations
- **Enums:** `ContentStatus` (DRAFT, PUBLISHED, ARCHIVED), `ContentType` (ARTICLE, PAGE, MEDIA)
- **Events:** `ContentCreated`, `ContentPublished`, `ContentUpdated` - Domain events
- **Services:**
  - `ContentService` - Core content business logic
  - `MediaService` - Media file management
  - `FileStorageService` - File storage abstraction
  - `CacheService` - Content caching strategy
- **Repositories:** Interface-based repository pattern for data persistence
- **ValueObjects:** `ContentSlug` - Encapsulates slug generation logic

**Key Features:**
- Content lifecycle management (Draft → Published → Archived)
- SEO-friendly slug generation with uniqueness validation
- Media attachment and featured image handling
- Content categorization and tagging
- Full-text search capabilities
- Cache management with automatic invalidation

### 🔹 User Domain

Manages user authentication, authorization, and profile management.

**Key Components:**
- **DTOs:** `CreateUserData`, `UpdateUserData` - User data transfer objects
- **Services:** `UserService` - User business logic including authentication
- **Repositories:** `UserRepositoryInterface` - User data persistence contract

**Key Features:**
- User CRUD operations with validation
- Secure authentication with token management
- Role-based access control (Admin, Editor, Author)
- Password hashing and validation
- Profile management capabilities

---

## 🏗️ Infrastructure Layer

Implements persistence and external service integrations using Laravel's Eloquent ORM.

**Key Components:**
- **Eloquent Repositories:** Concrete implementations of domain repository interfaces
- **Database Migrations:** Schema definitions with proper indexing
- **Model Relationships:** Eloquent relationships for complex queries
- **Service Bindings:** Dependency injection configuration

---

## 🧪 Testing

The application includes comprehensive testing coverage with **105+ tests** across unit and feature testing:

### Test Structure
```
tests/
├── Unit/
│   ├── UserDomainTest.php
│   └── ContentDomainTest.php
└── Feature/
    ├── AuthControllerTest.php
    ├── UserControllerTest.php
    ├── ContentControllerTest.php
    └── FeatureTestCase.php
```

### Running Tests
```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Run with coverage (requires Xdebug)
php artisan test --coverage
```

### Test Coverage
- **Unit Tests:** Focus on domain logic, services, and DTOs
- **Feature Tests:** End-to-end API testing with authentication
- **Database Tests:** Use RefreshDatabase trait for isolation
- **Mocking:** Extensive use of Mockery for service dependencies

---

## 🚀 Key Features & Capabilities

### Advanced Content Management
- **Multi-format Support:** Articles, Pages, and Media content types
- **Workflow Management:** Draft → Published → Archived lifecycle
- **Rich Media:** Featured images, media galleries, file attachments
- **Content Relationships:** Categories and tags with many-to-many relationships

### Performance Optimizations
- **Caching Strategy:** Redis-based content caching with tag invalidation
- **Database Indexing:** Optimized indexes for search and filtering
- **Full-text Search:** MySQL FULLTEXT indexes for content search
- **Eager Loading:** Optimized database queries with relationship loading
- **File Storage:** AWS S3 integration for scalable media storage

### Security Features
- **Authentication:** Laravel Sanctum token-based authentication
- **Authorization:** Role-based permissions with Spatie Laravel Permission
- **Input Validation:** Comprehensive request validation
- **File Security:** Secure file upload with type validation
- **Rate Limiting:** Role-based API rate limiting

### Developer Experience
- **Domain-Driven Design:** Clean architecture with separated concerns
- **Interface-based Design:** Dependency injection with interface contracts
- **Event-Driven:** Domain events for loose coupling
- **Comprehensive Testing:** High test coverage with multiple test types
- **API Documentation:** Well-documented RESTful API endpoints

---
