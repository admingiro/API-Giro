<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmpSdo extends Model
{
    public $timestamps = false;
    public $incrementing = false;

    protected $connection = 'empresa';
    protected $keyType = 'string';
    protected $primaryKey = 'CLAVE';
    protected $table = 'Supervisor_giro.EmpSdo';
    protected $fillable = [
        'CLAVE',
        'FECHA',
        'ID',
        'TIPO',
        'SDO1',
        'SDO2',
        'SDO3',
        'SDO4',
        'SDO5',
        'BAJA_IMSS',
        'CAUSA_BAJA',
        'FECHA_SALIDA',
        'AVISO_IMSS',
        'NOTA',
        'FECHA_CAPTURA',
        'ENVIO_AVISO_IMSS',
        'AUMENTO_SALARIAL',
    ];
}
