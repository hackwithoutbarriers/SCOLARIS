<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use InvalidArgumentException;

class PortfolioImageOptimizer
{
    /**
     * Convert uploaded portfolio images to WebP format and reduce their size.
     *
     * @param  array<int, string>  $paths
     * @return array<int, string>
     */
    public function optimize(array $paths): array
    {
        $optimized = [];
        $diskName = (string) config('filesystems.default', 'public');
        $disk = Storage::disk($diskName);
        $imageManager = ImageManager::gd();
        $canEncodeWebp = function_exists('imagewebp');

        foreach ($paths as $path) {
            if (blank($path)) {
                continue;
            }

            $this->ensureSafeRelativePath($path);
            if (! $this->isSafeStoredPath($path)) {
                throw new InvalidArgumentException('The portfolio image path is outside the public storage disk.');
            }

            if (! $disk->exists($path)) {
                $optimized[] = $path;

                continue;
            }

            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

            if ($extension === 'webp') {
                $optimized[] = $path;

                continue;
            }

            if (! $canEncodeWebp) {
                Log::warning('WebP encoding is unavailable; keeping the original portfolio image.', [
                    'path' => $path,
                ]);
                $optimized[] = $path;

                continue;
            }

            $sourcePath = tempnam(sys_get_temp_dir(), 'portfolio-source-');
            $webpAbsolutePath = tempnam(sys_get_temp_dir(), 'portfolio-webp-');

            if ($sourcePath === false || $webpAbsolutePath === false) {
                throw new \RuntimeException('Impossible de créer les fichiers temporaires pour optimiser l’image.');
            }

            file_put_contents($sourcePath, $disk->get($path));
            $webpPath = preg_replace('/\.[^.]+$/', '.webp', $path);

            $imageManager->read($sourcePath)
                ->resizeDown(1600, 1200)
                ->toWebp(72)
                ->save($webpAbsolutePath);

            $disk->put($webpPath, file_get_contents($webpAbsolutePath));

            if ($path !== $webpPath) {
                $disk->delete($path);
            }

            unlink($sourcePath);
            unlink($webpAbsolutePath);
            $optimized[] = $webpPath;
        }

        return $optimized;
    }

    private function ensureSafeRelativePath(string $path): void
    {
        if ($path === '' || str_starts_with($path, '/') || str_starts_with($path, '\\') || preg_match('/\A[A-Za-z]:[\\\\\/]/', $path) || str_contains(str_replace('\\', '/', $path), '../')) {
            throw new InvalidArgumentException('The portfolio image path must be relative to public storage.');
        }
    }

    private function isSafeStoredPath(string $path): bool
    {
        return ! str_starts_with($path, '/')
            && ! str_starts_with($path, '\\')
            && ! preg_match('/\A[A-Za-z]:[\\\\\/]/', $path);
    }
}
