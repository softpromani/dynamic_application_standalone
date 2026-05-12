<?php

namespace Softpro\Core\Models;

use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    protected $fillable = [
        'title',
        'type',
        'file_path',
        'link_url',
        'is_active',
        'sort_order',
    ];

    protected $appends = ['url'];

    public function getUrlAttribute()
    {
        if ($this->type === 'file') {
            return $this->file_path ? asset('storage/' . $this->file_path) : null;
        }

        return $this->link_url;
    }
}
