<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AvshocrecatReport extends Model
{
    protected $connection = 'pgsql';

    protected $table = 'avshocrecat_report';

    protected $guarded = [];

    public $timestamps = true;
}
