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

Each one of these connections has 3 entities defined: Customer, Address and Order.

```txt

                            ┌──────────┐
                            │Address   │◄──────────────┐
                            ├──────────┤               │
                 ┌──────────┤- customer│◄──────────┐   │
                 ▼          │- street  │           │   │
           ┌───────────┐    │- zipcode │           │   │
           │Customer   │    │- city    │           │   │
           ├───────────┤    │- country │           │   │
           │- email    │    │          │           │   │
           │- password │    └──────────┘           │   │
           │- firstname│                           │   │
           │- age      │◄─┐   ┌──────────────────┐ │   │
           │- telephone│  │   │Order             │ │   │
           │           │  │   ├──────────────────┤ │   │
           └───────────┘  └───┤- customer        │ │   │
                              │- telephone       │ │   │
                              │- email           │ │   │
                              │- amount          │ │   │
                              │- createdAt       │ │   │
                              │- billingAddress  ├─┘   │
                              │- shippingAddress ├─────┘
                              │- note            │
                              └──────────────────┘

```

Anonymization has been configured for each connection like this:

Table: address

  Target    | Anonymizer |  Options
 -----------|------------|-------------
  address_0 | address    | street_address: street, postal_code: zip_code, locality: city, country: country

Table: customer

  Target    | Anonymizer |  Options
 -----------|------------|-------------------
  email     | email      |
  password  | password   |
  lastname  | lastname   |
  firstname | firstname  |
  age       | integer    | min: 10, max: 99

Table: order

  Target    | Anonymizer |  Options
 -----------|------------|-----------------------------
  telephone | fr-fr.phone|
  email     | email      |
  amount    | float      |  min: 10, max: 99
  note      | lorem      |

At repository root, you will find 4 backups, one for each connection, and ready to be anonymized.

These backups contain 100K Customers and, for each one of them:
  - 2 Adresses for all available connections
  - 10 Orders

So there will be 100K Customers, 200K Adresses and 1 000K Orders to anonymized.

Results are:

|                                                   | PostgreSQL | SQLite | MySQL   | MariaDb |
|---------------------------------------------------|------------|--------|---------|---------|
| 100K Customers alone                              | ~5s        | ~9s    | ~53s    | ~20s
| 200K Addresses alone                              | ~6s        | ~10s   | ~42s    | ~26s
| 1 000K Orders alone                               | ~16s       | ~11s   | ~36m31s | ~1m15
| 100K Customers and 200K Addresses                 | ~7s        | ~10s   | ~1m16   | ~32s
| 100K Customers, 200K Addresses and 1 000K Orders  | ~24s       | ~25s   | ~36m47s | ~1m40

<small>**NB1**: For each line, the same backup file has been used. It means that the restore and the backup steps was
the same: in some cases (mysqland mariadb), these steps represent a big part of the time.</small><br>
<small>**NB2**: Each database vendor docker image has been used as is. Without any tweaking.
This could explain bad result for MySQL.</small>

--

This repository also comes with a complete docker stack, to start it and launch anonymization
on your local machine, follow these steps:

```sh
  # Start up the docker stack
  docker compose up -d

  # Install composer dependencies
  docker compose exec php-fpm composer install

  # Create database schema for each connections
  docker compose exec php-fpm bin/console doctrine:schema:update --complete --force --em=postgresql
  docker compose exec php-fpm bin/console doctrine:schema:update --complete --force --em=sqlite
  docker compose exec php-fpm bin/console doctrine:schema:update --complete --force --em=mysql
  docker compose exec php-fpm bin/console doctrine:schema:update --complete --force --em=mariadb

  # Create initial dummy data (~20min)
  docker compose exec php-fpm bin/console app:dummy-data --no-debug

  # Backup all databases
  docker compose exec php-fpm bin/console db-tools:backup --connection=postgresql
  docker compose exec php-fpm bin/console db-tools:backup --connection=sqlite
  docker compose exec php-fpm bin/console db-tools:backup --connection=mysql
  docker compose exec php-fpm bin/console db-tools:backup --connection=mariadb

  # Dump current anonymization configuration
  docker compose exec php-fpm bin/console db-tools:anonymization:dump-config

  # Anonymizing given backup
  # (to adapt with correct backup paths)
  time docker compose exec php-fpm bin/console db-tools:anonymize /var/www/var/db_tools/xx/xx/postgresql-xxxxxx.dump --connection=postgresql -n
  time docker compose exec php-fpm bin/console db-tools:anonymize /var/www/var/db_tools/xx/xx/sqlite-xxxxxx.sql --connection=sqlite -n
  time docker compose exec php-fpm bin/console db-tools:anonymize /var/www/var/db_tools/xx/xx/mariadb-xxxxxx.sql --connection=mariadb -n
  time docker compose exec php-fpm bin/console db-tools:anonymize /var/www/var/db_tools/xx/xx/mysql-xxxxxx.sql --connection=mysql -n

  # Drop all schemas
  docker compose exec php-fpm bin/console doctrine:schema:drop --force --em=postgresql
  docker compose exec php-fpm bin/console doctrine:schema:drop --force --em=sqlite
  docker compose exec php-fpm bin/console doctrine:schema:drop --force --em=mysql
  docker compose exec php-fpm bin/console doctrine:schema:drop --force --em=mariadb

  # Shut down containers
  docker compose down

  # Connect to each database
  docker compose exec postgres psql -U db db
  docker compose exec mysql mysql -u db db --password=password
  docker compose exec mariadb mariadb -u db db --password=password
```
