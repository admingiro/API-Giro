<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmpRh extends Model
{
    public $timestamps = false;
    public $incrementing = false;

    protected $connection = 'empresa';
    protected $keyType = 'string';
    protected $primaryKey = 'CLAVE';
    protected $table = 'Supervisor_giro.EmpRh';
    protected $fillable = [
        'CLAVE',
        'CALLE',
        'NUMERO',
        'INTERIOR',
        'COLONIA',
        'SECTOR',
        'CP',
        'MUNICIPIO',
        'ESTADO',
        'TELEFONO_1',
        'TELEFONO_2',
        'FECHA_NACIMIENTO',
        'LUGAR_NACIMIENTO',
        'ESTADO_CIVIL',
        'ESCOLARIDAD',
        'SEXO',
        'TELEFONO_CELULAR',
        'GRADUACION',
        'EMERGENCIA_CONTACTO',
        'EMERGENCIA_PARENTESCO',
        'EMERGENCIA_TELEFONO',
        'ESTADO_NACIMIENTO',
        'NACIONALIDAD',
        'EMAIL',
    ];
}
