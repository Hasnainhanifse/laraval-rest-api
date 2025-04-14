# Laravel REST API

A RESTful API built with Laravel for managing purchase orders and suppliers.

## Time Estimate (3-Day)

### Day 1: Setup and Core Features (5-6 hours)
- Initial project setup and configuration (1 hour)
- Database design and migrations (1 hour)
- Authentication implementation (1.5 hours)
- Basic CRUD for Suppliers (2 hours)

### Day 2: Purchase Orders and Items (5-6 hours)
- Purchase Order CRUD operations (2 hours)
- Order Items implementation (2 hours)
- Validation and business logic (2 hours)


Total Development Time: 12-14 hours across 3 days

## Setup Instructions

### Prerequisites

- PHP 8.1 or higher
- Composer
- MySQL 5.7 or higher
- Git
- Docker (optional)

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

### Docker Setup (Optional)

1. Make sure Docker and Docker Compose are installed on your system.

2. Build and start the containers:
```bash
docker-compose up -d
```

3. Install dependencies:
```bash
docker-compose exec app composer install
```

4. Generate application key:
```bash
docker-compose exec app php artisan key:generate
```

5. Run migrations:
```bash
docker-compose exec app php artisan migrate
```

6. Access the application:
- API will be available at: `http://localhost:8000`
- Database will be available at: `localhost:3306`

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

# Laravel REST API Documentation

## Authentication

All protected routes require authentication using Laravel Sanctum. Include the following header in your requests:

```
Authorization: Bearer {your_token}
```

To obtain a token:
1. Register a new user: `POST /api/register`
2. Login: `POST /api/login`

## API Endpoints

### Authentication

#### Register User
- **URL**: `/api/register`
- **Method**: `POST`
- **Body**:
  ```json
  {
    "name": "string",
    "email": "string",
    "password": "string",
    "password_confirmation": "string"
  }
  ```

#### Login
- **URL**: `/api/login`
- **Method**: `POST`
- **Body**:
  ```json
  {
    "email": "string",
    "password": "string"
  }
  ```

#### Logout
- **URL**: `/api/logout`
- **Method**: `POST`
- **Headers**: `Authorization: Bearer {token}`

#### Get Current User
- **URL**: `/api/me`
- **Method**: `GET`
- **Headers**: `Authorization: Bearer {token}`

### Purchase Orders

#### List Purchase Orders
- **URL**: `/api/purchase-orders`
- **Method**: `GET`
- **Headers**: `Authorization: Bearer {token}`
- **Query Parameters**:
  - `per_page` (default: 15): Number of items per page
  - `sort_by`: Field to sort by (e.g., `created_at`, `id`)
  - `sort_direction`: Sort direction (`asc` or `desc`)
  - `status`: Filter by status
  - `supplier_id`: Filter by supplier ID
  - `date_from`: Filter by date range start
  - `date_to`: Filter by date range end

#### Get Purchase Order
- **URL**: `/api/purchase-orders/{id}`
- **Method**: `GET`
- **Headers**: `Authorization: Bearer {token}`

#### Create Purchase Order
- **URL**: `/api/purchase-orders`
- **Method**: `POST`
- **Headers**: `Authorization: Bearer {token}`
- **Body**:
  ```json
  {
    "supplier_id": "integer",
    "order_date": "date",
    "expected_delivery_date": "date",
    "notes": "string"
  }
  ```

#### Update Purchase Order
- **URL**: `/api/purchase-orders/{id}`
- **Method**: `PUT`
- **Headers**: `Authorization: Bearer {token}`
- **Body**: Same as Create

#### Delete Purchase Order
- **URL**: `/api/purchase-orders/{id}`
- **Method**: `DELETE`
- **Headers**: `Authorization: Bearer {token}`

#### Send Purchase Order for Approval
- **URL**: `/api/purchase-orders/{id}/send-approval`
- **Method**: `POST`
- **Headers**: `Authorization: Bearer {token}`

### Order Items

#### List Order Items
- **URL**: `/api/purchase-orders/{purchase_order_id}/items`
- **Method**: `GET`
- **Headers**: `Authorization: Bearer {token}`
- **Query Parameters**:
  - `per_page` (default: 15): Number of items per page
  - `sort_by`: Field to sort by (e.g., `created_at`, `id`)
  - `sort_direction`: Sort direction (`asc` or `desc`)
  - `purchase_order_id`: Filter by purchase order ID

#### Get Order Item
- **URL**: `/api/purchase-orders/{purchase_order_id}/items/{order_item_id}`
- **Method**: `GET`
- **Headers**: `Authorization: Bearer {token}`

#### Create Order Item
- **URL**: `/api/purchase-orders/{purchase_order_id}/items`
- **Method**: `POST`
- **Headers**: `Authorization: Bearer {token}`
- **Body**:
  ```json
  {
    "sku": "string",
    "description": "string",
    "qty": "integer",
    "unit_price": "decimal"
  }
  ```

#### Update Order Item
- **URL**: `/api/purchase-orders/{purchase_order_id}/items/{order_item_id}`
- **Method**: `PUT`
- **Headers**: `Authorization: Bearer {token}`
- **Body**: Same as Create

#### Delete Order Item
- **URL**: `/api/purchase-orders/{purchase_order_id}/items/{order_item_id}`
- **Method**: `DELETE`
- **Headers**: `Authorization: Bearer {token}`

### Suppliers

#### List Suppliers
- **URL**: `/api/suppliers`
- **Method**: `GET`
- **Headers**: `Authorization: Bearer {token}`
- **Query Parameters**:
  - `per_page` (default: 15): Number of items per page
  - `sort_by`: Field to sort by (e.g., `name`, `id`)
  - `sort_direction`: Sort direction (`asc` or `desc`)
  - `search`: Search by name or email

#### Get Supplier
- **URL**: `/api/suppliers/{id}`
- **Method**: `GET`
- **Headers**: `Authorization: Bearer {token}`

#### Create Supplier
- **URL**: `/api/suppliers`
- **Method**: `POST`
- **Headers**: `Authorization: Bearer {token}`
- **Body**:
  ```json
  {
    "name": "string",
    "email": "string",
    "phone": "string",
    "address": "string"
  }
  ```

#### Update Supplier
- **URL**: `/api/suppliers/{id}`
- **Method**: `PUT`
- **Headers**: `Authorization: Bearer {token}`
- **Body**: Same as Create

#### Delete Supplier
- **URL**: `/api/suppliers/{id}`
- **Method**: `DELETE`
- **Headers**: `Authorization: Bearer {token}`

### Approval Logs

#### List Approval Logs
- **URL**: `/api/approval-logs`
- **Method**: `GET`
- **Headers**: `Authorization: Bearer {token}`
- **Query Parameters**:
  - `per_page` (default: 15): Number of items per page
  - `sort_by`: Field to sort by (e.g., `created_at`, `id`)
  - `sort_direction`: Sort direction (`asc` or `desc`)
  - `purchase_order_id`: Filter by purchase order ID
  - `status`: Filter by status

## Response Formats

### Success Response
```json
{
    "data": [...],
    "meta": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 15,
        "total": 75,
        "from": 1,
        "to": 15
    }
}
```

### Error Response
```json
{
    "message": "Error message here"
}
```

## Status Codes

- `200`: Success
- `201`: Created
- `204`: No Content (Success)
- `400`: Bad Request
- `401`: Unauthorized
- `403`: Forbidden
- `404`: Not Found
- `422`: Validation Error
- `500`: Server Error

## Pagination

All list endpoints support pagination with the following parameters:
- `per_page`: Number of items per page (default: 15)
- `page`: Page number to retrieve

## Sorting

List endpoints support sorting with:
- `sort_by`: Field to sort by
- `sort_direction`: `asc` or `desc`

## Filtering

Each endpoint supports relevant filters as query parameters.