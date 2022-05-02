server {
    listen [::]:80;
    listen 80;
    server_name {{implode(' ', $domains)}};

    error_log  /var/log/nginx/error.log;
    access_log /var/log/nginx/access.log;

    root {{$publicPath}};

    index index.php index.htm index.html;

    add_header Fastcgi-Cache $upstream_cache_status;

    # Specify a charset
    charset utf-8;

    # Set the max body size equal to PHP's max POST size.
    client_max_body_size {{$uploadLimit}}m;

    # https://www.nginx.com/resources/wiki/start/topics/tutorials/config_pitfalls/#virtualbox
    sendfile off;

    include includes.d/all/*.conf;

    # Prevent PHP scripts from being executed inside the uploads folder.
    location ~* /wp-content/uploads/.*\.php$ {
        deny all;
    }

    # Prevent Blade and Twig templates from being accessed directly.
    location ~* \.(blade\.php|twig)$ {
        deny all;
    }

    # composer
    location ~* composer\.(json|lock)$ {
        deny all;
    }

    location ~* composer/installed\.json$ {
        deny all;
    }

    location ~* auth\.json$ {
        deny all;
    }

    # npm
    location ~* package(-lock)?\.json$ {
        deny all;
    }

    # yarn
    location ~* yarn\.lock$ {
        deny all;
    }

    # bundler
    location ~* Gemfile(\.lock)?$ {
        deny all;
    }

    location ~* gems\.(rb|locked)?$ {
        deny all;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    if (!-e $request_filename) {
        rewrite ^.*$ /index.php last;
    }

    location ~ \.php$ {
        fastcgi_pass {{$name}}_php:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_buffers 16 16k;
        fastcgi_buffer_size 32k;
@if(isset($phpValues['max_execution_time']))
        fastcgi_read_timeout {{$phpValues['max_execution_time']}}s;
@endif
        include fastcgi_params;
    }
}