local cjson = require "cjson.safe"
local redis = require "resty.redis"

-- Captura dados da requisição
ngx.req.read_body()
local payload = {
    method = ngx.req.get_method(),
    uri_args = ngx.req.get_uri_args(),
    body = ngx.req.get_body_data(),
    content_type = ngx.var.content_type or "",
    timestamp = ngx.now()
}

-- Responde ao cliente imediatamente
ngx.header.content_type = "application/json"
ngx.status = ngx.HTTP_OK
ngx.say('{"status":"OK","message":"Accepted"}')

-- Timer em background com dados passados como arg
ngx.timer.at(0.001, function(premature, payload)
    if premature then return end

    local red = redis:new()
    red:set_timeout(100)

    local ok, err = red:connect("redis", 6379)
    if not ok then
        ngx.log(ngx.ERR, "Redis connect failed: ", err)
        return
    end

    local encoded = cjson.encode(payload)
    if not encoded then
        ngx.log(ngx.ERR, "JSON encode failed")
        return
    end

    local ok, err = red:rpush("payments_queue", encoded)
    if not ok then
        ngx.log(ngx.ERR, "Redis RPUSH failed: ", err)
    end

    red:set_keepalive(60000, 100)
end, payload)
