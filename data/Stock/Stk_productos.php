<?php

namespace Data\Stock;

class Stk_productos
{
    protected static $tabla      = 'stk_productos';
    protected static $conexionBD;

    public $id;
    public $idx;
    public $codigo;
    public $nombre;
    public $descripcion;
    public $categoria_id;
    public $subcategoria_id;
    public $marca_id;
    public $familia_id;
    public $precio_costo;
    public $precio_venta;
    public $stock_actual;
    public $stock_minimo;
    public $unidad;
    public $imagen;
    public $habilitado;
    public $en_tienda;
    public $created_at;

    public function __construct(array $args = [])
    {
        $this->id              = $args['id']              ?? null;
        $this->idx             = $args['idx']             ?? '';
        $this->codigo          = $args['codigo']          ?? '';
        $this->nombre          = $args['nombre']          ?? '';
        $this->descripcion     = $args['descripcion']     ?? '';
        $this->categoria_id    = $args['categoria_id']    ?? null;
        $this->subcategoria_id = $args['subcategoria_id'] ?? null;
        $this->marca_id        = $args['marca_id']        ?? null;
        $this->familia_id      = $args['familia_id']      ?? null;
        $this->precio_costo    = $args['precio_costo']    ?? 0;
        $this->precio_venta    = $args['precio_venta']    ?? 0;
        $this->stock_actual    = $args['stock_actual']    ?? 0;
        $this->stock_minimo    = $args['stock_minimo']    ?? 0;
        $this->unidad          = $args['unidad']          ?? 'unidad';
        $this->imagen          = $args['imagen']          ?? '';
        $this->habilitado      = $args['habilitado']      ?? 1;
        $this->en_tienda       = $args['en_tienda']       ?? 0;
    }

    public static function setDB(\PDO $database): void
    {
        self::$conexionBD = $database;
    }

    public static function buscar_nombre(string $termino, int $limit = 8): array
    {
        $like = "%{$termino}%";
        $sql  = "SELECT id, codigo, nombre, precio_venta, stock_actual, unidad
                 FROM " . self::$tabla . "
                 WHERE (nombre LIKE :like OR codigo LIKE :like2) AND habilitado = 1
                 ORDER BY nombre ASC LIMIT :lim";
        $c = self::$conexionBD->prepare($sql);
        $c->bindParam(':like',  $like);
        $c->bindParam(':like2', $like);
        $c->bindValue(':lim',   $limit, \PDO::PARAM_INT);
        $c->execute();
        return $c->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }
}
