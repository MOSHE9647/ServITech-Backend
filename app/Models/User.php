<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     * This means you can use the create() method to insert
     * data ONLY into these fields.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

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

    /**
     * Get the user's initials from the first name and the last name
     *
     * @return string
     */
    public function initials(): string
    {
        return $this->initials();
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * Send the password reset notification to the user.
     */
    public function sendPasswordResetNotification($token)
    {
        $locale = app()->getLocale();
        $appUrl = config('app.url');
        $email = urlencode($this->email);
        $url = "{$appUrl}/{$locale}/reset-password?token={$token}&email={$email}";
        $this->notify(new ResetPasswordNotification($url));
    }

    /**
     * Define a one-to-many relationship with the SupportRequest model.
     * This means that each user can have multiple support requests.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<SupportRequest, User>
     */
    public function supportRequests()
    {
        return $this->hasMany(SupportRequest::class);
    }
}
