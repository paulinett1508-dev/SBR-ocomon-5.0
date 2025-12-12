<?php

namespace OcomonApi\Core;

use OcomonApi\Models\Auth;
use OcomonApi\Models\User;
use OcomonApi\Core\Controller;

class OcomonApi extends Controller
{
    
    /** @var OcomonApi\Models\User|null */
    protected $user;

    /**
     * $headers
     * @var array|false
     */
    protected $headers;

    /**
     * $response
     * @var array|null
     */
    protected $response;
    
    
    /**
     * __construct
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct("/");

        header('Content-Type: application/json; charset=UTF-8');

        $this->headers = array_change_key_case(getallheaders(), CASE_LOWER);

        $request = $this->requestLimit("OcomonApi", 60, 1);
        if (!$request) {
            exit;
        }

        $auth = $this->auth();
        if (!$auth) {
            exit;
        }
    }

        /**
     * @param int $code
     * @param string|null $type
     * @param string|null $message
     * @param string $rule
     * @return OcomonApi
     */
    protected function call(int $code, string $type = null, string $message = null, string $rule = "errors"): OcomonApi
    {
        http_response_code($code);

        if (!empty($type)) {
            $this->response = [
                $rule => [
                    "type" => $type,
                    "message" => (!empty($message) ? $message : null)
                ]
            ];
        }
        return $this;
    }

    /**
     * @param array|null $response
     * @return OcomonApi
     */
    protected function back(array $response = null): OcomonApi
    {
        if (!empty($response)) {
            $this->response = (!empty($this->response) ? array_merge($this->response, $response) : $response);
        }

        echo json_encode($this->response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return $this;
    }

    private function auth(): bool
    {
        $endpoint = ["OcomonApiAuth", 60, 60];
        $request = $this->requestLimit($endpoint[0], $endpoint[1], $endpoint[2], true);

        if (!$request) {
            return false;
        }

        if (empty($this->headers["login"]) || empty($this->headers["app"]) || empty($this->headers["token"])) {
            $this->call(
                400, 
                "auth_empty", 
                "Favor informe as credenciais para autorização"
            )->back();
            
            return false;
        }

        $auth = new Auth();
        
        $user = $auth->attempt($this->headers["login"], $this->headers["app"], $this->headers["token"]);

        if (!$user) {
            $this->requestLimit($endpoint[0], $endpoint[1], $endpoint[2]);
            $this->call(
                401,
                "invalid_auth",
                "Problemas na autenticacao com as credenciais fornecidas"
            )->back();
            return false;
        }

        $this->user = $user;
        return true;
    }

    /**
     * @param string $endpoint
     * @param int $limit
     * @param int $seconds
     * @param bool $attempt
     * @return bool
     */
    protected function requestLimit(string $endpoint, int $limit, int $seconds, bool $attempt = false): bool
    {
        $userToken = (!empty($this->headers["login"]) ? base64_encode($this->headers["login"]) : null);

        if (!$userToken) {
            $this->call(
                400,
                "invalid_data",
                "Você precisa informar seu nome de usuário para continuar"
            )->back();
            

            return false;
        }

        $cacheDir = __DIR__ . "/../../" . CONF_UPLOAD_DIR . "/requests";

        if (!file_exists($cacheDir) || !is_dir($cacheDir)) {
            mkdir($cacheDir, 0755);
        }

        $cacheFile = "{$cacheDir}/{$userToken}.json";
        if (!file_exists($cacheFile) || !is_file($cacheFile)) {
            fopen($cacheFile, "w");
        }

        $userCache = json_decode(file_get_contents($cacheFile));
        $cache = (array)$userCache;

        $save = function ($cacheFile, $cache) {
            $saveCache = fopen($cacheFile, "w");
            fwrite($saveCache, json_encode($cache, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            fclose($saveCache);
        };

        if (empty($cache[$endpoint]) || $cache[$endpoint]->time <= time()) {
            if (!$attempt) {
                $cache[$endpoint] = [
                    "limit" => $limit,
                    "requests" => 1,
                    "time" => time() + $seconds
                ];

                $save($cacheFile, $cache);
            }
            return true;
        }

        if ($cache[$endpoint]->requests >= $limit) {
            $this->call(
                400,
                "request_limit",
                "Você exedeu o limite de requisições para essa ação"
            )->back();
            return false;
        }

        if (!$attempt) {
            $cache[$endpoint] = [
                "limit" => $limit,
                "requests" => $cache[$endpoint]->requests + 1,
                "time" => $cache[$endpoint]->time
            ];
            $save($cacheFile, $cache);
        }
        return true;
    }
}