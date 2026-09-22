<?php

namespace Model\Tesoreria;

use Model\ActiveDBC;

class Model_tes_cajas
{
    private static function ensureTable(): void
    {
        $db = ActiveDBC::getDB();
        if (!$db) throw new \RuntimeException('Sin conexión a BD cliente. Seleccioná una empresa.');
        $db->exec("CREATE TABLE IF NOT EXISTS tes_cajas (
            idx              INT AUTO_INCREMENT PRIMARY KEY,
            fecha            DATE          NOT NULL,
            estado           VARCHAR(10)   NOT NULL DEFAULT 'abierta',
            saldo_apertura   DECIMAL(12,2) NOT NULL DEFAULT 0,
            saldo_declarado  DECIMAL(12,2) NULL,
            saldo_cierre     DECIMAL(12,2) NULL,
            obs_cierre       TEXT          NULL,
            usuario_apertura VARCHAR(100)  NULL,
            usuario_cierre   VARCHAR(100)  NULL,
            hora_apertura    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
            hora_cierre      DATETIME      NULL,
            created_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_caja_fecha (fecha)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS tes_caja_retiros (
            idx        INT AUTO_INCREMENT PRIMARY KEY,
            caja_idx   INT           NOT NULL,
            monto      DECIMAL(12,2) NOT NULL,
            motivo     VARCHAR(255)  NOT NULL DEFAULT '',
            usuario    VARCHAR(100)  NULL,
            created_at TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_caja (caja_idx)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public static function getHoy(): ?array
    {
        self::ensureTable();
        $db = ActiveDBC::getDB();
        $st = $db->query("SELECT * FROM tes_cajas WHERE fecha = CURDATE() LIMIT 1");
        $row = $st ? $st->fetch() : false;
        return $row ?: null;
    }

    public static function getSaldoSugerido(): float
    {
        self::ensureTable();
        $db = ActiveDBC::getDB();
        $st = $db->query(
            "SELECT saldo_cierre FROM tes_cajas
             WHERE estado = 'cerrada' AND saldo_cierre IS NOT NULL
             ORDER BY fecha DESC LIMIT 1"
        );
        if (!$st) return 0.0;
        $row = $st->fetch();
        return $row ? (float)$row['saldo_cierre'] : 0.0;
    }

    public static function abrir(float $saldo, string $usuario): void
    {
        self::ensureTable();
        $db = ActiveDBC::getDB();
        $st = $db->prepare(
            "INSERT INTO tes_cajas (fecha, estado, saldo_apertura, usuario_apertura, hora_apertura)
             VALUES (CURDATE(), 'abierta', :saldo, :usuario, NOW())
             ON DUPLICATE KEY UPDATE
                 estado           = 'abierta',
                 saldo_apertura   = VALUES(saldo_apertura),
                 usuario_apertura = VALUES(usuario_apertura),
                 hora_apertura    = NOW()"
        );
        $st->execute([':saldo' => $saldo, ':usuario' => $usuario]);
    }

    /** Saldo teórico = apertura + cobros efectivo del día − retiros */
    public static function getSaldoTeorico(array $caja): float
    {
        $db      = ActiveDBC::getDB();
        $apertura = (float)($caja['saldo_apertura'] ?? 0);
        $cobros   = 0.0;
        $retiros  = 0.0;

        try {
            $st = $db->query(
                "SELECT COALESCE(SUM(monto),0) AS tot
                 FROM tes_movimientos
                 WHERE medio = 'efectivo' AND tipo = 'ingreso'
                   AND DATE(fecha) = CURDATE() AND referencia_tipo = 'venta'"
            );
            if ($st) $cobros = (float)($st->fetch()['tot'] ?? 0);
        } catch (\Throwable $e) {}

        try {
            $st = $db->prepare(
                "SELECT COALESCE(SUM(monto),0) AS tot
                 FROM tes_caja_retiros WHERE caja_idx = :idx"
            );
            $st->execute([':idx' => $caja['idx']]);
            $retiros = (float)($st->fetch()['tot'] ?? 0);
        } catch (\Throwable $e) {}

        return $apertura + $cobros - $retiros;
    }

    public static function getRetirosHoy(int $caja_idx): array
    {
        try {
            $db = ActiveDBC::getDB();
            $st = $db->prepare(
                "SELECT * FROM tes_caja_retiros WHERE caja_idx = :idx ORDER BY created_at DESC"
            );
            $st->execute([':idx' => $caja_idx]);
            return $st->fetchAll() ?: [];
        } catch (\Throwable $e) { return []; }
    }

    /** Resumen del día: ventas por medio de pago + totales */
    public static function getResumenDia(): array
    {
        $db = ActiveDBC::getDB();
        $res = [
            'ventas_cant'      => 0,
            'ventas_total'     => 0.0,
            'cobros_efectivo'  => 0.0,
            'cobros_otros'     => 0.0,
            'medios'           => [],
        ];

        try {
            $st = $db->query(
                "SELECT COUNT(*) AS cnt, COALESCE(SUM(total),0) AS tot
                 FROM vta_ventas WHERE DATE(created_at) = CURDATE() AND estado != 'anulada'"
            );
            if ($st) {
                $r = $st->fetch();
                $res['ventas_cant']  = (int)$r['cnt'];
                $res['ventas_total'] = (float)$r['tot'];
            }
        } catch (\Throwable $e) {}

        try {
            $st = $db->query(
                "SELECT medio,
                        COUNT(*) AS cant,
                        COALESCE(SUM(monto),0) AS tot
                 FROM tes_movimientos
                 WHERE tipo = 'ingreso'
                   AND DATE(fecha) = CURDATE()
                   AND referencia_tipo = 'venta'
                 GROUP BY medio
                 ORDER BY tot DESC"
            );
            if ($st) {
                $filas = $st->fetchAll();
                foreach ($filas as $f) {
                    $res['medios'][] = $f;
                    if ($f['medio'] === 'efectivo') {
                        $res['cobros_efectivo'] += (float)$f['tot'];
                    } else {
                        $res['cobros_otros'] += (float)$f['tot'];
                    }
                }
            }
        } catch (\Throwable $e) {}

        return $res;
    }

    /** Historial de cajas con resumen de ventas y retiros por día */
    public static function getHistorial(int $limit = 60, int $offset = 0): array
    {
        self::ensureTable();
        $db = ActiveDBC::getDB();
        try {
            $st = $db->prepare(
                "SELECT c.*,
                        COALESCE(v.ventas_total, 0)  AS ventas_total,
                        COALESCE(v.ventas_cant,  0)  AS ventas_cant,
                        COALESCE(r.retiros_total, 0) AS retiros_total,
                        COALESCE(r.retiros_cant,  0) AS retiros_cant
                 FROM tes_cajas c
                 LEFT JOIN (
                     SELECT DATE(created_at) AS fd,
                            SUM(total) AS ventas_total,
                            COUNT(*)   AS ventas_cant
                     FROM vta_ventas
                     WHERE estado != 'anulada'
                     GROUP BY DATE(created_at)
                 ) v ON v.fd = c.fecha
                 LEFT JOIN (
                     SELECT caja_idx,
                            SUM(monto) AS retiros_total,
                            COUNT(*)   AS retiros_cant
                     FROM tes_caja_retiros
                     GROUP BY caja_idx
                 ) r ON r.caja_idx = c.idx
                 ORDER BY c.fecha DESC
                 LIMIT :lim OFFSET :off"
            );
            $st->execute([':lim' => $limit, ':off' => $offset]);
            return $st->fetchAll() ?: [];
        } catch (\Throwable $e) { return []; }
    }

    public static function getByIdx(int $idx): ?array
    {
        self::ensureTable();
        $db = ActiveDBC::getDB();
        $st = $db->prepare("SELECT * FROM tes_cajas WHERE idx = :idx LIMIT 1");
        $st->execute([':idx' => $idx]);
        $row = $st->fetch();
        return $row ?: null;
    }

    /** Cierra la caja del día */
    public static function cerrar(
        int    $idx,
        float  $declarado,
        float  $saldo_cierre,
        string $obs,
        string $usuario
    ): void {
        $db = ActiveDBC::getDB();
        $st = $db->prepare(
            "UPDATE tes_cajas SET
                 estado          = 'cerrada',
                 saldo_declarado = :declarado,
                 saldo_cierre    = :cierre,
                 obs_cierre      = :obs,
                 usuario_cierre  = :usuario,
                 hora_cierre     = NOW()
             WHERE idx = :idx"
        );
        $st->execute([
            ':declarado' => $declarado,
            ':cierre'    => $saldo_cierre,
            ':obs'       => $obs,
            ':usuario'   => $usuario,
            ':idx'       => $idx,
        ]);
    }
}
