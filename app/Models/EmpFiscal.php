<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmpFiscal extends Model
{
    public $timestamps = false;
    public $incrementing = false;

    protected $connection = 'empresa';
    protected $keyType = 'string';
    protected $primaryKey = 'CLAVE';
    protected $table = 'Supervisor_giro.EmpFiscal';
    protected $fillable = [
        'CLAVE',
        'NOMBRE',
        'CALLE',
        'NUMERO',
        'INTERIOR',
        'COLONIA',
        'CP',
        'MUNICIPIO',
        'ESTADO',
    ];
}
