# Programming Languages Damascus University 2024 – Backend 📚

> A Laravel-powered RESTful API supporting an e-commerce-style application used for the
> Programming Languages course at Damascus University (2024). The backend handles
> users, stores, products, carts, orders, notifications, and related business logic.

---

## 🚀 Project Overview

This repository contains the backend implementation of a multi‑store marketplace system built
using **Laravel 10**. It exposes a JSON API consumed by mobile/web clients and includes:

-   JWT‑based authentication (Sanctum)
-   User registration, login, and profile management
-   Store and product management with categories, images, and inventory
-   Shopping cart, favorites, and order processing
-   Push notifications via FCM
-   Repository/service architecture for a clean separation of concerns
-   Extensive use of Laravel features (migrations, policies, resources, queues)

This project serves both as a course assignment and a foundation for further expansion.

---

## 🧩 Key Features

-   **Authentication & Authorization** – secure endpoints with role checks
-   **Product Catalog** – categories, stores, and product CRUD
-   **Image Handling** – upload and serve product/store images
-   **Shopping Cart & Favorites** – add/remove items, quantity updates
-   **Order Lifecycle** – place orders, update status, view history
-   **Notifications** – Firebase Cloud Messaging for order and activity alerts
-   **API Resources** – consistent JSON structure with Laravel resources
-   **Unit/Feature Tests** – ensure stability across critical flows

---

## 🛠 Tech Stack

| Component      | Technology       |
| -------------- | ---------------- |
| Language       | PHP 8.1+         |
| Framework      | Laravel 10       |
| Database       | MySQL / MariaDB  |
| Cache / Queue  | Redis (optional) |
| Authentication | Laravel Sanctum  |
| Notifications  | Firebase (FCM)   |
| Package Mgmt   | Composer / npm   |

---

## 📥 Requirements

-   PHP 8.1 or newer
-   Composer
-   Node.js & npm (for front‑end assets)
-   MySQL or compatible database

Optional services:

-   Redis (cache/queues)
-   Firebase project for push notifications

---

## ⚙️ Installation & Setup

```bash
# 1. clone repository
git clone <repo-url> back-end
cd back-end

# 2. install dependencies
composer install
npm install

# 3. environment
cp .env.example .env
# edit .env file and configure database, mail, FCM, etc.

php artisan key:generate

# 4. database
db credentials already set in .env
php artisan migrate --seed

# 5. build assets (if used)
npm run dev   # or "npm run build" for production

# 6. run server
php artisan serve
```

> **Tip:** use `php artisan serve --port=8001` or a dedicated Valet/Homestead environment
> for parallel development.

---

## 🔧 Configuration

Important `.env` variables:

```env
APP_NAME="PL-Damascus-Backend"
APP_ENV=local
APP_DEBUG=true

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pl_backend
DB_USERNAME=user
DB_PASSWORD=secret

FCM_SERVER_KEY=your_fcm_server_key
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
```

Adjust as needed for production environments.

---

## 📄 API Documentation

Endpoints are defined under `routes/api.php`. Some of the most-used routes include:

```
POST   /api/auth/register
POST   /api/auth/login
GET    /api/categories
GET    /api/stores
GET    /api/products
POST   /api/cart/add
POST   /api/orders
...
```

Resources and controllers include inline PHPDoc comments; you may generate
Swagger/OpenAPI docs with packages such as `darkaonline/l5-swagger` if required.

---

## ✅ Running Tests

Execute the test suite using PHPUnit:

```bash
php artisan test
```

Tests are located under `tests/Feature` and `tests/Unit`. Add new tests when implementing
business logic or fixing bugs.

---

## 🤝 Contributing

Contributions are welcome! Please follow these guidelines:

1. Fork the repository and create a feature branch (`feature/your-feature`).
2. Write tests for new functionality or bug fixes.
3. Ensure coding standards comply with **PSR-12** and run `php artisan lint` if available.
4. Submit a pull request with a clear description of your changes.

> Note: this project may be used for academic purposes; check with project maintainers
> for collaboration policies.

---

## 📚 Additional Resources

-   [Laravel Documentation](https://laravel.com/docs/10.x)
-   [Laracasts](https://laracasts.com)
-   [PHP The Right Way](https://phptherightway.com)

---

## 📝 License

This project is licensed under the **MIT License** – see the [LICENSE](LICENSE) file for details.

---

Happy coding! 🎉
