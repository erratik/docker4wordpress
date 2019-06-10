FROM wodby/wordpress-nginx:4-1.13-4.0.2
USER root
COPY nginx /etc/nginx/

COPY crontab /etc/cron.d/wpvr
RUN chmod 0644 /etc/cron.d/wpvr
RUN service cron start

RUN mkdir -p /logs/nginx \
  && chown -R 755 /logs/;

