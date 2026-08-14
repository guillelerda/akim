<?php

namespace Model\Compras;

use Model\ActiveDBC;
use Data\Compras\Cpa_proveedores;

class Model_cpa0001_proveedores extends ActiveDBC
{
    protected static $tabla      = 'cpa_proveedores';
    protected static $columnasDB = ['id', 'idx', 'razon_social', 'cuit', 'condicion_fiscal',
                                    'domicilio', 'localidad', 'provincia', 'cp',
                                    'telefono', 'email', 'contacto', 'condicion_pago',
                                    'habilitado', 'observaciones'];

    public static function jit_buscar(string $termino): array
    {
        Cpa_proveedores::setDB(self::$conexionBDC);
        return Cpa_proveedores::buscar_razon_social($termino, 8);
    }

    public function queryAll(): array
    {
        $c = self::$conexionBDC->prepare(
            "SELECT * FROM " . static::$tabla . " ORDER BY razon_social ASC"
        );
        $c->execute();
        return $c->fetchAll() ?: [];
    }

    public function queryIdx(string $idx): array|false
    {
        $c = self::$conexionBDC->prepare(
            "SELECT * FROM " . static::$tabla . " WHERE idx = :idx LIMIT 1"
        );
        $c->execute([':idx' => $idx]);
        return $c->fetch(\PDO::FETCH_ASSOC) ?: false;
    }

    public function insertar(array $a): void
    {
        $sql = "INSERT INTO " . static::$tabla . "
                (idx, razon_social, cuit, condicion_fiscal, domicilio, localidad, provincia, cp,
                 telefono, email, contacto, condicion_pago, habilitado, observaciones)
                VALUES
                (:idx, :razon_social, :cuit, :condicion_fiscal, :domicilio, :localidad, :provincia, :cp,
                 :telefono, :email, :contacto, :condicion_pago, :habilitado, :observaciones)";
        $c = self::$conexionBDC->prepare($sql);
        foreach (['idx','razon_social','cuit','condicion_fiscal','domicilio','localidad','provincia','cp',
                  'telefono','email','contacto','condicion_pago','habilitado','observaciones'] as $k) {
            $c->bindValue(":$k", $a[$k] ?? '');
        }
        $c->execute();
    }

    public function actualizar(array $a): void
    {
        $sql = "UPDATE " . static::$tabla . " SET
                razon_social=:razon_social, cuit=:cuit, condicion_fiscal=:condicion_fiscal,
                domicilio=:domicilio, localidad=:localidad, provincia=:provincia, cp=:cp,
                telefono=:telefono, email=:email, contacto=:contacto,
                condicion_pago=:condicion_pago, habilitado=:habilitado, observaciones=:observaciones
                WHERE idx=:idx";
        $c = self::$conexionBDC->prepare($sql);
        foreach (['razon_social','cuit','condicion_fiscal','domicilio','localidad','provincia','cp',
                  'telefono','email','contacto','condicion_pago','habilitado','observaciones','idx'] as $k) {
            $c->bindValue(":$k", $a[$k] ?? '');
        }
        $c->execute();
    }

    public function toggleHabilitado(string $idx): void
    {
        self::$conexionBDC->prepare(
            "UPDATE " . static::$tabla . " SET habilitado = 1 - habilitado WHERE idx = :idx"
        )->execute([':idx' => $idx]);
    }

    public function countActivos(): int
    {
        $r = self::$conexionBDC->query(
            "SELECT COUNT(*) FROM " . static::$tabla . " WHERE habilitado = 1"
        );
        return $r ? (int)$r->fetchColumn() : 0;
    }
}
