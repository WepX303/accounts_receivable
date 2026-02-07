<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AvshocrecatReport extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'avshocrecat_report';

    // report tablo => genelde insert/truncate ile doluyor; guard kapatalım
    protected $guarded = [];

    public $timestamps = true;
}