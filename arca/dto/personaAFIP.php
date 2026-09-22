<?php

namespace Arca\dto;

use Arca\dto\DomicilioFiscalAFIP;
use Arca\dto\MonotributoAFIP;
use Arca\dto\RegimenGeneralAFIP;

class PersonaAFIP {

    public $apellido        = "";
    public $domicilioFiscal = null;
    public $estadoClave     = "";
    public $idPersona       = 0;
    public $mesCierre       = 0;
    public $nombre          = "";
    public $tipoClave       = "";
    public $tipoPersona     = "";
    public $razonSocial     = "";
    public $datosRegimenGeneral = null;
    public $datosMonotributo    = null;

    public function __construct($responseObject)
    {
        $this->_map($responseObject);
    }

    private function _map($responseObject) {

        $datosGenerales = null;
        if (isset($responseObject->datosGenerales)) {
            $datosGenerales = $responseObject->datosGenerales;
        } else {
            $datosGenerales = $responseObject->persona;
        }

        $this->tipoPersona = $datosGenerales->tipoPersona ?? '';

        // ── Campos comunes (FISICA y JURIDICA) ─────────────────────────────────
        if (isset($datosGenerales->idPersona)) {
            $this->idPersona = $datosGenerales->idPersona;
        }
        if (isset($datosGenerales->estadoClave)) {
            $this->estadoClave = $datosGenerales->estadoClave;
        }
        if (isset($datosGenerales->tipoClave)) {
            $this->tipoClave = $datosGenerales->tipoClave;
        }
        if (isset($datosGenerales->mesCierre)) {
            $this->mesCierre = $datosGenerales->mesCierre;
        }

        // ── Persona FISICA ────────────────────────────────────────────────────
        if ($this->tipoPersona === 'FISICA') {
            $this->apellido = $datosGenerales->apellido ?? '';
            $this->nombre   = $datosGenerales->nombre   ?? '';
            // Construir razonSocial desde nombre + apellido si no viene explícita
            if (isset($datosGenerales->nombre)) {
                $this->razonSocial = trim(
                    ($datosGenerales->apellido ?? '') . ' ' . ($datosGenerales->nombre ?? '')
                );
            }
        }

        // ── Persona JURIDICA ──────────────────────────────────────────────────
        if ($this->tipoPersona === 'JURIDICA') {
            // La razón social puede venir en datosGenerales o en la raíz
            $this->razonSocial = $datosGenerales->razonSocial
                ?? $responseObject->razonSocial
                ?? '';
        }

        // ── razonSocial genérico (override si viene en la raíz) ───────────────
        if (!empty($responseObject->razonSocial)) {
            $this->razonSocial = $responseObject->razonSocial;
        }

        // ── Domicilio fiscal ──────────────────────────────────────────────────
        // PadronA13: datosGenerales->domicilioFiscal
        if (isset($datosGenerales->domicilioFiscal)) {
            $this->domicilioFiscal = new DomicilioFiscalAFIP($datosGenerales->domicilioFiscal);
        }
        // PadronA5: datosGenerales->domicilio[] → primer elemento
        if (isset($datosGenerales->domicilio) && count($datosGenerales->domicilio) > 0) {
            $this->domicilioFiscal = new DomicilioFiscalAFIP($datosGenerales->domicilio[0]);
        }

        // ── Regímenes ─────────────────────────────────────────────────────────
        if (isset($responseObject->datosRegimenGeneral)) {
            $this->datosRegimenGeneral = new RegimenGeneralAFIP($responseObject->datosRegimenGeneral);
        }
        if (isset($responseObject->datosMonotributo)) {
            $this->datosMonotributo = new MonotributoAFIP($responseObject->datosMonotributo);
        }
    }
}
