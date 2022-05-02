Greedo
======

Greedo is a pretty dumb command line tool to quickly spin up new PHP based projects in your local docker environment.

Hitorically, I've always used [Trellis](https://roots.io/trellis) for most of my WordPress related projects, and then 
Homestead for Laravel.  The problem with Trellis and related is the use of Vagrant and Vagrant's reliance on NFS for 
file sharing.  NFS server on newer version of MacOS, in my experience, is very problematic.  I would get random lock ups
in the vagrant VMs as NFS would inexplicably time out.  I'm not so married to vagrant that I wanted to spend the time
to figure it out.

So my next option was using Docker, which I've used before.  I tried giving Lando a spin, but had issues getting it 
working (came down to `sendfile on` in a nginx config, btw) and I also didn't care for it being such a black box.  I
don't understand why it can't be used as not only a tool to setup dev environments but also get you started on building
production ready docker images.

Greedo is a quick two day hack that is similar to Lando but much more limited and probably not as good or as flexible.

But it works for me.

Greedo uses a `greedo.yml` file to define your dev environment that it then uses to build docker compose files.  You can
up those files yourself, or continue using greedo to start/stop the environment.

Requirements
------------
Greedo requires:

- macOS with Docker Desktop or Linux with Docker installed (tested on Ubuntu 21.10)
- Caddy (optional)
- PHP 7.4+ with the pctl extension installed.

Installation
------------
To install, do the following:

```bash
git clone https://github.com/jawngee/greedo.git
cd greedo
composer update
chmod a+x bin/greedo
ln -s bin/greedo /usr/local/bin/greedo
```

I don't provide a binary because you may want to hack on Greedo's templates to generate docker and related files more
to your liking.

How Greedo Works
----------------
Greedo isn't based on docker images like Lando, it significantly simpler than that.  Greedo takes the `greedo.yml` 
configuration file and then runs that through a series of templates to generate all the related docker and config
files you need to run your environment.

Greedo uses Laravel Blade for templating.

Using Greedo
------------
Simply change to your project's directory and run `greedo init`.  I typically like to do this one directory above my
source.  For example, my directory layout for a WordPress project looks like this:

- Projects/
  - WordPress/
    - domainname.com/
      - site/
      - logs/

In the above example, I'd create the `greedo.yml` file in the `/Projects/WordPress/domainname.com/` directory.

When you run `greedo init`, you'll be prompted by a series of inputs to pick and choose all the things you need to
configure your environment.  After it's done, the `greedo.yml` file will be created and you can edit it further if you
need to.

Once that file is edited to your liking, simply run `greedo build` to generate the docker and config files.

`sudo greedo start` and `sudo greedo stop` will start and stop the environment.

Other Commands
--------------
Once your environment is running, you can do other things with greedo:

`greedo ssh <service>` will open a terminal in the container.  For example, `greedo ssh php` will open a terminal in the
php-fpm container.  Note that the service name is the name used in your `greedo.yml` file, eg. `php`, `db`, `cron`, etc.

`greedo wp <command>` will run a command using WP-CLI in your php-fpm container.

`greedo composer <command>` will run a command using composer in your php-fpm container.

Recipes
-------
Greedo can save your `greedo.yml` file as a "recipe" that you can then re-use later to apply a similar configuration to
another project.

`greedo recipes:add <recipe name>` will add the `greedo.yml` file in the current directory to `~/.greedo/recipes/` for
later recall.

`greedo recipes:use <recipe name>` will recall a previously saved `greedo.yml` file and apply it to the current
directory.

Greedo Sample Config
--------------------
```yml
version: 2

name: sample          # name of your projects, no spaces or special characters
domains:              # list of domains to use for your project, first domain being the primary
  - sample.local
proxy: caddy          # proxy to use, right now this is only caddy

services:             # list of services to run in your project, valid: php, db, cron, redis, memcached, mailhog
  php:
    version: '8.0'        # php version, valid 7.4, 8.0 and 8.1
    app_dir: ./site/      # directory of your app 
    public_dir:           # public html directory, relative to app_dir
    upload_limit: 512     # upload limit in megabytes
    port: 8081            # exposed public port
    mount:                # any additional mount points for php-fpm and nginx
      - ./logs/php/:/var/logs/php/:rw
    xdebug: true          # install xdebug?
    composer: true        # install composer?
    wpcli: true           # install wp-cli?
    # list of extensions to install, see https://github.com/mlocati/docker-php-extension-installer for complete list
    extensions:           
      - xmlrpc
      - mysqli
      - zip
      - gd
      - bcmath
      - opcache
      - exif
      - imagick
      - redis
      - memcache
      - memcached
      - vips
      - oauth
      - yaml
    # php.ini flags and values
    ini:                 
      flags:
        log_errors: On
        display_errors: On
      values:
        error_log: /var/logs/php/error.log

  db:
    server: mariadb       # database server, valid: mariadb, mysql, postgres
    name: sample          # name of the database
    user: wordpress       # user name
    password: password    # password
    port: 33306           # public port, optional
    
  cron:                   # crontab service for running cronjobs
      jobs:
        - "*/1 * * * * curl http://sample.local/wp-cron.php?doing_wp_cron > /dev/null 2>&1"

  redis:                  # redis service
    version: 7            # redis version, valid: 5, 6, 7
    port: 14567           # public port, optional

  memcached:              # memcached service
    mem: 64               # amount of memory to allocate  
    port: 21211           # public port, optional

  mailhog:                # mailhog service
    port: 8082            # public port, not optional
```