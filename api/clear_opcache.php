<?php
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPcache reseteado.";
} else {
    echo "OPcache no está disponible en este servidor.";
}