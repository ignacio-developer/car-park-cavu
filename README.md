# Car Park CAVU API (Laravel)

Simple REST API for managing car-park bookings: create/amend/cancel bookings, quote pricing (weekday/weekend + summer surcharge), and check availability per day.

---

## Tech stack:
- PHP 8.2+
- Laravel 12
- MySQL
- Composer

---

## 1) Clone & install

```bash
git clone https://github.com/ignacio-developer/car-park-cavu.git
cd car-park-cavu
composer install
cp .env.example .env
php artisan key:generate
```

---

## 2) Configure the database

### MySQL:

Edit `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=car_park_cavu
DB_USERNAME=root
DB_PASSWORD=
```

### 2). It should ask if you'd like to create `car_park_cavu` DB  
If it doesn't ask, run MySQL query:

```sql
CREATE DATABASE car_park_cavu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

---

## 3) Migrate & seed

This creates tables and seeds 10 parking spaces.

```bash
php artisan migrate --seed
```

If you need a clean reset:

```bash
php artisan migrate:fresh --seed
```

For seeding only:
```bash
php artisan db:seed
```
---

## 4) Run the API

```bash
php artisan serve
```

Default URL:  
**http://127.0.0.1:8000**

All routes are under `/api`.  
Example: `http://127.0.0.1:8000/api/bookings/`

---

## 5) API Endpoints, examples ready to use:

---

### Create booking using POSTMAN

Choose **POST**, **raw**, and enter the JSON object in the body.

```
POST /api/bookings
Content-Type: application/json
```

```json
{
  "reg_plate": "AB12 CDE",
  "start_at": "2025-12-20 09:00",
  "end_at":   "2025-12-23 10:00"
}
```

- Returns `201` with booking JSON (includes space, days, total_price_cents).  
- Returns `409` if no space is available.

---

### Create booking using cURL / terminal

```bash
curl -X POST http://127.0.0.1:8000/api/bookings \
  -H "Content-Type: application/json" \
  -d '{"reg_plate":"AB12 CDE","start_at":"2025-12-20 09:00","end_at":"2025-12-23 10:00"}'
```

---

### Amend booking using POSTMAN (partial updates allowed)

Choose **PATCH**, **raw**, and enter the JSON object in the body.

```
PATCH /api/bookings/{booking}
Content-Type: application/json
```

```json
{
  "start_at": "2025-12-21 10:00",
  "end_at":   "2025-12-24 10:00"
}
```

- Returns `200` with updated booking.  
- Returns `409` if new range has no available space.

---

### Amend booking using cURL / terminal

```bash
curl -X PATCH http://127.0.0.1:8000/api/bookings/1 \
  -H "Content-Type: application/json" \
  -d '{"end_at":"2025-12-25 10:00"}'
```

---

### Cancel booking using POSTMAN

Use **DELETE** method and enter booking ID.

```
DELETE /api/bookings/{booking}
```

- Returns `200`  
```json
{ "message": "Your booking has been successfully cancelled." }
```

- If `{booking}` doesn’t exist, Laravel returns `404`.

---

### Cancel booking using cURL / terminal

```bash
curl -X DELETE http://127.0.0.1:8000/api/bookings/1
```

---

### Check availability (per day) using POSTMAN

Use **GET** method, raw body, and enter dates or URL.

```
GET /api/availability?start_at=YYYY-mm-dd%20HH:ii&end_at=YYYY-mm-dd%20HH:ii
```

Returns an array of days:  
`date`, `total_spaces`, `booked`, `available`.

---

### Check availability (per day) using cURL / terminal

```bash
curl "http://127.0.0.1:8000/api/availability?start_at=2025-12-20%2000:00&end_at=2025-12-23%2000:00"
```

---

### Get pricing using POSTMAN

Use **GET** method, raw body, and enter dates or URL.

```
GET /api/pricing?start_at=YYYY-mm-dd%20HH:ii&end_at=YYYY-mm-dd%20HH:ii
```

---

### Get pricing using cURL / terminal

```bash
curl "http://127.0.0.1:8000/api/pricing?start_at=2025-12-20%2000:00&end_at=2025-12-23%2000:00"
```

---

## 6) Run tests

Run all tests:

```bash
php artisan test
```

Filter by class:

```bash
php artisan test --filter=PricingServiceTest
php artisan test --filter=BookingServiceTest
php artisan test --filter=BookingControllerTest
```

---
