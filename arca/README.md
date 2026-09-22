1. Crear una carpeta dentro del mismo con el [cuit] de la persona autorizada en AFIP 
2. Crear tres carpetas dentro de la anterior: ./[cuit]/wsfe , ./[cuit]/wsfex 
3. Dentro de dichas carpetas crear dos carpetas más: ./[cuit]./[serviceName]/tmp y ./[cuit]./[serviceName]/token
4. Crear las carpetas "./key/homologacion" y "./key/produccion" dentro de la carpeta con el [cuit]
5. En ./key/homologacion y ./key/produccion colocar los certificados generados en afip junto con las claves privadas.

Test:

1. Editar el archivo test.php y modificar el valor de la variable $CUIT por el de la persona autorizada en AFIP.
2. Probar la libreria desde consola con "php test.php" -> Debería imprimir OK por cada web service.
3. Opcional: Podrás probar los distintos tipos de comprobante desde los ejemplos nombrados como testFacturaX.php
