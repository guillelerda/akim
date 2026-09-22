<?php

namespace Controllers\Tesoreria;

use MVC\Router;
use Model\Model_apphelper;
use Model\Tesoreria\Model_tes_cajas;
use Model\ActiveDBC;

class Controller_tes_caja
{
    public static function apertura(Router $router): void
    {
        [$userName] = Model_apphelper::getUsuarioAutenticado();

        $saldo = (float)str_replace(',', '.', $_POST['saldo_apertura'] ?? '0');
        $saldo = max(0, $saldo);

        try {
            Model_tes_cajas::abrir($saldo, $userName);
            $_SESSION['flash_ok'] = 'Caja abierta con saldo inicial de $' . number_format($saldo, 2, ',', '.');
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Error al abrir caja: ' . $e->getMessage();
        }

        header('Location: tesoreria');
        exit;
    }

    public static function retiro(Router $router): void
    {
        [$userName] = Model_apphelper::getUsuarioAutenticado();

        $monto  = (float)str_replace(',', '.', $_POST['retiro_monto']  ?? '0');
        $motivo = trim($_POST['retiro_motivo'] ?? '');

        if ($monto <= 0 || $motivo === '') {
            $_SESSION['flash_error'] = 'Completá el monto y el motivo del retiro.';
            header('Location: tesoreria');
            exit;
        }

        try {
            $caja = Model_tes_cajas::getHoy();
            if (!$caja || ($caja['estado'] ?? '') !== 'abierta') {
                $_SESSION['flash_error'] = 'No hay caja abierta para registrar el retiro.';
                header('Location: tesoreria');
                exit;
            }

            $saldo_teo = Model_tes_cajas::getSaldoTeorico($caja);
            if ($monto > $saldo_teo) {
                $_SESSION['flash_error'] = 'El retiro ($' . number_format($monto, 2, ',', '.') . ') supera el saldo disponible en caja ($' . number_format($saldo_teo, 2, ',', '.') . ').';
                header('Location: tesoreria');
                exit;
            }

            $db = \Model\ActiveDBC::getDB();
            $st = $db->prepare(
                "INSERT INTO tes_caja_retiros (caja_idx, monto, motivo, usuario)
                 VALUES (:caja_idx, :monto, :motivo, :usuario)"
            );
            $st->execute([
                ':caja_idx' => $caja['idx'],
                ':monto'    => $monto,
                ':motivo'   => $motivo,
                ':usuario'  => $userName,
            ]);
            $_SESSION['flash_ok'] = 'Retiro de $' . number_format($monto, 2, ',', '.') . ' registrado.';
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Error al registrar el retiro.';
        }

        header('Location: tesoreria');
        exit;
    }

    public static function historial(Router $router): void
    {
        [$userName, , $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();
        $init    = Model_apphelper::inicialize();
        [$em_nombre] = Model_apphelper::getLicenciaAutenticada($init['licencia']);

        try {
            $filas = Model_tes_cajas::getHistorial(90, 0);
        } catch (\Throwable $e) {
            $filas = [];
            $_SESSION['flash_error'] = $e->getMessage();
        }

        $router->render('tesoreria/caja_historial', [
            'protegida'  => true,
            'auth'       => true,
            'page_title' => 'Historial de Caja',
            'username'   => $userName,
            'nombre'     => $nombre,
            'perfil'     => $perfil,
            'em_nombre'  => $em_nombre ?? 'AKIM',
            'filas'      => $filas,
        ]);
    }

    public static function cierre(Router $router): void
    {
        [$userName, , $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();
        $init     = Model_apphelper::inicialize();
        [$em_nombre] = Model_apphelper::getLicenciaAutenticada($init['licencia']);

        $caja = Model_tes_cajas::getHoy();

        // Sin caja abierta → volver al dashboard
        if (!$caja || ($caja['estado'] ?? '') !== 'abierta') {
            $_SESSION['flash_error'] = 'No hay caja abierta para cerrar.';
            header('Location: tesoreria');
            exit;
        }

        // ── POST: confirmar cierre ────────────────────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $declarado    = (float)str_replace(',', '.', $_POST['declarado']    ?? '0');
            $saldo_cierre = (float)str_replace(',', '.', $_POST['saldo_cierre'] ?? '0');
            $obs          = trim($_POST['obs_cierre'] ?? '');

            $saldo_teo  = Model_tes_cajas::getSaldoTeorico($caja);
            $diferencia = round($declarado - $saldo_teo, 2);

            if (abs($diferencia) > 0.01 && $obs === '') {
                $_SESSION['flash_error'] = 'Hay una diferencia de $' . number_format(abs($diferencia), 2, ',', '.') . '. Describí el motivo antes de cerrar.';
                header('Location: caja_cierre');
                exit;
            }

            Model_tes_cajas::cerrar(
                (int)$caja['idx'],
                $declarado,
                $saldo_cierre,
                $obs,
                $userName
            );

            $_SESSION['flash_ok'] = 'Caja cerrada correctamente. Saldo para mañana: $' . number_format($saldo_cierre, 2, ',', '.');
            header('Location: tesoreria');
            exit;
        }

        // ── GET: mostrar pantalla de cierre ───────────────────────────────────
        $saldo_teo = Model_tes_cajas::getSaldoTeorico($caja);
        $retiros   = Model_tes_cajas::getRetirosHoy((int)$caja['idx']);
        $resumen   = Model_tes_cajas::getResumenDia();

        $router->render('tesoreria/caja_cierre', [
            'protegida'   => true,
            'auth'        => true,
            'page_title'  => 'Cierre de Caja',
            'username'    => $userName,
            'nombre'      => $nombre,
            'perfil'      => $perfil,
            'em_nombre'   => $em_nombre ?? 'AKIM',
            'caja'        => $caja,
            'saldo_teo'   => $saldo_teo,
            'retiros'     => $retiros,
            'resumen'     => $resumen,
        ]);
    }
}
