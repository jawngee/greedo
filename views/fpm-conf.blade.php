[{{$name}}]
listen = /var/run/php-fpm-{{$name}}.sock
@if($fpm_user && $fpm_group)
listen.owner = {{$fpm_user}}
listen.group = {{$fpm_group}}
user = {{$fpm_user}}
group = {{$fpm_group}}
@endif
pm = dynamic
pm.max_children = 10
pm.start_servers = 1
pm.min_spare_servers = 1
pm.max_spare_servers = 3
pm.max_requests = 500
chdir = {{$public_dir}}
@foreach($php_flags as $flag => $flagValue)
php_flag[{{$flag}}] = {{$flagValue}}
@endforeach
@foreach($php_values as $value => $valueValue)
php_admin_value[{{$value}}] = {{$valueValue}}
@endforeach
php_admin_value[open_basedir] = /tmp:{{$app_dir}}
php_admin_value[upload_max_filesize] = {{$upload_limit}}M
php_admin_value[post_max_size] = {{$upload_limit}}M
request_terminate_timeout = 86400