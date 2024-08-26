[![Fat-Free Framework](ui/images/logo.png)](http://fatfreeframework.com/)

# F3 development bench

This is a dockerized environment for testing and developing things on the [fatfree-core](https://github.com/f3-factory/fatfree-core). 

It's utilizing Docker to spin up multiple containers, so the framework can be tested with multiple services and different versions of them at the same time.

Currently included:

- Apache 2.4
- Nginx
- Memcached
- Redis
- MySQL 5.7
- PostgreSQL
- SQL Server
- PHP-FPM 8.3
- PHP-FPM 8.2
- PHP-FPM 8.1
- PHP-FPM 8.0
- PHP-FPM 7.4
- PHP-FPM 7.3
- PHP-FPM 7.2

The services are bind to your local machine network using [Traefik](https://doc.traefik.io/traefik/). That way you'll have nice domain names for each php version with zero further configuration. 
Open localhost:8080 for reviewing the current traefik configuration.
Also included is ready to use **XDebug**.

## Usage

1. First install [docker](https://www.docker.com/products/docker-desktop).
2. Copy `sample.env` to `.env` and if needed, adjust the ports to your local machine in case you already have something running at the default ports.
3. Run `docker-compose build` to build the containers. This might take a round 20min, depending on your hardware.
4. Run `docker-compose up -d` to start the dev bench
5. Open your browser with the desired version to run:
   - Apache Webserver
     - http://f3.php83.localhost
     - http://f3.php82.localhost
     - http://f3.php81.localhost
     - http://f3.php80.localhost
     - http://f3.php74.localhost
     - http://f3.php73.localhost
     - http://f3.php72.localhost
   - Nginx
      - http://f3.nginx.php83.localhost
      - http://f3.nginx.php82.localhost
      - http://f3.nginx.php81.localhost
      - http://f3.nginx.php80.localhost
      - http://f3.nginx.php74.localhost


## TODO

- add mailhog for smtp testing

---

### Legal notice

This development environment belongs to the f3-factory community and is not meant for usage on a production system. No warranty for any security issues in case you put this on your public root.

**Copyright (c) 2024 F3::Factory**
