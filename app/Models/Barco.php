<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barco extends Model
{
    protected $table = 'barcos';

    protected $primaryKey = 'ID_barco';

    public $timestamps = false;
}
