<?php

namespace Model\Tesoreria;

use Model\ActiveDBC;

class Model_tes_cheques extends ActiveDBC
{
    protected static $tabla = 'tes_cheques';

    public static function queryEnCartera(): array
    {
        $stmt = self::$conexionBDC->query(
            "SELECT ch.*, c.nombre AS cuenta_nombre
             FROM tes_cheques ch
             LEFT JOIN tes_cuentas c ON c.id = ch.cuenta_id
             WHERE ch.tipo = 'recibido' AND ch.estado = 'cartera'
             ORDER BY ch.fecha_vencimiento ASC"
        );
        return $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
    }

    public static function queryEmitidosPendientes(): array
    {
        $stmt = self::$conexionBDC->query(
            "SELECT ch.*, c.nombre AS cuenta_nombre
             FROM tes_cheques ch
             LEFT JOIN tes_cuentas c ON c.id = ch.cuenta_id
             WHERE ch.tipo = 'emitido' AND ch.estado = 'cartera'
             ORDER BY ch.fecha_vencimiento ASC"
        );
        return $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
    }

    public static function countEnCartera(): int
    {
        $r = self::$conexionBDC->query(
            "SELECT COUNT(*) FROM tes_cheques WHERE tipo = 'recibido' AND estado = 'cartera'"
        );
        return $r ? (int)$r->fetchColumn() : 0;
    }

    public static function montoEnCartera(): float
    {
        $r = self::$conexionBDC->query(
            "SELECT COALESCE(SUM(monto), 0) FROM tes_cheques WHERE tipo = 'recibido' AND estado = 'cartera'"
        );
        return $r ? (float)$r->fetchColumn() : 0.0;
    }

    public static function insertar(array $a): int
    {
        self::$conexionBDC->prepare(
            "INSERT INTO tes_cheques
             (idx, tipo, estado, numero, banco, fecha_emision, fecha_vencimiento,
              monto, cuenta_id, motivo, referencia_tipo, referencia_idx)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        )->execute([
            uniqid('chq_'),
            $a['tipo'],
            $a['estado']           ?? 'cartera',
            $a['numero']           ?? '',
            $a['banco']            ?? '',
            $a['fecha_emision'],
            $a['fecha_vencimiento'],
            abs((float)($a['monto'] ?? 0)),
            !empty($a['cuenta_id']) ? (int)$a['cuenta_id'] : null,
            $a['motivo']           ?? '',
            $a['referencia_tipo']  ?? '',
            $a['referencia_idx']   ?? '',
        ]);
        return (int)self::$conexionBDC->lastInsertId();
    }

    public static function proxVencimientos(int $dias = 7): array
    {
        $stmt = self::$conexionBDC->prepare(
            "SELECT ch.*, c.nombre AS cuenta_nombre
             FROM tes_cheques ch
             LEFT JOIN tes_cuentas c ON c.id = ch.cuenta_id
             WHERE ch.estado IN ('cartera','depositado')
               AND ch.fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :d DAY)
             ORDER BY ch.fecha_vencimiento ASC"
        );
        $stmt->bindValue(':d', $dias, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public static function queryRecibidosActivos(): array
    {
        $stmt = self::$conexionBDC->query(
            "SELECT ch.*, c.nombre AS cuenta_nombre
             FROM tes_cheques ch
             LEFT JOIN tes_cuentas c ON c.id = ch.cuenta_id
             WHERE ch.tipo = 'recibido' AND ch.estado NOT IN ('rechazado','anulado')
             ORDER BY ch.fecha_vencimiento ASC"
        );
        return $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
    }

    public static function queryEmitidosActivos(): array
    {
        $stmt = self::$conexionBDC->query(
            "SELECT ch.*, c.nombre AS cuenta_nombre
             FROM tes_cheques ch
             LEFT JOIN tes_cuentas c ON c.id = ch.cuenta_id
             WHERE ch.tipo = 'emitido' AND ch.estado NOT IN ('cobrado','anulado')
             ORDER BY ch.fecha_vencimiento ASC"
        );
        return $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
    }

    public static function cambiarEstado(int $id, string $estado): void
    {
        self::$conexionBDC->prepare(
            "UPDATE tes_cheques SET estado = ? WHERE id = ?"
        )->execute([$estado, $id]);
    }
}
