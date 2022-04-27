server {
    listen [::]:80;
    listen 80;
    server_name {{$domain}};

    root  {{$public_dir}};
    index index.php index.htm index.html;
    add_header Fastcgi-Cache $upstream_cache_status;

    # Specify a charset
    charset utf-8;

    # Set the max body size equal to PHP's max POST size.
    client_max_body_size {{$upload_limit}}m;

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
        try_files $uri $uri/ /index.php?$args;
    }

    include h5bp/directive-only/extra-security.conf;
    include h5bp/directive-only/x-ua-compatible.conf;
    include h5bp/location/cross-domain-fonts.conf;
    include h5bp/location/protect-system-files.conf;

    add_header Content-Security-Policy "frame-ancestors 'self'" always;

    # Conditional X-Frame-Options until https://core.trac.wordpress.org/ticket/40020 is resolved
    set $x_frame_options SAMEORIGIN;
    if ($arg_customize_changeset_uuid) {
        set $x_frame_options "";
    }
    add_header X-Frame-Options $x_frame_options always;

    # Prevent search engines from indexing non-production environments
    add_header X-Robots-Tag "noindex, nofollow" always;

    location ~ \.php$ {
        try_files $uri /index.php;

        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        fastcgi_pass unix:/var/run/php-fpm-{{$name}}.sock;
    }
}