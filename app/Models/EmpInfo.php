<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmpInfo extends Model
{
    public $timestamps = false;
    public $incrementing = false;

    protected $connection = 'empresa';
    protected $keyType = 'string';
    protected $primaryKey = 'CLAVE';
    protected $table = 'Supervisor_giro.EmpInfo';
    protected $fillable = [
        'CLAVE',
        'FECHA_ENTRADA',
        'FECHA_SALIDA',
        'CREDITO_INFONAVIT',
        'FECHA_CAPTURA',
        'INICIO_DESCUENTO_INF',
        'TIPO_MOVIMIENTO',
        'TIPO_DESCUENTO',
        'VALOR_DESCUENTO',
        'APORTACION_INFONAVIT',
        'APLICA_TABLA_DISMINUCION',
        'NOTA',
    ];
}
