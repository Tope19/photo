<?php

namespace Photo\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $user_id user id
 * @property varchar $caption caption
 * @property varchar $title title
 * @property varchar $mime_type mime type
 * @property varchar $src src
 * @property int $location_id location id
 * @property timestamp $created_at created at
 * @property timestamp $updated_at updated at
 * @property PhotoLocation $photoLocation belongsTo
 * @property \Illuminate\Database\Eloquent\Collection $albumphoto belongsToMany
 */
class Photo extends Model
{

    /**
     * Database table name
     */
    protected $table = 'photo_photos';
    /**
     * Protected columns from mass assignment
     */
    protected $fillable = ['caption', 'title'];


    /**
     * Date time columns.
     */
    protected $dates = [];

    public static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->user_id) && auth()->check()) {
                $model->user_id = auth()->user()->id;
            }
            return true;
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        $userModel = config('auth.providers.users.model');
        return $this->belongsTo($userModel);
    }

    /**
     * photoLocation
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    /**
     * albumphotos
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function albums()
    {
        return $this->belongsToMany(Album::class, 'album_photo');
    }

    /**
     * caption column mutator.
     */
    public function setCaptionAttribute($value)
    {
        $this->attributes['caption'] = htmlspecialchars($value);
    }

    /**
     * title column mutator.
     */
    public function setTitleAttribute($value)
    {
        $this->attributes['title'] = htmlspecialchars($value);
    }

    public function getSrc()
    {
        return !empty($this->src) ? $this->src : config('photo.default');
    }

    public function getLocationName()
    {
        try {
            return $this->location->name;

        } catch (\Exception $e) {
            return '';
        }
    }

    public function getLocationPlaceId()
    {
        try {
            return $this->location->place_id;

        } catch (\Exception $e) {
            return '';
        }
    }
}