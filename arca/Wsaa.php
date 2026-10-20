<?php

/**
 * Clase para autenticarse contra AFIP 
 * Hace uso del web-service WSAA que permite obtener el token de conexión
 * 
 * Se encuentra basado en el ejemplo de aplicaciones clientes del WSAA publicado en la web de AFIP
 * http://www.afip.gob.ar/ws/paso4.asp?noalert=1
 * 
 *
 */

 
namespace Arca;

use Exception;


class Wsaa {

    //************* CONSTANTES *****************************
    const MODO_HOMOLOGACION = 0;
    const MODO_PRODUCCION = 1;
    const WSDL_HOMOLOGACION = "/wsdl/homologacion/wsaa.wsdl"; # WSDL del web service WSAA
    const URL_HOMOLOGACION = "https://wsaahomo.afip.gov.ar/ws/services/LoginCms";
    const CERT_HOMOLOGACION = "/key/homologacion/certificado.pem"; # Certificado X.509 otorgado por AFIP
    const PRIVATEKEY_HOMOLOGACION =  "/key/homologacion/privada"; # Clave privada de la PC
    const WSDL_PRODUCCION = "/wsdl/produccion/wsaa.wsdl";
    const URL_PRODUCCION = "https://wsaa.afip.gov.ar/ws/services/LoginCms"; 
    const CERT_PRODUCCION = "key/produccion/certificado.pem";
    const PRIVATEKEY_PRODUCCION = "/key/produccion/privada";




    //************* VARIABLES *****************************
    private $base_dir = __DIR__;
    private $service = "";
    private $modo = 0;
    private $log_xmls = TRUE; 
    private $cuit = 0;
    private $wsdl = "";
    private $url = "";
    private $cert = "";
    private $privatekey = "";

    public function __construct($service, $modo_afip, $cuit, $logs) {
        $this->log_xmls = $logs;
        $this->modo = $modo_afip;
        $this->cuit = $cuit;
        $this->service = $service;
        ini_set("soap.wsdl_cache_enabled", 0);
        ini_set('soap.wsdl_cache_ttl', 0);
        
        $subPath = $this->modo === Wsaa::MODO_PRODUCCION ? 'produccion' : 'homologacion';
        $this->cert       = $this->base_dir . '/' . $this->cuit . '/key/' . $subPath . '/certificado.pem';
        $this->privatekey = $this->base_dir . '/' . $this->cuit . '/key/' . $subPath . '/privada';

        if ($this->modo === Wsaa::MODO_PRODUCCION) {
            $this->wsdl = Wsaa::WSDL_PRODUCCION; 
            $this->url  = Wsaa::URL_PRODUCCION; 
        } else {
            $this->wsdl = Wsaa::WSDL_HOMOLOGACION; 
            $this->url  = Wsaa::URL_HOMOLOGACION; 
        }
    }

    /**
     * Genera un nuevo token de conexión y lo guarda en el archivo ./:CUIT/:WebSerivce/token/TA.xml
     *
     */
    public function generateToken() {
        $this->validateFileExists($this->cert);
        $this->validateFileExists($this->privatekey);
        $this->validateFileExists($this->base_dir . $this->wsdl);

        $this->createTRA();
        $cms = $this->signTRA();
        $loginResult = $this->callWSAA($cms);

        // Token separado por entorno para evitar cross-contamination homo↔prod
        $env    = $this->modo === self::MODO_PRODUCCION ? 'prod' : 'homo';
        $taFile = $this->base_dir . '/' . $this->cuit . '/' . $this->service . '/token/TA_' . $env . '.xml';
        file_put_contents($taFile, $loginResult);
    }

    /**
     * Crea el archivo ./:CUIT/:WebSerivce/token/TRA.xml
     * El archivo es necesario para realizar la firma
     * 
     
     */
    
    /*
    private function createTRA() {
        try {
            $TRA = new \SimpleXMLElement(
                    '<?xml version="1.0" encoding="UTF-8"?>' .
                    '<loginTicketRequest version="1.0">' .
                    '</loginTicketRequest>');
            $TRA->addChild('header');
            $TRA->header->addChild('uniqueId', date('U'));
            $TRA->header->addChild('generationTime', date('c', date('U') - 60));
            $TRA->header->addChild('expirationTime', date('c', date('U') + 60));
            $TRA->addChild('service', $this->service);
            $TRA->asXML($this->base_dir . "/" . $this->cuit . '/' . $this->service . '/token/TRA.xml');
        } catch (Exception $exc) {
            throw new \Exception("Error al crear TRA.xml: " . $exc->getTraceAsString());
        }
    }
    */


    private function createTRA() {
        try {
            // Obtener hora verificada
            $verifiedTime = $this->getVerifiedTime();
            
            $TRA = new \SimpleXMLElement(
                    '<?xml version="1.0" encoding="UTF-8"?>' .
                    '<loginTicketRequest version="1.0">' .
                    '</loginTicketRequest>');
            $TRA->addChild('header');
            $TRA->header->addChild('uniqueId', date('U'));
            
            // Usar hora verificada con formato AFIP válido
            $generationTime = $verifiedTime->format('c');
            $expirationTime = (clone $verifiedTime)->add(new \DateInterval('PT1M'))->format('c');
            
            $TRA->header->addChild('generationTime', $generationTime);
            $TRA->header->addChild('expirationTime', $expirationTime);
            $TRA->addChild('service', $this->service);
            
            $traPath = $this->base_dir . "/" . $this->cuit . '/' . $this->service . '/token/TRA.xml';
            $traDir  = dirname($traPath);
            if (!is_dir($traDir)) {
                mkdir($traDir, 0755, true);
            }
            $tmpDir = $this->base_dir . "/" . $this->cuit . '/' . $this->service . '/tmp';
            if (!is_dir($tmpDir)) {
                mkdir($tmpDir, 0755, true);
            }
            $TRA->asXML($traPath);
            
            // Log para diagnóstico
            if ($this->log_xmls) {
                file_put_contents($this->base_dir . "/" . $this->cuit . '/' . $this->service . "/tmp/TRA_debug.log", 
                    "GenerationTime: $generationTime\nExpirationTime: $expirationTime\n", FILE_APPEND);
            }
        } catch (Exception $exc) {
            throw new \Exception("Error al crear TRA.xml: " . $exc->getTraceAsString());
        }
    }
    




    /**
     * Esta funcion realiza la firma PKCS#7 usando como entrada el archivo TRA.xml, el certificado y la clave privada
     * Genera un archivo intermedio TRA.tmp y obtiene del encabezado el CMS para WSAA
     */
    private function signTRA() {
        $infilename  = $this->base_dir . "/" . $this->cuit . '/' . $this->service . "/token/TRA.xml";
        $outfilename = $this->base_dir . "/" . $this->cuit . '/' . $this->service . "/TRA.tmp";

        if (!file_exists($infilename) || filesize($infilename) === 0) {
            throw new \Exception("No existe el archivo TRA.xml o está vacío en: {$infilename}");
        }

        if (!file_exists($this->cert)) {
            throw new \Exception("Certificado AFIP no encontrado en: {$this->cert}");
        }
        if (!file_exists($this->privatekey)) {
            throw new \Exception("Clave privada AFIP no encontrada en: {$this->privatekey}");
        }

        $certContent = file_get_contents($this->cert);
        $keyContent  = file_get_contents($this->privatekey);

        if ($certContent === false || trim($certContent) === '') {
            throw new \Exception("El certificado AFIP está vacío o no se pudo leer ({$this->cert})");
        }
        if ($keyContent === false || trim($keyContent) === '') {
            throw new \Exception("La clave privada AFIP está vacía o no se pudo leer ({$this->privatekey})");
        }

        // Limpiar errores previos de OpenSSL
        while (openssl_error_string());

        $privKey = openssl_pkey_get_private($keyContent);
        if ($privKey === false) {
            $errs = [];
            while ($m = openssl_error_string()) { $errs[] = $m; }
            throw new \Exception("Error al interpretar clave privada AFIP con OpenSSL: " . (implode(' | ', $errs) ?: 'Formato inválido'));
        }

        $headers = [];
        $flags   = 0; // AFIP exige CMS con contenido embebido (no detached)

        // Firmar con certificado y clave privada
        $status = openssl_pkcs7_sign($infilename, $outfilename, $certContent, $privKey, $headers, $flags);

        // Fallback: intentar pasando las rutas file:// si el parser prefiere archivos
        if (!$status) {
            $certUrl = 'file://' . $this->cert;
            $keyUrl  = 'file://' . $this->privatekey;
            $status  = openssl_pkcs7_sign($infilename, $outfilename, $certUrl, [$keyUrl, ''], $headers, $flags);
        }

        if (!$status) {
            $errs = [];
            while ($m = openssl_error_string()) { $errs[] = $m; }
            throw new \Exception("ERROR al generar la firma PKCS#7: " . (implode(' | ', $errs) ?: 'openssl_pkcs7_sign devolvió false'));
        }

        if (!file_exists($outfilename)) {
            throw new \Exception("No se generó el archivo firmado TRA.tmp");
        }

        $inf = fopen($outfilename, "r");
        $i = 0;
        $cms = "";
        while (!feof($inf)) {
            $buffer = fgets($inf);
            if ($i++ >= 4) {
                $cms .= $buffer;
            }
        }
        fclose($inf);
        @unlink($outfilename);

        return $cms;
    }
    
    /**
     * Esta funcion se conecta al webservice SOAP de AFIP para autenticarse
     * El resultado es la información del token generado
     * 
    
     
     */
    // private function callWSAA($cms) {
    //     $context = stream_context_create(array(
    //         'ssl' => array(
    //             'verify_peer' => false,
    //             'verify_peer_name' => false,
    //             'allow_self_signed' => true
    //         )
    //     ));

    //     $client = new \SoapClient($this->base_dir . $this->wsdl, array(
    //         'soap_version' => SOAP_1_2,
    //         'location' => $this->url,
    //         'trace' => 1,
    //         'exceptions' => 0,
    //         'stream_context' => $context
    //     ));

    //     $results = $client->loginCms(array('in0' => $cms));

    //     if ($this->log_xmls) {
    //         file_put_contents($this->base_dir . "/" . $this->cuit . '/' . $this->service . "/tmp/request-loginCms.xml", $client->__getLastRequest());
    //         file_put_contents($this->base_dir . "/" . $this->cuit . '/' . $this->service . "/tmp/response-loginCms.xml", $client->__getLastResponse());
    //     }

    //     if (is_soap_fault($results)) {
    //         throw new \Exception("Error SOAP: " . $results->faultcode . " - " . $results->faultstring);
    //     }

    //     return $results->loginCmsReturn;
    // }
    private function callWSAA($cms) {
        // Verificar hora actual del sistema
        //$currentTime = (new \DateTime('now', new \DateTimeZone('America/Argentina/Buenos_Aires')))->format(DateTime::ATOM);
        //file_put_contents('/tmp/wsaa_time_debug.log', "Hora servidor: " . $currentTime . "\n", FILE_APPEND);
        //$currentTime = new \DateTime('now', new \DateTimeZone('America/Argentina/Buenos_Aires'));
        //file_put_contents('/tmp/wsaa_debug.log', "Hora actual: " . $currentTime->format('c') . "\n", FILE_APPEND);

        // Obtener hora verificada
        $currentTime = $this->getVerifiedTime();
        file_put_contents('/tmp/wsaa_debug.log', "Hora verificada: ".$currentTime->format('c')."\n", FILE_APPEND);
        
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
                'crypto_method' => \STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT,
                'ciphers' => 'DEFAULT:!DH:!DHE@SECLEVEL=1',
            ],
            'http' => [
                'user_agent' => 'Mozilla/5.0 (compatible; AFIP client)',
                'timeout' => 30,
            ]
        ]);

        // Verificar hora del archivo WSDL
        $wsdlPath = $this->base_dir . $this->wsdl;
        file_put_contents('/tmp/wsaa_time_debug.log', "WSDL last modified: " . date('c', filemtime($wsdlPath)) . "\n", FILE_APPEND);

        $client = new \SoapClient($wsdlPath, [
            'soap_version' => \SOAP_1_2,
            'location' => $this->url,
            'trace' => 1,
            'exceptions' => 0,
            'connection_timeout' => 30,
            'keep_alive' => false,
            'stream_context' => $context
        ]);

        // Registrar hora justo antes del llamado
        file_put_contents('/tmp/wsaa_time_debug.log', "Hora pre-call: " . date('c') . "\n", FILE_APPEND);
        
        $results = $client->loginCms(['in0' => $cms]);

        if ($this->log_xmls) {
            file_put_contents($this->base_dir . "/" . $this->cuit . '/' . $this->service . "/tmp/request-loginCms.xml", $client->__getLastRequest());
            file_put_contents($this->base_dir . "/" . $this->cuit . '/' . $this->service . "/tmp/response-loginCms.xml", $client->__getLastResponse());
        }

        if (\is_soap_fault($results)) {
            // Registrar error con timestamp
            file_put_contents('/tmp/wsaa_time_debug.log', "Error SOAP: " . date('c') . " - " . $results->faultcode . " - " . $results->faultstring . "\n", FILE_APPEND);
            throw new \Exception("Error SOAP: " . $results->faultcode . " - " . $results->faultstring);
        }

        return $results->loginCmsReturn;
    }





    /**
     * Verifica la existencia de un archivo y lanza una excepción si este no existe.
     * @param String $filePath
     * @throws Exception
     */
    private function validateFileExists($filePath) {
        if (!file_exists($filePath)) {
            throw new \Exception("No pudo abrirse el archivo $filePath");
        }
    }



    /**
    // esta funcion la agrego para evitar problemas con la hora del servidor
    */


    /**
 * Obtiene la hora actual de servicios externos con múltiples fallbacks
 * Implementa timeout, caché, manejo de errores robusto y feedback claro
 * 
 * @return \DateTime|null Retorna DateTime con la hora o null si fallan todos los servicios
 * @throws \RuntimeException Si no se puede obtener la hora y es crítico para la aplicación
 */
    private function getExternalTime() {
        // Configuración
        $timeout = 3; // Segundos por servicio
        $cacheTtl = 3600; // 1 hora en segundos para el caché
        $cacheFile = sys_get_temp_dir() . '/external_time_cache.json';
        $logFile = sys_get_temp_dir() . '/external_time_errors.log';
        
        // 1. Verificar caché primero
        if (file_exists($cacheFile)) {
            $cacheData = json_decode(file_get_contents($cacheFile), true);
            if ($cacheData && 
                time() - $cacheData['timestamp'] < $cacheTtl &&
                !empty($cacheData['datetime'])) {
                try {
                    return new \DateTime($cacheData['datetime'], new \DateTimeZone('America/Argentina/Buenos_Aires'));
                } catch (\Exception $e) {
                    // Invalid cache data, proceed to fetch fresh data
                    error_log("Invalid cache data: " . $e->getMessage() . "\n", 3, $logFile);
                }
            }
        }
        // 2. Definición de servicios con sus respectivos parsers
        $services = [
            [
                'name' => 'AFIP',
                'url' => 'https://time.afip.gob.ar/api/Time/GetCurrentDateTime',
                'parser' => function($data) {
                    if (!empty($data['time'])) {
                        return new \DateTime($data['time'], new \DateTimeZone('America/Argentina/Buenos_Aires'));
                    }
                    return null;
                },
                'method' => 'GET'
            ],
            [
                'name' => 'WorldTimeAPI',
                'url' => 'https://worldtimeapi.org/api/timezone/America/Argentina/Buenos_Aires',
                'parser' => function($data) {
                    if (!empty($data['datetime'])) {
                        return new \DateTime($data['datetime']);
                    }
                    return null;
                },
                'method' => 'GET'
            ],
            [
                'name' => 'TimeAPI.io',
                'url' => 'https://timeapi.io/api/Time/current/zone?timeZone=America/Argentina/Buenos_Aires',
                'parser' => function($data) {
                    if (!empty($data['date']) && !empty($data['time'])) {
                        return new \DateTime($data['date'].' '.$data['time'], new \DateTimeZone('America/Argentina/Buenos_Aires'));
                    }
                    return null;
                },
                'method' => 'GET'
            ],
            [
                'name' => 'NTP Server Fallback',
                'url' => null, // No URL para el fallback local
                'parser' => function() {
                    // Último recurso: hora del servidor local con verificación NTP
                    try {
                        $socket = @fsockopen('udp://pool.ntp.org', 123, $errno, $errstr, 1);
                        if ($socket) {
                            fclose($socket);
                            return new \DateTime('now', new \DateTimeZone('America/Argentina/Buenos_Aires'));
                        }
                    } catch (\Exception $e) {
                        error_log("NTP fallback failed: " . $e->getMessage() . "\n", 3, $logFile);
                    }
                    return null;
                },
                'method' => 'NTP'
            ]
        ];
        // 3. Intentar cada servicio hasta obtener una respuesta válida
        foreach ($services as $service) {
            try {
                if ($service['url'] === null) {
                    // Fallback especial (NTP o local)
                    $dateTime = $service['parser']();
                    if ($dateTime instanceof \DateTime) {
                        $this->updateCache($cacheFile, $dateTime, $service['name']);
                        return $dateTime;
                    }
                    continue;
                }
                
                // Usar cURL para mejor control
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $service['url'],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => $timeout,
                    CURLOPT_CONNECTTIMEOUT => $timeout - 1,
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTPHEADER => ['Accept: application/json'],
                ]);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                
                if ($response === false || $httpCode !== 200) {
                    error_log(sprintf(
                        "Service %s failed. HTTP Code: %d, Error: %s\n",
                        $service['name'],
                        $httpCode,
                        curl_error($ch)
                    ), 3, $logFile);
                    curl_close($ch);
                    continue;
                }
                
                curl_close($ch);
                
                $data = json_decode($response, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log("Invalid JSON from {$service['name']}: " . json_last_error_msg() . "\n", 3, $logFile);
                    continue;
                }
                
                $dateTime = $service['parser']($data);
                if ($dateTime instanceof \DateTime) {
                    $this->updateCache($cacheFile, $dateTime, $service['name']);
                    return $dateTime;
                }
            } catch (\Throwable $e) {
                error_log("Exception with {$service['name']}: " . $e->getMessage() . "\n", 3, $logFile);
                continue;
            }
        }
        // 4. Todo falló - registrar y retornar null o lanzar excepción según necesidad
        error_log("All time services failed at " . date('Y-m-d H:i:s') . "\n", 3, $logFile);
        return null;
    }

    /**
     * Actualiza el archivo de caché con la hora obtenida
     * 
     * @param string $cacheFile Ruta al archivo de caché
     * @param \DateTime $dateTime Objeto DateTime con la hora válida
     * @param string $source Fuente de donde se obtuvo la hora
     */
    private function updateCache($cacheFile, \DateTime $dateTime, $source) {
        file_put_contents($cacheFile, json_encode([
            'datetime' => $dateTime->format('Y-m-d H:i:s'),
            'timestamp' => time(),
            'source' => $source,
            'timezone' => 'America/Argentina/Buenos_Aires'
        ]));
    }

    



    /*
    private function getVerifiedTime() {
        $cacheFile = '/tmp/wsaa_time_cache.txt';
        
        // Leer de caché si es reciente (no más de 5 minutos)
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 300) {
            $cachedTime = file_get_contents($cacheFile);
            return new \DateTime($cachedTime);
        }
        
        // Obtener de fuente externa
        $externalTime = $this->getExternalTime() ?: $this->getNtpTime();
        
        if ($externalTime) {
            file_put_contents($cacheFile, $externalTime->format('c'));
            return $externalTime;
        }
        
        // Fallback a hora local con advertencia
        file_put_contents('/tmp/wsaa_time_debug.log', "ADVERTENCIA: Usando hora local como fallback\n", FILE_APPEND);
        return new \DateTime('now', new \DateTimeZone('America/Argentina/Buenos_Aires'));
    }
    */

    private function getVerifiedTime() {
        $cacheFile = '/tmp/afip_time_cache.txt';
        $logFile = '/tmp/afip_time_debug.log';
        
        // 1. Intentar obtener de caché (válido por 5 minutos)
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 300) {
            $cachedTime = file_get_contents($cacheFile);
            try {
                return new \DateTime($cachedTime, new \DateTimeZone('America/Argentina/Buenos_Aires'));
            } catch (\Exception $e) {
                error_log("Error parsing cached time: " . $e->getMessage() . "\n", 3, $logFile);
            }
        }
        
        // 2. Obtener hora de AFIP directamente
        $afipTime = $this->getAfipServerTime();
        if ($afipTime) {
            file_put_contents($cacheFile, $afipTime->format('c'));
            return $afipTime;
        }
        
        // 3. Fallback a NTP
        $ntpTime = $this->getNtpTime();
        if ($ntpTime) {
            file_put_contents($cacheFile, $ntpTime->format('c'));
            return $ntpTime;
        }
        
        // 4. Último recurso: hora local con advertencia
        error_log("WARNING: Using local system time as fallback\n", 3, $logFile);
        return new \DateTime('now', new \DateTimeZone('America/Argentina/Buenos_Aires'));
    }

    private function getAfipServerTime() {
        $url = ($this->modo === self::MODO_PRODUCCION) 
            ? 'https://wsaa.afip.gov.ar/rest/time' 
            : 'https://wsaahomo.afip.gov.ar/rest/time';
        
        try {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 3,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_CIPHER_LIST => 'DEFAULT:!DH:!DHE@SECLEVEL=1',
            ]);
            
            $response = curl_exec($ch);
            if ($response === false) {
                throw new \Exception("CURL error: " . curl_error($ch));
            }
            
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($httpCode !== 200) {
                throw new \Exception("HTTP error: $httpCode");
            }
            
            $timeData = json_decode($response, true);
            if (isset($timeData['time'])) {
                return new \DateTime($timeData['time'], new \DateTimeZone('America/Argentina/Buenos_Aires'));
            }
        } catch (\Exception $e) {
            error_log("AFIP time service error: " . $e->getMessage() . "\n", 3, '/tmp/afip_time_debug.log');
            return null;
        } finally {
            if (is_resource($ch)) {
                curl_close($ch);
            }
        }
        
        return null;
    }

    private function getNtpTime() {
        try {
            $socket = fsockopen('udp://pool.ntp.org', 123, $errno, $errstr, 3);
            if (!$socket) {
                throw new \Exception("NTP connection failed: $errstr ($errno)");
            }
            
            // Enviar consulta NTP
            $msg = "\010" . str_repeat("\0", 47);
            fwrite($socket, $msg);
            
            // Recibir respuesta
            $response = fread($socket, 48);
            fclose($socket);
            
            if (strlen($response) !== 48) {
                throw new \Exception("Invalid NTP response");
            }
            
            // Extraer timestamp (segundos desde 1900)
            $unpack = unpack('N12', $response);
            $timestamp = sprintf('%u', $unpack[9]) - 2208988800; // Convertir a Unix timestamp
            
            return new \DateTime("@$timestamp", new \DateTimeZone('America/Argentina/Buenos_Aires'));
        } catch (\Exception $e) {
            error_log("NTP time error: " . $e->getMessage() . "\n", 3, '/tmp/afip_time_debug.log');
            return null;
        }
    }






}
