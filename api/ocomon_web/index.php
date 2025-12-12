<?php
ob_start();

require __DIR__ . "/" . "../ocomon_api/app/Boot/Config.php";
require __DIR__ . "/" . "../ocomon_api/app/Boot/Helpers.php";
require __DIR__ . "/" . "../ocomon_api/vendor/autoload.php";

/**
 * BOOTSTRAP
 */

use CoffeeCode\Router\Router;

/**
 * API ROUTES
 * index
 */
$route = new Router(url(), ":");
$route->namespace("OcomonApi\WebControllers");


/** Tickets */
$route->group("/formfields");
// $route->get("/", "Tickets:index");
$route->get("/", "FormField:list");



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