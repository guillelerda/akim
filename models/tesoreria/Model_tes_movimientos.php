<?php

namespace Model\Tesoreria;

use Model\ActiveDBC;

class Model_tes_movimientos extends ActiveDBC
{
    protected static $tabla = 'tes_movimientos';

    public static function registrar(array $a): void
    {
        $cuenta_id = (int)($a['cuenta_id'] ?? 0);
        $monto     = abs((float)($a['monto'] ?? 0));
        if ($cuenta_id <= 0 || $monto <= 0) return;

        $fecha = $a['fecha']       ?? date('Y-m-d');
        self::$conexionBDC->prepare(
            "INSERT INTO tes_movimientos
             (idx, cuenta_id, tipo, estado, concepto, monto, fecha, fecha_valor,
              medio, referencia_tipo, referencia_idx, cheque_id, usuario_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        )->execute([
            uniqid('tesm_'),
            $cuenta_id,
            $a['tipo']            ?? 'ingreso',
            $a['estado']          ?? 'confirmado',
            trim($a['concepto']   ?? ''),
            $monto,
            $fecha,
            $a['fecha_valor']     ?? $fecha,
            $a['medio']           ?? '',
            $a['referencia_tipo'] ?? '',
            $a['referencia_idx']  ?? '',
            !empty($a['cheque_id']) ? (int)$a['cheque_id'] : null,
            (int)($a['usuario_id'] ?? 0),
        ]);
    }

    public static function queryRecientes(int $limit = 50): array
    {
        $stmt = self::$conexionBDC->prepare(
            "SELECT m.*, c.nombre AS cuenta_nombre, c.tipo AS cuenta_tipo
             FROM tes_movimientos m
             JOIN tes_cuentas c ON c.id = m.cuenta_id
             WHERE m.estado != 'anulado'
             ORDER BY m.fecha DESC, m.id DESC
             LIMIT :lim"
        );
        $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public static function anular(int $id): void
    {
        self::$conexionBDC->prepare(
            "UPDATE tes_movimientos SET estado = 'anulado' WHERE id = ?"
        )->execute([$id]);
    }

    public static function confirmarPorCheque(int $chequeId): void
    {
        self::$conexionBDC->prepare(
            "UPDATE tes_movimientos SET estado = 'confirmado', fecha_valor = CURDATE()
             WHERE cheque_id = ? AND estado = 'pendiente'"
        )->execute([$chequeId]);
    }

    public static function anularPorCheque(int $chequeId): void
    {
        self::$conexionBDC->prepare(
            "UPDATE tes_movimientos SET estado = 'anulado'
             WHERE cheque_id = ? AND estado = 'pendiente'"
        )->execute([$chequeId]);
    }

    public static function queryPendientesPorFecha(int $dias = 90): array
    {
        $stmt = self::$conexionBDC->prepare(
            "SELECT m.fecha_valor,
                    m.tipo,
                    SUM(m.monto)                          AS total,
                    COUNT(*)                              AS cant,
                    GROUP_CONCAT(m.concepto   ORDER BY m.id SEPARATOR '||') AS conceptos,
                    GROUP_CONCAT(c.nombre     ORDER BY m.id SEPARATOR '||') AS cuentas,
                    GROUP_CONCAT(m.medio      ORDER BY m.id SEPARATOR '||') AS medios
             FROM tes_movimientos m
             LEFT JOIN tes_cuentas c ON c.id = m.cuenta_id
             WHERE m.estado = 'pendiente'
               AND m.fecha_valor >= CURDATE()
               AND m.fecha_valor <= DATE_ADD(CURDATE(), INTERVAL :dias DAY)
             GROUP BY m.fecha_valor, m.tipo
             ORDER BY m.fecha_valor ASC, m.tipo DESC"
        );
        $stmt->bindValue(':dias', $dias, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public static function queryLedger(int $limit = 100): array
    {
        $stmt = self::$conexionBDC->prepare(
            "SELECT m.*, c.nombre AS cuenta_nombre, c.tipo AS cuenta_tipo
             FROM tes_movimientos m
             JOIN tes_cuentas c ON c.id = m.cuenta_id
             WHERE m.estado != 'anulado'
             ORDER BY m.fecha DESC, m.id DESC
             LIMIT :lim"
        );
        $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }
}
