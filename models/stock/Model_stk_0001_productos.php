<?php

namespace Model\Stock;

use Model\ActiveDBC;
use Model\Model_JIT;
use Data\Stock\Stk_productos;

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

    // ── JIT: crear producto mínimo ────────────────────────────────────────────
    public static function jit_crear(string $nombre, float $precio = 0): int
    {
        return Model_JIT::findOrCreate(
            'stk_productos',
            'nombre',
            $nombre,
            [
                'idx'          => uniqid('prd_'),
                'codigo'       => strtoupper(substr($nombre, 0, 8)) . rand(100, 999),
                'nombre'       => $nombre,
                'precio_venta' => $precio,
                'stock_actual' => 0,
                'stock_minimo' => 0,
                'unidad'       => 'unidad',
                'habilitado'   => 1,
                'en_tienda'    => 0,
            ]
        );
    }

    public function queryAll(): array
    {
        $sql      = "SELECT p.*, c.nombre AS categoria_nombre, m.nombre AS marca_nombre
                     FROM " . static::$tabla . " p
                     LEFT JOIN stk_categorias  c ON c.id = p.categoria_id
                     LEFT JOIN stk_marcas      m ON m.id = p.marca_id
                     WHERE p.habilitado = 1
                     ORDER BY p.nombre ASC";
        $consulta = self::$conexionBDC->prepare($sql);
        $consulta->execute();
        return $consulta->fetchAll() ?: [];
    }
}
