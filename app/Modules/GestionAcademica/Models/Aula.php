<?php

namespace App\Modules\GestionAcademica\Models;

use Illuminate\Database\Eloquent\Model;

class Aula extends Model
{
    protected $table = 'aula';

    protected $primaryKey = 'id_aula';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'capacidad',
    ];
}
