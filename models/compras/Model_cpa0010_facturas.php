<?php

namespace Model\Compras;

use Model\ActiveDBC;

class Model_cpa0010_facturas extends ActiveDBC
{
    protected static $tabla      = 'cpa_facturas';
    protected static $columnasDB = ['id', 'idx', 'numero', 'tipo', 'proveedor_id', 'proveedor_label',
                                    'fecha', 'fecha_vencimiento', 'condicion_pago',
                                    'total_neto', 'iva_pct', 'iva', 'total', 'estado', 'observaciones'];

    public function queryAll(): array
    {
        $c = self::$conexionBDC->prepare(
            "SELECT f.*, COALESCE(p.razon_social, f.proveedor_label) AS prov_nombre
             FROM " . static::$tabla . " f
             LEFT JOIN cpa_proveedores p ON p.id = f.proveedor_id
             ORDER BY f.created_at DESC"
        );
        $c->execute();
        return $c->fetchAll() ?: [];
    }

    public function queryIdx(string $idx): array|false
    {
        $c = self::$conexionBDC->prepare(
            "SELECT f.*, COALESCE(p.razon_social, f.proveedor_label) AS prov_nombre
             FROM " . static::$tabla . " f
             LEFT JOIN cpa_proveedores p ON p.id = f.proveedor_id
             WHERE f.idx = :idx LIMIT 1"
        );
        $c->execute([':idx' => $idx]);
        return $c->fetch(\PDO::FETCH_ASSOC) ?: false;
    }

    public function getItems(string $factura_idx): array
    {
        $c = self::$conexionBDC->prepare(
            "SELECT * FROM cpa_facturas_items WHERE factura_idx = :idx ORDER BY orden ASC"
        );
        $c->execute([':idx' => $factura_idx]);
        return $c->fetchAll() ?: [];
    }

    public function insertar(array $a, array $items): string
    {
        $idx = uniqid('cpa_');
        self::$conexionBDC->prepare(
            "INSERT INTO " . static::$tabla . "
             (idx, numero, tipo, proveedor_id, proveedor_label, fecha, fecha_vencimiento,
              condicion_pago, total_neto, iva_pct, iva, total, estado, observaciones)
             VALUES
             (:idx, :numero, :tipo, :proveedor_id, :proveedor_label, :fecha, :fecha_vencimiento,
              :condicion_pago, :total_neto, :iva_pct, :iva, :total, 'pendiente', :observaciones)"
        )->execute([
            ':idx'               => $idx,
            ':numero'            => trim($a['numero'] ?? ''),
            ':tipo'              => $a['tipo'] ?? 'A',
            ':proveedor_id'      => ($a['proveedor_id'] !== '' ? (int)$a['proveedor_id'] : null),
            ':proveedor_label'   => trim($a['proveedor_label'] ?? ''),
            ':fecha'             => $a['fecha'],
            ':fecha_vencimiento' => ($a['fecha_vencimiento'] ?? '') ?: null,
            ':condicion_pago'    => $a['condicion_pago'] ?? 'Contado',
            ':total_neto'        => (float)($a['total_neto'] ?? 0),
            ':iva_pct'           => (float)($a['iva_pct'] ?? 21),
            ':iva'               => (float)($a['iva'] ?? 0),
            ':total'             => (float)($a['total'] ?? 0),
            ':observaciones'     => trim($a['observaciones'] ?? ''),
        ]);

        $ci = self::$conexionBDC->prepare(
            "INSERT INTO cpa_facturas_items
             (factura_idx, producto_label, cantidad, precio_unit, subtotal, orden)
             VALUES (:fidx, :label, :cant, :precio, :sub, :ord)"
        );
        foreach ($items as $i => $item) {
            if (empty(trim($item['producto_label'] ?? ''))) continue;
            $ci->execute([
                ':fidx'  => $idx,
                ':label' => trim($item['producto_label']),
                ':cant'  => (float)($item['cantidad'] ?? 1),
                ':precio'=> (float)($item['precio_unit'] ?? 0),
                ':sub'   => (float)($item['subtotal'] ?? 0),
                ':ord'   => $i,
            ]);
        }
        return $idx;
    }

    public function anular(string $idx): void
    {
        self::$conexionBDC->prepare(
            "UPDATE " . static::$tabla . " SET estado = 'anulada' WHERE idx = :idx"
        )->execute([':idx' => $idx]);
    }

    public function marcarPagada(string $idx): void
    {
        self::$conexionBDC->prepare(
            "UPDATE " . static::$tabla . " SET estado = 'pagada' WHERE idx = :idx"
        )->execute([':idx' => $idx]);
    }

    public function getStatsMes(): array
    {
        try {
            $r = self::$conexionBDC->query(
                "SELECT COUNT(*) AS cant, COALESCE(SUM(total),0) AS total
                 FROM " . static::$tabla . "
                 WHERE DATE_FORMAT(fecha,'%Y-%m') = DATE_FORMAT(CURDATE(),'%Y-%m')
                   AND estado != 'anulada'"
            );
            return $r ? ($r->fetch(\PDO::FETCH_ASSOC) ?: ['cant' => 0, 'total' => 0])
                      : ['cant' => 0, 'total' => 0];
        } catch (\Throwable $e) {
            return ['cant' => 0, 'total' => 0];
        }
    }

    public function countPendientes(): int
    {
        try {
            $r = self::$conexionBDC->query(
                "SELECT COUNT(*) FROM " . static::$tabla . " WHERE estado = 'pendiente'"
            );
            return $r ? (int)$r->fetchColumn() : 0;
        } catch (\Throwable $e) { return 0; }
    }
}
