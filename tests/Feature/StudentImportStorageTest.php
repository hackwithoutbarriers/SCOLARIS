<?php

namespace Tests\Feature;

use App\Services\StudentImportStorage;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentImportStorageTest extends TestCase
{
    public function test_shared_private_disk_file_is_streamed_to_a_temporary_local_copy_and_cleaned_up(): void
    {
        Storage::fake('s3');
        config([
            'filesystems.disks.s3.driver' => 's3',
            'student-import.disk' => 's3',
        ]);
        Storage::disk('s3')->put('student-imports/synthetic.csv', 'synthetic,row');

        $storage = new StudentImportStorage;
        $temporaryPath = $storage->localPath('student-imports/synthetic.csv');

        $this->assertSame('synthetic,row', file_get_contents($temporaryPath));
        unset($storage);

        $this->assertFileDoesNotExist($temporaryPath);
    }
}
