# Migrating Laravel POS to a NativePHP Desktop App

To package your POS into an `.exe` with NativePHP, we need to transition the database from MySQL to SQLite. SQLite stores your entire database in a single file, making it perfect for a standalone desktop application.

Here is the complete, start-to-finish process for setting up NativePHP correctly.

## Step 1: Update Your Environment Variables

Open your `.env` file and change your database connection settings.

**Remove or comment out your MySQL settings:**
```env
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=laravel
# DB_USERNAME=root
# DB_PASSWORD=
```

**Add the SQLite connection:**
```env
DB_CONNECTION=sqlite
```

## Step 2: Fix MySQL-Specific Migrations (Crucial)

SQLite handles column types and alterations differently than MySQL. You must ensure your migrations don't contain raw MySQL syntax. 

1. **Replace `LONGBLOB` with `binary()`**
   If any migration uses `$table->longText('file_data')` or raw DB statements like `DB::statement("ALTER TABLE phppos_app_files MODIFY file_data LONGBLOB")`, it will fail. Change these to use Laravel's SQLite-compatible binary columns: `$table->binary('file_data');`.
2. **Handle `ENUM` types**
   SQLite does not natively support `ENUM`. Any raw SQL attempting to `MODIFY ... ENUM` must be removed or handled gracefully using `$table->enum(...)` during creation instead of raw alters.

## Step 3: Install and Configure NativePHP

Now that your schema is compatible, install the NativePHP package.

1. **Install the package:**
   ```bash
   composer require nativephp/electron
   ```
2. **Run the NativePHP installer:**
   ```bash
   php artisan native:install
   ```
3. **Configure the NativePHP Database**
   NativePHP manages its own SQLite database independently of your local project (it stores it in the user's hidden `AppData` folder on Windows).
   Open `config/nativephp.php` and add the database configuration:
   ```php
   'database' => [
       'default' => 'sqlite',
   ],
   ```

## Step 4: Run the NativePHP Database Setup (IMPORTANT)

Since NativePHP uses its own environment and database file, running the standard `php artisan migrate:fresh --seed` in your terminal **will not** seed the desktop application. If you skip this step, you won't be able to log in to the POS interface!

Run these specific NativePHP commands in your terminal to set up the desktop database:

```bash
# Rebuild the NativePHP database schema
php artisan native:migrate:fresh

# Seed the NativePHP database with the default admin and config
php artisan native:seed
```

*(Note: The default login credentials will be **admin** / **12345678**)*

## Step 5: Run the Desktop Dev Server

You are now ready to launch the POS as a desktop app in development mode:

```bash
php artisan native:serve
```

*This will compile the Electron app and open a native desktop window. Changes you make to your codebase will hot-reload automatically.*

## Step 6: Build the Executable

Once everything is working perfectly, you can compile the app into a distributable installer and `.exe` file.

```bash
php artisan native:build win
```

This will create a standalone executable in your `dist/` directory that you can send to your users. They will be able to double-click it and run the POS system instantly with no MySQL server required.
