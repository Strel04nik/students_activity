<?php
use Rtr\Router;
use modules\Auth;
use modules\Reg;

// GET маршруты
Router::getMethods("/", "home");
Router::getMethods("/auth", "authpage");
Router::getMethods("/reg", "regpage");

// POST маршруты
Router::postMethods("/login", Auth::class, "login", $_POST);
Router::postMethods("/logout", Auth::class, "logout", $_POST);
Router::postMethods("/register", Reg::class, "register", $_POST);

Router::action();   