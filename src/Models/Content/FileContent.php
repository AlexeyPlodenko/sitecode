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
        $disk = config('sitecode.disk');
        $baseUrl = config("filesystems.disks.$disk.url");

        return "$baseUrl/$this->content";
    }

    public function getAbsoluteFilePath(): string
    {
        $disk = config('sitecode.disk');
        $root = config("filesystems.disks.$disk.root");
        if (!$root) {
            throw new RuntimeException("filesystems.disks.$disk.root is not specified in /config/filesystems.php.");
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
