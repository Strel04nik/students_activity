<?php

namespace Rtr;

class Router
{
    public static $getRoutes = [];
    public static $postRoutes = [];

    public static function getMethods($URI, $namepage)
    {
        self::$getRoutes[] = [
            "uri" => $URI,
            "namepage" => $namepage
        ];
    }

    public static function postMethods($URI, $class, $method, $data)
    {
        self::$postRoutes[] = [
            "uri" => $URI,
            "class" => $class,
            "class_method" => $method,
            "data" => $data
        ];
    }

    public static function action()
    {
        $routing = $_GET['routing'] ?? "";
        $requestUri = "/" . $routing;
        $method = $_SERVER['REQUEST_METHOD'];

        if ($method === "POST") {
            foreach (self::$postRoutes as $route) {
                if ($route['uri'] === $requestUri) {
                    $class = $route['class'];
                    $methodName = $route['class_method'];
                    $data = $route['data'];
                    $class::$methodName($data);
                    return;
                }
            }
        }

        foreach (self::$getRoutes as $route) {
            if ($route['uri'] === $requestUri) {
                $viewFile = "src/views/pages/" . $route['namepage'] . ".php";
                include $viewFile;
                return;
            }
        }

        // Если ничего не найдено
        header("HTTP/1.0 404 Not Found");
        die("404 - Страница не найдена");
    }
}