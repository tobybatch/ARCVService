<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


/**
 * @property string $name
 * @property string $prefix
 * @property string $print_pref
 * @property Sponsor $sponsor
 * @property Registration[] $registrations
 * @property CentreUser[] $centreUsers
 * @property Centre[] $neighbours
 * @property Family[] $families
 */
class Centre extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'prefix', 'print_pref', 'sponsor_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    public function nextCentreSequence()
    {
        // Get the last family
        $last_family = $this->families()->orderByDesc('centre_sequence')->first();

        // Set a default
        $sequence = 1;

        // Override it if the family has a sequence.
        if ($last_family && $last_family->centre_sequence) {
            $sequence = $last_family->centre_sequence +1;
        }

        return $sequence;
    }

    /**
     * Get the Registrations for this Centre
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function registrations()
    {
        return $this->hasMany('App\Registration');
    }

    /**
     * Get the CentreUsers who belong to this Centre
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsToMany
     */
    public function centreUsers()
    {
        return $this->belongsToMany('App\CentreUser');
    }

    /**
     * Get the Sponsor for this Centre
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function sponsor()
    {
        return $this->belongsTo('App\Sponsor');
    }

    /**
     * Gets all the siblings under the same parent (including this one).
     * Self join; possible a better way to do this.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function neighbours()
    {
        return $this->hasMany('App\Centre', 'sponsor_id', 'sponsor_id');
    }

    public function families()
    {
        return $this->hasMany('App\Family', 'initial_centre_id');
    }

}
