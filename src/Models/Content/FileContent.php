<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Models\Content;

use Alexeyplodenko\Sitecode\Models\MediaAttributes;
use RuntimeException;

class FileContent extends AbstractContent
{
    private MediaAttributes $mediaAttributes;

    public function getContent(): string
    {
        return htmlspecialchars($this->raw(), ENT_QUOTES);
    }

    public function getFileName(): string
    {
        return $this->content;
    }

    public function raw(): string
    {
        $baseUrl = config('filesystems.disks.public_media_uploads.url');

        return "$baseUrl/$this->content";
    }

    public function getAbsoluteFilePath(): string
    {
        $root = config('filesystems.disks.public_media_uploads.root');
        if (!$root) {
            throw new RuntimeException('filesystems.disks.public_media_uploads.root is not specified in configuration.');
        }

        return "$root/$this->content";
    }

    public function getMediaAttributes(): MediaAttributes
    {
        if (!isset($this->mediaAttributes)) {
            $filePath = $this->getAbsoluteFilePath();

            $size = getimagesize($filePath);
            $this->mediaAttributes = new MediaAttributes($size[0], $size[1], $size[2], $size);
        }

        return $this->mediaAttributes;
    }
}
