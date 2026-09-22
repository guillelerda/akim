<?php

namespace Model\Stock;

use Model\ActiveDBC;

class Model_stk_depositos extends ActiveDBC
{
    protected static $tabla = 'stk_depositos';

    public function queryAll(): array
    {
        $stmt = self::$conexionBDC->query(
            "SELECT * FROM stk_depositos ORDER BY es_default DESC, nombre ASC"
        );
        return $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
    }

    public static function queryHabilitados(): array
    {
        $stmt = self::$conexionBDC->query(
            "SELECT * FROM stk_depositos WHERE habilitado = 1 ORDER BY es_default DESC, nombre ASC"
        );
        return $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
    }

    public static function getDefault(): int
    {
        $stmt = self::$conexionBDC->query(
            "SELECT id FROM stk_depositos WHERE es_default = 1 AND habilitado = 1 LIMIT 1"
        );
        if ($stmt) {
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($row) return (int)$row['id'];
        }
        $stmt2 = self::$conexionBDC->query(
            "SELECT id FROM stk_depositos WHERE habilitado = 1 ORDER BY id ASC LIMIT 1"
        );
        if ($stmt2) {
            $row2 = $stmt2->fetch(\PDO::FETCH_ASSOC);
            if ($row2) return (int)$row2['id'];
        }
        return 0;
    }

    public function insertar(array $a): void
    {
        self::$conexionBDC->prepare(
            "INSERT INTO stk_depositos (idx, nombre, descripcion, es_default, habilitado)
             VALUES (?, ?, ?, ?, 1)"
        )->execute([
            uniqid('dep_'),
            trim($a['nombre']      ?? ''),
            trim($a['descripcion'] ?? ''),
            !empty($a['es_default']) ? 1 : 0,
        ]);

        if (!empty($a['es_default'])) {
            $this->setDefault((int)self::$conexionBDC->lastInsertId());
        }
    }

    public function setDefault(int $id): void
    {
        self::$conexionBDC->prepare("UPDATE stk_depositos SET es_default = 0")->execute();
        self::$conexionBDC->prepare("UPDATE stk_depositos SET es_default = 1 WHERE id = ?")->execute([$id]);
    }

    public function toggle(int $id): void
    {
        self::$conexionBDC->prepare(
            "UPDATE stk_depositos SET habilitado = 1 - habilitado WHERE id = ? AND es_default = 0"
        )->execute([$id]);
    }

    public function eliminar(int $id): void
    {
        $stmt = self::$conexionBDC->prepare("SELECT es_default FROM stk_depositos WHERE id = ?");
        $stmt->execute([$id]);
        $row  = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row || $row['es_default']) return;
        self::$conexionBDC->prepare("DELETE FROM stk_depositos WHERE id = ?")->execute([$id]);
    }
}
