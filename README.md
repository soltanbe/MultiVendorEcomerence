# 🛒 MultiVendorEcomerence — Laravel 12 Multi-Vendor Order System

This project is a multi-vendor e-commerce order management system built with Laravel 12.

Each product can be listed by multiple vendors with different prices. When a customer places an order, the system dynamically:
- Selects the lowest price per product from available vendors
- Applies applicable discount rules based on product, category, quantity, or customer loyalty
- Creates sub-orders grouped by vendor
- Dispatches queued jobs to notify vendors

---

## 🚀 Features

- 🛍️ Multi-vendor product listing (via `product_vendors`)
- 💸 Dynamic pricing engine: best price per product
- 🎯 Discount rule engine (Strategy Pattern via DB)
- 📦 Automatic sub-order grouping by vendor
- 📬 Queued notifications to vendors
- 🧾 Full logging of order processing and discount application

---

## 🧱 Models Overview

| Model             | Description                                     |
|------------------|-------------------------------------------------|
| Product           | Main product entity                            |
| Vendor            | Each seller/vendor in the system               |
| ProductVendor     | Pivot table for vendor-specific product prices |
| Customer          | End user placing orders                        |
| Order             | Main order placed by customer                  |
| OrderItem         | Each product in the main order                 |
| SubOrder          | Vendor-specific order                          |
| SubOrderItem      | Products grouped under each vendor             |
| DiscountRules     | Stores discount rules in DB                    |
| Category          | Category assigned to each product              |

---

## 🧠 Discount Rule Engine

### Stored in DB (`discounts` table)

| Type      | Target          | Example                                       |
|-----------|------------------|-----------------------------------------------|
| category  | `electronics`     | 10% off all electronics                      |
| quantity  | `min_quantity=3`  | 5% off when buying 3+ units                  |
| loyalty   | N/A               | 5% off for loyal customers                   |

All logic is loaded dynamically via the Strategy Pattern.

---

## 📂 Cloning & Setup

```bash
git clone https://github.com/soltanbe/MultiVendorEcomerence.git
cd MultiVendorEcomerence

composer install
npm install && npm run build

Run migrations and seeders

php artisan migrate:fresh --seed

Generate random orders

php artisan order:random {count}

Process pending orders (split to vendors + apply discounts)

php artisan orders:process-pending

Dispatch vendor notifications (queued jobs)

php artisan sub-orders:notify-vendors

Sample Log Output
[2025-06-01 10:23:13] local.INFO: Starting processing of 1 random order(s)
[2025-06-01 10:23:13] local.INFO: Processing Order #1
[2025-06-01 10:23:13] local.INFO: Selected customer: 3 - Amit (amit@example.com)
[2025-06-01 10:23:13] local.INFO: Pulled product from DB: ID 23, Name: Bed Frame
[2025-06-01 10:23:13] local.INFO: Created order ID: 1
[2025-06-01 10:23:13] local.INFO: Saved OrderItem ID: 1 - Product #23 (Qty: 3, Price: ₪87.00) via Vendor #3
[2025-06-01 10:23:21] local.INFO: 📦 Quantity discount found {"product_id":23,"product_name":"Bed Frame","category":"furniture","discount_percent":"7.50","rule_id":3}
[2025-06-01 10:23:21] local.INFO: Product #23 base: ₪87.00 discount: 7.5% → final: ₪80.48
[2025-06-01 10:23:21] local.INFO: ✅ Created SubOrder #1 for Vendor #3 (Order #1)
[2025-06-01 10:23:21] local.INFO: 🧾 SubOrderItem created: Bed Frame (ID #23) | Qty: 3 | Original: ₪87.00 | Discounted: ₪80.48 | Discount: ₪6.52
[2025-06-01 10:23:28] local.INFO: Job is queued for Sending sub-orders to Vendor #3 for Customer #3 SubOrder #1

[2025-06-01 10:23:29] local.INFO: Sending sub-orders for Customer #3 to Vendor #3 name: Vendor C phone: 050-3333333 email: vendorc@example.com
[2025-06-01 10:23:29] local.INFO: SubOrder #1 product_id #23 name Bed Frame quantity 3 unit price original 87.00 unit price 80.48
[2025-06-01 10:23:29] local.INFO: SubOrder #1 (Order #1) - Total original: ₪261.00  Total: ₪241.44
