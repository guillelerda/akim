<?php

namespace Controllers\Tesoreria;

use MVC\Router;
use Model\Model_apphelper;
use Model\Tesoreria\Model_tes_cheques;
use Model\Tesoreria\Model_tes_movimientos;

class Controller_tes_cheques
{
    public static function lista(Router $router): void
    {
        [$userName, , $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = $_POST['accion'] ?? '';
            $id     = (int)($_POST['id'] ?? 0);

            if ($id > 0) {
                match($accion) {
                    // ── Cheques recibidos ──────────────────────────────────
                    'depositar' => self::_depositar($id),
                    'rechazar'  => self::_rechazar($id),
                    // ── Cheques emitidos ───────────────────────────────────
                    'cobrado'   => self::_cobrado($id),
                    'devuelto'  => self::_devuelto($id),
                    default     => null,
                };
            }
            echo "<script>location.href='tes_cheques';</script>";
            return;
        }

        $recibidos = [];
        $emitidos  = [];
        try {
            $recibidos = Model_tes_cheques::queryRecibidosActivos();
            $emitidos  = Model_tes_cheques::queryEmitidosActivos();
        } catch (\Throwable $e) { /* tablas vacías */ }

        $flash_ok    = $_SESSION['flash_ok']    ?? null; unset($_SESSION['flash_ok']);
        $flash_error = $_SESSION['flash_error'] ?? null; unset($_SESSION['flash_error']);

        $router->render('tesoreria/tes_cheques', [
            'protegida'   => true,
            'auth'        => true,
            'page_title'  => 'Gestión de Cheques',
            'username'    => $userName,
            'nombre'      => $nombre,
            'perfil'      => $perfil,
            'recibidos'   => $recibidos,
            'emitidos'    => $emitidos,
            'flash_ok'    => $flash_ok,
            'flash_error' => $flash_error,
        ]);
    }

    // ── Cheque recibido: depositamos en el banco ──────────────────────────────
    private static function _depositar(int $id): void
    {
        Model_tes_cheques::cambiarEstado($id, 'depositado');
        Model_tes_movimientos::confirmarPorCheque($id);
        $_SESSION['flash_ok'] = 'Cheque marcado como depositado. El movimiento fue confirmado.';
    }

    // ── Cheque recibido: nos lo devuelven sin fondos ──────────────────────────
    private static function _rechazar(int $id): void
    {
        Model_tes_cheques::cambiarEstado($id, 'rechazado');
        Model_tes_movimientos::anularPorCheque($id);
        $_SESSION['flash_ok'] = 'Cheque rechazado. El ingreso pendiente fue anulado.';
    }

    // ── Cheque emitido: el proveedor lo cobró ────────────────────────────────
    private static function _cobrado(int $id): void
    {
        Model_tes_cheques::cambiarEstado($id, 'cobrado');
        Model_tes_movimientos::confirmarPorCheque($id);
        $_SESSION['flash_ok'] = 'Cheque marcado como acreditado. El egreso fue confirmado.';
    }

    // ── Cheque emitido: nos lo devuelven ─────────────────────────────────────
    private static function _devuelto(int $id): void
    {
        Model_tes_cheques::cambiarEstado($id, 'anulado');
        Model_tes_movimientos::anularPorCheque($id);
        $_SESSION['flash_ok'] = 'Cheque devuelto. El egreso pendiente fue anulado.';
    }
}
