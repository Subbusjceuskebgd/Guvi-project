# GUVI Internship Project — Setup Guide

## Tech Stack
- HTML | CSS | JavaScript | PHP | MySQL | MongoDB | Redis

## Folder Structure
```
guvi-project/
├── assets/
├── css/style.css
├── js/login.js | profile.js | register.js
├── php/config.php | login.php | profile.php | register.php
├── index.html | login.html | profile.html | register.html
├── setup.sql
└── README.md
```

## Prerequisites
- PHP 8.1+ (extensions: pdo_mysql, redis, mongodb)
- MySQL 8.0+  |  MongoDB 6.0+  |  Redis 7.0+
- Composer (for MongoDB PHP library)

## Setup Steps

### 1. Install MongoDB PHP Library
```bash
cd guvi-project
composer require mongodb/mongodb
```
Then add this line at the TOP of php/config.php:
```php
require_once __DIR__ . '/../vendor/autoload.php';
```

### 2. Setup MySQL
```bash
mysql -u root -p < setup.sql
```

### 3. Configure php/config.php
```php
define('MYSQL_USER', 'your_username');
define('MYSQL_PASS', 'your_password');
```

### 4. Start Services
```bash
sudo systemctl start mysql
sudo systemctl start mongod
sudo systemctl start redis-server
```

### 5. Run
```bash
php -S localhost:8000
# Open: http://localhost:8000
```

## App Flow
Register → Login → Profile
- Registration data → MySQL (Prepared Statements)
- Profile details   → MongoDB
- Session token     → Browser localStorage (frontend) + Redis (backend)

## Requirements Checklist
- [x] HTML, CSS, JS, PHP in separate files
- [x] jQuery AJAX only (no form submission)
- [x] Bootstrap responsive forms
- [x] MySQL for registration (Prepared Statements)
- [x] MongoDB for profile details
- [x] Session in browser localStorage
- [x] Redis for backend session storage
- [x] No PHP Sessions used
