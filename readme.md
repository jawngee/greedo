Greedo
======
Greedo is a pretty dumb command line tool to quickly spin up new PHP based projects in your local dev environment.

I normally use [Trellis](https://roots.io/trellis) for most of my WordPress related projects, and then Homestead for Laravel.  The issue I've been running into is that NFS (the default that Vagrant uses for sharing host folders to the VM) is buggy af on macOS.  Consistent lock ups on the VM side regardless of whatever NFS options I'm using to mount.  This seems to happen in Parallels AND VirtualBox.

My next option was using Docker (and Lando) but I ran into some weird issues with that too.

So I finally just setup a Kubuntu VM in Parallels and got a working dev setup going, using Parallels shared folders to mount host directories to serve from.

I built Greedo in a couple of hours to spin up sites, create databases, etc.

Usage
-----
To install, do the following:

```bash
git clone https://github.com/jawngee/greedo.git
cd greedo
composer update
chmod a+x bin/greedo
ln -s bin/greedo /usr/local/bin/greedo
```

Greedo assumes you are using Ubuntu server 18+.  My dev server is setup similarly to how Trellis setups up a VM.

Once installed, run the `greedo init` command to create a new project yml file.

After the file is created, run `sudo greedo start` to create your nginx and php-fpm configs, add your dev's site host name to the hosts file and, optionally, create your database.

To stop the site you can run `sudo greedo stop`.  To stop the site AND delete the database run `sudo greedo stop --destroy`.

What Greedo Actually Does
----
When you run `sudo greedo start`, greedo will:

- Create an nginx config for your site and store it in `/etc/nginx/sites-enabled/<project_name>.conf`.
- Create a php-fpm config for your site and store it in `/etc/php/<php version>/fpm/pool.d/<project_name>.conf`.
- Add your host name to `/etc/hosts`.
- Create you database, if it doesn't exist, and user in mysql (optional).

PHP Ini Settings
----
You can set various php.ini flags and values within the php section of the `greedo.yml` file:

```yml
php:
  version: 7.4
  flags:
    log_errors: On
    display_errors: On
  values:
    error_log: /var/logs/php-error-sample.log
```

