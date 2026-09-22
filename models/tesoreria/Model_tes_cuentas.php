<?php

namespace Model\Tesoreria;

use Model\ActiveDBC;

class Model_tes_cuentas extends ActiveDBC
{
    protected static $tabla = 'tes_cuentas';

    public function queryAll(): array
    {
        $stmt = self::$conexionBDC->query(
            "SELECT * FROM tes_cuentas ORDER BY tipo, nombre"
        );
        return $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
    }

    public static function queryHabilitadas(): array
    {
        $stmt = self::$conexionBDC->query(
            "SELECT * FROM tes_cuentas WHERE habilitada = 1 ORDER BY tipo, favorito DESC, nombre"
        );
        return $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
    }

    public static function getSaldos(): array
    {
        $stmt = self::$conexionBDC->query(
            "SELECT c.*,
                    COALESCE(
                        SUM(CASE WHEN m.tipo='ingreso' AND m.estado='confirmado' THEN m.monto ELSE 0 END) -
                        SUM(CASE WHEN m.tipo='egreso'  AND m.estado='confirmado' THEN m.monto ELSE 0 END)
                    , 0) AS saldo_actual,
                    COALESCE(
                        SUM(CASE WHEN m.tipo='ingreso' AND m.estado IN ('confirmado','pendiente') THEN m.monto ELSE 0 END) -
                        SUM(CASE WHEN m.tipo='egreso'  AND m.estado IN ('confirmado','pendiente') THEN m.monto ELSE 0 END)
                    , 0) AS saldo_disponible
             FROM tes_cuentas c
             LEFT JOIN tes_movimientos m ON m.cuenta_id = c.id AND m.estado != 'anulado'
             GROUP BY c.id
             ORDER BY c.tipo, c.favorito DESC, c.nombre"
        );
        return $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
    }

    public static function setSingleFavorito(int $id): void
    {
        $stmt = self::$conexionBDC->prepare("SELECT tipo FROM tes_cuentas WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $tipo = $stmt->fetchColumn();
        if (!$tipo) return;

        self::$conexionBDC->prepare(
            "UPDATE tes_cuentas SET favorito = 0 WHERE tipo = ?"
        )->execute([$tipo]);
        self::$conexionBDC->prepare(
            "UPDATE tes_cuentas SET favorito = 1 WHERE id = ?"
        )->execute([$id]);
    }

    public function insertar(array $a): void
    {
        self::$conexionBDC->prepare(
            "INSERT INTO tes_cuentas (idx, tipo, nombre, descripcion, banco, numero_cuenta, cbu, alias_cbu, moneda)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        )->execute([
            uniqid('tesc_'),
            $a['tipo']             ?? 'efectivo',
            trim($a['nombre']      ?? ''),
            trim($a['descripcion'] ?? ''),
            trim($a['banco']       ?? ''),
            trim($a['numero_cuenta'] ?? ''),
            trim($a['cbu']         ?? ''),
            trim($a['alias_cbu']   ?? ''),
            $a['moneda']           ?? 'ARS',
        ]);

        $saldo_ini = (float)($a['saldo_inicial'] ?? 0);
        if ($saldo_ini != 0) {
            $cuenta_id = (int)self::$conexionBDC->lastInsertId();
            self::$conexionBDC->prepare(
                "INSERT INTO tes_movimientos
                 (idx, cuenta_id, tipo, estado, concepto, monto, fecha, fecha_valor, medio)
                 VALUES (?, ?, ?, 'confirmado', 'Saldo inicial', ?, CURDATE(), CURDATE(), 'saldo_inicial')"
            )->execute([
                uniqid('tesm_'),
                $cuenta_id,
                $saldo_ini > 0 ? 'ingreso' : 'egreso',
                abs($saldo_ini),
            ]);
        }
    }

    public static function getSaldoActualTotal(): float
    {
        $r = self::$conexionBDC->query(
            "SELECT COALESCE(SUM(
                CASE WHEN tipo='ingreso' THEN monto ELSE -monto END
             ), 0) AS total
             FROM tes_movimientos
             WHERE estado = 'confirmado'"
        );
        return $r ? (float)$r->fetchColumn() : 0.0;
    }

    public function toggle(int $id): void
    {
        self::$conexionBDC->prepare(
            "UPDATE tes_cuentas SET habilitada = 1 - habilitada WHERE id = ?"
        )->execute([$id]);
    }

    public function eliminar(int $id): void
    {
        $c = self::$conexionBDC->prepare("SELECT COUNT(*) FROM tes_movimientos WHERE cuenta_id = ?");
        $c->execute([$id]);
        if ((int)$c->fetchColumn() > 0) return;
        self::$conexionBDC->prepare("DELETE FROM tes_cuentas WHERE id = ?")->execute([$id]);
    }
}
