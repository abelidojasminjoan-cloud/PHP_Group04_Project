# Local PHP server

From the project root, run exactly:

```powershell
php -S localhost:8000 -t public
```

Open http://localhost:8000/. No router argument is required. Keep the terminal
running and press Ctrl+C to stop it.

## Files and shortcuts

The actual account, actions, admin, staff, assets, and uploads folders live outside
public/. Their six entries under public/ are Windows directory junctions pointing
to those same original folders. They are not backups or second copies. Uploaded
product images are original files in uploads/products/; viewing them through
public/uploads shows the same files.

PHP's built-in server only serves files under its document root. These shortcuts
are therefore required to keep both the original folders outside public/ and the
exact startup command above. Do not delete them unless changing the server setup.
There is no routes/ folder and no public/public compatibility folder.

If moving this checkout, recreate the shortcuts from the project root:

```powershell
foreach ($name in @('account', 'actions', 'admin', 'staff', 'assets', 'uploads')) {
    New-Item -ItemType Junction -Path "public/$name" -Target (Resolve-Path $name).Path
}
```

Private .env, configuration, services, includes, database, vendor, tools, and backups
remain outside public/. Use clean URLs such as /login.php and /shop.php.
Apache/XAMPP uses the root .htaccess for those URLs under /purevia_website.
Docker creates equivalent Linux links. The application's URLs and design stay the
same, and uploaded files are not moved into a routing folder.