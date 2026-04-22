# LocalPhone Marketplace

A full-stack web application for buying and selling second-hand mobile phones. Local shops can list their inventory and customers can browse, compare, and contact sellers.

## Features

### Customer Features
- User registration/login
- Browse phones by brand, price, condition, and location
- Search functionality
- View phone details
- Contact shop / WhatsApp integration
- Wishlist

### Shop Owner Features
- Register/Login
- Add/Edit/Delete phone listings
- Upload images
- Set price and condition
- Manage inventory
- View customer inquiries

### Admin Features
- Manage users
- Approve/reject shop registrations
- Monitor listings
- Remove spam/fake products

## Tech Stack

- **Frontend:** HTML5, CSS3, JavaScript (Vanilla)
- **Backend:** PHP
- **Database:** MySQL
- **Server:** Apache (XAMPP)

## Installation

### Prerequisites
- XAMPP (or similar LAMP/WAMP stack)
- PHP 7.4 or higher
- MySQL 5.7 or higher

### Setup Instructions

1. **Copy Project Files**
   - Copy the `recell` folder to your XAMPP's `htdocs` directory
   - Path should be: `d:\xamp\htdocs\recell`

2. **Start XAMPP Services**
   - Start Apache server
   - Start MySQL server

3. **Create Database**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Import the database schema from `database/schema.sql`
   - This will create the `localphone_marketplace` database with all required tables

4. **Configure Database Connection**
   - The database configuration is in `config/database.php`
   - Default settings:
     - Host: localhost
     - Username: root
     - Password: (empty)
     - Database: localphone_marketplace

5. **Access the Application**
   - Open your browser and go to: http://localhost/recell

## Default Login Credentials

### Admin
- Email: admin@localphone.com
- Password: admin123

### Shop Owner (Pre-registered)
- Email: shop@localphone.com
- Password: shop123

## Project Structure

```
/recell
│── /api                  # Backend API endpoints
│   ├── admin.php         # Admin operations
│   ├── inquiry.php       # Contact seller
│   ├── login.php         # User login
│   ├── products.php      # Product CRUD
│   ├── register.php      # User registration
│   └── wishlist.php      # Wishlist management
│── /admin                # Admin panel
│   └── dashboard.php     # Admin dashboard
│── /assets               # Frontend assets
│   ├── /css
│   │   └── style.css     # Main stylesheet
│   └── /js
│       └── main.js       # Main JavaScript
│── /config               # Configuration files
│   └── database.php      # Database connection
│── /database             # Database files
│   └── schema.sql        # Database schema
│── /includes             # PHP includes
│   └── auth.php          # Authentication helpers
│── /pages                # Frontend pages
│   ├── login.php         # Login page
│   ├── product.php       # Product detail page
│   ├── products.php      # Product listing page
│   ├── register.php      # Registration page
│   ├── shop-dashboard.php # Shop owner dashboard
│   └── wishlist.php      # User wishlist
│── /uploads              # Product images
│── index.php             # Home page
│── .htaccess             # Apache configuration
│── README.md             # This file
│── database/
│── includes/
│── pages/
│── admin/
│── api/
│── assets/
│── uploads/
```

## Database Schema

### Tables
1. **users** - User accounts (customers, shops, admin)
2. **shops** - Shop information linked to users
3. **products** - Phone listings
4. **inquiries** - Customer messages to sellers
5. **wishlist** - Customer saved products

## API Endpoints

### Authentication
- `POST /api/register.php` - Register new user
- `POST /api/login.php` - User login
- `GET /api/logout.php` - User logout

### Products
- `GET /api/products.php` - Get all products (with filters)
- `GET /api/products.php/{id}` - Get single product
- `POST /api/products.php` - Add new product (shop only)
- `PUT /api/products.php/{id}` - Update product (shop only)
- `DELETE /api/products.php/{id}` - Delete product (shop only)

### Wishlist
- `GET /api/wishlist.php` - Get user's wishlist
- `POST /api/wishlist.php` - Add to wishlist
- `DELETE /api/wishlist.php` - Remove from wishlist

### Inquiry
- `POST /api/inquiry.php` - Send message to seller

### Admin
- `POST /api/admin.php` - Admin operations (approve/reject shops, remove products)

## Security Features

- Password hashing with bcrypt
- SQL injection prevention with prepared statements
- Input validation and sanitization
- Session-based authentication
- Role-based access control
- File upload validation

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## License

This project is for educational purposes.

## Support

For any issues or questions, please contact the development team.