<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\belongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Collection;
use App\Notifications\StorePasswordResetNotification;

class CentreUser extends Authenticatable
{
    use Notifiable;

    protected $guard = 'store';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'role', 'downloader',
    ];

    /**
     * Calculated attributes
     *
     * @var array
     */
    protected $appends = [
        'homeCentre'
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
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'downloader' => 'boolean',
    ];

    /**
     * Get the Notes that belong to this CentreUser
     *
     * @return HasMany
     */
    //Because of merge and refactoring User to CentreUser, FK has to be explicitly stated here
    public function notes()
    {
        return $this->hasMany('App\Note', 'user_id');
    }

    /**
     * Get the CentreUser's Current Centre
     *
     * @return Centre
     */
    public function getCentreAttribute()
    {
        // Check the session for a variable.
        $currentCentreId = session('CentreUserCurrentCentreId');

        // check it's a number
        if (is_numeric($currentCentreId)) {
            // check the centre is in our set
            /** @var Centre $currentCentre */
            $currentCentre = $this->centres()->where('id', $currentCentreId)->first();
            if ($currentCentre) {
                return $currentCentre;
            }
        }

        // return default homeCentre if broken.
        /** @var Centre $currentCentre */
        $currentCentre = $this->homeCentre;
        return $currentCentre;
    }

    /**
     * Get the centres assigned to a user
     *
     * @return belongsToMany
     */
    public function centres()
    {
        return $this->belongsToMany('App\Centre');
    }

    /**
     * Gets the first homeCentre, makes it an attribute.
     *
     * @return Centre
     */
    public function getHomeCentreAttribute()
    {
        return $this->homeCentres()->first();
    }

    /**
     * Get the home centres for this user
     * Alas, we lack a belongsToThrough method to this is a collections.
     *
     * @return belongsToMany
     */
    protected function homeCentres()
    {
        return $this->belongsToMany('App\Centre')->wherePivot('homeCentre', true);
    }

    /**
     * Get the relevant centres for this CentreUser, accounting for it's role
     *
     * @return Collection
     */
    public function relevantCentres()
    {
        // default to empty collection
        $centres = collect([]);
        switch ($this->role) {
            case "foodmatters_user":
                // Just get all centres
                $centres = collect(Centre::get()->all());
                break;
            case "centre_user":
                // If we have one, get our centre's neighbours
                /** @var Centre $centre */
                $centre = $this->centre;
                if (!is_null($centre)) {
                    $centres = collect($centre->neighbours()->get()->all());
                }
                break;
        }
        return $centres;
    }

    /**
     * Is a given centre relevant to this CentreUser?
     *
     * @param Centre $centre
     * @return bool
     */
    public function isRelevantCentre(Centre $centre)
    {
        return $this->relevantCentres()->contains('id', $centre->id);
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new StorePasswordResetNotification($token, $this->name));
    }

    /**
     * Scope order by name
     *
     * @param Builder $query
     * @param string $direction
     */
    public function scopeOrderByName(Builder $query, $direction = 'asc')
    {
        $query->orderBy('name', $direction);
    }

    /**
     * Scope order by email
     *
     * @param Builder $query
     * @param string $direction
     */
    public function scopeOrderByEmail(Builder $query, $direction = 'asc')
    {
        $query->orderBy('email', $direction);
    }

    /**
     * Call a macro [see AppServiceProvider::boot()] to add an order by centre name
     *
     * @param Builder $query
     * @param string $direction
     */
    public function scopeOrderByHomeCentre(Builder $query, $direction = 'asc')
    {
        $query->orderBySub(
            Centre::select('name')
                ->whereRaw('centre_id = centres.id'),
            $direction
        );
    }

    /**
     * Scope order by downloader
     *
     * @param Builder $query
     * @param string $direction
     */
    public function scopeOrderByDownloader(Builder $query, $direction)
    {
        $query->orderBy('downloader', $direction);
    }

    /**
     * Strategy to sort columns
     *
     * @param Builder $query
     * @param array $sort
     * @return Builder
     */
    public function scopeOrderByField(Builder $query, $sort)
    {
        switch ($sort['orderBy']) {
            case 'name':
                return $query->orderByName($sort['direction']);
                break;
            case 'email':
                return $query->orderByEmail($sort['direction']);
                break;
            case 'centre':
                return $query->orderByHomeCentre($sort['direction']);
                break;
            case 'downloader':
                return $query->orderByDownloader($sort['direction']);
                break;
            default:
                return $query->orderByHomeCentre('asc');
        }
    }

}
