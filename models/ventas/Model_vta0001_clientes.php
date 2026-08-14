<?php

namespace Model\Ventas;

use Model\ActiveDBC;
use Model\Model_JIT;
use Data\Ventas\Vta_clientes;

class Model_vta0001_clientes extends ActiveDBC
{
    protected static $tabla      = 'vta_clientes';
    protected static $columnasDB = ['id', 'idx', 'razon_social', 'cuit', 'condicion_fiscal',
                                    'tipo_documento', 'nro_documento', 'domicilio', 'localidad',
                                    'provincia', 'cp', 'telefono', 'email', 'condicion_pago',
                                    'limite_credito', 'habilitado', 'observaciones'];

    // ── JIT: buscar para autocomplete (devuelve lista + opción "Crear nuevo") ──
    public static function jit_buscar(string $termino): array
    {
        Vta_clientes::setDB(self::$conexionBDC);
        $lista = Vta_clientes::buscar_razon_social($termino, 8);
        foreach ($lista as &$item) { $item['nuevo'] = false; }
        $lista[] = ['id' => 0, 'razon_social' => $termino, 'nuevo' => true];
        return $lista;
    }

    // ── JIT: crear cliente mínimo y retornar su ID ────────────────────────────
    public static function jit_crear(string $razon_social): int
    {
        return Model_JIT::findOrCreate(
            'vta_clientes',
            'razon_social',
            $razon_social,
            [
                'idx'            => uniqid('cli_'),
                'razon_social'   => $razon_social,
                'habilitado'     => 1,
                'condicion_pago' => 'Contado',
            ]
        );
    }

    // ── CRUD estándar ─────────────────────────────────────────────────────────

    public function queryAll(): array
    {
        $sql      = "SELECT * FROM " . static::$tabla . " ORDER BY razon_social ASC";
        $consulta = self::$conexionBDC->prepare($sql);
        $consulta->execute();
        return $consulta->fetchAll() ?: [];
    }

    public function insertar(array $a): int
    {
        Vta_clientes::setDB(self::$conexionBDC);
        return Vta_clientes::insertar($a);
    }

    public function actualizar(array $a): void
    {
        $sql = "UPDATE " . static::$tabla . " SET
                razon_social=:razon_social, cuit=:cuit, condicion_fiscal=:condicion_fiscal,
                tipo_documento=:tipo_documento, nro_documento=:nro_documento,
                domicilio=:domicilio, localidad=:localidad, provincia=:provincia, cp=:cp,
                telefono=:telefono, email=:email, condicion_pago=:condicion_pago,
                limite_credito=:limite_credito, habilitado=:habilitado, observaciones=:observaciones
                WHERE id=:id";
        $c = self::$conexionBDC->prepare($sql);
        foreach (['razon_social','cuit','condicion_fiscal','tipo_documento','nro_documento',
                  'domicilio','localidad','provincia','cp','telefono','email','condicion_pago',
                  'limite_credito','habilitado','observaciones','id'] as $k) {
            $c->bindValue(":$k", $a[$k] ?? '');
        }
        $c->execute();
    }
}
