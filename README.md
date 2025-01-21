# Symfony API

## Setup

Install dependencies:
```
composer install
```

Setup env vars in `.env.local`
```
DATABASE_URL="mysql://USER:PASS@127.0.0.1:3306/lexsynergy_api"
MAILER_DSN=smtp://USER:PASS@sandbox.smtp.mailtrap.io:25
```

Setup DB:
```
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

Run the app locally:
```
symfony server:start
```

## API

Docs located here:
```
http://127.0.0.1:8000/api/doc
```

Endpoints:
```
curl --location 'http://127.0.0.1:8000/api/events'
```

```
curl --location 'http://127.0.0.1:8000/api/events' \
--header 'Content-Type: application/json' \
--data '{
    "name": "first event",
    "startDate": "2025-01-17 09:00:00",
    "endDate": "2025-01-17 17:00:00",
    "location": "London",
    "capacity": 13
}'
```

```
curl --location 'http://127.0.0.1:8000/api/events/1'
```

```
curl --location 'http://127.0.0.1:8000/api/events/1/attendees' \
--header 'Content-Type: application/json' \
--data-raw '{
    "name": "John Doe",
    "email": "john.doe@example.com"
}'
```

```
curl --location --request DELETE 'http://127.0.0.1:8000/api/events/1/attendees/1'
```

## Mailer

Emails handled asynchronously, stored in the DB.

Run the following command to consume the messages and send the emails (I use mailtrap locally):
```
php bin/console messenger:consume async
```

From email is set in `.env` `EMAIL_FROM`

## Tests

Add test DB to `.env.test.local`
```
DATABASE_URL="mysql://root:root@127.0.0.1:3306/dbname_test"
```

Setup test DB (this should be automated with CI)
```
php bin/console --env=test doctrine:database:create
php bin/console --env=test doctrine:migrations:migrate
```

Run tests:
```
php bin/phpunit
```

```
OK (10 tests, 35 assertions)
```