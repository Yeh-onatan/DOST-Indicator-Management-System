npm run dev
php artisan serve

# 🌱 Laravel Commands Guide

Quick reference for working with a Laravel project (development, database, and common tasks).

---

## 🔧 1. Project Setup

### Create a new Laravel project

```bash
composer create-project laravel/laravel project-name
# or, if you have the installer:
laravel new project-name
```

### Install dependencies for an existing project

From inside the project folder:

```bash
composer install      # PHP dependencies
npm install           # JS/CSS dependencies
```

### Copy environment file & generate app key

```bash
cp .env.example .env  # (Git Bash/macOS/Linux)
# or manually duplicate .env.example to .env

php artisan key:generate
```

Update `.env` for database, mail, etc.

---

## 🚀 2. Running the App (Local Development)

Start the Laravel development server:

```bash
php artisan serve
```

Then open the URL shown (usually `http://127.0.0.1:8000`).

Run Vite for frontend assets (Tailwind/JS):

```bash
npm run dev           # development (auto-reload)
npm run build         # production build
```

---

## 🗄️ 3. Database & Migrations

### Run migrations

```bash
php artisan migrate
```

### Rollback / reset

```bash
php artisan migrate:rollback     # undo last batch
php artisan migrate:reset        # reset all migrations
php artisan migrate:fresh        # drop all tables and re-run migrations
```

### Seed database

```bash
php artisan db:seed
php artisan db:seed --class=UsersSeeder
```

### Migrate + seed

```bash
php artisan migrate --seed
```

---

## 🧱 4. Make Commands (Generate Files)

### Models

```bash
php artisan make:model ModelName
php artisan make:model ModelName -m     # with migration
php artisan make:model ModelName -mf    # with migration + factory
```

### Controllers

```bash
php artisan make:controller MyController
php artisan make:controller MyController --resource
```

### Migrations

```bash
php artisan make:migration create_table_name_table
```

### Seeders & Factories

```bash
php artisan make:seeder UsersSeeder
php artisan make:factory UserFactory
```

### Other common generators

```bash
php artisan make:middleware CheckSomething
php artisan make:request StoreSomethingRequest
php artisan make:command CustomCommand
```

---

## 🧭 5. Routes & Debugging

List all routes:

```bash
php artisan route:list
```

Clear caches (useful after config/view changes):

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

Rebuild config cache:

```bash
php artisan config:cache
```

---

## 🧪 6. Testing & Tinker

Run tests:

```bash
php artisan test
# or
phpunit
```

Open Tinker (interactive REPL for Laravel):

```bash
php artisan tinker
```

---

## 🧰 7. Useful NPM Scripts (Laravel + Vite)

From project root:

```bash
npm run dev        # watch & rebuild on file change
npm run build      # production build
npm run lint       # if configured
```

---

## ✅ 8. Typical Workflow (Existing Project)

1. Clone repo & enter folder  
   ```bash
   git clone https://github.com/USERNAME/REPO.git
   cd REPO
   ```

2. Install dependencies  
   ```bash
   composer install
   npm install
   ```

3. Configure environment  
   ```bash
   cp .env.example .env
   php artisan key:generate
   # edit .env for DB, etc.
   ```

4. Migrate database  
   ```bash
   php artisan migrate --seed  # if seeders available
   ```

5. Run app  
   ```bash
   php artisan serve
   npm run dev
   ```

Now you’re ready to develop! 🎉
