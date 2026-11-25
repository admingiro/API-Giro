<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmpEducacion extends Model
{
    public $timestamps = false;
    public $incrementing = false;

    protected $connection = 'empresa';
    protected $keyType = 'string';
    protected $primaryKey = 'CLAVE';
    protected $table = 'Supervisor_giro.Emp_Educacion';
    protected $fillable = [
        'CLAVE',
        'NIVEL_ESCOLARIDAD',
        'INSTITUCION',
        'GRADO_MAX_ESTUDIOS',
        'ESTATUS',
        'COMPROBANTE_ESTUDIOS',
        'CEDULA_PROFESIONAL',
        'CARRERA',
        'NIVEL_ESTUDIOS_ACT',
        'INSTITUCION_EST_ACT',
        'HORARIOS_EST_ACT',
        'GRADO_EST_ACT',
        'CERTIFICADOS',
        'NIVEL_INGLES',
        'AREA_ESTUDIO'
    ];
}