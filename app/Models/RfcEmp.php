<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RfcEmp extends Model
{
    public $timestamps = false;
    public $incrementing = false;

    protected $keyType = 'string';
    protected $primaryKey = 'CLAVE';
    protected $table = 'Supervisor_giro.RFCEMP';
    protected $fillable = [
        'CLAVE',
        'NOMBRE',
        'CALLE',
        'NUMERO',
        'INTERIOR',
        'COLONIA',
        'SECTOR',
        'CP',
        'TELEFONO_1',
        'TELEFONO_2',
        'RFC',
        'PATH_TRABAJO',
        'PATH_FOTOS',
        'PATH_REPORTES',
        'PATH_COMPILADOS',
        'MUNICIPIO',
        'CIUDAD',
        'FAX',
        'REGIMEN_FISCAL',
        'ESTADO',
        'ORGANIGRAMA',
        'BASE_ACTIVA'
    ];
}