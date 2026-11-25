<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmpTipoEmp extends Model
{
    public $timestamps = false;
    public $incrementing = false;

    protected $connection = 'empresa';
    protected $keyType = 'string';
    protected $primaryKey = 'CLAVE';
    protected $table = 'Supervisor_giro.EmpTipoEmp';
    protected $fillable = [
        'CLAVE',
        'FECHA_ENTRADA',
        'FECHA_SALIDA',
        'CATALOGO',
        'NOTA',
        'FECHA_CAPTURA',
    ];
}