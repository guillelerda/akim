<?php

namespace Model\Stock;

use Model\ActiveDBC;

class Model_stk001_categorias extends ActiveDBC
{
    protected static $tabla = 'stk_categorias';

    public function queryAll(): array
    {
        $sql  = "SELECT * FROM " . static::$tabla . " ORDER BY nombre ASC";
        $stmt = self::$conexionBDC->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public static function queryHabilitadas(): array
    {
        $sql  = "SELECT * FROM stk_categorias WHERE habilitada = 1 ORDER BY nombre ASC";
        $stmt = self::$conexionBDC->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public static function countHabilitadas(): int
    {
        try {
            $r = self::$conexionBDC->query("SELECT COUNT(*) FROM stk_categorias WHERE habilitada = 1");
            return $r ? (int)$r->fetchColumn() : 0;
        } catch (\Throwable $e) { return 0; }
    }

    public function insertar(string $nombre): int
    {
        $sql  = "INSERT INTO " . static::$tabla . " (nombre, habilitada) VALUES (?, 1)";
        $stmt = self::$conexionBDC->prepare($sql);
        $stmt->execute([trim($nombre)]);
        return (int)self::$conexionBDC->lastInsertId();
    }

    public function toggle(int $id): void
    {
        $sql  = "UPDATE " . static::$tabla . " SET habilitada = 1 - habilitada WHERE id = ?";
        $stmt = self::$conexionBDC->prepare($sql);
        $stmt->execute([$id]);
    }

    public function eliminar(int $id): void
    {
        // Solo si no hay productos asociados
        $c = self::$conexionBDC->prepare("SELECT COUNT(*) FROM stk_productos WHERE categoria_id = ?");
        $c->execute([$id]);
        if ((int)$c->fetchColumn() === 0) {
            self::$conexionBDC->prepare("DELETE FROM " . static::$tabla . " WHERE id = ?")->execute([$id]);
        }
    }
}
