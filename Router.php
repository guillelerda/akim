<?php

namespace MVC;

class Router
{
    public array $getRoutes  = [];
    public array $postRoutes = [];

    public function get(string $url, callable $fn): void
    {
        $this->getRoutes[$url] = $fn;
    }

    public function post(string $url, callable $fn): void
    {
        $this->postRoutes[$url] = $fn;
    }

    public function comprobarRutas(): void
    {
        $auth             = $_SESSION['login'] ?? null;
        $rutas_protegidas = ['/admin', '/menu', '/ventas', '/compras', '/stock', '/tienda'];

        $currentUri = $_SERVER['REQUEST_URI'] ?? '/';
        $partes     = explode('/', $currentUri);

        $currentUrl = (A_HTTP === 'W')
            ? '/' . strtok($partes[1] ?? '', '?')
            : '/' . strtok($partes[2] ?? '', '?');

        $method = $_SERVER['REQUEST_METHOD'];

        $fn = ($method === 'GET')
            ? ($this->getRoutes[$currentUrl]  ?? null)
            : ($this->postRoutes[$currentUrl] ?? null);

        // Proteger rutas
        if ($currentUrl === '/login') {
            $auth = true;
        }
        $segmento_raiz = '/' . (explode('/', ltrim($currentUrl, '/'))[0] ?? '');
        if (in_array($segmento_raiz, $rutas_protegidas) && !$auth) {
            echo "<script>location.href='login';</script>";
            return;
        }

        if ($fn) {
            call_user_func($fn, $this);
        } else {
            echo "<h2 style='font-family:sans-serif;padding:2rem'>Página no encontrada — ruta no válida</h2>";
        }
    }

    /**
     * Renderiza una vista dentro de un layout.
     *
     * @param string $view   Nombre de la vista (sin extensión), relativo a /views/
     * @param array  $datos  Variables que estarán disponibles en la vista
     * @param string $layout Nombre del layout (sin extensión), relativo a /views/layout/
     */
    public function render(string $view, array $datos = [], string $layout = 'layout'): void
    {
        foreach ($datos as $key => $value) {
            $$key = $value;
        }

        ob_start();
        include_once __DIR__ . "/views/{$view}.php";
        $contenido = ob_get_clean();

        $layoutFile = __DIR__ . "/views/layout/{$layout}.php";
        if (file_exists($layoutFile)) {
            include_once $layoutFile;
        } else {
            include_once __DIR__ . '/views/layout/layout.php';
        }
    }
}
