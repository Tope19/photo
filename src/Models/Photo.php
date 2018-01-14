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
    protected $guarded = ['id'];


    /**
     * Date time columns.
     */
    protected $dates = [];

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
        return $this->src;
    }

    public function getLocationName()
    {
        return 'Your image Location';
    }
}