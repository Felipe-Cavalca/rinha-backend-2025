FROM openresty/openresty:alpine

RUN apk add --no-cache \
    lua-resty-http \
    lua-resty-redis \
    lua-cjson

COPY nginx.conf /usr/local/openresty/nginx/conf/nginx.conf
COPY handler.lua /usr/local/openresty/nginx/lua/handler.lua
