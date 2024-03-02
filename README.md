# Sales

A service sales manager especially for freelancers. Built in German, available in English too.

## Features

- A dashboard showing current trends and tax data
- Keep track of clients
- Create projects for clients and provide estimations
- Create invoices and fill them with descriptive positions
- PDF export invoices from projects
- Keep track of expenses
- Keep track of gifts/donations (e.g. if you're an OS maintainer)

![sales_demo](https://github.com/devmount/sales/assets/5441654/037e8b6b-e673-430f-91c2-39146cc54d1b)

## Setup

Prerequisites:

- PHP 8.2 or later
- Composer 2.5 or later

```bash
git clone https://github.com/devmount/sales # get files
cd sales                       # switch to app directory
composer install               # install dependencies
cp .env.example .env           # init environment configuration
touch database/database.sqlite # create database file (or setup your database of choice)
php artisan migrate            # create database structure
php artisan key:generate       # build a secure key for the app
php artisan db:seed            # create initial admin user
npm i
```

## Development

To start a local development server, run:

```bash
php artisan serve # start dev webserver backend
npm run dev       # start dev webserver frontend
```

Now you can log in on <http://localhost:8000> with the initial admin user credentials (email: `admin@example.com`, password: `Joh.3,16`).

## Production

To build the application for production, run:

```bash
composer install --optimize-autoloader --no-dev
php artisan config:cache # combine all configuration files into a single, cached file
php artisan route:cache  # reduce all route registrations into a single method call within a cached file
php artisan view:cache   # precompile all blade views
php artisan icons:cache  # precompile all icons
npm run build
```

In `.env` set `APP_DEBUG` to false and `APP_URL` to your production url. Change more values if needed.

The webserver should be configured to serve the `public/` directory as root.

If you don't have composer installed on your webserver (e.g. because you are restricted by your provider), you can download a portable version into the project root:

```bash
wget https://getcomposer.org/download/latest-stable/composer.phar
chmod +x composer.phar
php composer.phar # use composer commands like this
```

## License

This project is a filament / Laravel framework based open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
