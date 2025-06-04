
# 🛒 MultiVendorEcomerence

**MultiVendorEcomerence** is a Laravel 12 e-commerce system that supports multiple vendors per product, automatic order splitting, dynamic pricing, and a flexible discount engine. It uses queued jobs to notify vendors and is built with a modular and testable architecture.

---

## 📌 What the Project Does

When a customer places an order with multiple products:

1. The system selects the **lowest vendor price** per product.
2. It **groups products by vendor** and creates **sub-orders**.
3. It **dispatches jobs** to notify vendors of their sub-orders.
4. It **applies dynamic discounts** based on:
    - Quantity
    - Product category
    - Customer loyalty
5. It logs all applied discounts for transparency and analysis.
6. It tracks notifications sent to vendors.

---

## 🧱 Data Models

| Model               | Description |
|--------------------|-------------|
| **User**            | Represents system users (if used for authentication). |
| **Customer**        | A user who places orders. |
| **Category**        | Categories to group products (used in discount rules). |
| **Product**         | A product available for sale. Has many vendors and belongs to a category. |
| **Vendor**          | A seller who provides products. |
| **ProductVendor**   | Pivot table linking products and vendors. Includes `price`, `stock`. |
| **Order**           | The main customer order. Linked to one customer and contains multiple `OrderItem`s. |
| **OrderItem**       | Represents each item in the main order (product + quantity). |
| **SubOrder**        | A vendor-specific part of an order. Belongs to one `Order`, one `Vendor`. |
| **SubOrderItem**    | Items within a `SubOrder`. Belongs to one product. |
| **AppliedDiscount** | Records each discount that was applied on a sub-order item. |
| **DiscountRule**    | Stores reusable discount logic definitions (category, quantity, etc). |
| **Notification**    | Represents a vendor notification sent for a sub-order. |

---

### 🔗 Model Relationships

```
Customer
 └── hasMany(Order)
Order
 ├── hasMany(OrderItem)
 ├── hasMany(SubOrder)
 └── belongsTo(Customer)
OrderItem
 └── belongsTo(Product)
Product
 ├── belongsTo(Category)
 ├── belongsToMany(Vendor) [via ProductVendor]
 └── hasMany(OrderItem)
SubOrder
 ├── belongsTo(Order)
 ├── belongsTo(Vendor)
 ├── hasMany(SubOrderItem)
 └── hasMany(Notification)
SubOrderItem
 ├── belongsTo(SubOrder)
 ├── belongsTo(Product)
 └── hasMany(AppliedDiscount)
AppliedDiscount
 ├── belongsTo(SubOrderItem)
 └── belongsTo(DiscountRule)
DiscountRule
 └── hasMany(AppliedDiscount)
Notification
 ├── belongsTo(SubOrder)
 └── belongsTo(Vendor)
```

---

## ⚙️ OrderProcessingService

Central service that handles all order logic:

- Selects the best vendor price for each product.
- Applies discount rules via strategy pattern.
- Creates the main order + items.
- Groups into sub-orders by vendor.
- Dispatches `NotifyVendorJob` for each sub-order.
- Records each applied discount.
- Logs notifications in the `notifications` table.

---


## 🎯 Dynamic Discount Rule Engine

The system uses the **Strategy Pattern** to dynamically apply multiple discount rules during order processing.

Each rule implements the following interface:

```php
interface DiscountRuleInterface {
    public function apply(Product $product, Customer $customer, int $quantity, int $vendorId, int $orderId): array;
}
```

The `apply` method returns an array of applicable discounts, where each item is in the format:

```php
[
    'rule_id' => <discount_rules.id>,
    'amount' => <float between 0 and 1>, // e.g., 0.10 for 10%
]

Allow max Total Discounts 0.5 => 50%
```

### ✅ Implemented Rules:

- **QuantityDiscountRule**  
  Applies discounts for products where quantity exceeds a defined threshold (`min_quantity`).

- **CategoryDiscountRule**  
  Applies discounts based on the product's category name (`target` column).

- **LoyaltyCustomerDiscountRule**  
  Applies a discount if the customer has made a certain number of past orders (compared to `min_quantity`).

Each rule logs debug info using `CustomHelper::log(...)` and matches only `active` rules from the `discount_rules` table.

These rules are resolved dynamically from the service container under the key `discount.rules` and iterated in the `OrderProcessingService`.


## 📬 Queued Jobs

### `NotifyVendorJob`

- Dispatched for each `SubOrder`.
- Simulates sending an email/notification to the vendor.
- Logs output in `storage/logs/laravel.log`.
- Creates a new `Notification` record in the database.

---

## 🛠 Artisan Commands

### Generate and Process Orders:

```bash
php artisan orders:generate-random
php artisan orders:process-pending
php artisan migrate:fresh --seed
php artisan user:create
                            {name : The name of the user}
                            {email : The email of the user}
                            {password : The password for the user}
```
- `user:create`: Create a new user via CLI.
- `php artisan migrate:fresh --seed`: Restart All Migrations with build Random Data.
- `generate-random`: Creates a random order with products.
- `process-pending`: Processes pending orders and triggers jobs.


---

## 🧪 Testing

Run:

```bash
php artisan test
```

Includes tests for:

- Order processing
- Vendor grouping
- Discount rule application
- Job dispatch verification
- Applied discount records
- Notification creation

---

## ▶️ Getting Started

```bash
git clone https://github.com/soltanbe/MultiVendorEcomerence.git
cd MultiVendorEcomerence
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan queue:work
```

---

## 🧠 Tech Stack

- Laravel 12
- PHP 8.2+
- MySQL
- Queues and Jobs
- Eloquent ORM
- Inertia.js (React Frontend)
- Strategy Pattern for Discounts

---

## 👤 Author

Developed with ❤️ by [Soltan B](https://github.com/soltanbe)

---


---

## 🔐 Authentication and Console Interface

The system includes a basic authentication flow:

- **User login** via email and password.
- After login, users are redirected to the **/console** route.

### 🖥️ Console Interface

The `/console` route displays a **React + Inertia.js** based interface that allows:

- Running predefined **Artisan commands** from the browser.
- Viewing the **output of each command** in real time.
- A clean **developer console-style UI** built with Material UI (MUI).

This interface is useful for developers or admins to interact with Laravel internals without SSH access.



## 📄 License

This project is open-sourced under the [MIT license](LICENSE).
