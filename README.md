# PixelPodWeb

* **GITHUB LINK** : `https://github.com/houtaroudes/PixelPodWeb` **(My Public Repo)**
                     **(Solo Dev and Main Programmer/Coder of Group 1)(Bryan Sacueza)**
* **P.S** : **I started this developing since march and debugging this for more than 2months just uploaded on my github (May 24.2026)**
            **I don't want anyone steal or copy my own website :)**

## File Structure

```PHP
pixelpodweb/
├── admin/
│   ├── css/admin.css
│   ├── js/admin.js
│   ├── includes/ (header, footer, sidebar)
│   ├── index.php        ← Dashboard
│   ├── bookings.php     ← Manage bookings
│   ├── services.php     ← Manage packages
│   ├── customers.php    ← View customers
│   ├── events.php       ← Event calendar
│   ├── payments.php     ← Payment tracking
│   ├── inquiries.php    ← Contact messages
│   ├── analytics.php    ← Charts & reports
│   └── settings.php     ← Admin settings
├── api/
│   └── booking_status.php
├── config/
│   └── database.php     ← DB settings 
├── includes/
│   ├── auth.php
│   ├── header.php
│   └── footer.php
├── public/
    ├── css/style.css
    ├── js/main.js
    ├── index.php        ← Home page
    ├── services.php     ← Services
    ├── booking.php      ← Book now
    ├── contact.php      ← Contact
    ├── login.php
    ├── register.php
    ├── dashboard.php    ← Customer portal
    └── logout.php
├── router.php           ← front controller for the built-in server
└── booth/               ← browser-only photobooth (Vite, no backend)
```

***

## Run It Locally

Every link in the app is built as `SITE_URL . '/public/...'`, and the admin area and uploaded photos sit beside `public/` instead of inside it, so the web root has to be the project root. `router.php` is the front controller that makes serving that root safe: it blocks `config/`, `includes/`, `database/` and `docs/`, sends `/` to the landing page, and hands every real file back to the built-in server.

```bash
# 1. Database: import the dump, then create config/database.php
mysql -u root pixelpod_db < database/pixelpod_db.sql
cp config/database.example.php config/database.php     # then fill in the values

# 2. Serve (docroot is the project root on purpose)
SITE_URL=http://127.0.0.1:8123 php -S 127.0.0.1:8123 router.php
```

- Landing page: http://127.0.0.1:8123/public/index.php
- Admin: http://127.0.0.1:8123/admin/index.php

`config/database.php` reads `DB_*`, `SITE_URL`, `APP_ENV` and `APP_DEBUG` from the environment first, so a host is configured from its dashboard rather than from a committed file. `railway.json` runs the same `router.php` front controller through `php -S`, which means a local run and a deployment exercise the same paths.

## The Browser Booth

`booth/` is a second, browser-only photobooth: pick a layout and a look, sit through the countdown, and leave with one strip. It composites on a canvas and needs no PHP or database, so it deploys anywhere static.

```bash
cd booth
npm install
npm test        # strip geometry, filters and filename tests
npm run dev
```

## Where It Is Live

Vercel cannot run PHP or MySQL, and the landing page reads its packages from the
database, so the two halves are hosted separately:

| What | Where | How |
|---|---|---|
| Landing page and packages | https://pixelpodweb.vercel.app | a static snapshot of the rendered pages, rebuilt with `node tools/export-static.mjs` against a running local server and deployed from `site-preview/` (gitignored, generated) |
| The browser booth | https://pixelpod-booth.vercel.app | the `booth/` app deployed on its own |

Booking, accounts and the admin area need PHP and a database session, so they
run locally, or on a PHP host. `railway.json` is already written for the second
route: point a Railway project at this repo, add a MySQL service, set `DB_HOST`,
`DB_NAME`, `DB_USER`, `DB_PASS`, `SITE_URL` and `APP_ENV`, then import
`database/pixelpod_db.sql`.
