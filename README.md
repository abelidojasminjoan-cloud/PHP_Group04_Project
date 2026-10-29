# PureVia: A Skin Care E-commerce System with Skin type Matching and Ingredient Compatibility

# Group Members: 
1. Abelido, Jasmin Joan - @abelidojasminjoan
2. Cuvin, Maria Dianne - @diannecuvs
3. Macadato, Ahmhida - @ahmhida
4. Tambe, Bryan Roi - @tambebryanroi

# Project Description
A web-based e-commerce system developed to provide customers with an easy, convenient, 
and personalized way to browse and purchase skincare products. The system offers a variety of skincare 
products and provides relevant product information to help customers make informed purchasing decisions.

# Technology Used
- PHP
- MySQL
- HTML
- CSS
- JavaScript
- Security Tools

# System Features
- Checkout and Order Placement 
- Shopping Cart
- Skin Type Matching
- Category Management
- Product Management
- Product Catalog, Search, and Filtering
- Ingredient Compatibility
- Order Management
- Administration Dashboard
- Payment Management 
- Customer Profile Management 
- Audit Logs

# Database 
purevia_db.sql

# Installation/Setup

Requirements: PHP, MySQL/MariaDB (available through XAMPP), and Composer.

1. Open the PureVia project folder in Visual Studio Code.
2. Start MySQL in XAMPP. The terminal command below serves the website, so Apache is not required for the website. If using XAMPP's phpMyAdmin, also start Apache to access phpMyAdmin.
3. Create the `purevia_db` database in phpMyAdmin and import `database/purevia_db.sql`.
4. Configure the database connection and other required settings in `.env`.
5. Run `composer install` from the project root if the Composer dependencies are not installed.
6. When setting up a fresh checkout or moving the project, follow the [local PHP server setup](docs/local-php-server.md) to recreate the public directory junctions.
7. Open a terminal in the project root (the folder containing `public`, `config`, and `composer.json`) and run:

```powershell
php -S localhost:8000 -t public
```

8. Open http://localhost:8000/ in your browser. Keep the terminal running while using the website; press Ctrl + C to stop the server.


For the research-informed skin assessment and admin question bank, run
`php tools/migrate-skin-questions.php` after the existing skin-profile schema is installed.
See [assessment evidence, limitations and verification](docs/skin-assessment-evidence.md).

