<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Factor extends Model
{
    public $timestamps = false;
    public $incrementing = false;

    protected $connection = 'empresa';
    protected $keyType = 'string';
    protected $primaryKey = 'CLAVE';
    protected $table = 'Supervisor_giro.Factor';
    protected $fillable = [
        'CLAVE',
        'ANT_INICIAL',
        'ANT_FINAL',
        'FACTOR',
        'CUOTA_FIJA',
        'DIAS_DE_VACACIONES',
        'PRIMA_VACACIONAL',
        'DIAS_DE_AGUINALDO',
        'NUMERO_COMIDAS',
        'DIAS_EXTRAS',
    ];
}