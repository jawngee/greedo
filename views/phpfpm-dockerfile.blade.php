FROM php:{{$phpVer}}-fpm-alpine

WORKDIR {{$publicPath}}

@if($installMysqlClient)
RUN apk add --no-cache mysql-client
@endif

@if(count($extensions) > 0)
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions \
@foreach($extensions as $extension)
    {{$extension}} @if(!$loop->last)\@endif

@endforeach
@endif

@if ($installComposer)
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
@endif

@if ($installWPCLI)
RUN curl -o /bin/wp-cli.phar https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
RUN chmod +x /bin/wp-cli.phar \
    && mv /bin/wp-cli.phar /usr/local/bin/wp
@endif