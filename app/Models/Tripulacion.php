<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tripulacion extends Model
{
    protected $table = 'tripulaciones';

    protected $primaryKey = 'ID_tripulacion';

    public $timestamps = false;
}
