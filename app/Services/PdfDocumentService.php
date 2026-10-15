<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Spatie\Browsershot\Browsershot;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class PdfDocumentService
{
    public function downloadView(
        string $view,
        array $data,
        string $filename,
        string $paper = 'a4',
        string $orientation = 'portrait',
        ?string $cacheKey = null,
    ): Response {
        set_time_limit((int) config('browsershot.php_execution_timeout', 0));

        $cachePath = $cacheKey === null ? null : $this->cachePath($view, $cacheKey);

        if ($cachePath !== null && is_file($cachePath) && filesize($cachePath) > 0) {
            return response()->download($cachePath, $filename);
        }

        $temporaryBasePath = tempnam(storage_path('app'), 'pdf-');

        if ($temporaryBasePath === false) {
            throw new \RuntimeException('Impossible de créer le fichier temporaire pour le PDF.');
        }

        $targetPath = $temporaryBasePath.'.pdf';
        unlink($temporaryBasePath);

        try {
            $browserShot = Browsershot::html($this->inlineLocalAssets(view($view, $data)->render()))
                ->format(strtoupper($paper))
                ->showBackground()
                ->margins(0, 0, 0, 0)
                ->timeout((int) config('browsershot.timeout', 120));

            if ($orientation === 'landscape') {
                $browserShot->landscape();
            }

            $nodeBinary = config('browsershot.node_binary');
            if (is_string($nodeBinary) && $nodeBinary !== '') {
                $browserShot->setNodeBinary($nodeBinary);
            }

            $npmBinary = config('browsershot.npm_binary');
            if (is_string($npmBinary) && $npmBinary !== '') {
                $browserShot->setNpmBinary($npmBinary);
            }

            $nodeModulePath = config('browsershot.node_module_path');
            if (is_string($nodeModulePath) && $nodeModulePath !== '') {
                $browserShot->setNodeModulePath($nodeModulePath);
            }

            $chromePath = config('browsershot.chrome_path');
            if (is_string($chromePath) && $chromePath !== '') {
                $browserShot->setChromePath($chromePath);
            }

            if ((bool) config('browsershot.no_sandbox', false)) {
                $browserShot->noSandbox();
            }

            $chromiumArguments = array_map(
                static fn (string $argument): string => ltrim($argument, '-'),
                (array) config('browsershot.chromium_args', []),
            );
            if ($chromiumArguments !== []) {
                $browserShot->addChromiumArguments($chromiumArguments);
            }

            if ($cachePath === null) {
                $browserShot->save($targetPath);

                return response()->download($targetPath, $filename)->deleteFileAfterSend(true);
            }

            $cacheDirectory = dirname($cachePath);
            if (! is_dir($cacheDirectory) && ! mkdir($cacheDirectory, 0755, true) && ! is_dir($cacheDirectory)) {
                throw new \RuntimeException('Impossible de créer le cache des PDF.');
            }

            $lockHandle = fopen($cachePath.'.lock', 'c+');
            if ($lockHandle === false) {
                throw new \RuntimeException('Impossible de verrouiller le cache des PDF.');
            }

            try {
                if (! flock($lockHandle, LOCK_EX)) {
                    throw new \RuntimeException('Impossible de verrouiller le cache des PDF.');
                }

                if (! is_file($cachePath) || filesize($cachePath) === 0) {
                    $browserShot->save($targetPath);
                    if (! rename($targetPath, $cachePath)) {
                        throw new \RuntimeException('Impossible d’enregistrer le cache du PDF.');
                    }
                }
            } finally {
                flock($lockHandle, LOCK_UN);
                fclose($lockHandle);
            }

            return response()->download($cachePath, $filename);
        } catch (Throwable $exception) {
            if (is_file($targetPath)) {
                unlink($targetPath);
            }

            Log::error('La génération du PDF par Chromium a échoué.', [
                'view' => $view,
                'filename' => $filename,
                'paper' => $paper,
                'orientation' => $orientation,
                'exception' => $exception,
            ]);

            throw new \RuntimeException(
                'La génération du PDF a échoué. Vérifiez que Node.js, Puppeteer et Chromium sont installés.',
                previous: $exception,
            );
        }
    }

    private function cachePath(string $view, string $cacheKey): string
    {
        $viewPath = resource_path('views/'.str_replace('.', DIRECTORY_SEPARATOR, $view).'.blade.php');
        $viewVersion = is_file($viewPath) ? (string) filemtime($viewPath) : 'unknown';

        return storage_path('app/pdf-cache/'.hash('sha256', $cacheKey.'|'.$viewVersion).'.pdf');
    }

    private function inlineLocalAssets(string $html): string
    {
        $html = preg_replace_callback(
            '/(?P<attribute>\b(?:src|href)\s*=\s*)(?P<quote>["\'])(?P<path>[^"\']+)(?P=quote)/i',
            fn (array $matches): string => $matches['attribute'].$matches['quote'].
                $this->assetAsDataUri($matches['path']).$matches['quote'],
            $html
        ) ?? $html;

        return preg_replace_callback(
            '/url\((?P<quote>["\']?)(?P<path>[^)"\']+)(?P=quote)\)/i',
            fn (array $matches): string => 'url('.$matches['quote'].
                $this->assetAsDataUri(trim($matches['path'])).$matches['quote'].')',
            $html
        ) ?? $html;
    }

    private function assetAsDataUri(string $path): string
    {
        $resolvedPath = realpath($path);

        if ($resolvedPath === false || ! $this->isAllowedAssetPath($resolvedPath)) {
            return $path;
        }

        $contents = file_get_contents($resolvedPath);
        if ($contents === false) {
            return $path;
        }

        $mimeType = mime_content_type($resolvedPath) ?: 'application/octet-stream';

        return 'data:'.$mimeType.';base64,'.base64_encode($contents);
    }

    private function isAllowedAssetPath(string $path): bool
    {
        $allowedRoots = [
            realpath(public_path('images')),
            realpath(public_path('fonts')),
            realpath(storage_path('app/public')),
        ];

        foreach (array_filter($allowedRoots) as $root) {
            if ($path === $root || str_starts_with($path, $root.DIRECTORY_SEPARATOR)) {
                return true;
            }
        }

        return false;
    }
}
