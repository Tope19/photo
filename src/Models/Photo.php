<?php

namespace Photo\Models;

use Illuminate\Database\Eloquent\Model;
use Photo\Services\PhotoRenderService;

/**
 * @property int                                      $user_id       user id
 * @property string                                   $caption       caption
 * @property string                                   $title         title
 * @property string                                   $mime_type     mime type
 * @property string                                   $src           src
 * @property int                                      $location_id   location id
 * @property \Carbon\Carbon                           $created_at    created at
 * @property \Carbon\Carbon                           $updated_at    updated at
 * @property \Illuminate\Database\Eloquent\Collection $albumphoto    belongsToMany
 */
class Photo extends Model
{
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_PENDING = 'pending';

    /**
     * Database table name.
     */
    protected $table = 'photo_photos';

    /**
     * Protected columns from mass assignment.
     */
    protected $fillable = ['user_id', 'caption', 'src', 'exif'];

    protected $dates = ['captured_at'];
    /**
     * @var array
     */
    protected $casts = ['exif' => 'array'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function photoable()
    {
        return $this->morphTo();
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
     * Album.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function albums()
    {
        return $this->belongsToMany(Album::class, 'album_photo');
    }

    /**
     * @param $query
     * @param $keyword
     *
     * @return mixed
     */
    public function scopeQ($query, $keyword)
    {
        return $query->where(function ($q) use ($keyword) {
            $q->orWhere('caption', 'LIKE', '%' . $keyword . '%')
                ->orWhere('src', 'LIKE', '%' . $keyword . '%');
        });
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        $default = config('photo.filesystem');
        $storage = app('filesystem.' . $default);
        return $storage->url($this->src);
    }

    /**
     * @return mixed
     */
    public function render(): string
    {
        $photoRender = app(PhotoRenderService::class);
        return $photoRender->render($this);
    }

    /**
     * @return mixed
     */
    public function renderThumbnails(): string
    {
        $photoRender = app(PhotoRenderService::class);
        return $photoRender->renderThumbnails($this);
    }

    /**
     * @return mixed
     */
    public function getUrls(): array
    {
        $photoRender = app(PhotoRenderService::class);
        return $photoRender->getUrls($this);
    }



}
