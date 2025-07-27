local cjson = require "cjson.safe"

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
ngx.eof()

-- Envia dados para um worker em background
ngx.timer.at(0, function(premature, data)
    if premature then return end

    local ok, body = pcall(cjson.encode, data)
    if not ok or not body then
        ngx.log(ngx.ERR, "JSON encode failed")
        return
    end

    local workers = {"worker1", "worker2", "worker3"}
    local host = workers[math.random(#workers)]

    local sock = ngx.socket.tcp()
    sock:settimeouts(100, 100, 1)

    local ok, err = sock:connect(host, 80)
    if not ok then
        ngx.log(ngx.ERR, "failed to connect to worker: ", err)
        return
    end

    local req = "POST /process HTTP/1.1\r\n" ..
                "Host: " .. host .. "\r\n" ..
                "Content-Type: application/json\r\n" ..
                "Content-Length: " .. #body .. "\r\n" ..
                "Connection: close\r\n\r\n" ..
                body

    local bytes, err = sock:send(req)
    if not bytes then
        ngx.log(ngx.ERR, "failed to send request: ", err)
    end

    sock:close()
end, payload)
