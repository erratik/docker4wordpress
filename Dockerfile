FROM wodby/wordpress-nginx:4-1.13-4.0.2
USER root
COPY nginx /etc/nginx/
# COPY nginx/* /etc/nginx/
RUN mkdir -p /logs/nginx \
    && chown -R 755 /logs/;
    
