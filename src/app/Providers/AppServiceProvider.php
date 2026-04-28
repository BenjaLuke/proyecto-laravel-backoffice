<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Paginator::useBootstrapFive();
        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $document) {
                $document->info->title = 'Laravel Project API';
                $document->info->description = 'API para la gestión de productos, categorías, tarifas, imágenes y pedidos. Incluye endpoints documentados automáticamente y preparados para evolución futura.';
                $document->info->version = env('API_VERSION', '1.0.0');

                $document->secure(
                    SecurityScheme::http('bearer')
                );
            });
    }
}
