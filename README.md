# A Symfony app to demonstrate the DbToolsBundle performance

The DbToolsBundle helps Symfony developpers to easily set up an anonymization workflow.

Unlike other anonymization tools, we choose to anonimyze throught SQL update queries instead of
loading each entities to anonymize them with some PHP scripts.

This method makes the DbToolsBundle really fast to anonymize data.

The purpose of this application is to demonstrate these capabilities.

In the `app/` directory, you will find a Symfony application that uses 4 different
DBAL Doctrine connections, one for each of those database platform:
  * SQLite
  * PostgreSQL
  * MariaDb
  * MySQL

Each one of these connections has 3 entities defined like this:
* Customer
* Message
* Appointment

At repository root, you will find 4 backups, one for each connection, and ready to be anonymized.

This repository also comes with a complete docker stack, to start it and launch anonymization
on your local machine, follow these steps:

```sh
  # Start up the docker stack
  docker compose up -d

  # Install composer dependencies
  docker compose exec php-fpm composer install

  # Create database schema for each connections
  docker compose exec php-fpm bin/console doctrine:schema:update --complete --force --em=sqlite
  docker compose exec php-fpm bin/console doctrine:schema:update --complete --force --em=postgresql
  docker compose exec php-fpm bin/console doctrine:schema:update --complete --force --em=mysql
  docker compose exec php-fpm bin/console doctrine:schema:update --complete --force --em=mariadb
```

From here, you are ready to launch anonymization for each given backup:

```sh
  time docker compose exec php-fpm bin/console db-tools:anonymize /var/www/sqlite.sql --connection=sqlite -n
  time docker compose exec php-fpm bin/console db-tools:anonymize /var/www/postgresql.dump --connection=postgresql -n
  time docker compose exec php-fpm bin/console db-tools:anonymize /var/www/mysql.sql --connection=mysql -n
  time docker compose exec php-fpm bin/console db-tools:anonymize /var/www/mariadb.sql --connection=mariadb -n
```
----

If you want to play more with this application, here is some usefull commands:

```sh
  # Create dummy data (100 000 customers) for each connection
  docker compose exec php-fpm bin/console app:dummy-data --no-debug

  # Backup all databases
  docker compose exec php-fpm bin/console db-tools:backup --connection=sqlite
  docker compose exec php-fpm bin/console db-tools:backup --connection=postgresql
  docker compose exec php-fpm bin/console db-tools:backup --connection=mysql
  docker compose exec php-fpm bin/console db-tools:backup --connection=mariadb

  # Dump current anonymization configuration
  docker compose exec php-fpm bin/console db-tools:anonymization:dump-config

  # Drop all schemas
  docker compose exec php-fpm bin/console doctrine:schema:drop --force --em=sqlite
  docker compose exec php-fpm bin/console doctrine:schema:drop --force --em=postgresql
  docker compose exec php-fpm bin/console doctrine:schema:drop --force --em=mysql
  docker compose exec php-fpm bin/console doctrine:schema:drop --force --em=mariadb

  # Shut down containers
  docker compose down

  # Connect to each database
  docker compose exec postgres psql -U db db
  docker compose exec mysql mysql -u db db --password=password
  docker compose exec mariadb mariadb -u db db --password=password
```
