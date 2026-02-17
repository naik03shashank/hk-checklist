# Client Deployment Guide

This repository contains the updated source code for the "HK Checklist" application.

**Version:** 1.0 (Feb 9 2026)
**Fixes Included:**
1.  **MySQL Database Compatibility:** Supports importing the `dbase.sql` file.
2.  **Login Page:** Optimized environment configuration.
3.  **Features:** Room Skipping, Admin Media Upload, Housekeeper Timestamped Photos.

## How to Deploy on Your Live Server
1.  **Upload Source:** Upload the application files to your server.
2.  **Environment:**
    *   Copy `.env.example` to `.env`.
    *   Set `DB_CONNECTION=mysql` and your database credentials.
    *   Ensure `APP_DEBUG=false` for production.
3.  **Install & Setup:**
    ```bash
    composer install --no-dev
    php artisan key:generate
    php artisan migrate
    npm install && npm run build
    ```
    *Note: `php artisan migrate` will only add new tables/columns needed for the updates. It will NOT delete your existing data.*


## Notes on Performance
*   The previous test server (Render Free Tier) sleeps after inactivity, causing a 5-minute wake-up delay.
*   **Your paid/live server will NOT have this issue** and will be fast immediately.
