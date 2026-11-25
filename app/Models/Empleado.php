<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empleado extends Model
{
    public $timestamps = false;
    public $incrementing = false;

    protected $connection = 'empresa';
    // protected $keyType = 'string';
    // protected $primaryKey = 'CLAVE';
    protected $table = 'Supervisor_giro.vista_empleado';
    // protected $fillable = [
    //     'CLAVE',
    // ];
}