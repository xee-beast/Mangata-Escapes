<?php

namespace App;

use App\Models\AuthLog;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasRoles, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name', 'last_name', 'email', 'password', 'username',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Set the user's email address.
     *
     * @param  string  $value
     * @return void
     */
    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = strtolower($value);
    }

    /**
     * Set the user's first name.
     *
     * @param  string  $value
     * @return void
     */
    public function setFirstNameAttribute($value)
    {
        $this->attributes['first_name'] = ucwords($value);
    }

    /**
     * Set the user's last name.
     *
     * @param  string  $value
     * @return void
     */
    public function setLastNameAttribute($value)
    {
        $this->attributes['last_name'] = ucwords($value);
    }

    /**
     * Verify if the user is super admin.
     */
    public function isSuper()
    {
        return $this->hasRole('super admin');
    }

    /**
     * Verify if the user is super admin or admin.
     */
    public function isAdmin()
    {
        return $this->hasAnyRole(['super admin', 'admin']);
    }

    /**
     * Get the user's travel agent model.
     */
    public function travel_agent()
    {
        return $this->hasOne('App\Models\TravelAgent');
    }

    public function sendEmailVerificationNotification()
    {
        return false;
    }

    public function authLogs() {
        return $this->hasMany(AuthLog::class);
    }

    public function hasPermission($permission) {
        return $this->roles->contains(function ($role) use ($permission) {
            return $role->hasPermissionTo($permission);
        });
    }
}
