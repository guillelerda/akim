<?php

namespace Model;

use Model\ActiveDBC;

class Model_parametros extends ActiveDBC
{
    protected static $tabla = 'app_parametros';

    public static function getAll(): array
    {
        $sql  = "SELECT clave, valor FROM " . static::$tabla . " ORDER BY categoria, clave";
        $stmt = self::$conexionBDC->prepare($sql);
        $stmt->execute();
        $rows   = $stmt->fetchAll() ?: [];
        $result = [];
        foreach ($rows as $row) {
            $result[$row['clave']] = $row['valor'];
        }
        return $result;
    }

    public static function get(string $clave, string $default = ''): string
    {
        $sql  = "SELECT valor FROM " . static::$tabla . " WHERE clave = ?";
        $stmt = self::$conexionBDC->prepare($sql);
        $stmt->execute([$clave]);
        $row  = $stmt->fetch();
        return $row ? (string)$row['valor'] : $default;
    }

    public static function set(string $clave, string $valor): void
    {
        self::$conexionBDC->prepare(
            "INSERT INTO " . static::$tabla . " (clave, valor)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE valor = VALUES(valor)"
        )->execute([$clave, $valor]);
    }
}
