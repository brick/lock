# Docker environment

The docker environment facilitates local development and testing.

## Prerequisites
* [Docker Engine](https://docs.docker.com/engine/) `v1.13+`
* [Docker Compose](https://docs.docker.com/compose/) `v2.0+`

## Configuration

Create a `.env` file, optionally adjusting the environment variables to your needs:

```bash
cp .env.dist .env
```

*Note: if you make changes to the `.env` file, you need to restart the containers with `docker compose up -d`.*

## Startup

```bash
docker compose up -d
docker compose exec php bash
composer install
```

## Running tests

Inside the `php` container:

```bash
vendor/bin/phpunit
````

## Shutdown

```bash
docker compose down
```
