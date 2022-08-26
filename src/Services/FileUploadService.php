<?php

namespace App\Services;

use DateTime;
use Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploadService
{
    public const OUTSIDE_CONTENT = 'outside-content';
    public const CONTENT_PATH = '/var/www/content';

    protected $kernelProjectDirectory;

    public function __construct() {
        $this->kernelProjectDirectory = '/var/www/html/public';
    }

    private function getMimeType(?string $file = null): ?string {
        if ($file === null) {
            return null;
        }
        try {
            $mimeType = mime_content_type($file);
            if ($mimeType === 'image/svg') {
                return 'image/svg+xml';
            }
            return empty($mimeType) ? null : $mimeType;
        } catch (Exception $exception) {
        }
        return null;
    }

    public function storeTempFile(
        string $folderLocationOnServer,
        UploadedFile $file,
        $tempFolder = '',
        $shouldReturnPath = false
    ): ?string {
        $basePath = $this->kernelProjectDirectory;
        if ($folderLocationOnServer === self::OUTSIDE_CONTENT) {
            $basePath = self::CONTENT_PATH;
        }
        if ($folderLocationOnServer === 'temp') {
            $filepath = sys_get_temp_dir() . '/' . $tempFolder;
        }  else {
            $filepath = $basePath . "/temp-uploads/{$tempFolder}";
        }
        $fileSystem = new Filesystem();
        if (!$fileSystem->exists($filepath)) {
            $fileSystem->mkdir($filepath);
        }
        if ($fileSystem->exists($filepath)) {
            $filename = sp_random_str(10, '0123456789abcdefghilkmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ') . '.' . strtolower($file->getClientOriginalExtension());
            $file->move($filepath, $filename);
            if (!$shouldReturnPath) {
                return $filename;
            }

            return $filepath . '/' . $filename;
        }
        return null;
    }

    public function purgeTempFolder(
        string $folderLocationOnServer,
        string $tempFolder,
        string $entityFolder
    ): bool {
        [$fileSystem, $filepath] = $this->getFileSystemAndPath($folderLocationOnServer, $tempFolder, $entityFolder);
        if (!$fileSystem->exists($filepath)) {
            return true;
        }
        try {
            $fileSystem->remove($filepath);
            return true;
        } catch (Exception $exception) {
            return false;
        }
    }

    public function getLocalFilePath(
        string $folderLocationOnServer,
        string $tempFolder,
        string $entityFolder,
        string $fileName
    ): ?string {
        [$fileSystem, $filepath] = $this->getFileSystemAndPath($folderLocationOnServer, $tempFolder, $entityFolder);
        if (!$fileSystem->exists($filepath)) {
            return null;
        }

        return "{$filepath}/{$fileName}";
    }

    public function uploadTempFileToLocal(
        string $folderLocationOnServer,
        string $tempFolder,
        string $entityFolder,
        string $uploadFolder,
        string $fileName,
        bool $removeFolder = true
    ): string {
        [$fileSystem, $filepath] = $this->getFileSystemAndPath($folderLocationOnServer, $tempFolder, $entityFolder);
        if (!$fileSystem->exists($filepath)) {
            throw new \RuntimeException("No temp folder found - {$filepath}");
        }
        $currentPath = "{$filepath}/{$fileName}";
        $newPath = "/var/www/html/public/uploads/{$entityFolder}/{$uploadFolder}/{$fileName}";
        $fileSystem->copy($currentPath, $newPath);
        if ($removeFolder) {
            try {
                $fileSystem->remove($currentPath);
            } catch (Exception $exception) {
            }
        }
        return $newPath;
    }

    private function generateLocalPathWithExtensionBasedOnUrl(
        string $localPath = null,
        string $fileName = null,
        string $url
    ) {
        if ($localPath == null) {
            $localPath = sys_get_temp_dir() . '/' . sp_random_str(10);
        }
        $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
        if ($fileName == null) {
            $fileName = sp_random_str(10) . '_' . (new DateTime())->getTimestamp();
        }
        $fileSystem = new Filesystem();
        if (!$fileSystem->exists($localPath)) {
            $fileSystem->mkdir($localPath);
        }
        if (strpos($fileName, ".$extension") === -1) {
            $fileName .= ".$extension";
        }
        return compact('localPath', 'fileName', 'fileSystem');
    }

    public function getFileSystemAndPath(string $folderLocationOnServer, string $tempFolder, string $entityFolder): array {
        $fileSystem = new Filesystem();
        $basePath = $this->kernelProjectDirectory;
        if ($folderLocationOnServer === self::OUTSIDE_CONTENT) {
            $basePath = self::CONTENT_PATH;
        }
        if ($folderLocationOnServer === 'temp') {
            $filepath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $tempFolder;
        } else {
            $filepath = $basePath . "/temp-uploads/{$entityFolder}/{$tempFolder}";
        }
        return array($fileSystem, $filepath);
    }


    public function getTemporaryFilePath(
        string $folderLocationOnServer,
        string $tempFolder,
        string $entityFolder,
        string $fileName
    ) {
        [$fileSystem, $filepath] = $this->getFileSystemAndPath($folderLocationOnServer, $tempFolder, $entityFolder);
        if ($fileSystem->exists($filepath)) {
            return "{$filepath}/{$fileName}";
        }

        throw new Exception("No temp folder found - {$filepath}");
    }
}
