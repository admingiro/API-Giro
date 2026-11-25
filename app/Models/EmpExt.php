<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmpExt extends Model
{
    public $timestamps = false;
    public $incrementing = false;

    protected $connection = 'empresa';
    protected $keyType = 'string';
    protected $primaryKey = 'CLAVE';
    protected $table = 'Supervisor_giro.EmpExt';
    protected $fillable = [
        'CLAVE',
        'SEGURO_GASTOS_MED_MAYORES',
        'SEGURO_DE_VIDA',
        'CLABE_INTERBANCARIA',
        'TARJETA_SI_VALE',
        'BANCO_CFDI',
        'REGIMEN',
        'EMAIL',
        'NO_AJUSTE_ANUAL',
        'SUSPENSION_INFONAVIT',
        'SALDO_VACACIONES',
        'FECHA_SALDO_VACACIONES',
        'SALDO_PRIMA_VACACIONAL',
        'FECHA_FOI',
        'SALDO_VACACIONES_FI',
        'SALDO_PRIMA_VACACIONAL_FI',
        'SALDO_VACACIONES_FOI',
        'SALDO_PRIMA_VACACIONAL_FOI',
        'TIPO_CONTRATO',
        'DEPTO_PORTAL',
        'DIRECCION',
        'CATEGORIA',
        'NIVEL',
        'FECHA_ANTIGUEDAD',
        'NIVEL_EXTRA',
        'NOTA',
        'ID_JEFE',
        'ID_DIRECTOR',
        'EMPRESA',
        'CODIGO_POSTAL_FISCAL',
        'NOMBRE_FISCAL',
    ];
}