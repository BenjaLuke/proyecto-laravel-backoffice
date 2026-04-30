<?php

use App\Models\ProductImage;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('products:import-msx-covers {source=storage/app/imports/msx-covers}', function (string $source) {
    $sourcePath = preg_match('/^[A-Za-z]:\\\\/', $source) === 1 || Str::startsWith($source, ['/', '\\'])
        ? $source
        : base_path($source);

    if (! File::isDirectory($sourcePath)) {
        $this->error("Source directory not found: {$sourcePath}");

        return self::FAILURE;
    }

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp'];

    $covers = collect(File::files($sourcePath))
        ->filter(fn ($file) => in_array(strtolower($file->getExtension()), $allowedExtensions, true))
        ->sortBy(fn ($file) => Str::lower($file->getFilename()))
        ->values();

    $images = ProductImage::query()
        ->orderBy('product_id')
        ->orderByDesc('is_primary')
        ->orderBy('sort_order')
        ->orderBy('id')
        ->get();

    if ($images->isEmpty()) {
        $this->warn('No product_images records found.');

        return self::SUCCESS;
    }

    if ($covers->count() < $images->count()) {
        $this->error("Not enough cover files. Found {$covers->count()} for {$images->count()} product images.");

        return self::FAILURE;
    }

    $disk = Storage::disk('public');

    foreach ($images as $index => $image) {
        $cover = $covers[$index];
        $extension = strtolower($cover->getExtension());
        $basename = pathinfo($cover->getFilename(), PATHINFO_FILENAME);
        $slug = Str::slug($basename);

        if ($slug === '') {
            $slug = 'cover-'.$image->id;
        }

        $newPath = "products/{$image->product_id}/{$image->id}-{$slug}.{$extension}";
        $targetDirectory = dirname($disk->path($newPath));

        File::ensureDirectoryExists($targetDirectory);

        if ($image->path && $image->path !== $newPath && $disk->exists($image->path)) {
            $disk->delete($image->path);
        }

        File::copy($cover->getRealPath(), $disk->path($newPath));

        $image->forceFill([
            'path' => $newPath,
            'original_name' => $cover->getFilename(),
        ])->save();
    }

    $this->info("Imported {$images->count()} covers from {$sourcePath}.");

    return self::SUCCESS;
})->purpose('Import local MSX cover files into product_images and public storage');
