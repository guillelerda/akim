<?php

namespace Model\Tesoreria;

use Model\ActiveDBC;

class Model_tes_tarjetas extends ActiveDBC
{
    protected static $tabla = 'tes_tarjetas';

    public function queryAll(): array
    {
        $stmt = self::$conexionBDC->query(
            "SELECT t.*, c.nombre AS cuenta_nombre
             FROM tes_tarjetas t
             LEFT JOIN tes_cuentas c ON c.id = t.cuenta_id
             ORDER BY t.nombre"
        );
        return $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
    }

    public static function queryHabilitadas(): array
    {
        $stmt = self::$conexionBDC->query(
            "SELECT t.*, c.nombre AS cuenta_nombre
             FROM tes_tarjetas t
             LEFT JOIN tes_cuentas c ON c.id = t.cuenta_id
             WHERE t.habilitada = 1
             ORDER BY t.favorito DESC, t.nombre"
        );
        return $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
    }

    public static function setSingleFavorito(int $id): void
    {
        self::$conexionBDC->exec("UPDATE tes_tarjetas SET favorito = 0");
        self::$conexionBDC->prepare(
            "UPDATE tes_tarjetas SET favorito = 1 WHERE id = ?"
        )->execute([$id]);
    }

    public static function actualizar(int $id, array $a): void
    {
        self::$conexionBDC->prepare(
            "UPDATE tes_tarjetas
             SET nombre        = ?,
                 red           = ?,
                 tipo_tarjeta  = ?,
                 plazo_dias    = ?,
                 descuento_pct = ?,
                 cuenta_id     = ?
             WHERE id = ?"
        )->execute([
            trim($a['nombre']           ?? ''),
            trim($a['red']              ?? ''),
            $a['tipo_tarjeta']          ?? 'credito',
            (int)($a['plazo_dias']      ?? 0),
            (float)($a['descuento_pct'] ?? 0),
            !empty($a['cuenta_id']) ? (int)$a['cuenta_id'] : null,
            $id,
        ]);
    }

    public function insertar(array $a): void
    {
        self::$conexionBDC->prepare(
            "INSERT INTO tes_tarjetas (idx, nombre, red, tipo_tarjeta, plazo_dias, descuento_pct, cuenta_id)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        )->execute([
            uniqid('test_'),
            trim($a['nombre']           ?? ''),
            trim($a['red']              ?? ''),
            $a['tipo_tarjeta']          ?? 'credito',
            (int)($a['plazo_dias']      ?? 0),
            (float)($a['descuento_pct'] ?? 0),
            !empty($a['cuenta_id']) ? (int)$a['cuenta_id'] : null,
        ]);
    }

    public static function getById(int $id): ?array
    {
        $stmt = self::$conexionBDC->prepare(
            "SELECT * FROM tes_tarjetas WHERE id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    public function toggle(int $id): void
    {
        self::$conexionBDC->prepare(
            "UPDATE tes_tarjetas SET habilitada = 1 - habilitada WHERE id = ?"
        )->execute([$id]);
    }

    public function eliminar(int $id): void
    {
        self::$conexionBDC->prepare("DELETE FROM tes_tarjetas WHERE id = ?")->execute([$id]);
    }
}
