<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class StudentImportStorage
{
    /** @var list<string> */
    private array $temporaryPaths = [];

    public function diskName(): string
    {
        return (string) config('student-import.disk', 'local');
    }

    public function localPath(string $path): string
    {
        $diskName = $this->diskName();
        $disk = Storage::disk($diskName);
        if (! $disk->exists($path)) {
            throw new RuntimeException('Le fichier privé de cet import n’est plus disponible.');
        }

        if (config("filesystems.disks.{$diskName}.driver") === 'local') {
            return $disk->path($path);
        }

        $source = $disk->readStream($path);
        if (! is_resource($source)) {
            throw new RuntimeException('Le fichier privé de cet import ne peut pas être lu.');
        }

        $temporaryPath = tempnam(sys_get_temp_dir(), 'student-import-');
        if ($temporaryPath === false) {
            fclose($source);
            throw new RuntimeException('Un fichier temporaire sécurisé ne peut pas être créé.');
        }

        $destination = fopen($temporaryPath, 'wb');
        if ($destination === false) {
            fclose($source);
            $this->removeTemporaryPath($temporaryPath);
            throw new RuntimeException('Un fichier temporaire sécurisé ne peut pas être ouvert.');
        }

        $copied = stream_copy_to_stream($source, $destination);
        fclose($source);
        fclose($destination);
        if ($copied === false || $copied !== $disk->size($path)) {
            $this->removeTemporaryPath($temporaryPath);
            throw new RuntimeException('Le fichier privé de cet import ne peut pas être copié.');
        }

        $this->temporaryPaths[] = $temporaryPath;

        return $temporaryPath;
    }

    public function __destruct()
    {
        foreach ($this->temporaryPaths as $path) {
            $this->removeTemporaryPath($path);
        }
    }

    private function removeTemporaryPath(string $path): void
    {
        if (is_file($path) && ! unlink($path)) {
            report(new RuntimeException('Un fichier temporaire privé d’import n’a pas pu être supprimé.'));
        }
    }
}
