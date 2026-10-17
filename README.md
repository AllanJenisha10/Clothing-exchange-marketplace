<div align="center">

<img src="https://readme-typing-svg.demolab.com?font=DM+Sans&weight=700&size=32&duration=3000&pause=800&color=315B49&center=true&vCenter=true&width=800&lines=ReWear+Clothing+Swap;Swap+Style%2C+Not+Waste;Give+Good+Clothes+Another+Story" alt="Animated ReWear title" />

**A community-first clothing exchange marketplace — trade pre-loved fashion, keep textiles in use, and refresh your wardrobe without buying new.**

[GitHub Repository](https://github.com/AllanJenisha10/Clothing-exchange-marketplace)

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php&logoColor=white) ![MySQL](https://img.shields.io/badge/MySQL-MariaDB-4479A1?style=for-the-badge&logo=mysql&logoColor=white) ![Responsive](https://img.shields.io/badge/Responsive-UI-315B49?style=for-the-badge)

</div>

## Project overview

ReWear is a PHP + MySQL web application for exchanging clothing directly between people. Members publish items, discover pieces by category and location, make item-for-item swap requests, negotiate in a message thread, and track the exchange from their dashboard. Estimated values are guidance only; users should agree on fairness and condition before completing a swap.

## Implemented modules

- **Marketplace:** search by title, brand or description; filter by category and city/area.
- **Accounts:** registration, login/logout, password hashing, session regeneration, CSRF tokens and profile/location editing.
- **Listings:** create, edit and delete owned listings; validated JPG/PNG/WEBP uploads (4 MB limit); item condition, size, brand, city/area and estimated swap value.
- **Item detail:** view item and owner details, then choose one of your available listings to offer.
- **Swap requests:** send, accept or reject requests; accepted items are reserved; participants can mark an accepted swap completed.
- **Messages:** private, request-linked conversations available only to swap participants.
- **Admin moderation:** server-side admin role check, activity counts, hide/restore listings and view submitted disputes.
- **Responsive UI:** desktop and mobile layouts.

## Technology

| Technology | Purpose |
|---|---|
| PHP 8+ | Server-side application and validation |
| MySQL / MariaDB | Users, listings, swap requests and messages |
| HTML, CSS, JavaScript | Responsive user interface |
| PDO prepared statements | Parameterized database queries |
| PHP password API | Password hashing and verification |

## Project structure

```text
Clothing-exchange-marketplace-main/
├── index.php                 # Marketplace and search
├── listing.php               # Item detail and swap request
├── listing_form.php          # Create/edit listing
├── login.php / register.php  # Authentication
├── dashboard.php             # Listings and swap management
├── profile.php               # User profile/location
├── chat.php                  # Swap conversation threads
├── admin.php                 # Admin moderation
├── logout.php
├── includes/
│   ├── bootstrap.php         # Database, auth, CSRF and helpers
│   ├── header.php
│   └── footer.php
├── assets/css/style.css
├── assets/js/app.js
├── database/schema.sql
└── uploads/                  # Uploaded item images (not committed)
```

## Run locally with XAMPP

1. Install XAMPP with PHP 8+ and MySQL/MariaDB.
2. Extract the repository into `C:\xampp\htdocs\Clothing-exchange-marketplace-main` (or your XAMPP `htdocs` path).
3. Start **Apache** and **MySQL** in the XAMPP Control Panel.
4. Open `http://localhost/phpmyadmin/`.
5. Use **Import** to import `database/schema.sql`. It creates the `rewear_marketplace` database and required tables. For local evaluation only, optionally import `database/demo_data.sql` to add sample clothing and demo accounts (password: `SwapDemo2026!`). Never import demo accounts into production.
6. Open `includes/bootstrap.php`. Local defaults are `127.0.0.1`, database `rewear_marketplace`, username `root`, and a blank password. Change these only if your local MySQL credentials differ. Production should use environment variables `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS`.
7. Ensure the `uploads` folder is writable by PHP.
8. Visit `http://localhost/Clothing-exchange-marketplace-main/`.
9. Register two test users and create at least one available listing for each. Then test a swap between the accounts.

### Enable administrator access

Register your own account first, then run this query in phpMyAdmin (replace the email with your own account email):

```sql
USE rewear_marketplace;
UPDATE users SET role = 'admin' WHERE email = 'your-email@example.com';
```

Log out and back in so the session reloads the admin role. Do not make ordinary users admins.

## Core workflow

1. Register and set a general city/area.
2. Publish an item with category, size, condition, optional brand, description and image.
3. Browse/search listings and open an item.
4. Choose one of your own available items and submit a swap request.
5. The item owner reviews the request, opens Messages, and negotiates.
6. The owner accepts or rejects the request. Accepted items are reserved.
7. Once the real-world exchange has happened, a participant marks the swap completed.

## Deployment checklist

This is a **PHP/MySQL** application, so GitHub Pages alone cannot host it. Deploy it to a PHP-capable host with MySQL/MariaDB (for example, a suitable shared PHP host or a VPS).

1. Create the production database and import `database/schema.sql`.
2. Upload the project files, including `includes/`, `assets/`, `database/` and the PHP pages.
3. Set `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS` in the host's environment configuration if supported; otherwise configure credentials securely in `includes/bootstrap.php` and never commit them.
4. Configure the `uploads/` directory for safe image storage and confirm PHP file-upload limits.
5. Enable HTTPS, verify session cookie settings, and register an administrator using the SQL above.
6. Test registration, login, upload, search, location filters, swap request acceptance/rejection, messages, dashboard and admin permissions on the live URL.
7. Add the **deployed application URL** to this README and submit that live URL to Unified Mentor. The GitHub source URL is not the deployed application.

## Important limitations / next production improvements

- Swap value is a transparent category/condition heuristic, not an appraisal or AI valuation.
- Location matching is city/area text filtering, not GPS or distance calculation.
- Chat is request-linked and refresh-based; it is not real-time WebSocket chat.
- Participants can report a problem from a swap conversation; admins can review and mark a dispute resolved. This is a basic workflow, not a formal mediation or appeals system.
- Courier booking, payment, AI recommendations and mobile apps are outside Phase 1.
- Before public launch, add rate limiting, email verification/password reset, stronger image moderation, automated tests, backups, and production-specific security review.

## Developer

**Allan Jenisha R.** · [@AllanJenisha10](https://github.com/AllanJenisha10)

<div align="center"><b>Wear more. Waste less. Swap better. ♻</b></div>
