<?php

namespace Controllers;

use MVC\Router;

class Controllers_paginas
{
    public static function inicio(Router $router): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

        // Si ya está logueado, ir directo al menú
        if ($_SESSION['login'] ?? false) {
            echo "<script>location.href='menu';</script>";
            return;
        }

        $router->render('home/home', [], 'layout_home');
    }
}
