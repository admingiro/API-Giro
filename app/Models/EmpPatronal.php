<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmpPatronal extends Model
{
    public $timestamps = false;
    public $incrementing = false;

    protected $connection = 'empresa';
    protected $keyType = 'string';
    protected $primaryKey = 'CLAVE';
    protected $table = 'Supervisor_giro.EmpPatronal';
    protected $fillable = [
        'CLAVE',
        'FECHA_ENTRADA',
        'FECHA_SALIDA',
        'CATALOGO',
        'NOTA',
        'FECHA_CAPTURA',
    ];
}
