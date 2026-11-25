<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmpJefe extends Model
{
    public $timestamps = false;
    public $incrementing = false;

    protected $connection = 'empresa';
    protected $keyType = 'string';
    protected $primaryKey = 'CLAVE';
    protected $table = 'Supervisor_giro.EMP_JEFE';
    protected $fillable = [
        'CLAVE',
        'FECHA_INICIO',
        'FECHA_FIN',
        'ID_JEFE',
        'ID_DIRECTOR',
        'ID_SUPERVISOR',
        'NOTA',
        'FECHA_CAPTURA',
    ];
}