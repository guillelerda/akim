<?php

namespace Model\Stock;

use Model\ActiveDBC;
use Model\Model_JIT;
use Data\Stock\Stk_productos;
use Model\Stock\Model_stk_movimientos;

class Model_stk_0001_productos extends ActiveDBC
{
    protected static $tabla      = 'stk_productos';
    protected static $columnasDB = ['id', 'idx', 'codigo', 'nombre', 'descripcion',
                                    'categoria_id', 'subcategoria_id', 'marca_id', 'familia_id',
                                    'precio_costo', 'precio_venta', 'stock_actual', 'stock_minimo',
                                    'unidad', 'imagen', 'habilitado', 'en_tienda'];

    // ── JIT: buscar producto para autocomplete ────────────────────────────────
    public static function jit_buscar(string $termino): array
    {
        Stk_productos::setDB(self::$conexionBDC);
        $lista = Stk_productos::buscar_nombre($termino, 8);
        foreach ($lista as &$item) { $item['nuevo'] = false; }
        $lista[] = [
            'id'          => 0,
            'codigo'      => '',
            'nombre'      => $termino,
            'precio_venta'=> 0,
            'stock_actual'=> 0,
            'unidad'      => 'unidad',
            'nuevo'       => true,
        ];
        return $lista;
    }

    // ── JIT: crear producto mínimo (findOrCreate por nombre) ─────────────────
    public static function jit_crear(string $nombre, float $precio_venta = 0, float $precio_costo = 0): int
    {
        return Model_JIT::findOrCreate(
            'stk_productos',
            'nombre',
            $nombre,
            [
                'idx'          => uniqid('prd_'),
                'codigo'       => strtoupper(substr($nombre, 0, 8)) . rand(100, 999),
                'nombre'       => $nombre,
                'precio_costo' => $precio_costo ?: $precio_venta,
                'precio_venta' => $precio_venta,
                'stock_actual' => 0,
                'stock_minimo' => 0,
                'unidad'       => 'unidad',
                'habilitado'   => 1,
                'en_tienda'    => 0,
            ]
        );
    }

    // ── Registrar producto desde factura de compra ────────────────────────────
    public static function registrar_desde_compra(string $nombre, float $precio_costo, float $cantidad, int $deposito_id = 0): int
    {
        $sql  = "SELECT id FROM " . static::$tabla . " WHERE nombre = ? LIMIT 1";
        $stmt = self::$conexionBDC->prepare($sql);
        $stmt->execute([$nombre]);
        $row  = $stmt->fetch();

        if ($row) {
            self::$conexionBDC->prepare(
                "UPDATE " . static::$tabla . " SET precio_costo = ? WHERE id = ?"
            )->execute([$precio_costo, $row['id']]);
            $producto_id = (int)$row['id'];
        } else {
            $idx = uniqid('prd_');
            self::$conexionBDC->prepare(
                "INSERT INTO " . static::$tabla . "
                 (idx, codigo, nombre, precio_costo, precio_venta, stock_actual, unidad, habilitado, en_tienda)
                 VALUES (?, ?, ?, ?, ?, 0, 'unidad', 1, 0)"
            )->execute([
                $idx,
                strtoupper(substr($nombre, 0, 8)) . rand(100, 999),
                $nombre,
                $precio_costo,
                $precio_costo,
            ]);
            $producto_id = (int)self::$conexionBDC->lastInsertId();
        }

        if ($producto_id > 0 && $deposito_id > 0) {
            Model_stk_movimientos::registrar('compra', $producto_id, $deposito_id, $cantidad, $precio_costo);
        }

        return $producto_id;
    }

    // ── Consulta para Punto de Venta ─────────────────────────────────────────

    public static function queryParaPos(): array
    {
        $stmt = self::$conexionBDC->query(
            "SELECT p.id, p.idx, p.codigo, p.nombre, p.precio_venta,
                    p.stock_actual, p.unidad, p.categoria_id, p.imagen,
                    COALESCE(c.nombre, 'Sin categoría') AS categoria_nombre
             FROM stk_productos p
             LEFT JOIN stk_categorias c ON c.id = p.categoria_id
             WHERE p.habilitado = 1
             ORDER BY c.nombre ASC, p.nombre ASC"
        );
        return $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
    }

    public function setImagen(string $idx, string $path): void
    {
        self::$conexionBDC->prepare(
            "UPDATE " . static::$tabla . " SET imagen = ? WHERE idx = ?"
        )->execute([$path, $idx]);
    }

    // ── CRUD completo ─────────────────────────────────────────────────────────

    public function queryAll(): array
    {
        if (!self::isConnected()) return [];
        $sql      = "SELECT p.*, c.nombre AS categoria_nombre
                     FROM " . static::$tabla . " p
                     LEFT JOIN stk_categorias c ON c.id = p.categoria_id
                     ORDER BY p.nombre ASC";
        $consulta = self::$conexionBDC->prepare($sql);
        $consulta->execute();
        return $consulta->fetchAll() ?: [];
    }

    public function queryIdx(string $idx): array|false
    {
        $sql  = "SELECT p.*, c.nombre AS categoria_nombre
                 FROM " . static::$tabla . " p
                 LEFT JOIN stk_categorias c ON c.id = p.categoria_id
                 WHERE p.idx = ? LIMIT 1";
        $stmt = self::$conexionBDC->prepare($sql);
        $stmt->execute([$idx]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: false;
    }

    public function insertar(array $a): string
    {
        $idx = uniqid('prd_');
        self::$conexionBDC->prepare(
            "INSERT INTO " . static::$tabla . "
             (idx, codigo, nombre, descripcion, categoria_id,
              precio_costo, precio_venta, stock_actual, stock_minimo,
              unidad, habilitado, en_tienda)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        )->execute([
            $idx,
            trim($a['codigo']       ?? ''),
            trim($a['nombre']       ?? ''),
            trim($a['descripcion']  ?? ''),
            ($a['categoria_id'] ?? '') !== '' ? (int)$a['categoria_id'] : null,
            (float)($a['precio_costo']  ?? 0),
            (float)($a['precio_venta']  ?? 0),
            (float)($a['stock_actual']  ?? 0),
            (float)($a['stock_minimo']  ?? 0),
            trim($a['unidad']       ?? 'unidad'),
            !empty($a['habilitado']) ? 1 : 0,
            !empty($a['en_tienda'])  ? 1 : 0,
        ]);
        return $idx;
    }

    public function actualizar(array $a): void
    {
        self::$conexionBDC->prepare(
            "UPDATE " . static::$tabla . " SET
             codigo = ?, nombre = ?, descripcion = ?, categoria_id = ?,
             precio_costo = ?, precio_venta = ?,
             stock_actual = ?, stock_minimo = ?,
             unidad = ?, habilitado = ?, en_tienda = ?
             WHERE idx = ?"
        )->execute([
            trim($a['codigo']      ?? ''),
            trim($a['nombre']      ?? ''),
            trim($a['descripcion'] ?? ''),
            ($a['categoria_id'] ?? '') !== '' ? (int)$a['categoria_id'] : null,
            (float)($a['precio_costo'] ?? 0),
            (float)($a['precio_venta'] ?? 0),
            (float)($a['stock_actual'] ?? 0),
            (float)($a['stock_minimo'] ?? 0),
            trim($a['unidad']      ?? 'unidad'),
            !empty($a['habilitado']) ? 1 : 0,
            !empty($a['en_tienda'])  ? 1 : 0,
            $a['idx'],
        ]);
    }

    public function toggle(string $idx): void
    {
        self::$conexionBDC->prepare(
            "UPDATE " . static::$tabla . " SET habilitado = 1 - habilitado WHERE idx = ?"
        )->execute([$idx]);
    }

    public function eliminar(string $idx): void
    {
        self::$conexionBDC->prepare(
            "DELETE FROM " . static::$tabla . " WHERE idx = ?"
        )->execute([$idx]);
    }

    public static function getStats(): array
    {
        try {
            $r = self::$conexionBDC->query(
                "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN stock_actual < stock_minimo AND stock_minimo > 0 THEN 1 ELSE 0 END) AS bajo_minimo,
                    COALESCE(SUM(stock_actual * precio_costo), 0) AS valor_inventario
                 FROM stk_productos WHERE habilitado = 1"
            );
            return $r ? ($r->fetch(\PDO::FETCH_ASSOC) ?: []) : [];
        } catch (\Throwable $e) {
            return ['total' => 0, 'bajo_minimo' => 0, 'valor_inventario' => 0];
        }
    }
}
