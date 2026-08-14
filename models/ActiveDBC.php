<?php

namespace Model;

/**
 * ActiveDBC — Conexión a la base de datos del CLIENTE (multi-tenant).
 * Paralelo a ActiveDB (BD principal), pero apunta a la BD de la empresa
 * cuya licencia el usuario seleccionó en la sesión.
 */
class ActiveDBC
{
    protected static $conexionBDC;

    public static function setDB(\PDO $database): void
    {
        self::$conexionBDC = $database;
    }

    public static function getDB(): ?\PDO
    {
        return self::$conexionBDC ?? null;
    }

    public static function isConnected(): bool
    {
        return self::$conexionBDC instanceof \PDO;
    }

    // ── Lecturas genéricas ────────────────────────────────────────────────────

    public function queryAll(): array
    {
        $sql      = "SELECT * FROM " . static::$tabla;
        $consulta = self::$conexionBDC->prepare($sql);
        $consulta->execute();
        return $consulta->fetchAll() ?: [];
    }

    public function queryId(string $id): mixed
    {
        $sql      = "SELECT * FROM " . static::$tabla . " WHERE id = :id";
        $consulta = self::$conexionBDC->prepare($sql);
        $consulta->bindParam(':id', $id);
        $consulta->execute();
        return $consulta->fetch(\PDO::FETCH_OBJ) ?: null;
    }

    public function queryIdx(string $idx): mixed
    {
        $sql      = "SELECT * FROM " . static::$tabla . " WHERE idx = :idx";
        $consulta = self::$conexionBDC->prepare($sql);
        $consulta->bindParam(':idx', $idx);
        $consulta->execute();
        return $consulta->fetch(\PDO::FETCH_OBJ) ?: null;
    }

    public function deleteIdx(string $idx): void
    {
        self::$conexionBDC->prepare("DELETE FROM " . static::$tabla . " WHERE idx = :idx")
            ->execute([':idx' => $idx]);
    }

    public function deleteId(string $id): void
    {
        self::$conexionBDC->prepare("DELETE FROM " . static::$tabla . " WHERE id = :id")
            ->execute([':id' => $id]);
    }

    public function atributos(): array
    {
        $atributos = [];
        foreach (static::$columnasDB as $col) {
            if ($col === 'id') continue;
            $atributos[$col] = $this->$col;
        }
        return $atributos;
    }
}
