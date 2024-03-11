# A Symfony app to demonstrate the DbToolsBundle performance

The DbToolsBundle helps Symfony developpers to easily set up an anonymization workflow.

Unlike other anonymization tools, we choose to anonymize throught SQL update queries instead of
loading each entities to anonymize them with some PHP scripts.

This method makes the DbToolsBundle really fast to anonymize data.

The purpose of this application is to demonstrate these capabilities.

In the `app/` directory, you will find a Symfony application that uses 4 different
DBAL Doctrine connections, one for each of those database platforms:
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

After having generating data, here is the results we got:

|                                                   | PostgreSQL | SQLite | MariaDb | MySQL
|---------------------------------------------------|------------|--------|---------|---------
| 100K Customers alone                              | ~5s        | ~7s    | ~20s    | ~53s
| 500K Customers alone                              | ~9s        | ~10s   | ~37s    | ~3m44s
| 1 000K Customers alone                            | ~16s       | ~16s   | ~1m23s  | ~43m56s
| 200K Addresses alone                              | ~6s        | ~10s   | ~26s    | ~42s
| 1 000K Orders alone                               | ~16s       | ~11s   | ~1m15s  | ~36m31s
| 100K Customers and 200K Addresses                 | ~7s        | ~10s   | ~32s    | ~1m16s
| 100K Customers, 200K Addresses and 1 000K Orders  | ~24s       | ~25s   | ~1m40s  | ~36m47s

<small>**NB1**: For each line, the same backup file has been used. It means that the restore and the backup steps was
the same: in some cases (mysqland mariadb), these steps represent a big part of the time.</small><br>
<small>**NB2**: Each database vendor docker image has been used as is. Without any tweaking.
This could explain the bad results for MySQL.</small>

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
