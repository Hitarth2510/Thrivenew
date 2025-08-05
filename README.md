# Thrive Cafe Billing Software

A comprehensive, single-location, web-based billing software designed specifically for Thrive Cafe. This application provides a complete solution for order management, menu configuration, promotional offers, and detailed sales analytics.

## 🚀 Features

### Direct-Access Analytics Dashboard
- **Visual Sales & Profit Statistics**: Interactive charts with real-time data
- **Top Selling Items**: Display most popular products and combos
- **Date Range Filters**: Today, Past 7 Days, Last 28 Days, Custom Range
- **Dynamic Updates**: All components update when filters are applied

### Multi-Device Order Management
- **Tabbed Billing Interface**: Handle multiple orders simultaneously
- **Draft Order System**: Auto-save prevents data loss
- **Dynamic Item Search**: Real-time search with auto-completion
- **Checkout & Payment Process**: Complete payment workflow with discount options

### Menu & Profit Management
- **Product Management**: Add, edit, delete products with cost tracking
- **Combo Management**: Create combinations with auto-calculated costs
- **Profit Tracking**: Real-time profit calculation for all items

### Promotions & Offers Engine
- **Time-Based Offers**: Set specific date and time ranges
- **Flexible Discounts**: Apply to entire bill or specific items
- **Automatic Application**: Offers applied during checkout

### Data Export & Reporting
- **CSV Export**: Sales reports, product lists, combo details, customer data
- **Date Range Support**: Export data for any time period
- **Comprehensive Reports**: Detailed analytics for business insights

## 🛠 Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript (ES6+), Bootstrap 5
- **Backend**: PHP 8.x
- **Database**: MySQL 8.x / MariaDB
- **Libraries**: Chart.js, jQuery
- **Server**: Apache/Nginx compatible

## 📋 Prerequisites

- PHP 8.0 or higher
- MySQL 8.0 or MariaDB 10.4+
- Web server (Apache/Nginx)
- Modern web browser

## 🔧 Installation

1. **Clone/Download the project**
   ```bash
   # Download and extract to your web server directory
   # Example: C:\xampp\htdocs\Thrive (for XAMPP on Windows)
   ```

2. **Database Setup**
   - Create a MySQL database named `thrive_cafe_db`
   - Update database credentials in `config/config.php` if needed
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'thrive_cafe_db');
   ```

3. **Initialize Database with Sample Data**
   ```bash
   # Access via browser or command line
   http://localhost/Thrive/setup/init_data.php
   ```

4. **Set Permissions** (Linux/Mac)
   ```bash
   chmod -R 755 /path/to/Thrive
   chmod -R 777 logs/
   ```

5. **Access the Application**
   ```
   http://localhost/Thrive/
   ```

## 📁 Project Structure

```
Thrive/
├── api/                    # API endpoints
│   ├── dashboard.php       # Dashboard data API
│   ├── products.php        # Product management API
│   ├── combos.php          # Combo management API
│   ├── offers.php          # Offers management API
│   ├── orders.php          # Order processing API
│   ├── search.php          # Item search API
│   └── export.php          # Data export API
├── assets/
│   ├── css/
│   │   └── style.css       # Custom styles
│   └── js/
│       └── app.js          # Main application logic
├── config/
│   └── config.php          # Database and app configuration
├── includes/
│   └── modals.php          # Modal dialogs
├── setup/
│   └── init_data.php       # Database initialization script
├── logs/                   # Application logs
├── index.php               # Main application file
└── README.md
```

## 🎯 Usage Guide

### Dashboard
- **Default View**: Opens directly to analytics dashboard
- **Date Filters**: Use buttons to filter data by time period
- **Export**: Click export buttons to download CSV reports

### Order Management
- **New Bill**: Click "+" to create additional bill tabs
- **Add Items**: Use search bar to find and add products/combos
- **Checkout**: Click checkout button to process payment
- **Payment**: Select payment method and apply discounts

### Menu Management
- **Products**: Add items with selling price and making cost
- **Combos**: Create combinations from existing products
- **Auto-calculation**: Combo costs calculated automatically

### Offers & Promotions
- **Create Offers**: Set time-based discounts
- **Flexible Application**: Apply to all items or specific products
- **Automatic Activation**: Offers applied during valid time periods

## 🔐 Security Features

- **SQL Injection Protection**: All queries use prepared statements
- **Input Validation**: Server-side validation for all inputs
- **XSS Prevention**: HTML encoding for user inputs
- **Error Logging**: Comprehensive error tracking

## 📊 Database Schema

### Core Tables
- `products`: Menu items with pricing
- `combos`: Product combinations
- `combo_items`: Many-to-many relationship
- `offers`: Promotional campaigns
- `offer_items`: Offer applicability
- `customers`: Customer information
- `sales_orders`: Completed transactions
- `sales_order_items`: Order line items

## 🎨 Responsive Design

- **Mobile First**: Optimized for all screen sizes
- **Touch Friendly**: Large buttons and easy navigation
- **Fast Loading**: Optimized assets and efficient queries
- **Offline Capable**: Draft orders saved locally

## 🔄 API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/dashboard.php` | GET | Dashboard statistics |
| `/api/products.php` | GET/POST/PUT/DELETE | Product CRUD |
| `/api/combos.php` | GET/POST/PUT/DELETE | Combo CRUD |
| `/api/offers.php` | GET/POST/PUT/DELETE | Offers CRUD |
| `/api/orders.php` | POST | Process orders |
| `/api/search.php` | GET | Search items |
| `/api/export.php` | GET | Export data |

## 🐛 Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `config/config.php`
   - Ensure MySQL service is running
   - Verify database exists

2. **Permission Denied**
   - Set proper file permissions (755 for folders, 644 for files)
   - Ensure web server has read access

3. **Chart Not Loading**
   - Check internet connection (Chart.js CDN)
   - Verify JavaScript console for errors

4. **Search Not Working**
   - Ensure products exist in database
   - Check API endpoint accessibility

## 🚀 Performance Optimization

- **Database Indexing**: Optimized queries with proper indexes
- **Caching**: Browser caching for static assets
- **Minification**: Compressed CSS and JavaScript
- **Lazy Loading**: Charts load only when needed

## 🔒 Backup & Maintenance

### Regular Backups
```sql
-- Database backup
mysqldump -u root -p thrive_cafe_db > backup_$(date +%Y%m%d).sql
```

### Log Rotation
- Monitor `logs/error.log` for issues
- Rotate logs regularly to prevent disk space issues

## 📞 Support

For technical support or feature requests:
- Review this documentation
- Check the troubleshooting section
- Examine browser console for JavaScript errors
- Review server logs for PHP errors

## 📄 License

This software is proprietary and developed specifically for Thrive Cafe. All rights reserved.

## 🔄 Version History

### v1.0.0 (Current)
- Initial release
- Complete billing system
- Dashboard analytics
- Order management
- Menu management
- Offers system
- Data export functionality

---

**Thrive Cafe Billing Software** - Streamlining cafe operations with modern web technology.
