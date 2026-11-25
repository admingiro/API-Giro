<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmpPrin extends Model
{
    public $timestamps = false;
    public $incrementing = false;

    protected $connection = 'empresa';
    protected $keyType = 'string';
    protected $primaryKey = 'CLAVE';
    protected $table = 'Supervisor_giro.EmpPrin';
    protected $fillable = [
        'CLAVE',
        'NOMBREN',
        'NOMBREP',
        'NOMBREM',
        'INGRESO',
        'PLANTA',
        'FOTO',
        'PE',
        'RFC',
        'TIPO_NOM',
        'TIPO_PAGO',
        'TURNO',
        'CTA_TARJETA',
        'PTU',
        'CUOTA_SINDICAL',
        'VALOR_MINUTO',
        'AFILIACION',
        'BANCO_SAR',
        'SUCURSAL',
        'CTA_CHEQUES',
        'CONTROL_INTERNO',
        'APORTACION_SAR',
        'APORTACION_INFONAVIT',
        'CREDITO_INFONAVIT',
        'INICIO_DESCUENTO_INF',
        'TIPO_DESCUENTO',
        'VALOR_DESCUENTO',
        'CURP',
        'JORNADA_REDUCIDA',
        'SEMANA_REDUCIDA',
        'CALENDARIO',
        'COSTO_COMEDOR',
        'BLOQUEAR_COMEDOR',
        'FECHA_CAPTURA',
        'SDO_VAC_INI',
        'SDO_VAC_INI_PRIMA',
        'TURNO_ROTATIVO',
        'TURNOINT',
        'CHECADORES',
        'SUPERVISOR_RELOJ',
        'ACCESOS_ILIMITADOS',
        'TOLERANCIA_RETARDO',
        'NO_REQUIERE_CHECAR',
        'BAJA_ADMINISTRATIVA',
        'FECHA_BAJA_ADMINISTRATIVA',
        'BANCO',
        'PENSION',
        'REGIMEN',
        'SUSPENSION',
        'FECHA_SUSPENSION',
        'AVISO',
        'TIPO_SALARIO',
        'CLABE_INTERBANCARIA',
        'UNIDAD_MEDICA',
        'ORIGEN',
        'TIPO_CONTRATO',
        'ACCESO_WEB',
        'ACTIVA_JEFE',
    ];
}
