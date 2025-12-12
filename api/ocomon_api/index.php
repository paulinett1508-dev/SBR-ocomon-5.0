<?php
ob_start();

require __DIR__ . "/" . "app/Boot/Config.php";
require __DIR__ . "/" . "app/Boot/Helpers.php";
require __DIR__ . "/vendor/autoload.php";

/**
 * BOOTSTRAP
 */

use CoffeeCode\Router\Router;

/**
 * API ROUTES
 * index
 */
$route = new Router(url(), ":");
$route->namespace("OcomonApi\Controllers");


/** Tickets */
$route->group("/tickets");
// $route->get("/", "Tickets:index");
$route->get("/{id}", "Tickets:read");
/* Para testar as variáveis de ambiente - remover essa rota */
// $route->get("/{id}/envVars", "Tickets:envVars");
// $route->put("/{id}", "Tickets:update");

$route->post("/", "Tickets:create");
// $route->post("/", function(){
//     echo json_encode(['message' => 'Teste no arquivo de rotas']);
// });


// $route->group("/files");
// $route->post("/{ticket}", "Files:create");

/** Configs TESTES */
// $route->group("/msgconfigs");
// $route->get("/{event}", "MsgConfigs:read");

/** Solution TESTES */
// $route->group("/solutions");
// $route->get("/{numero}", "Solutions:read");

/* Access Tokens - TESTES */
// $route->group("/tokens");
// $route->get("/{user}/{app}", "AccessTokens:read");


/**
 * ROUTE
 */
$route->dispatch();

/**
 * ERROR REDIRECT
 */
if ($route->error()) {
    header('Content-Type: application/json; charset=UTF-8');
    http_response_code(404);

    echo json_encode([
        "errors" => [
            "type " => "endpoint_not_found",
            "message" => "Não foi possível processar a requisição"
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

ob_end_flush();