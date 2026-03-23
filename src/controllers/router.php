<?php

namespace Rtr;

class Router
{
    public static $list = [];

    public static function getMethods($URI, $namepage)
    {
        self::$list[] = [
            "uri" => $URI,
            "namepage" => $namepage
        ];
    }

    public static function postMethods($URI, $class, $method, $data)
    {
        self::$list[] = [
            "uri" => $URI,
            "class" => $class,
            "class_method" => $method,
            "data" => $data
        ];
    }

    public static function action()
    {
        $routing = $_GET['routing'] ?? "";

        foreach (self::$list as $varRout) {
            if ($varRout['uri'] === "/" . $routing) {
                if ($_SERVER['REQUEST_METHOD'] === "GET") {
                    $viewFile = "src/views/pages/" . $varRout['namepage'] . ".php";
                    include $viewFile;
                } else if ($_SERVER['REQUEST_METHOD'] === "POST") {
                    $class = $varRout['class'];
                    $method = $varRout['class_method'];
                    $data = $varRout['data'];
                    $class::$method($data);
                } else
                    die("404 - Страница не найдена");
            }
        }
    }
}
