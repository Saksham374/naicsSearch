<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NaicsCode extends Model
{
    protected $fillable = [
        'naics_code',
        'description'
    ];

   protected $table = 'naics_codes';


    public function indexes()
    {
        return $this->hasMany(NaicsIndex::class);
    }
}