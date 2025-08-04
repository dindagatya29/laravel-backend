<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Folder extends Model
{
    protected $fillable = ['name'];

    public function files()
    {
        return $this->hasMany(File::class);
    }
    public function folder()
{
    return $this->belongsTo(Folder::class);
}

}
