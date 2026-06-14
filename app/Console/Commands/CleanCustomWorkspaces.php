<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CleanCustomWorkspaces extends Command
{
    // Nama command yang akan dipanggil
    protected $signature = 'app:clean-custom-workspaces';
    protected $description = 'Sapu bersih folder customized downloads yang berumur lebih dari 10 menit';

    public function handle()
    {
        $root = storage_path('app/customized_downloads');

        if (! File::isDirectory($root)) {
            $this->info('Folder root tidak ditemukan.');
            return;
        }

        $deletedCount = 0;

        foreach (File::directories($root) as $directory) {
            // Sesuai kode Anda: hapus jika usianya lebih dari 10 menit
            if (File::lastModified($directory) < now()->subMinutes(10)->timestamp) {
                File::deleteDirectory($directory);
                $deletedCount++;
            }
        }

        $this->info("Pembersihan selesai. {$deletedCount} folder sampah berhasil dihapus.");
    }
}