<?php

namespace Photo\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Photo\Exceptions\ImageNotFoundException;

class ImageDownloadService
{
    /**
     * @var string
     */
    private string $imageUrl;

    /**
     * @var \Illuminate\Contracts\Filesystem\Filesystem
     */
    private Filesystem $storage;

    /**
     * ImageDownload constructor.
     *
     * @param \Illuminate\Contracts\Filesystem\Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->storage = $filesystem;
    }

    /**
     * @param string $path
     *
     * @throws \Photo\Exceptions\ImageNotFoundException
     *
     * @return string
     */
    public function download(string $path)
    {
        $ch = curl_init($this->imageUrl);
        $headers = $this->exists();

        $contentType = $headers['Content-Type'] ?? '';
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $imageContent = curl_exec($ch);
        curl_close($ch);

        if (!$this->storage->exists($path)) {
            $this->storage->makeDirectory($path);
        }
        $originalFilename = pathinfo($this->imageUrl, PATHINFO_BASENAME);
        $filename = $this->storage->exists($path.'/'.$originalFilename) ? Str::random(32).'.'.$this->getExtension($contentType) : $originalFilename;

        $finalURL = $path.'/'.$filename;
        $this->storage->put($finalURL, $imageContent, 'public');

        return $finalURL;
    }

    /**
     * @throws \Photo\Exceptions\ImageNotFoundException
     *
     * @return bool|array
     */
    public function exists()
    {
        $file_headers = @get_headers($this->imageUrl, 1);
        if (!$file_headers || 'HTTP/1.1 404 Not Found' == $file_headers[0] || 'HTTP/1.1 403 Forbidden' == $file_headers[0]) {
            throw new ImageNotFoundException('Given image URL is not exists');
        } else {
            return $file_headers;
        }
    }

    /**
     * Get Image Extension by ime type.
     *
     * @param $contentType
     *
     * @return string
     */
    private function getExtension(string $contentType): string
    {
        $extension = '';
        switch ($contentType) {
            case 'image/png':
                $extension = 'png';
                break;
            case 'image/gif':
                $extension = 'gif';
                break;
            case 'image/jpeg':
                $extension = 'jpeg';
                break;
            default:
                $extension = 'jpg';
                break;
        }

        return $extension;
    }

    /**
     * Set Image Source URL.
     *
     * @param string $imageUrl
     *
     * @throws \Exception
     *
     * @return \Photo\Services\ImageDownloadService
     */
    public function setImageUrl(string $imageUrl): self
    {
        if (filter_var(trim($imageUrl), FILTER_VALIDATE_URL)) {
            $this->imageUrl = trim($imageUrl);
        } else {
            throw new \Exception('Invalid Image source');
        }

        return $this;
    }
}
