# Translation Management System

A Laravel-based API for managing translations and tags with multi-language support.

## Features

- **Authentication**: Secure API endpoints using Laravel Sanctum
- **Translation Management**: CRUD operations for translations with multi-language support
- **Tag Management**: Organize translations with tags
- **API Documentation**: OpenAPI/Swagger documentation for all endpoints
- **Search & Filter**: Advanced search capabilities for translations
- **Export**: Export translations in a structured format

## API Endpoints

### Authentication
- `POST /api/register` - Register a new user
- `POST /api/login` - Login and get access token
- `POST /api/logout` - Logout (requires authentication)

### Translations (Protected Routes)
- `GET /api/translations` - List all translations
- `POST /api/translations` - Create a new translation
- `GET /api/translations/{id}` - Get a specific translation
- `PUT /api/translations/{id}` - Update a translation
- `DELETE /api/translations/{id}` - Delete a translation
- `GET /api/translations/search` - Search translations with filters
- `GET /api/translations/export` - Export translations

### Tags (Protected Routes)
- `GET /api/tags` - List all tags with their translations
- `POST /api/tags` - Create a new tag
- `GET /api/tags/{id}` - Get a specific tag
- `PUT /api/tags/{id}` - Update a tag
- `DELETE /api/tags/{id}` - Delete a tag

## Authentication

All endpoints (except register and login) require authentication using Laravel Sanctum. Include the token in your requests:

```bash
curl -H "Authorization: Bearer YOUR_TOKEN_HERE" http://localhost:8000/api/tags
```

To get a token:
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email": "your-email@example.com", "password": "your-password"}'
```

## API Documentation

The API is documented using OpenAPI/Swagger annotations. You can access the documentation at:
```
http://localhost:8000/api/documentation
```

The documentation includes:
- Detailed request/response schemas
- Authentication requirements
- Example requests and responses
- Available endpoints and their parameters

## Architecture

### Service Layer
The API uses a service layer (`TranslationService`, `TagService`) to encapsulate business logic. This decouples the controllers from the underlying data and business rules, making the code more testable and maintainable.

### Transformers
Transformers (`TranslationTransformer`, `TagTransformer`) are used to transform models into JSON responses:
- `TranslationTransformer`: Groups translations by key and maps locale => value
- `TagTransformer`: Includes translations grouped by key and mapped by locale => value

### Request Validation
Dedicated Form Request classes (`CreateRequest`, `UpdateRequest`) validate incoming data. All form requests extend `BaseFormRequest` which returns JSON responses for validation errors.

## Setup Instructions

### Prerequisites
- Docker and Docker Compose
- PHP 8.1 or higher
- Composer
- MySQL 8.0 or higher

### Installation

1. Clone the repository:
```bash
git clone <repository-url>
cd translation-management-system
```

2. Copy the environment file:
```bash
cp .env.example .env
```

3. Start the Docker containers:
```bash
docker compose up -d
```

4. Install dependencies:
```bash
docker compose exec app composer install
```

5. Generate application key:
```bash
docker compose exec app php artisan key:generate
```

6. Run migrations and seeders:
```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed --class=TranslationSeeder
```

### Development

The application uses Docker for development. Key services:
- `app`: Laravel application (PHP 8.1)
- `db`: MySQL database
- `nginx`: Web server

### Testing

Run the test suite:
```bash
docker compose exec app php artisan test
```

## Recent Updates

- Added authentication using Laravel Sanctum
- Protected API endpoints with auth middleware
- Added OpenAPI/Swagger documentation
- Improved error handling and logging
- Fixed translation-tag relationship issues
- Added proper request validation
- Implemented transformers for consistent API responses

## Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

This project is licensed under the MIT License.
