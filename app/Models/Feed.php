<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feed extends Model
{
    protected $fillable = ['title','image', 'visibility','user_id'];

    public function getImageAttribute($image)
    {
        $defaultImage = 'default_post.png';
        return asset('uploads/post_images/' . ($image ?? $defaultImage));
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class)->whereNull('parent_id');
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }
}
