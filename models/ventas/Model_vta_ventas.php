<?php

namespace Model\Ventas;

use Model\ActiveDBC;
use Model\Stock\Model_stk_movimientos;
use Model\Stock\Model_stk_depositos;

class Model_vta_ventas extends ActiveDBC
{
    protected static $tabla      = 'vta_ventas';
    protected static $columnasDB = ['id','idx','numero','cliente_id','fecha','condicion_pago',
                                    'descuento','total_bruto','total','estado','observaciones','vendedor'];

    /**
     * Inserta una venta completa (cabecera + ítems + descuento de stock).
     * Retorna el idx generado.
     */
    public static function insertar(array $wizard, string $vendedor): string
    {
        $db  = self::$conexionBDC;
        $idx = uniqid('vta_');

        $total_bruto = array_sum(array_column($wizard['items'], 'subtotal'));
        $descuento   = (float)($wizard['descuento'] ?? 0);
        $total_neto  = round($total_bruto * (1 - $descuento / 100), 2);
        $dep_id      = Model_stk_depositos::getDefault();

        $db->beginTransaction();
        try {
            // Próximo número correlativo — dentro de transacción para evitar duplicados
            $r   = $db->query("SELECT COALESCE(MAX(numero),0) AS max_num FROM vta_ventas");
            $num = $r ? ((int)$r->fetch()['max_num'] + 1) : 1;

            // Cabecera
            $db->prepare(
                "INSERT INTO vta_ventas
                 (idx, numero, cliente_id, fecha, condicion_pago,
                  descuento, total_bruto, total, estado, observaciones, vendedor, created_at)
                 VALUES
                 (:idx, :numero, :cliente_id, :fecha, :condicion_pago,
                  :descuento, :total_bruto, :total, 'emitida', :obs, :vendedor, NOW())"
            )->execute([
                ':idx'            => $idx,
                ':numero'         => $num,
                ':cliente_id'     => (int)($wizard['cliente_id'] ?? 0) ?: null,
                ':fecha'          => $wizard['fecha_venta'] ?? date('Y-m-d'),
                ':condicion_pago' => $wizard['condicion_pago'] ?? 'Contado',
                ':descuento'      => $descuento,
                ':total_bruto'    => $total_bruto,
                ':total'          => $total_neto,
                ':obs'            => $wizard['observaciones'] ?? '',
                ':vendedor'       => $vendedor,
            ]);

            // Ítems + movimiento de stock
            $cItem = $db->prepare(
                "INSERT INTO vta_ventas_items
                 (venta_idx, producto_id, producto_label, cantidad, precio_unit, subtotal, orden)
                 VALUES (:vi, :pid, :plabel, :cant, :precio, :sub, :ord)"
            );

            foreach ($wizard['items'] as $ord => $item) {
                $cItem->execute([
                    ':vi'     => $idx,
                    ':pid'    => (int)$item['producto_id'] ?: null,
                    ':plabel' => $item['producto_label'],
                    ':cant'   => $item['cantidad'],
                    ':precio' => $item['precio_unit'],
                    ':sub'    => $item['subtotal'],
                    ':ord'    => $ord,
                ]);
                if ((int)$item['producto_id'] > 0) {
                    Model_stk_movimientos::registrar(
                        'venta',
                        (int)$item['producto_id'],
                        $dep_id,
                        (float)$item['cantidad'],
                        (float)$item['precio_unit'],
                        'Venta #' . $num,
                        0,
                        0
                    );
                }
            }

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }

        return $idx;
    }

    public static function listar(int $limit = 100): array
    {
        $sql = "SELECT v.*,
                       COALESCE(c.razon_social, 'Consumidor final') AS cliente_nombre
                FROM vta_ventas v
                LEFT JOIN vta_clientes c ON c.id = v.cliente_id
                ORDER BY v.created_at DESC
                LIMIT :lim";
        $c = self::$conexionBDC->prepare($sql);
        $c->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $c->execute();
        return $c->fetchAll() ?: [];
    }

    public static function queryNumero(int $numero): ?array
    {
        $c = self::$conexionBDC->prepare(
            "SELECT v.*, COALESCE(c.razon_social,'Consumidor final') AS cliente_nombre
             FROM vta_ventas v
             LEFT JOIN vta_clientes c ON c.id = v.cliente_id
             WHERE v.numero = :num LIMIT 1"
        );
        $c->execute([':num' => $numero]);
        return $c->fetch() ?: null;
    }

    public static function getByIdx(string $idx): ?array
    {
        $c = self::$conexionBDC->prepare(
            "SELECT v.*,
                    COALESCE(c.razon_social,'Consumidor final') AS cliente_nombre,
                    c.cuit             AS cliente_cuit,
                    c.condicion_fiscal AS cliente_condicion_fiscal
             FROM vta_ventas v
             LEFT JOIN vta_clientes c ON c.id = v.cliente_id
             WHERE v.idx = :idx LIMIT 1"
        );
        $c->execute([':idx' => $idx]);
        return $c->fetch() ?: null;
    }

    public static function marcarCobrada(string $idx): void
    {
        self::$conexionBDC->prepare("UPDATE vta_ventas SET estado='cobrada' WHERE idx=:idx")
            ->execute([':idx' => $idx]);
    }

    public static function anular(string $idx): void
    {
        self::$conexionBDC->prepare("UPDATE vta_ventas SET estado='anulada' WHERE idx=:idx")
            ->execute([':idx' => $idx]);
    }

    public static function getItems(string $venta_idx): array
    {
        $c = self::$conexionBDC->prepare(
            "SELECT * FROM vta_ventas_items WHERE venta_idx = :vi ORDER BY orden ASC"
        );
        $c->execute([':vi' => $venta_idx]);
        return $c->fetchAll() ?: [];
    }

    public static function statsMes(): array
    {
        $r = self::$conexionBDC->query(
            "SELECT COUNT(*) AS cant, COALESCE(SUM(total),0) AS total
             FROM vta_ventas
             WHERE MONTH(fecha) = MONTH(CURDATE()) AND YEAR(fecha) = YEAR(CURDATE())
               AND estado != 'anulada'"
        );
        return $r ? $r->fetch() : ['cant' => 0, 'total' => 0];
    }

    public static function getByClienteId(int $clienteId): array
    {
        try {
            $c = self::$conexionBDC->prepare(
                "SELECT v.*,
                        COALESCE(c.razon_social, 'Consumidor final') AS cliente_nombre
                 FROM vta_ventas v
                 LEFT JOIN vta_clientes c ON c.id = v.cliente_id
                 WHERE v.cliente_id = :cid
                 ORDER BY v.fecha DESC, v.numero DESC
                 LIMIT 500"
            );
            $c->execute([':cid' => $clienteId]);
            return $c->fetchAll() ?: [];
        } catch (\Throwable $e) { return []; }
    }

    public static function getCobranzasPendientes(): array
    {
        try {
            $r = self::$conexionBDC->query(
                "SELECT v.*,
                        COALESCE(c.razon_social, 'Consumidor final') AS cliente_nombre
                 FROM vta_ventas v
                 LEFT JOIN vta_clientes c ON c.id = v.cliente_id
                 WHERE v.estado = 'emitida'
                 ORDER BY v.fecha ASC, v.numero ASC"
            );
            return $r ? $r->fetchAll() : [];
        } catch (\Throwable $e) { return []; }
    }
}
