<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Personaje extends Model
{
    protected $table = 'personajes';

    protected $primaryKey = 'ID_personaje';

    public $timestamps = false;
}
