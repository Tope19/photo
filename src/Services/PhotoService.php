<?php
/**
 * Created by PhpStorm.
 * User: Tuhin
 * Date: 2/7/2018
 * Time: 10:49 PM
 */

namespace Photo\Services;


use Illuminate\Http\Request;
use Photo\Models\Location;
use Photo\Models\Photo;
use Photo\Photo as PhotoLib;

class PhotoService
{
    /**
     * @var Photo
     */
    protected $photo;
    protected $folder;

    public function __construct(Photo $photo)
    {
        $this->photo = $photo;
        $this->folder = config('photo.rootPath', 'photos');
    }

    /**
     * @param Request $request
     * @return Photo
     * @throws \Exception
     */
    public function save(Request $request)
    {
        if ($request->hasFile('file')) {
            $url = (new PhotoLib())->setFolder($this->folder)->upload($request->file('file'))->resize()->getUrls();
            if (!empty($url)) {
                $this->photo->src = array_shift($url);
            }
            $placeApiData = $request->get('place_api_data');
            if (!empty($placeApiData)) {
                $data = json_decode($placeApiData, true);
                if (is_array($data)) {
                    $location = new Location();
                    $location->fill($data);
                    if ($location->save()) {
                        $this->photo->location_id = $location->id;
                    }
                }
            }
            if (empty($this->photo->caption)) {
                $this->photo->caption = $request->file('file')->getBasename();
            }
            if (empty($this->photo->title)) {
                $this->photo->title = $request->file('file')->getBasename();
            }

            $this->photo->save();
        }


        return $this->photo;
    }

    public function setFolder($folder)
    {
        $this->folder = $folder;
        return $this;
    }
}