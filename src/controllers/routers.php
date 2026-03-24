<?php
use Rtr\Router;
use modules\Auth;
use modules\Reg;
use modules\Event;
use modules\User;
use db\database;

// GET маршруты
Router::getMethods("/", "home");
Router::getMethods("/auth", "authpage");
Router::getMethods("/reg", "regpage");
Router::getMethods("/event", "event"); 
Router::getMethods("/eventsetting", "eventsetting"); 
Router::getMethods("/events", "events"); 
Router::getMethods("/profile", "profile"); 
Router::getMethods("/hr-dashboard", "hr_dashboard");
Router::getMethods("/activity", "activity");
Router::getMethods("/rating", "rating");
Router::getMethods("/admin", "admin");


// POST маршруты
Router::postMethods("/login", Auth::class, "login", $_POST);
Router::postMethods("/logout", Auth::class, "logout", $_POST);
Router::postMethods("/register", Reg::class, "register", $_POST);
Router::postMethods("/event/join", Event::class, "join", $_POST);
Router::postMethods("/create-bonus", Event::class, "createBonus", $_POST);
Router::postMethods("/add-review", User::class, "addReview", $_POST);

database::connect();
Router::action();