<?php
/**
 * Created by PhpStorm.
 * User: Tuhin
 * Date: 8/25/2018
 * Time: 10:46 PM
 */

namespace Photo\Services;


use GooglePlace\Services\Nearby;
use GooglePlace\Services\TextSearch;
use Photo\Models\Location;
use Photo\Models\Photo;
use PlaceApi\Place;

class ExifDataService
{
    protected $fillable = [
        'FileDateTime',
        'DateTimeOriginal',
        'FileSize',
        'FileType',
        'Orientation',
        'MimeType',
        'COMPUTED',
        'Model',
        'Make',
        'FocalLength',
        'ApertureValue',
        'ShutterSpeedValue',
        'ExposureTime',
        'Flash',
        'ISOSpeedRatings',
        'WhiteBalance'
    ];

    protected $photo;
    protected $data = [];

    /**
     * @var Location
     */
    protected $locationModel;

    /**
     * ExifDataService constructor.
     * @param Photo $photo
     */
    public function __construct(Photo $photo)
    {
        $this->photo = $photo;
        $this->data = exif_read_data(storage_path('app/public/' . $this->photo->getSrc()));
    }

    public function toArray()
    {
        return array_intersect_key($this->data, array_flip($this->fillable));
    }

    public function location()
    {
        $latLng = $this->getCoordinates();
        if ($latLng) {
            $place = new Place(['where' => implode(",", $latLng), 'radius' => 2000]);
            $locations = $place->nearBy();

            if ($locations->count() > 0) {
                $placeModel = $locations->first();
                $locationModel = Location::firstOrNew(['place_id' => $placeModel->id()]);
                $locationModel->locality = $placeModel->locality();
                $locationModel->city = $placeModel->locality();
                $locationModel->state = $placeModel->state();
                $locationModel->country = $placeModel->country();
                $locationModel->latitude = $latLng['latitude'] ?? null;
                $locationModel->longitude = $latLng['longitude'] ?? null;
                $locationModel->save();
                $this->locationModel = $locationModel;
                return $locationModel;
            }
        }
        return false;
    }

    /**
     * Example coordinate values
     *
     * Latitude - 49/1, 4/1, 2881/100, N
     * Longitude - 121/1, 58/1, 4768/100, W
     */
    protected function toDecimal($deg, $min, $sec, $ref)
    {

        $float = function ($v) {
            return (count($v = explode('/', $v)) > 1) ? $v[0] / $v[1] : $v[0];
        };

        $d = $float($deg) + (($float($min) / 60) + ($float($sec) / 3600));
        return ($ref == 'S' || $ref == 'W') ? $d *= -1 : $d;
    }

    public function save()
    {
        if (is_array($this->data)) {
            $locationModel = $this->location();
            if ($locationModel) {
                $this->photo->location_id = $locationModel->id;
                $this->photo->save();
            }
            $data = $this->toArray();
            if (is_array($data)) {
                $this->photo->exif = $data;
                $this->photo->taken_at = $data['DateTimeOriginal'] ?? null;
                $this->photo->save();
            }
            return true;
        }
        return false;
    }

    public function getCoordinates()
    {
        $exif = $this->data;

        $coord = (isset($exif['GPSLatitude'], $exif['GPSLongitude'])) ? array(
            'latitude' => sprintf('%.6f', $this->toDecimal($exif['GPSLatitude'][0], $exif['GPSLatitude'][1], $exif['GPSLatitude'][2], $exif['GPSLatitudeRef'])),
            'longitude' => sprintf('%.6f', $this->toDecimal($exif['GPSLongitude'][0], $exif['GPSLongitude'][1], $exif['GPSLongitude'][2], $exif['GPSLongitudeRef']))
        ) : null;
        return $coord;
    }

    public function getRawData()
    {
        return $this->data;
    }
}