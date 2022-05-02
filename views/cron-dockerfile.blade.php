FROM alpine:3.3

RUN apk add --update apk-cron curl && rm -rf /var/cache/apk/*
ADD ./cron/crontab.txt /crontab.txt
COPY ./cron/entry.sh /entry.sh
RUN chmod 755 /entry.sh
RUN /usr/bin/crontab /crontab.txt

CMD ["/entry.sh"]