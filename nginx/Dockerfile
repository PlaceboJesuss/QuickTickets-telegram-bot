FROM nginx:alpine

# Устанавливаем openssl для генерации DH параметров
RUN apt-get update && apt-get install -y --no-install-recommends openssl && \
    rm -rf /var/lib/apt/lists/*

COPY ./nginx.conf.template /etc/nginx/conf.d/default.conf.template
COPY ./entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80 443
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
