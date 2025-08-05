# 🚀 Thrive Cafe Billing Software - Installation Guide

## Quick Start (5 Minutes Setup)

### Prerequisites
- **XAMPP/WAMP/LAMP**: Web server with PHP 7.4+ and MySQL 5.7+
- **Modern Browser**: Chrome, Firefox, Safari, or Edge

### Step 1: Download & Extract
1. Extract the Thrive folder to your web server directory:
   - **XAMPP**: `C:\xampp\htdocs\Thrive`
   - **WAMP**: `C:\wamp64\www\Thrive`
   - **Linux**: `/var/www/html/Thrive`

### Step 2: Start Web Server
- Start Apache and MySQL services from your control panel
- Ensure both services are running (green status)

### Step 3: System Check
1. Open browser and navigate to: `http://localhost/Thrive/system-check.php`
2. Verify all checks pass ✅
3. Fix any issues shown in red ❌

### Step 4: Initialize Database
1. Navigate to: `http://localhost/Thrive/setup/init_data.php`
2. Wait for "Database initialization completed successfully!" message
3. This creates all tables and adds sample data

### Step 5: Launch Application
1. Navigate to: `http://localhost/Thrive/`
2. You should see the Analytics Dashboard
3. Start using the application immediately!

---

## 📋 Detailed Installation

### Windows (XAMPP)

1. **Download XAMPP**
   - Visit: https://www.apachefriends.org/
   - Download latest version with PHP 8.0+

2. **Install XAMPP**
   - Run installer as Administrator
   - Install to `C:\xampp`
   - Start Apache and MySQL from Control Panel

3. **Extract Thrive**
   ```
   Extract to: C:\xampp\htdocs\Thrive\
   ```

4. **Access Application**
   ```
   http://localhost/Thrive/system-check.php
   ```

### Linux (Ubuntu/Debian)

1. **Install LAMP Stack**
   ```bash
   sudo apt update
   sudo apt install apache2 mysql-server php php-mysql php-pdo php-mbstring
   ```

2. **Start Services**
   ```bash
   sudo systemctl start apache2
   sudo systemctl start mysql
   ```

3. **Extract Thrive**
   ```bash
   sudo cp -r Thrive/ /var/www/html/
   sudo chown -R www-data:www-data /var/www/html/Thrive/
   sudo chmod -R 755 /var/www/html/Thrive/
   ```

4. **Access Application**
   ```
   http://localhost/Thrive/system-check.php
   ```

### macOS (MAMP)

1. **Download MAMP**
   - Visit: https://www.mamp.info/
   - Download free version

2. **Install and Configure**
   - Set document root to Applications/MAMP/htdocs/
   - Start Apache and MySQL

3. **Extract Thrive**
   ```
   Extract to: /Applications/MAMP/htdocs/Thrive/
   ```

4. **Access Application**
   ```
   http://localhost:8888/Thrive/system-check.php
   ```

---

## ⚙️ Configuration

### Database Settings
Edit `config/config.php` if needed:

```php
define('DB_HOST', 'localhost');     // Database host
define('DB_USER', 'root');          // Database username
define('DB_PASS', '');              // Database password
define('DB_NAME', 'thrive_cafe_db'); // Database name
```

### Application Settings
```php
define('APP_NAME', 'Thrive Cafe Billing Software');
define('TIMEZONE', 'Asia/Kolkata'); // Your timezone
```

### Security (Production)
1. Change database password
2. Enable HTTPS
3. Set proper file permissions
4. Regular backups

---

## 🔧 Troubleshooting

### Common Issues

#### 1. "Database connection failed"
**Solution:**
- Ensure MySQL is running
- Check database credentials in `config/config.php`
- Create database manually: `CREATE DATABASE thrive_cafe_db;`

#### 2. "Permission denied"
**Linux/Mac:**
```bash
sudo chown -R www-data:www-data /var/www/html/Thrive/
sudo chmod -R 755 /var/www/html/Thrive/
sudo chmod -R 777 /var/www/html/Thrive/logs/
```

**Windows:**
- Run as Administrator
- Check folder permissions

#### 3. "Page not found" or 404 errors
- Verify web server is running
- Check file paths are correct
- Ensure .htaccess is supported (Apache)

#### 4. "Charts not loading"
- Check internet connection (Chart.js CDN)
- Open browser developer tools (F12)
- Look for JavaScript errors

#### 5. "Search not working"
- Ensure database has products
- Check browser network tab for API errors
- Verify API endpoints are accessible

### System Requirements Check

Run this in terminal to verify PHP:
```bash
php -v                    # Check PHP version
php -m | grep pdo        # Check PDO extension
php -m | grep mysql      # Check MySQL extension
```

### Database Manual Setup

If automatic setup fails:
```sql
CREATE DATABASE thrive_cafe_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE thrive_cafe_db;
-- Then run the init_data.php script
```

---

## 📊 Features Walkthrough

### 1. Dashboard
- **Access**: Default landing page
- **Features**: Sales analytics, profit tracking, top items
- **Filters**: Today, 7 days, 28 days, custom range

### 2. Order Management
- **New Bill**: Click "+" to add bill tabs
- **Search Items**: Type to find products/combos
- **Quantity**: Use +/- buttons or type directly
- **Checkout**: Select payment method, apply discounts

### 3. Menu Management
- **Products**: Add individual items
- **Combos**: Create product combinations
- **Pricing**: Set selling price and making cost
- **Status**: Enable/disable items

### 4. Offers & Promotions
- **Time-based**: Set date and time ranges
- **Flexible**: Apply to all items or specific ones
- **Automatic**: Applied during valid periods

### 5. Data Export
- **CSV Reports**: Sales, products, combos, customers
- **Date Filtering**: Export data for any period
- **Excel Compatible**: Open directly in spreadsheet software

---

## 🔒 Security & Backup

### Regular Backups
```bash
# Database backup
mysqldump -u root -p thrive_cafe_db > backup_$(date +%Y%m%d).sql

# Full backup
tar -czf thrive_backup_$(date +%Y%m%d).tar.gz /path/to/Thrive/
```

### Security Checklist
- [ ] Change default database password
- [ ] Enable HTTPS in production
- [ ] Regular security updates
- [ ] Monitor error logs
- [ ] Restrict database access
- [ ] Use strong passwords

---

## 📞 Support

### Self-Help
1. Check this installation guide
2. Run system check: `http://localhost/Thrive/system-check.php`
3. Review browser console (F12) for JavaScript errors
4. Check server error logs

### Log Files
- **Application Logs**: `logs/error.log`
- **Apache Logs**: 
  - Windows XAMPP: `C:\xampp\apache\logs\error.log`
  - Linux: `/var/log/apache2/error.log`
- **PHP Logs**: Check `php.ini` for log location

### Testing Environment
Use the sample data to test all features:
- 20 sample products
- 5 sample combos  
- 3 sample offers
- 5 sample customers
- Sample sales orders

---

## 🎯 Post-Installation

### First Steps
1. **Customize Menu**: Add your actual products and prices
2. **Set Offers**: Create promotional campaigns
3. **Train Staff**: Show them the interface
4. **Test Thoroughly**: Process sample orders
5. **Backup**: Create initial backup

### Going Live
1. Remove sample data (optional)
2. Set production database credentials
3. Enable HTTPS
4. Set up regular backups
5. Monitor performance

---

**🎉 Congratulations! Your Thrive Cafe Billing Software is ready to use!**

For the best experience:
- Use on tablets/large screens for order entry
- Regular data backups
- Keep browser updated
- Monitor system performance

**Start URL**: `http://localhost/Thrive/`
