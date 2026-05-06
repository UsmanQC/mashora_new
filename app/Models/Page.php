<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model
{
    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'pages';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'id';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['slug', 'title', 'title_ar', 'content', 'content_ar', 'use_for'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'slug', 'use_for', 'deleted_at', 'created_at', 'updated_at'
    ];

    public function getContentLocalAttribute($value)
    {
        return \App::getLocale() == 'en' ? $this->content : $this->content_ar;
    }
}
