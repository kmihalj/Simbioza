
# Installation

This project is a Composer-enabled PHP framework that serves as a base
for building custom applications. The recommended
way to install it is via Composer’s create-project command.

If you don’t yet have Composer installed, see https://getcomposer.org/download/

## Requirements

Check the `composer.json` file for the required PHP version and extensions.

## 1. Create a project

```shell
composer create-project aaieduhr/heartphrame my-project
```

Adjust the directory name (`my-project`) to your liking.

You\'ll want to delete the `.git` directory and initialize a new Git repository.

```shell
rm -rf .git
git init --initial-branch=main
```

## 2. Install dependencies

To install the dependencies, run the following command:

```shell
composer install
```

## 3. Running code quality tools

At this point, all code quality checks should pass. The `pre-commit` script
runs a series of tools to ensure code quality, including PHP Code
Sniffer, PHPStan, and PHPUnit.

```shell
composer pre-commit
```

## 4. Add your first commit

Replace any occurrences of `aaieduhr` with your username or organization name;
and `heartphrame` with the name of your application, project, or repository.
Also, adjust authors in `composer.json`.

After that, you can push your changes as an initial commit to your module repo:

```shell
git remote add origin git@git-instance.org:project/repo.git
git add .
git commit -m "Initial commit"
git push --set-upstream origin main
```

## 5. Adjust environment configuration

Copy the `config/env.php.dist` file to `config/env.php` and adjust the
environment variables as needed.

```shell
cp config/env.php.dist config/env.php
nano config/env.php
```
You can use the command `vendor/bin/hph encryption:generate-key`
to generate a new encryption key and set it in the `config/env.php` file.

> **Note:** The `config/env.php` file is ignored by Git, so you can safely
adjust it as needed.

## 6. Make the runtime `data/` folder writable

Web server needs write access to the `data/` folder, so make sure it is
writable by the web server user. Apache example:

```shell
chown -R www-data:www-data data/
```

## 7. Run the application

Configure your web server’s document root to point to the `public/` directory.

### Apache Example

```apache
<VirtualHost *:80>
    ServerName your-project.test
    DocumentRoot /path/to/your-project/public

    <Directory /path/to/your-project/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Nginx Example

```nginx
server {
    listen 80;
    server_name your-project.test;
    root /path/to/your-project/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock; # Adjust to your PHP version
    }
}
```

After that, you can visit the application using your Web Browser, for
example, at `http://localhost/`, or similar (depending on your
server configuration).

Note that this repository includes sample code and files that are here just
for demonstration purposes. You should adjust or delete them before you start
developing your application. This includes, but is not limited to:
- `database/migrations/`
- `src`
- `views`
- `lang`

