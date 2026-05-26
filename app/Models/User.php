<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'password', 'tag', 'avatar'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $appends = ['avatar_url'];
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user)
        {
            if(!$user->tag) {
                $user->tag = self::generateUniqueTag();
            }
        });
    }

    private static function generateUniqueTag(): string
    {
        do{
            $tag = 'user_' . Str::lower(Str::random(8));
        } while (self::where('tag', $tag)->exists());

        return $tag;
    }

    public function getAvatarUrlAttribute()
    {
        if ($this->avatar){
            return asset('storage/' . $this->avatar);
        }
        return null;
    }

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class)
                    ->withTimestamps()
                    ->withPivot('cleared_at');
    }
}
