# PixelPodWeb

* **GITHUB LINK** : `https://github.com/houtaroudes/PixelPodWebsite-Solo-` (Solo Dev)
* **P.S** : **I started this developing since march and debugging this for more than 2months just uploaded on my github (May 24.2026)**
            **I don't want anyone steal or copy my own website :)**

### Websites

* **Public site:** `http://localhost/PixelPodWeb/public/index.php`
* **Admin panel:** `http://localhost/PixelPodWeb/admin/index.php`
* **Photobooth:** `http://localhost/PixelPodWeb/public/photobooth/index.php`
* **Services/Packages:**`http://localhost/PixelPodWeb/public/services.php`
* **Book Now:** `http://localhost/PixelPodWeb/public/booking.php`
* **Contacts:** `http://localhost/PixelPodWeb/public/contact.php`
* **LogIn:** `http://localhost/PixelPodWeb/public/login.php`

## Package Thumbnail Images

* **Upload File** — upload JPG/PNG/WebP/GIF from your computer (max 5MB)
* **Paste URL** — paste any direct image link from the web
* Images are shown on both the public Services page and the Admin panel
* Uploaded files are stored in `uploads/services/`

***

## Open the Website

* **Public site:** `http://localhost/PixelPodWeb/public/index.php`
* **Admin panel:** `http://localhost/PixelPodWeb/admin/index.php`

***

## Admin and Customer accounts

* **Admin** 
**Username** : PixelPod@gmail.com/PixelPod
**Password** : pixelpod

**Customer**
**Username** : bry.sacueza@gmail.com/bryansacueza
**Password** : bryansacueza

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
├── database/
│   └── pixelpod_db.sql    
├── includes/
│   ├── auth.php
│   ├── header.php
│   └── footer.php
└── public/
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
```

***