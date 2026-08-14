<?php

namespace Model;

class ActiveDB
{
    protected static $conexionBD;

    public static function setDB(\PDO $database): void
    {
        self::$conexionBD = $database;
    }

    public static function getDB(): \PDO
    {
        return self::$conexionBD;
    }

    // ── Lecturas genéricas ────────────────────────────────────────────────────

    public function queryAll(): array
    {
        $sql      = "SELECT * FROM " . static::$tabla;
        $consulta = self::$conexionBD->prepare($sql);
        $consulta->execute();
        return $consulta->fetchAll() ?: [];
    }

    public function queryAll_ordered(): array
    {
        $sql      = "SELECT * FROM " . static::$tabla . " ORDER BY orden ASC";
        $consulta = self::$conexionBD->prepare($sql);
        $consulta->execute();
        return $consulta->fetchAll() ?: [];
    }

    public function queryAll_habilitados(): array
    {
        $sql      = "SELECT * FROM " . static::$tabla . " WHERE habilitada = 1 ORDER BY nombre ASC";
        $consulta = self::$conexionBD->prepare($sql);
        $consulta->execute();
        return $consulta->fetchAll() ?: [];
    }

    public function queryId(string $id): mixed
    {
        $sql      = "SELECT * FROM " . static::$tabla . " WHERE id = :id";
        $consulta = self::$conexionBD->prepare($sql);
        $consulta->bindParam(':id', $id);
        $consulta->execute();
        return $consulta->fetch(\PDO::FETCH_OBJ) ?: null;
    }

    public function queryIdx(string $idx): mixed
    {
        $sql      = "SELECT * FROM " . static::$tabla . " WHERE idx = :idx";
        $consulta = self::$conexionBD->prepare($sql);
        $consulta->bindParam(':idx', $idx);
        $consulta->execute();
        return $consulta->fetch(\PDO::FETCH_OBJ) ?: null;
    }

    public function queryCodigo(string $codigo): mixed
    {
        $sql      = "SELECT * FROM " . static::$tabla . " WHERE codigo = :codigo";
        $consulta = self::$conexionBD->prepare($sql);
        $consulta->bindParam(':codigo', $codigo);
        $consulta->execute();
        return $consulta->fetch(\PDO::FETCH_OBJ) ?: null;
    }

    public function queryHabilitada(string $habilitada): array
    {
        $sql      = "SELECT * FROM " . static::$tabla . " WHERE habilitada = :habilitada";
        $consulta = self::$conexionBD->prepare($sql);
        $consulta->bindParam(':habilitada', $habilitada);
        $consulta->execute();
        return $consulta->fetchAll() ?: [];
    }

    // ── Búsqueda para JIT / autocomplete ─────────────────────────────────────
    // Busca registros donde 'nombre' o 'razon_social' contengan $termino.
    // Las subclases pueden sobreescribir con campos propios.
    public function queryBuscar(string $termino, int $limit = 10): array
    {
        $tabla    = static::$tabla;
        $campo    = property_exists($this, 'razon_social') ? 'razon_social' : 'nombre';
        $like     = "%{$termino}%";
        $sql      = "SELECT * FROM {$tabla} WHERE {$campo} LIKE :like LIMIT :lim";
        $consulta = self::$conexionBD->prepare($sql);
        $consulta->bindParam(':like', $like);
        $consulta->bindValue(':lim',  $limit, \PDO::PARAM_INT);
        $consulta->execute();
        return $consulta->fetchAll() ?: [];
    }

    // ── Bajas ─────────────────────────────────────────────────────────────────

    public function deleteIdx(string $idx): void
    {
        $sql      = "DELETE FROM " . static::$tabla . " WHERE idx = :idx";
        $consulta = self::$conexionBD->prepare($sql);
        $consulta->bindParam(':idx', $idx);
        $consulta->execute();
    }

    public function deleteId(string $id): void
    {
        $sql      = "DELETE FROM " . static::$tabla . " WHERE id = :id";
        $consulta = self::$conexionBD->prepare($sql);
        $consulta->bindParam(':id', $id);
        $consulta->execute();
    }

    // ── Sanitización ──────────────────────────────────────────────────────────

    public function atributos(): array
    {
        $atributos = [];
        foreach (static::$columnasDB as $col) {
            if ($col === 'id') continue;
            $atributos[$col] = $this->$col;
        }
        return $atributos;
    }

    public function sanitizarAtributos(): array
    {
        $sanitizado = [];
        foreach ($this->atributos() as $key => $value) {
            $sanitizado[$key] = $this->limpiar_cadena((string)($value ?? ''));
        }
        return $sanitizado;
    }

    public function limpiar_cadena(string $cadena): string
    {
        $cadena = trim($cadena);
        if ($cadena === '') return '';

        $bloqueos = [
            '<script>', '</script>', '<script src', '<?php', '?>',
            'SELECT * FROM', 'DELETE FROM', 'INSERT INTO', 'DROP TABLE',
            'DROP DATABASE', 'TRUNCATE TABLE', '--',
        ];
        foreach ($bloqueos as $b) {
            $cadena = str_ireplace($b, '', $cadena);
        }
        return trim($cadena);
    }
}
