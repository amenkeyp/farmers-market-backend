# Farmers Market Platform — Backend (Laravel 10)

Production-grade REST API for a farmers market platform in Côte d'Ivoire.
Built with **Laravel 10**, **PHP 8.1+**, **MySQL**, **Sanctum**.
All financial flows (credit issuance, credit-limit enforcement, FIFO debt repayment,
commodity-by-kg pricing) are implemented with **DB transactions + row locking**.

---

## 1. Stack & architecture

- Laravel 10 + PHP 8.1+
- MySQL (production) / SQLite (also supported, used in CI)
- Laravel Sanctum (token auth)
- Clean architecture: `Controllers` → `FormRequests` → `Services` → `Models` → `Resources`
- Consistent JSON response envelope:
  ```json
  { "success": true, "message": "...", "data": ... }
  ```

### Folder map

```
app/
  Http/
    Controllers/Api/      # AuthController, UserController, CategoryController,
                          # ProductController, FarmerController, TransactionController,
                          # DebtController, RepaymentController
    Middleware/RoleMiddleware.php
    Requests/             # FormRequest validation per resource
    Resources/            # API Resources (response shaping)
  Models/                 # User, Category, Product, Farmer, Transaction,
                          # TransactionItem, Debt, Repayment, Setting
  Services/
    TransactionService.php   # checkout flow + credit-limit enforcement
    RepaymentService.php     # FIFO repayment allocator
    CommodityService.php     # kg → FCFA conversion via configurable rate
  Traits/ApiResponse.php
config/market.php             # default interest rate, currency, default rate/kg
database/
  migrations/
  seeders/
routes/api.php
docs/postman_collection.json  # ready-to-import Postman collection
```

---

## 2. Setup

```bash
# 1. Install deps
composer install

# 2. Configure env (copy & edit)
cp .env.example .env
php artisan key:generate

# Edit .env: APP_NAME, DB_*, MARKET_DEFAULT_INTEREST_RATE (default 0.05)

# 3. Create database (MySQL)
mysql -u root -e "CREATE DATABASE farmers_market CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 4. Migrate + seed
php artisan migrate --seed

# 5. Run
php artisan serve
# API base URL: http://127.0.0.1:8000/api
```

### Demo credentials (after seed)

| Role        | Email                    | Password   |
|-------------|--------------------------|------------|
| admin       | admin@market.ci          | password   |
| supervisor  | supervisor@market.ci     | password   |
| operator    | operator@market.ci       | password   |
| operator    | operator2@market.ci      | password   |

---

## 3. Roles & access

- **admin**     — full access; can create supervisors and operators.
- **supervisor**— can create operators (only those they created appear in their listing); manage catalog & farmers.
- **operator** — checkout, repayment, read-only catalog.

Enforced via `RoleMiddleware` registered as `role:` (e.g. `->middleware('role:admin,supervisor')`).

---

## 4. Database schema (high-level)

| Table              | Purpose |
|--------------------|---------|
| `users`            | Auth + role enum (`admin`/`supervisor`/`operator`) |
| `settings`         | Key/value config (interest rate, per-SKU rate/kg overrides…) |
| `categories`       | Nested via `parent_id` |
| `products`         | SKU + `unit_price` (unit-priced) **OR** `rate_per_kg` (commodity) |
| `farmers`          | Identifier, phone, `credit_limit`, denormalised `current_debt` |
| `transactions`     | `cash` or `credit`, with `subtotal`, `interest_rate`, `interest_amount`, `total_amount` |
| `transaction_items`| Snapshots of unit/rate/quantity per line |
| `debts`            | 1 per credit transaction. Holds `original_amount`, `remaining_amount`, `status` |
| `repayments`       | Cash inflow from a farmer (any payment method) |
| `repayment_debt`   | Pivot: which repayment paid which debt, with **before/after audit snapshots** |

All money columns use `DECIMAL(14,2)` (or `(14,4)` for rates). FCFA has no fractional unit; precision kept internally for safe interest math.

---

## 5. Business logic (financial core)

### 5.1 Credit calculation
For a credit transaction:

```
subtotal        = Σ line_total
interest_amount = round(subtotal × interest_rate, 2)
total_amount    = subtotal + interest_amount   ← this is what the farmer owes
```

`interest_rate` defaults to `config('market.default_interest_rate')` (= 5%) and can be overridden per request.

### 5.2 Credit limit enforcement
Inside a single DB transaction with `SELECT ... FOR UPDATE` on the farmer row:

```
if (current_debt + new_total_amount > credit_limit) → 422 reject
```

Atomic + race-safe. See `App\Services\TransactionService::checkout()`.

### 5.3 Commodity (kg) conversion
Products with `unit = 'kg'` and `rate_per_kg > 0` are commodities:

```
line_total = quantity_kg × rate_per_kg
```

Resolution order in `CommodityService::resolveRatePerKg()`:
1. `products.rate_per_kg` column
2. `settings` row keyed `commodity.rate_per_kg.{SKU}`
3. `config('market.default_rate_per_kg')`

### 5.4 FIFO repayment
`App\Services\RepaymentService::repay()`:

1. Locks farmer row.
2. Locks all open / partially_paid debts ordered by `issued_at ASC, id ASC`.
3. Walks them oldest-first, applying `min(remaining, leftover)` to each.
4. Updates each debt's `remaining_amount` & `status` (`open` → `partially_paid` → `paid`).
5. Decrements `farmers.current_debt` by the **applied** amount only.
6. Records each allocation in `repayment_debt` with `debt_remaining_before` / `debt_remaining_after` for full audit trail.

#### Edge cases handled
| Case                                     | Behavior |
|------------------------------------------|----------|
| amount ≤ 0                               | 422 reject |
| Farmer has no outstanding debt           | 422 reject |
| Exact payment                            | `applied = amount, change = 0`, all involved debts → `paid` |
| Partial payment                          | Oldest debt(s) reduced; first non-cleared debt becomes `partially_paid` |
| Overpayment                              | `applied = total_outstanding`, `change_amount = amount − applied` (refund recorded, **no auto-credit**) |

### 5.5 Race condition prevention
Both services use `DB::transaction()` + `lockForUpdate()` on:
- the farmer row (credit limit / running balance)
- the affected products (stock decrement)
- the open debts (FIFO allocation)

This prevents double-spending of the credit limit under concurrent operators.

---

## 6. API reference (summary)

Base URL: `/api`. All non-auth endpoints require `Authorization: Bearer <token>`.

### Auth
| Method | Path           | Body                                  |
|--------|----------------|---------------------------------------|
| POST   | `/auth/login`  | `{email, password, device_name?}`     |
| GET    | `/auth/me`     | –                                     |
| POST   | `/auth/logout` | –                                     |

### Users (admin/supervisor)
`GET|POST /users`, `GET|PUT|PATCH|DELETE /users/{user}`

### Categories
`GET /categories?tree=1` → nested tree.
`GET|POST|PUT|PATCH|DELETE /categories[/{category}]`

### Products
`GET /products?category_id=&search=&is_active=`
`GET|POST|PUT|PATCH|DELETE /products[/{product}]`

### Farmers
`GET /farmers?search=<identifier|phone|name>&region=&is_active=`
`GET|POST|PUT|PATCH|DELETE /farmers[/{farmer}]`

### Transactions (checkout)
```http
POST /api/transactions
{
  "farmer_id": 4,
  "type": "credit",
  "interest_rate": 0.05,
  "due_at": "2026-08-01",
  "items": [
    { "product_id": 1, "quantity": 50 },
    { "product_id": 8, "quantity": 5 }
  ],
  "notes": "Crédit campagne 2026"
}
```
Response includes `subtotal`, `interest_amount`, `total_amount`, generated `debt`.

### Debts
`GET /debts?farmer_id=&status=&open_only=1&overdue=1&sort=fifo|newest|amount`
`GET /debts/{debt}`

### Repayments (FIFO)
```http
POST /api/repayments
{ "farmer_id": 4, "amount": 50000, "method": "cash", "notes": "Acompte" }
```
Response includes `applied_amount`, `change_amount`, and the per-debt `allocations` array.

A full Postman collection is provided at `docs/postman_collection.json`.

---

## 7. Configuration

`config/market.php`:

| Key                       | Env override                  | Default |
|---------------------------|-------------------------------|---------|
| `default_interest_rate`   | `MARKET_DEFAULT_INTEREST_RATE`| `0.05`  |
| `default_rate_per_kg`     | `MARKET_DEFAULT_RATE_PER_KG`  | `0`     |
| `currency`                | `MARKET_CURRENCY`             | `XOF`   |

Per-product rates can also be stored in `settings` table with key
`commodity.rate_per_kg.{SKU}` (used as fallback when `products.rate_per_kg` is null).

---

## 8. Verification (already run during build)

The `DemoMarketSeeder` exercises the full flow:

1. **Cash checkout** for farmer #1 (no debt created).
2. **Credit checkout** of 5 sacs NPK + 20 kg café for farmer #4 → debt #1 = 140,700 FCFA.
3. **Credit checkout** of 50 kg cacao for farmer #4 → debt #2 = 78,750 FCFA.
4. **Partial repayment** of 50,000 FCFA → applied entirely to debt #1 (oldest) → 90,700 remaining; debt #2 untouched.
5. `farmers.current_debt = 169,450 FCFA` ✓

You can replay it with `php artisan migrate:fresh --seed`.

---

## 9. Conventions

- All money fields are `decimal(14,2)`; rates are `decimal(14,4)`.
- All financial operations are wrapped in `DB::transaction` with explicit row locks.
- All inputs validated through `FormRequest` classes.
- All responses go through API Resources.
- Errors are normalized in `App\Exceptions\Handler::renderApi()`.
- `RuntimeException` thrown by services produces a clean 422 with the business message.

## 10. License
Proprietary — internal use for the Farmers Market Platform project.