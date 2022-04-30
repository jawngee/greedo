version: '3.9'
services:
@if($hasDB)
  {{$name}}_db:
    container_name: {{$name}}_db
    image: {{$dbImage}}
    restart: always
    networks:
      - {{$name}}_network
@if($dbServer === 'mariadb')
    volumes:
      - {{$name}}-db-volume:/var/lib/mysql:rw
    environment:
      MARIADB_RANDOM_ROOT_PASSWORD: 'yes'
      MARIADB_DATABASE: {{$dbName}}
      MARIADB_USER: {{$dbUser}}
      MARIADB_PASSWORD: {{$dbPassword}}
@if(!empty($dbPort))
    ports:
      - {{$dbPort}}:3306
@endif
@elseif($dbServer === 'postgres')
    volumes:
      - {{$name}}-db-volume:/var/lib/postgresql/data:rw
    environment:
      POSTGRES_DB: {{$dbName}}
      POSTGRES_USER: {{$dbUser}}
      POSTGRES_PASSWORD: {{$dbPassword}}
@if(!empty($dbPort))
    ports:
      - {{$dbPort}}:5432
@endif
@endif
@endif

@if($hasRedis)
  {{$name}}_redis:
    container_name: {{$name}}_redis
    image: redis:{{$redisVersion}}
    networks:
      - {{$name}}_network
@if(!empty($redisPort))
    ports:
      - {{$redisPort}}:6379
@endif
@endif

@if($hasMemcached)
  {{$name}}_memcached:
    container_name: {{$name}}_memcached
    image: bitnami/memcached:latest
    environment:
      MEMCACHED_CACHE_SIZE: {{$memcachedMemory}}
    networks:
      - {{$name}}_network
@if(!empty($memcachedPort))
    ports:
      - {{$memcachedPort}}:11211
@endif
@endif

@if($hasMailhog)
  {{$name}}_mailhog:
    container_name: {{$name}}_mailhog
    image: mailhog/mailhog
    networks:
      - {{$name}}_network
@if(!empty($mailhogPort))
    ports:
      - {{$mailhogPort}}:8025
@endif
@endif

@if($hasPHP)
  {{$name}}_php:
    container_name: {{$name}}_php
    build:
      context: ./phpfpm
    image: {{$name}}_php:{{$phpVer}}
    volumes:
      - {{$appDir}}:/srv/www
      - ./conf/php-fpm/www.conf:/usr/local/etc/php-fpm.d/www.conf
@foreach($mounts as $mount)
      - {{$mount}}
@endforeach
    networks:
      - {{$name}}_network

  {{$name}}_nginx:
    container_name: {{$name}}_nginx
    image: nginx:stable-alpine
    ports:
      - {{$publicPort}}:80
    volumes:
      - {{$appDir}}:/srv/www
      - ./conf/nginx/default.conf:/etc/nginx/conf.d/default.conf:ro
    networks:
      - {{$name}}_network
    network_mode: bridged
    depends_on:
      - {{$name}}_php
@if($hasDB)
      - {{$name}}_db
@endif
@if($hasRedis)
      - {{$name}}_redis
@endif
@if($hasMemcached)
      - {{$name}}_memcached
@endif
@if($hasMailhog)
      - {{$name}}_mailhog
@endif
@endif

networks:
  {{$name}}_network:
    driver: bridge

@if($hasDB)
volumes:
  {{$name}}-db-volume: {}
@endif