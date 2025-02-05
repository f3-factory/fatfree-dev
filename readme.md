[![Fat-Free Framework](ui/images/logo.png)](http://fatfreeframework.com/)

# F3 development bench

This is a dockerized environment for testing and developing the [fatfree-core](https://github.com/f3-factory/fatfree-core) library and extensions for it. 

It's utilizing Docker composer to spin up multiple containers, so the framework can be tested with all supported services and different versions of them at the same time.

Currently included:

- Load Balancer
  - Traefik
- Webserver
  - Apache 2.4
  - Nginx
- HTTP Application Server
  - Swoole
  - OpenSwoole
  - RoadRunner
- Caching
  - Memcached
  - Redis
- Database
  - MySQL 8.0
  - PostgreSQL
  - SQL Server
- PHP Versions
  - PHP-FPM 8.4
  - PHP-FPM 8.3
  - PHP-FPM 8.2

The services are bind to your local machine network using [Traefik](https://doc.traefik.io/traefik/). That way you'll have nice domain names for each php version with zero further configuration. 
Open http://localhost:8080 for reviewing the current traefik configuration.
Also included is ready to use **XDebug**.

## Usage

1. First install [docker](https://www.docker.com/products/docker-desktop).
2. Copy `sample.env` to `.env` and if needed, adjust the ports to your local machine in case you already have something running at the default ports.
3. Run `docker-compose build` to build the containers. This might take a while, depending on your hardware.
4. Run `docker-compose up -d` to start the dev bench
5. Open your browser with the desired version to run:
   - Apache Webserver
     - PHP 8.4: http://f3.php84.localhost
     - PHP 8.3: http://f3.php83.localhost
     - PHP 8.2: http://f3.php82.localhost
     - PHP 8.2 + SSL: https://f3.php82.localhost
   - Nginx
     - PHP 8.4: http://f3.nginx.php84.localhost
     - PHP 8.3: http://f3.nginx.php83.localhost
     - PHP 8.2: http://f3.nginx.php82.localhost
     - PHP 8.2 + SSL: https://f3.nginx.php82.localhost
   - F3-Overdrive:
     - Swoole, PHP 8.4: http://f3.php84swoole.localhost
     - Swoole, PHP 8.4, Nginx Proxy: http://f3.nginx.php84swoole.localhost
     - OpenSwoole, PHP 8.2: http://f3.php84openswoole.localhost/
     - RoadRunner, PHP 8.4, Nginx Proxy: http://f3.nginx.php84rr.localhost/

## TODO

- add mailhog for smtp testing

---

### Legal notice

This development environment belongs to the f3-factory community and is not meant for usage on a production system. No warranty for any security issues in case you put this on your public root.

**Copyright (c) 2025 F3::Factory**
