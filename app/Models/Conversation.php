<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class Conversation extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'user_id',
        'title',
        'model',
        'uuid'
    ];

    protected $casts = [
        'uuid' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($conversation) {
            $conversation->messages()->delete();
        });
    }

    // Méthode pour obtenir l'URL de partage
    public function getShareUrlAttribute()
    {
        return route('conversation.share', $this->uuid);
    }
}
