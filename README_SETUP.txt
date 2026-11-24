FreshMart - Setup and Presentation Guide

1. Copy the super folder into C:\xampp\htdocs\FreshMart\
2. Start Apache and MySQL in XAMPP.
3. Open phpMyAdmin and select the supermarket_db database.
4. Back up the database first. Import database/repair_existing_database.sql to add missing account columns and stock_history without deleting existing records.
5. Open http://localhost/FreshMart/super/
6. Sign in using an existing Admin, Storekeeper, or Customer account.

Supported roles
- Admin: products, categories, stock overview, customers, orders, payments, reports, staff management, and account settings.
- Storekeeper: stock in/out, stock history, low-stock alerts, and account settings.
- Customer: browse products, cart, checkout, payments, orders, and account settings.

Admin creates and manages Storekeeper accounts from Staff Management. Customers register through the public registration page. Login automatically redirects each role to its own dashboard.

Before presentation, verify that db.php points to database supermarket_db and that the database tables used by the project exist. The repair SQL adds is_active and profile_photo columns only if missing and creates stock_history if missing.

Suggested presentation test
1. Admin: sign in; add/edit a category and product; create a Storekeeper account; view customers, orders, payments, and reports.
2. Storekeeper: sign in; record stock in and stock out; view stock history and low-stock products.
3. Customer: register or sign in; browse products; add products to cart; checkout; select a payment method; view orders.
