<?php

namespace Data\Compras;

class Cpa_proveedores
{
    protected static $tabla      = 'cpa_proveedores';
    protected static $conexionBD;

    public $id;
    public $idx;
    public $razon_social;
    public $cuit;
    public $condicion_fiscal;
    public $domicilio;
    public $localidad;
    public $provincia;
    public $cp;
    public $telefono;
    public $email;
    public $contacto;
    public $condicion_pago;
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
        $this->domicilio        = $args['domicilio']        ?? '';
        $this->localidad        = $args['localidad']        ?? '';
        $this->provincia        = $args['provincia']        ?? '';
        $this->cp               = $args['cp']               ?? '';
        $this->telefono         = $args['telefono']         ?? '';
        $this->email            = $args['email']            ?? '';
        $this->contacto         = $args['contacto']         ?? '';
        $this->condicion_pago   = $args['condicion_pago']   ?? 'Contado';
        $this->habilitado       = $args['habilitado']       ?? 1;
        $this->observaciones    = $args['observaciones']    ?? '';
    }

    public static function setDB(\PDO $database): void
    {
        self::$conexionBD = $database;
    }

    public static function buscar_razon_social(string $termino, int $limit = 8): array
    {
        $like = "%{$termino}%";
        $sql  = "SELECT id, razon_social, cuit FROM " . self::$tabla . "
                 WHERE razon_social LIKE :like AND habilitado = 1
                 ORDER BY razon_social ASC LIMIT :lim";
        $c = self::$conexionBD->prepare($sql);
        $c->bindParam(':like', $like);
        $c->bindValue(':lim',  $limit, \PDO::PARAM_INT);
        $c->execute();
        return $c->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }
}
