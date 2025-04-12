# Laravel REST API

A RESTful API built with Laravel for managing purchase orders and suppliers.

## Time Estimate (3-Day Plan)

### Day 1: Setup and Core Features (5-6 hours)
- Initial project setup and configuration (1 hour)
- Database design and migrations (1 hour)
- Authentication implementation (1.5 hours)
- Basic CRUD for Suppliers (2 hours)

### Day 2: Purchase Orders and Items (5-6 hours)
- Purchase Order CRUD operations (2 hours)
- Order Items implementation (2 hours)
- Validation and business logic (2 hours)

### Day 3: Polish and Deployment (4-5 hours)
- API documentation setup (1.5 hours)
- Error handling implementation (1 hour)
- Deployment setup and configuration (1 hour)
- Final deployment and verification (1 hour)

Total Development Time: 14-17 hours across 3 days

## Setup Instructions

### Prerequisites

- PHP 8.1 or higher
- Composer
- MySQL 5.7 or higher
- Git

### Local Development Setup

1. Clone the repository:
```bash
git clone <repository-url>
cd laravel-rest-api
```

2. Install dependencies:
```bash
composer install
```

3. Create environment file:
```bash
cp .env.example .env
```

4. Configure your database in `.env`:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

5. Generate application key:
```bash
php artisan key:generate
```

6. Run migrations:
```bash
php artisan migrate
```

7. Start the development server:
```bash
php artisan serve
```

### Deployment Setup (Render)

1. Push your code to a Git repository
2. Connect your repository to Render
3. Create a new Web Service using the existing `render.yaml` configuration
4. The deployment will automatically:
   - Set up the MySQL database
   - Install dependencies
   - Generate application key
   - Run migrations

## API Constraints

### Authentication
- API uses Laravel Sanctum for token-based authentication
- All endpoints require authentication except login/register

### Purchase Orders
- Status can only be: 'w', 'p', 'a', 'r'
- Total amount is automatically calculated from order items
- Only pending orders can be sent for approval
- Once approved/rejected, orders cannot be modified

### Order Items
- Quantity must be greater than 0
- Unit price must be greater than 0
- Each item must be associated with a purchase order
- Items cannot be modified once the purchase order is approved/rejected

### Suppliers
- Each supplier must have a unique email
- Phone number is required and must be valid
- One supplier can have multiple purchase orders

## Rate Limits
- API requests are limited to 60 per minute per user

## Error Handling
- All errors return appropriate HTTP status codes
- Error responses include meaningful messages
- Model not found errors return 404 with custom messages
