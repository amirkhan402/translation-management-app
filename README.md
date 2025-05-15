# Translation Management System (TMS)

A modern, scalable, and enterprise-grade Laravel-based API for managing translations and tags with robust multi-language support. Built with best practices, clean architecture, and developer experience in mind.

## 🚀 Key Features

- **RESTful API Design**: Following REST principles with consistent endpoint naming and response formats
- **Advanced Authentication**: Secure API endpoints using Laravel Sanctum with JWT tokens
- **Comprehensive Translation Management**: 
  - CRUD operations with validation
  - Multi-language support
  - Efficient caching for translations
  - Bulk operations support
  - Advanced search and filtering
- **Smart Tag System**: 
  - Hierarchical tag organization
  - Efficient tag-translation relationships
  - Tag-based filtering and grouping
- **Developer Experience**:
  - OpenAPI/Swagger documentation with detailed schemas
  - Consistent error handling and responses
  - Comprehensive logging
  - Clear validation messages
- **Performance Optimizations**:
  - Efficient database queries with proper indexing
  - Caching for frequently accessed data
  - Pagination for large datasets
  - Optimized export functionality

## 🏗 Architecture

### Clean Architecture Implementation
- **Controllers**: Handle HTTP requests and responses, following single responsibility principle
- **Services**: Encapsulate business logic with dependency injection
- **Form Requests**: Handle request validation and authorization
- **Resources**: Transform models into JSON responses
- **Interfaces**: Define contracts for better testability and maintainability

### Design Patterns
- Service Layer Pattern for business logic
- Factory Pattern for object creation
- Strategy Pattern for flexible algorithms
- Observer Pattern for event handling

### Code Organization
```
app/
├── Contracts/          # Interface definitions
├── Http/
│   ├── Controllers/    # API Controllers
│   ├── Requests/       # Form Request validation
│   └── Resources/      # API Resources
├── Models/             # Eloquent models
├── Services/           # Business logic
└── Exceptions/         # Custom exceptions
```

## 🔒 Security Features

- JWT-based authentication
- Request validation and sanitization
- SQL injection prevention
- XSS protection
- CSRF protection
- Rate limiting
- Input validation
- Secure password hashing

## 📚 API Documentation

Interactive API documentation powered by OpenAPI/Swagger:
```
http://localhost:8000/api/documentation
```

Features:
- Detailed request/response schemas
- Authentication flows
- Example requests and responses
- Interactive testing interface
- Error response documentation
- Schema validation

## 🛠 Technical Stack

- **Backend Framework**: Laravel 10.x
- **Database**: MySQL 8.0
- **Cache**: Redis
- **Authentication**: Laravel Sanctum
- **API Documentation**: OpenAPI/Swagger
- **Containerization**: Docker
- **Testing**: PHPUnit
- **Code Quality**: PHPStan, Laravel Pint
- **Version Control**: Git

## 🚀 Getting Started

### Prerequisites
- Docker and Docker Compose
- PHP 8.1+
- Composer
- MySQL 8.0+

### Quick Start

1. Clone and setup:
```bash
git clone <repository-url>
cd translation-management-system
cp .env.example .env
```

2. Start the development environment:
```bash
docker compose up -d
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

3. Access the application:
- API: http://localhost:8000/api
- Documentation: http://localhost:8000/api/documentation
- Health Check: http://localhost:8000/health

### Development Workflow

1. **Local Development**:
```bash
docker compose exec app composer install
docker compose exec app php artisan serve
```

2. **Code Quality**:
```bash
docker compose exec app composer lint
docker compose exec app composer analyse
```

## 🧪 Testing (Planned)

Testing infrastructure will be implemented in future updates, including:
- Unit Tests for individual components
- Feature Tests for API endpoints
- Integration Tests for service interactions
- Performance Tests for load testing
- Security Tests for authentication and authorization

## 📈 Performance Optimizations

- Database indexing for faster queries
- Redis caching for frequently accessed data
- Efficient pagination implementation
- Optimized database queries
- Lazy loading of relationships
- Response compression
- Query optimization

## 🔄 Recent Updates

- Implemented clean architecture principles with service layer
- Added comprehensive API documentation
- Enhanced error handling and logging
- Optimized database queries and caching
- Improved validation using Form Requests
- Added performance monitoring
- Implemented rate limiting
- Enhanced export functionality

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👥 Authors

- **Amir Khan** - *Initial work* - [Your GitHub](https://github.com/amirkhan402)

## 🙏 Acknowledgments

- Laravel Team for the amazing framework
- OpenAPI/Swagger for API documentation
- Docker for containerization
- All contributors who have helped shape this project
