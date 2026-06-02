<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NaicsIndex extends Model
{
   protected $table = 'naics_indexes';
    
   protected $fillable = [
        'naics_code_id',
        'index_description'
    ];

    public function naicsCode()
    {
        return $this->belongsTo(NaicsCode::class);
    }
}
