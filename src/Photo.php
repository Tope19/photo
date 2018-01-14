<?php
/**
 * Created by PhpStorm.
 * User: digitaldreams
 * Date: 14/01/18
 * Time: 17:22
 */

namespace Photo;


use Photo\Library\Resize;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class Photo
{
    /**
     * @var
     */
    protected $folder;

    /**
     * @var
     */
    protected $fullPath;

    /**
     * @var
     */
    protected $driver;
    /**
     * @var array
     */
    protected $urls = [];

    /**
     * Photo constructor.
     */
    public function __construct()
    {
        $this->folder = config('photo.uploadPath', 'photos');
        $this->driver = config('photo.driver', 'public');

        //Creating root folder if not exists
        $this->makeRootPath();
    }

    /**
     * @param $files
     * @return Photo
     */
    public function upload($files)
    {
        $ret = [];
        if (is_array($files)) {
            foreach ($files as $file) {
                if ($file instanceof UploadedFile) {
                    $ret[] = $this->doUpload();
                }
            }
        } else {
            $ret[] = $this->doUpload($files);
        }
        $this->urls = $ret;
        return $this;
    }

    public function resize($size = '')
    {
        $sizes = !empty($size) ? [$size] : config('photo.sizes', []);
        $absUrls = $this->getAbsoluteUrls();
        foreach ($absUrls as $url) {
            foreach ($sizes as $size) {
                try {
                    $resize = new Resize($url, $size);
                    $resize->save();
                } catch (\Exception $e) {
                    continue;
                }
            }
        }
        return $this;
    }

    /**
     * @param UploadedFile $document
     * @return bool|string
     */
    protected function doUpload(UploadedFile $document)
    {
        if ($document->isValid()) {
            $fileName = uniqid(rand(1000, 99999)) . '.' . $document->getClientOriginalExtension();
            return $document->storeAs($this->folder, $fileName, $this->driver);
        }
        return false;
    }

    /**
     *
     */
    private function makeRootPath()
    {
        if (in_array($this->driver, ['local', 'public'])) {
            $rootPath = $this->getRootPath();
            $this->fullPath = rtrim($rootPath, "/") . "/" . $this->folder;
            if (!file_exists($this->fullPath)) {
                mkdir($this->fullPath);
            }
        }
    }

    /**
     * @return \Illuminate\Config\Repository|mixed
     */
    protected function getRootPath()
    {
        return config('filesystems.disks.' . $this->driver . '.root');
    }

    /**
     * Return Relative URL of the file
     * @return array
     */
    public function getUrls()
    {
        return $this->urls;
    }

    /**
     * @return array
     */
    public function getAbsoluteUrls()
    {
        $fullUrls = [];
        $urls = $this->urls;
        $rootPath = $this->getRootPath();
        foreach ($urls as $url) {
            $fullUrls[] = rtrim($rootPath, "/") . "/" . $url;
        }
        return $fullUrls;
    }
}