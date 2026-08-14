<?php

namespace Model;

/**
 * Model_JIT — Patrón Just-In-Time para AKIM.
 *
 * Permite que cualquier entidad (cliente, producto, proveedor, concepto...)
 * se cree en el momento en que se la necesita, sin interrumpir el flujo de carga.
 *
 * Uso típico desde un endpoint AJAX:
 *   $jit = new Model_JIT();
 *   $resultado = $jit->buscarOCrear('clientes', 'razon_social', $termino, $atributos_minimos);
 *   json_response($resultado);
 */
class Model_JIT extends ActiveDBC
{
    /**
     * Busca registros que coincidan con $termino en el campo $campo de $tabla.
     * Retorna un array listo para el dropdown de autocomplete.
     * El último ítem siempre es la opción "Crear nuevo".
     *
     * @param  string $tabla     Nombre de la tabla BD
     * @param  string $campo     Campo sobre el que se busca (ej: 'razon_social', 'nombre')
     * @param  string $termino   Texto que escribió el usuario
     * @param  int    $limit     Máximo de resultados antes de "Crear nuevo"
     * @return array             Lista de opciones [{id, label, nuevo: false|true}]
     */
    public static function buscar(string $tabla, string $campo, string $termino, int $limit = 8): array
    {
        $db   = self::$conexionBDC;
        $like = '%' . $termino . '%';

        $sql      = "SELECT id, {$campo} AS label FROM {$tabla}
                     WHERE {$campo} LIKE :like
                     ORDER BY {$campo} ASC
                     LIMIT :lim";
        $consulta = $db->prepare($sql);
        $consulta->bindParam(':like', $like);
        $consulta->bindValue(':lim',  $limit, \PDO::PARAM_INT);
        $consulta->execute();
        $lista = $consulta->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        foreach ($lista as &$item) {
            $item['nuevo'] = false;
        }

        // Opción especial JIT — siempre al final
        $lista[] = [
            'id'    => 0,
            'label' => $termino,
            'nuevo' => true,
        ];

        return $lista;
    }

    /**
     * Busca un registro por valor exacto de $campo en $tabla.
     * Si no existe, lo inserta con $atributos y retorna el nuevo ID.
     * Si existe, retorna su ID.
     *
     * @param  string $tabla      Tabla donde buscar/insertar
     * @param  string $campo      Campo de búsqueda (ej: 'razon_social')
     * @param  string $valor      Valor a buscar/crear
     * @param  array  $atributos  Datos completos para el INSERT si no existe
     * @return int                ID del registro encontrado o recién creado
     */
    public static function findOrCreate(string $tabla, string $campo, string $valor, array $atributos): int
    {
        $db = self::$conexionBDC;

        // Buscar
        $sql      = "SELECT id FROM {$tabla} WHERE {$campo} = :valor LIMIT 1";
        $consulta = $db->prepare($sql);
        $consulta->bindParam(':valor', $valor);
        $consulta->execute();
        $existente = $consulta->fetch(\PDO::FETCH_ASSOC);

        if ($existente) {
            return (int) $existente['id'];
        }

        // Insertar
        $campos       = implode(', ', array_keys($atributos));
        $placeholders = ':' . implode(', :', array_keys($atributos));
        $sql          = "INSERT INTO {$tabla} ({$campos}) VALUES ({$placeholders})";
        $consulta     = $db->prepare($sql);
        foreach ($atributos as $key => $val) {
            $consulta->bindValue(":$key", $val);
        }
        $consulta->execute();

        return (int) $db->lastInsertId();
    }

    /**
     * Crea un registro con defaults mínimos si no existe.
     * Versión simplificada de findOrCreate para entidades muy simples (categorías, marcas...).
     *
     * @param  string $tabla  Tabla
     * @param  string $nombre Nombre a buscar/crear
     * @return int            ID resultante
     */
    public static function findOrCreateByNombre(string $tabla, string $nombre): int
    {
        return self::findOrCreate(
            $tabla,
            'nombre',
            $nombre,
            ['nombre' => $nombre, 'habilitada' => 1]
        );
    }
}
