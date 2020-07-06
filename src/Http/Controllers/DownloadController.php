<?php

namespace Photo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Filesystem\Filesystem;
use Photo\Http\Requests\Photos\Download;
use Photo\Http\Requests\Photos\Dropzone;
use Photo\Models\Photo;
use Photo\Repositories\PhotoRepository;
use Photo\Services\ImageDownloadService;
use Photo\Services\PhotoService;

class DownloadController extends Controller
{
    /**
     * @var \Photo\Services\ImageDownloadService
     */
    protected ImageDownloadService $imageDownloadService;

    /**
     * @var
     */
    protected PhotoService $photoService;

    /**
     * @var \Photo\Repositories\PhotoRepository
     */
    protected PhotoRepository $photoRepository;


    /**
     * @var \Illuminate\Contracts\Filesystem\Filesystem
     */
    private Filesystem $storage;

    /**
     * DownloadController constructor.
     *
     * @param \Photo\Services\ImageDownloadService        $imageDownloadService
     * @param \Photo\Services\PhotoService                $photoService
     * @param \Photo\Repositories\PhotoRepository         $photoRepository
     * @param \Illuminate\Contracts\Filesystem\Filesystem $storage
     */
    public function __construct(ImageDownloadService $imageDownloadService, PhotoService $photoService, PhotoRepository $photoRepository, Filesystem $storage)
    {
        $this->imageDownloadService = $imageDownloadService;
        $this->photoService = $photoService;
        $this->photoRepository = $photoRepository;
        $this->storage = $storage;
    }

    /**
     * Download Image from image source URL. e.g. https://example.com/image.png.
     *
     * @param \Photo\Http\Requests\Photos\Download $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function downloadUrl(Download $request)
    {
        try {
            $url = $request->get('url');
            $this->imageDownloadService->setImageUrl($url);

            $path = $this->imageDownloadService->download('images');
            $photo = new Photo();
            $photo->caption = null;
            $photo->src = $path;
            $photo->save();
            $this->photoService->convertMaxDimensionToWebP($path);
            foreach (config('photo.sizes') as $name => $info) {
                $this->photoService->setDimension($info['width'], $info['height'], $info['path']);
            }
            $this->photoService->convert($path);

            return response()->json([
                'file' => $photo->getUrl(),
                'success' => true,
                'url' => route('photo::photos.show', $photo->id),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'success' => false,
            ]);
        }
    }

    /**
     * @param \Photo\Http\Requests\Photos\Dropzone $request
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function dropzone(Dropzone $request)
    {
        if ($request->file('file')->isValid()) {

            $model = $this->photoRepository->create($request->file('file'));

            session()->flash('message', 'Photo saved');

            return response()->json([
                'file' => $model->getUrl(),
                'success' => true,
                'url' => route('photo::photos.show', $model->id),
            ]);
        } else {
            return response()->json([
                'success' => false,
            ]);
        }
    }

    /**
     * Download image to user device.
     *
     * @param \Photo\Models\Photo $photo
     *
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function download(Photo $photo)
    {
        $this->authorize('update', $photo);

        return $this->storage->download($photo->src);
    }
}
