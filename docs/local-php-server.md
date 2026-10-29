# Local PHP server

From the project root, run:

```powershell
php -S localhost:8000 -t public
```

Open http://localhost:8000/ and keep the terminal running.

The local document root contains directory junctions to the existing assets,
uploads, actions, account, admin, and staff directories. These are references to
the original files, not duplicate source folders. The public/public PHP entry
points preserve existing /public/page.php links. They load the original page.
Private config, services, vendor, database, and .env remain outside the document root.

If moving this Windows checkout, recreate the junctions from the project root:

```powershell
foreach ($name in @('assets', 'uploads', 'actions', 'account', 'admin', 'staff')) {
    New-Item -ItemType Junction -Path "public/$name" -Target (Resolve-Path $name).Path
}
```

Under PHP's built-in server, URL configuration uses the current local origin.
Apache/XAMPP keeps the existing /purevia_website base path and .env APP_URL.
No CSS, markup layout, database records, or payment logic is changed.