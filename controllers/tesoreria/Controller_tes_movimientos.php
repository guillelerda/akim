<?php

namespace Controllers\Tesoreria;

use MVC\Router;
use Model\Model_apphelper;
use Model\Tesoreria\Model_tes_movimientos;
use Model\Tesoreria\Model_tes_cuentas;

class Controller_tes_movimientos
{
    public static function lista(Router $router): void
    {
        [$userName, , $nombre, $perfil] = Model_apphelper::getUsuarioAutenticado();

        $dias      = (int)($_GET['dias'] ?? 90);
        $dias      = in_array($dias, [30, 60, 90]) ? $dias : 90;

        $movimientos = [];
        $flujo       = [];
        $saldo_hoy   = 0.0;

        try {
            $movimientos = Model_tes_movimientos::queryLedger(200);
            $saldo_hoy   = Model_tes_cuentas::getSaldoActualTotal();
            $flujo       = self::_calcularFlujo($dias, $saldo_hoy);
        } catch (\Throwable $e) { /* tablas vacías */ }

        $router->render('tesoreria/tes_movimientos', [
            'protegida'   => true,
            'auth'        => true,
            'page_title'  => 'Movimientos y Flujo de Caja',
            'username'    => $userName,
            'nombre'      => $nombre,
            'perfil'      => $perfil,
            'movimientos' => $movimientos,
            'flujo'       => $flujo,
            'saldo_hoy'   => $saldo_hoy,
            'dias'        => $dias,
        ]);
    }

    // ── Proyección de flujo de caja ───────────────────────────────────────────

    private static function _calcularFlujo(int $dias, float $saldo_ini): array
    {
        $pendientes = Model_tes_movimientos::queryPendientesPorFecha($dias);

        // Indexar por fecha → {ingresos, egresos, detalle[]}
        $porFecha = [];
        foreach ($pendientes as $row) {
            $f = $row['fecha_valor'];
            if (!isset($porFecha[$f])) {
                $porFecha[$f] = ['ingresos' => 0.0, 'egresos' => 0.0, 'det' => []];
            }
            $porFecha[$f][$row['tipo'] === 'ingreso' ? 'ingresos' : 'egresos'] += (float)$row['total'];
            $porFecha[$f]['det'][] = $row;
        }

        ksort($porFecha);

        $saldo = $saldo_ini;
        $rows  = [];
        foreach ($porFecha as $fecha => $data) {
            $neto  = $data['ingresos'] - $data['egresos'];
            $saldo += $neto;
            $rows[] = [
                'fecha'    => $fecha,
                'ingresos' => $data['ingresos'],
                'egresos'  => $data['egresos'],
                'neto'     => $neto,
                'saldo'    => $saldo,
                'det'      => $data['det'],
            ];
        }

        return $rows;
    }
}
