<?php

namespace Data\Ventas;

class Vta_clientes
{
    protected static $tabla      = 'vta_clientes';
    protected static $conexionBD;

    public $id;
    public $idx;
    public $razon_social;
    public $cuit;
    public $condicion_fiscal;
    public $tipo_documento;
    public $nro_documento;
    public $domicilio;
    public $localidad;
    public $provincia;
    public $cp;
    public $telefono;
    public $email;
    public $condicion_pago;
    public $limite_credito;
    public $habilitado;
    public $observaciones;
    public $created_at;

    public function __construct(array $args = [])
    {
        $this->id               = $args['id']               ?? null;
        $this->idx              = $args['idx']              ?? '';
        $this->razon_social     = $args['razon_social']     ?? '';
        $this->cuit             = $args['cuit']             ?? '';
        $this->condicion_fiscal = $args['condicion_fiscal'] ?? '';
        $this->tipo_documento   = $args['tipo_documento']   ?? 'DNI';
        $this->nro_documento    = $args['nro_documento']    ?? '';
        $this->domicilio        = $args['domicilio']        ?? '';
        $this->localidad        = $args['localidad']        ?? '';
        $this->provincia        = $args['provincia']        ?? '';
        $this->cp               = $args['cp']               ?? '';
        $this->telefono         = $args['telefono']         ?? '';
        $this->email            = $args['email']            ?? '';
        $this->condicion_pago   = $args['condicion_pago']   ?? 'Contado';
        $this->limite_credito   = $args['limite_credito']   ?? 0;
        $this->habilitado       = $args['habilitado']       ?? 1;
        $this->observaciones    = $args['observaciones']    ?? '';
    }

    public static function setDB(\PDO $database): void
    {
        self::$conexionBD = $database;
    }

    public static function insertar(array $a): int
    {
        $sql = "INSERT INTO " . self::$tabla . "
                (idx, razon_social, cuit, condicion_fiscal, tipo_documento, nro_documento,
                 domicilio, localidad, provincia, cp, telefono, email, condicion_pago,
                 limite_credito, habilitado, observaciones)
                VALUES
                (:idx, :razon_social, :cuit, :condicion_fiscal, :tipo_documento, :nro_documento,
                 :domicilio, :localidad, :provincia, :cp, :telefono, :email, :condicion_pago,
                 :limite_credito, :habilitado, :observaciones)";
        $c = self::$conexionBD->prepare($sql);
        foreach (['idx','razon_social','cuit','condicion_fiscal','tipo_documento','nro_documento',
                  'domicilio','localidad','provincia','cp','telefono','email','condicion_pago',
                  'limite_credito','habilitado','observaciones'] as $k) {
            $c->bindValue(":$k", $a[$k] ?? '');
        }
        $c->execute();
        return (int) self::$conexionBD->lastInsertId();
    }

    public static function buscar_razon_social(string $termino, int $limit = 8): array
    {
        $like = "%{$termino}%";
        $sql  = "SELECT id, razon_social FROM " . self::$tabla . "
                 WHERE razon_social LIKE :like AND habilitado = 1
                 ORDER BY razon_social ASC LIMIT :lim";
        $c = self::$conexionBD->prepare($sql);
        $c->bindParam(':like', $like);
        $c->bindValue(':lim',  $limit, \PDO::PARAM_INT);
        $c->execute();
        return $c->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }
}
