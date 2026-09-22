<?php

namespace Model\Stock;

use Model\ActiveDBC;

class Model_stk_movimientos extends ActiveDBC
{
    protected static $tabla = 'stk_movimientos';

    // ── tipos: compra | ajuste_pos | ajuste_neg | transferencia | venta ──────────
    public static function registrar(
        string $tipo,
        int    $producto_id,
        int    $deposito_origen_id,
        float  $cantidad,
        float  $precio_ref          = 0,
        string $motivo              = '',
        int    $deposito_destino_id = 0,
        int    $usuario_id          = 0
    ): void {
        if ($producto_id <= 0 || $deposito_origen_id <= 0 || $cantidad <= 0) return;

        self::$conexionBDC->prepare(
            "INSERT INTO stk_movimientos
             (idx, tipo, producto_id, deposito_origen_id, deposito_destino_id,
              cantidad, precio_ref, motivo, usuario_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        )->execute([
            uniqid('mov_'),
            $tipo,
            $producto_id,
            $deposito_origen_id,
            $deposito_destino_id > 0 ? $deposito_destino_id : null,
            $cantidad,
            $precio_ref,
            $motivo,
            $usuario_id,
        ]);

        $positivos = ['compra', 'ajuste_pos'];
        $negativos = ['ajuste_neg', 'venta'];

        if (in_array($tipo, $positivos)) {
            self::upsertStock($producto_id, $deposito_origen_id, $cantidad);
        } elseif (in_array($tipo, $negativos)) {
            self::upsertStock($producto_id, $deposito_origen_id, -$cantidad);
        } elseif ($tipo === 'transferencia') {
            self::upsertStock($producto_id, $deposito_origen_id,  -$cantidad);
            if ($deposito_destino_id > 0) {
                self::upsertStock($producto_id, $deposito_destino_id, $cantidad);
            }
        }

        self::recalcTotal($producto_id);
    }

    private static function upsertStock(int $producto_id, int $deposito_id, float $delta): void
    {
        self::$conexionBDC->prepare(
            "INSERT INTO stk_stock (producto_id, deposito_id, stock_actual)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE stock_actual = stock_actual + ?"
        )->execute([$producto_id, $deposito_id, $delta, $delta]);
    }

    private static function recalcTotal(int $producto_id): void
    {
        self::$conexionBDC->prepare(
            "UPDATE stk_productos
             SET stock_actual = (
                 SELECT COALESCE(SUM(s.stock_actual), 0)
                 FROM stk_stock s WHERE s.producto_id = ?
             )
             WHERE id = ?"
        )->execute([$producto_id, $producto_id]);
    }

    public static function getStockEnDeposito(int $producto_id, int $deposito_id): float
    {
        $stmt = self::$conexionBDC->prepare(
            "SELECT stock_actual FROM stk_stock WHERE producto_id = ? AND deposito_id = ? LIMIT 1"
        );
        $stmt->execute([$producto_id, $deposito_id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? (float)$row['stock_actual'] : 0.0;
    }

    public static function queryFiltrado(array $f = []): array
    {
        $where  = [];
        $params = [];

        if (!empty($f['tipo'])) {
            $where[]  = 'mov.tipo = ?';
            $params[] = $f['tipo'];
        }
        if (!empty($f['producto_id'])) {
            $where[]  = 'mov.producto_id = ?';
            $params[] = (int)$f['producto_id'];
        }
        if (!empty($f['deposito_id'])) {
            $where[]  = '(mov.deposito_origen_id = ? OR mov.deposito_destino_id = ?)';
            $params[] = (int)$f['deposito_id'];
            $params[] = (int)$f['deposito_id'];
        }
        if (!empty($f['fecha_desde'])) {
            $where[]  = 'mov.fecha >= ?';
            $params[] = $f['fecha_desde'] . ' 00:00:00';
        }
        if (!empty($f['fecha_hasta'])) {
            $where[]  = 'mov.fecha <= ?';
            $params[] = $f['fecha_hasta'] . ' 23:59:59';
        }

        $sql = "SELECT mov.*,
                       p.nombre  AS producto_nombre,
                       p.codigo  AS producto_codigo,
                       d1.nombre AS deposito_origen,
                       d2.nombre AS deposito_destino
                FROM stk_movimientos mov
                JOIN stk_productos  p  ON p.id  = mov.producto_id
                JOIN stk_depositos  d1 ON d1.id = mov.deposito_origen_id
                LEFT JOIN stk_depositos d2 ON d2.id = mov.deposito_destino_id"
              . (count($where) ? ' WHERE ' . implode(' AND ', $where) : '')
              . ' ORDER BY mov.fecha DESC, mov.id DESC LIMIT 1000';

        $stmt = self::$conexionBDC->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }
}
