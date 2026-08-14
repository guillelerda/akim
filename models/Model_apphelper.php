<?php

namespace Model;

use Model\ActiveDB;
use Model\Administrador\Model_empresas;
use Model\Administrador\Model_licencias;
use Model\Administrador\Model_usuarios;
use Model\Genericas\Model_provincias;
use Model\Genericas\Model_condicion_fiscal;
use Model\Genericas\Model_tipo_documentos;

class Model_apphelper
{
    public static function inicialize(): array
    {
        $licencia       = $_SESSION['licencia']       ?? '';
        $directorioFoto = REPO_PATH . $licencia . '/';
        verificar_carpeta($directorioFoto . 'productos/');
        return [
            'licencia'       => $licencia,
            'directorioFoto' => $directorioFoto,
        ];
    }

    public static function getUsuarioAutenticado(): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
        $userName = $_SESSION['usuario'] ?? '';
        if ($userName === '') {
            echo "<script>location.href='login';</script>";
            exit;
        }
        $obj     = new Model_usuarios();
        $raw     = $obj->query_username($userName);
        $usuario = json_decode(json_encode($raw));

        $nombre = openssl_decrypt($usuario->nombre ?? '', SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV);
        $perfil = openssl_decrypt($usuario->perfil ?? '', SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV);
        $email  = openssl_decrypt($usuario->email  ?? '', SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV);

        return [$userName, $usuario, $nombre, $perfil, $email, $usuario->id ?? 0, $usuario->foto ?? ''];
    }

    public static function getLicenciaAutenticada(string $licencia): array
    {
        $obj_lic  = new Model_licencias();
        $data_lic = $obj_lic->getRegistro_lic_code($licencia);
        if (!$data_lic) return [null, ['Licencia inválida']];

        $em_cod    = openssl_decrypt($data_lic['lic_empresa_cod'] ?? '', SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV);
        $obj_em    = new Model_empresas();
        $dataEm    = $obj_em->getRegistro_codigo($em_cod);
        if (!$dataEm) return [null, ['Empresa inválida']];

        $em_nombre = openssl_decrypt($dataEm['em_nombre'] ?? '', SSL_COD, SSL_KEY, SSL_OPT, SSL_CIV) ?: ($dataEm['em_nombre'] ?? '');

        return [
            $em_nombre,
            $dataEm['em_slogan'] ?? '',
            $dataEm['em_logo']   ?? '',
        ];
    }

    public static function cargarListas(): array
    {
        $provincias      = (new Model_provincias())->queryAll()             ?? [];
        $cond_fiscal     = (new Model_condicion_fiscal())->queryAll()       ?? [];
        $tipo_docs       = (new Model_tipo_documentos())->lista_habilitados() ?? [];
        return [$provincias, $cond_fiscal, $tipo_docs];
    }

    public static function DeterminarModuloActivo(): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
        $modulo = $_SESSION['moduloActivo']  ?? 'VTA';
        $layout = $_SESSION['layoutActivo']  ?? 'layout';
        $volver = $_SESSION['sidebarActiva'] ?? 'menu_modulos';
        return [$modulo, $layout, $volver];
    }

    // ── Imágenes ──────────────────────────────────────────────────────────────

    public static function procesarImagen(array $file, string $directorio): array
    {
        $errores = [];
        if (!isset($file) || $file['error'] !== 0) return [null, $errores];

        $ext   = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $check = getimagesize($file['tmp_name']);

        if (!$check)                                    $errores[] = 'El archivo no es una imagen válida.';
        if ($file['size'] > 500000)                     $errores[] = 'La imagen supera 500KB.';
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) $errores[] = 'Solo JPG, PNG o GIF.';
        if ($errores) return [null, $errores];

        verificar_carpeta($directorio);
        $nombre = generate_string(10) . '.' . $ext;
        $target = rtrim($directorio, '/') . '/' . $nombre;

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            $errores[] = 'Error al subir el archivo.';
            return [null, $errores];
        }
        return [$nombre, []];
    }

    public static function procesarImagenBase64(string $imgBase64, string $directorio): array
    {
        if (!preg_match('/^data:image\/(\w+);base64,/', $imgBase64, $type)) {
            return [null, ['Formato base64 inválido.']];
        }
        $ext     = strtolower($type[1]);
        $imgData = base64_decode(substr($imgBase64, strpos($imgBase64, ',') + 1));

        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) return [null, ['Solo JPG, PNG o GIF.']];
        if (strlen($imgData) > 500000)                       return [null, ['Imagen supera 500KB.']];

        verificar_carpeta($directorio);
        $nombre = generate_string(10) . '.' . $ext;
        if (!file_put_contents(rtrim($directorio, '/') . '/' . $nombre, $imgData)) {
            return [null, ['Error al guardar la imagen.']];
        }
        return [$nombre, []];
    }

    // ── Fechas ────────────────────────────────────────────────────────────────

    public static function toSqlDateTime(mixed $value, ?string $timezone = null): ?string
    {
        $dt = self::toDateTime($value, $timezone);
        return $dt ? $dt->format('Y-m-d H:i:s') : null;
    }

    public static function toDateTime(mixed $value, ?string $timezone = null): ?\DateTime
    {
        if ($value === null || $value === '') return null;
        $tz = $timezone ? new \DateTimeZone($timezone) : null;
        if ($value instanceof \DateTime) { if ($tz) $value->setTimezone($tz); return $value; }
        if (!is_string($value)) return null;

        $n = preg_replace('/\s+/', ' ', str_replace('/', '-', trim($value)));
        if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $n)) $n .= ' 00:00:00';

        foreach (['Y-m-d H:i:s', 'Y-n-j H:i:s', 'Y-m-d G:i:s', 'Y-n-j G:i:s'] as $fmt) {
            $dt = \DateTime::createFromFormat($fmt, $n, $tz ?: null);
            if ($dt instanceof \DateTime) return $dt;
        }
        try { return $tz ? new \DateTime($n, $tz) : new \DateTime($n); } catch (\Throwable $e) { return null; }
    }
}
