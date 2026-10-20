<?php

namespace Controllers\Tesoreria;

use MVC\Router;
use Model\Model_apphelper;
use Model\Tesoreria\Model_tes_cuentas;
use Model\Tesoreria\Model_tes_cheques;
use Model\Tesoreria\Model_tes_movimientos;
use Model\Tesoreria\Model_tes_cajas;

class Controllers_tes_menu
{
    public static function menu(Router $router): void
    {
        [$userName, , $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();
        Model_apphelper::requerirAccesoModulo('TES');
        $_SESSION['moduloActivo'] = 'TES';

        $flash_ok    = $_SESSION['flash_ok']    ?? null; unset($_SESSION['flash_ok']);
        $flash_error = $_SESSION['flash_error'] ?? null; unset($_SESSION['flash_error']);

        $cuentas      = Model_tes_cuentas::getSaldos();
        $movRecientes = Model_tes_movimientos::queryRecientes(20);
        $chqCartera   = Model_tes_cheques::montoEnCartera();
        $chqCount     = Model_tes_cheques::countEnCartera();

        $caja_hoy       = Model_tes_cajas::getHoy();
        $saldo_sugerido = Model_tes_cajas::getSaldoSugerido();
        $saldo_teo      = null;
        if ($caja_hoy && ($caja_hoy['estado'] ?? '') === 'abierta') {
            $saldo_teo = Model_tes_cajas::getSaldoTeorico($caja_hoy);
        }

        $total_efectivo   = 0.0;
        $total_banco_act  = 0.0;
        $total_banco_disp = 0.0;
        foreach ($cuentas as $c) {
            if ($c['tipo'] === 'efectivo') {
                $total_efectivo += (float)$c['saldo_actual'];
            } else {
                $total_banco_act  += (float)$c['saldo_actual'];
                $total_banco_disp += (float)$c['saldo_disponible'];
            }
        }

        $router->render('tesoreria/tes_menu', [
            'protegida'        => true,
            'auth'             => true,
            'username'         => $userName,
            'nombre'           => $nombre,
            'perfil'           => $perfil,
            'cuentas'          => $cuentas,
            'movRecientes'     => $movRecientes,
            'chqCartera'       => $chqCartera,
            'chqCount'         => $chqCount,
            'total_efectivo'   => $total_efectivo,
            'total_banco_act'  => $total_banco_act,
            'total_banco_disp' => $total_banco_disp,
            'caja_hoy'         => $caja_hoy,
            'saldo_sugerido'   => $saldo_sugerido,
            'saldo_teo'        => $saldo_teo,
            'flash_ok'         => $flash_ok,
            'flash_error'      => $flash_error,
        ]);
    }
}
