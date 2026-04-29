<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ActivityLog;
use App\Exports\ProductsExport;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;

use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;


class ProductController extends Controller
{
    private const PRODUCT_FILTERS_SESSION_KEY = 'backoffice.products.index.saved_filters';

    public function index(Request $request): View
    {
        $today = now()->toDateString();

        $categories = Category::orderBy('name')->get();

        $savedFilters = $this->getSavedProductFilters($request);
        $hasSavedFilters = $this->hasActiveProductFilters($savedFilters);

        $usingSavedFilters = $request->boolean('use_saved_filters') && $hasSavedFilters;

        $filters = $usingSavedFilters
            ? $savedFilters
            : $this->normalizeProductFilters($request->only($this->productFilterKeys()));

        $search = $filters['search'];
        $code = $filters['code'];
        $categoryId = $filters['category_id'];
        $minPrice = $filters['min_price'];
        $maxPrice = $filters['max_price'];
        $rateStatus = $filters['rate_status'];
        $imageStatus = $filters['image_status'];
        $sort = $filters['sort'];

        $currentRateConstraint = function ($query) use ($today) {
            $query->whereDate('start_date', '<=', $today)
                ->where(function ($subQuery) use ($today) {
                    $subQuery->whereNull('end_date')
                        ->orWhereDate('end_date', '>=', $today);
                });
        };

        $productsQuery = Product::with([
            'categories',
            'images',
            'rates' => function ($query) use ($currentRateConstraint) {
                $currentRateConstraint($query);
                $query->orderByDesc('start_date');
            },
        ]);

        if ($search !== '') {
            $productsQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('code', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        if ($code !== '') {
            $productsQuery->where('code', 'like', '%' . $code . '%');
        }

        if ($categoryId !== '') {
            $productsQuery->whereHas('categories', function ($query) use ($categoryId) {
                $query->where('categories.id', $categoryId);
            });
        }

        if ($imageStatus === 'with_images') {
            $productsQuery->whereHas('images');
        } elseif ($imageStatus === 'without_images') {
            $productsQuery->doesntHave('images');
        }

        $hasPriceFilter = $minPrice !== '' || $maxPrice !== '';

        if ($rateStatus === 'with_current') {
            $productsQuery->whereHas('rates', function ($query) use ($currentRateConstraint, $minPrice, $maxPrice) {
                $currentRateConstraint($query);

                if ($minPrice !== '') {
                    $query->where('price', '>=', $minPrice);
                }

                if ($maxPrice !== '') {
                    $query->where('price', '<=', $maxPrice);
                }
            });
        } elseif ($rateStatus === 'without_current') {
            $productsQuery->whereDoesntHave('rates', function ($query) use ($currentRateConstraint) {
                $currentRateConstraint($query);
            });
        } elseif ($hasPriceFilter) {
            $productsQuery->whereHas('rates', function ($query) use ($currentRateConstraint, $minPrice, $maxPrice) {
                $currentRateConstraint($query);

                if ($minPrice !== '') {
                    $query->where('price', '>=', $minPrice);
                }

                if ($maxPrice !== '') {
                    $query->where('price', '<=', $maxPrice);
                }
            });
        }

        switch ($sort) {
            case 'name_desc':
                $productsQuery->orderBy('name', 'desc');
                break;

            case 'code_asc':
                $productsQuery->orderBy('code', 'asc');
                break;

            case 'code_desc':
                $productsQuery->orderBy('code', 'desc');
                break;

            case 'newest':
                $productsQuery->orderBy('id', 'desc');
                break;

            case 'oldest':
                $productsQuery->orderBy('id', 'asc');
                break;

            default:
                $productsQuery->orderBy('name', 'asc');
                break;
        }

        $products = $productsQuery
            ->paginate(15)
            ->withQueryString();

        $hasActiveFilters = $this->hasActiveProductFilters($filters);
        $activeFilterBadges = $this->buildProductFilterBadges($filters, $categories);
        $savedFilterBadges = $this->buildProductFilterBadges($savedFilters, $categories);

        return view('backoffice.products.index', compact(
            'products',
            'categories',
            'search',
            'code',
            'categoryId',
            'minPrice',
            'maxPrice',
            'rateStatus',
            'imageStatus',
            'sort',
            'filters',
            'hasActiveFilters',
            'hasSavedFilters',
            'usingSavedFilters',
            'activeFilterBadges',
            'savedFilterBadges'
        ));
    }

    public function saveFilters(Request $request): RedirectResponse
    {
        $filters = $this->normalizeProductFilters($request->only($this->productFilterKeys()));

        if (!$this->hasActiveProductFilters($filters)) {
            return redirect()
                ->route('products.index')
                ->with('warning', 'No hay filtros activos para guardar.');
        }

        $request->session()->put(self::PRODUCT_FILTERS_SESSION_KEY, $filters);

        return redirect()
            ->route('products.index', ['use_saved_filters' => 1])
            ->with('success', 'Filtros guardados correctamente.');
    }

    public function clearSavedFilters(Request $request): RedirectResponse
    {
        $request->session()->forget(self::PRODUCT_FILTERS_SESSION_KEY);

        return redirect()
            ->route('products.index')
            ->with('success', 'Filtros guardados eliminados correctamente.');
    }

    public function create(): View
    {
        $categories = Category::orderBy('name')->get();

        $rates = [
            [
                'start_date' => '',
                'end_date' => '',
                'price' => '',
            ]
        ];

        $existingImages = [];

        return view('backoffice.products.create', compact('categories', 'rates', 'existingImages'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateProduct($request);

        $storedPaths = [];
        $createdFromSourceMap = [];
        $createdFromNewUploadMap = [];
        $primaryImageSource = $request->input('primary_image_source');

        DB::beginTransaction();

        try {
            $product = Product::create([
                'code' => $data['code'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'min_stock' => $data['min_stock'],
            ]);

            $product->categories()->sync($data['categories']);

            foreach ($data['rates'] as $rate) {
                $product->rates()->create([
                    'start_date' => $rate['start_date'],
                    'end_date' => $rate['end_date'] ?: null,
                    'price' => $rate['price'],
                ]);
            }

            $sortOrder = 1;

            $duplicateSourceProductId = $request->input('duplicate_source_product_id');
            $copySourceImages = collect((array) $request->input('copy_source_images', []))
                ->map(fn ($id) => (int) $id)
                ->all();

            if ($duplicateSourceProductId && !empty($copySourceImages)) {
                $sourceProduct = Product::with('images')->findOrFail($duplicateSourceProductId);

                $imagesToCopy = $sourceProduct->images
                    ->whereIn('id', $copySourceImages);

                foreach ($imagesToCopy as $sourceImage) {
                    $extension = pathinfo($sourceImage->path, PATHINFO_EXTENSION);
                    $newFilename = (string) Str::uuid() . ($extension ? '.' . $extension : '');
                    $newPath = "products/{$product->id}/{$newFilename}";

                    Storage::disk('public')->copy($sourceImage->path, $newPath);
                    $storedPaths[] = $newPath;

                    $createdImage = $product->images()->create([
                        'path' => $newPath,
                        'original_name' => $sourceImage->original_name,
                        'sort_order' => $sortOrder,
                        'is_primary' => false,
                    ]);

                    $createdFromSourceMap[(int) $sourceImage->id] = $createdImage->id;
                    $sortOrder++;
                }
            }

            foreach ($request->file('images', []) as $index => $uploadedImage) {
                $path = $uploadedImage->store("products/{$product->id}", 'public');
                $storedPaths[] = $path;

                $createdImage = $product->images()->create([
                    'path' => $path,
                    'original_name' => $uploadedImage->getClientOriginalName(),
                    'sort_order' => $sortOrder,
                    'is_primary' => false,
                ]);

                $createdFromNewUploadMap[(int) $index] = $createdImage->id;
                $sortOrder++;
            }

            $this->resequenceProductImages($product);
            $this->applyPrimaryImage(
                $product,
                $primaryImageSource,
                $createdFromSourceMap,
                $createdFromNewUploadMap
            );

            $product->refresh()->load(['categories', 'rates', 'images']);
            $createdSnapshot = $this->getProductActivitySnapshot($product);

            if ($duplicateSourceProductId) {
                $sourceProduct = Product::find($duplicateSourceProductId);

                $this->logProductActivity(
                    $product,
                    'duplicated',
                    'Producto duplicado a partir de otro producto.',
                    [
                        'source_product_id' => $duplicateSourceProductId,
                        'after' => $createdSnapshot,
                    ]
                );

                if ($sourceProduct) {
                    $this->logProductActivity(
                        $sourceProduct,
                        'duplicate_source_used',
                        'Este producto se ha usado como base para crear una copia.',
                        [
                            'new_product_id' => $product->id,
                            'new_product_code' => $product->code,
                            'new_product_name' => $product->name,
                        ]
                    );
                }
            } else {
                $this->logProductActivity(
                    $product,
                    'created',
                    'Producto creado.',
                    [
                        'after' => $createdSnapshot,
                    ]
                );
            }

            $product->load(['categories', 'rates', 'images']);

            $createdSnapshot = $this->getProductActivitySnapshot($product);

            DB::commit();

            return redirect()
                ->route('products.index')
                ->with('success', 'Producto creado correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();

            foreach ($storedPaths as $path) {
                Storage::disk('public')->delete($path);
            }

            throw $e;
        }
    }

    public function edit(Product $product): View
    {
        $categories = Category::orderBy('name')->get();
        $selectedCategories = $product->categories()->pluck('categories.id')->toArray();

        $rates = $product->rates()
            ->orderBy('start_date')
            ->get()
            ->map(function ($rate) {
                return [
                    'start_date' => optional($rate->start_date)->format('Y-m-d'),
                    'end_date' => optional($rate->end_date)->format('Y-m-d'),
                    'price' => $rate->price,
                ];
            })
            ->toArray();

        if (empty($rates)) {
            $rates = [
                [
                    'start_date' => '',
                    'end_date' => '',
                    'price' => '',
                ]
            ];
        }

        $existingImages = $product->images()->get();

        return view('backoffice.products.edit', compact(
            'product',
            'categories',
            'selectedCategories',
            'rates',
            'existingImages'
        ));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validateProduct($request, $product);

        $product->Load(['categories', 'rates', 'images']);
        $beforeSnapshot = $this->getProductActivitySnapshot($product);
        $storedPaths = [];
        $createdFromNewUploadMap = [];
        $primaryImageSource = $request->input('primary_image_source');

        DB::beginTransaction();

        try {
            $product->update([
                'code' => $data['code'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'min_stock' => $data['min_stock'],
            ]);

            $product->categories()->sync($data['categories']);

            $product->rates()->delete();

            foreach ($data['rates'] as $rate) {
                $product->rates()->create([
                    'start_date' => $rate['start_date'],
                    'end_date' => $rate['end_date'] ?: null,
                    'price' => $rate['price'],
                ]);
            }

            $deleteIds = collect($request->input('delete_images', []))
                ->map(fn ($id) => (int) $id)
                ->all();

            foreach ($product->images as $image) {
                if (in_array($image->id, $deleteIds, true)) {
                    Storage::disk('public')->delete($image->path);
                    $image->delete();
                    continue;
                }

                $newSort = $request->input("existing_images.{$image->id}.sort_order", $image->sort_order);

                $image->update([
                    'sort_order' => (int) $newSort,
                    'is_primary' => false,
                ]);
            }

            $sortOrder = (int) (ProductImage::where('product_id', $product->id)->max('sort_order') ?? 0);

            foreach ($request->file('images', []) as $index => $uploadedImage) {
                $sortOrder++;

                $path = $uploadedImage->store("products/{$product->id}", 'public');
                $storedPaths[] = $path;

                $createdImage = $product->images()->create([
                    'path' => $path,
                    'original_name' => $uploadedImage->getClientOriginalName(),
                    'sort_order' => $sortOrder,
                    'is_primary' => false,
                ]);

                $createdFromNewUploadMap[(int) $index] = $createdImage->id;
            }

            $this->resequenceProductImages($product);
            $this->applyPrimaryImage($product, $primaryImageSource, [], $createdFromNewUploadMap);

            $product->refresh()->load(['categories', 'rates', 'images']);
            $afterSnapshot = $this->getProductActivitySnapshot($product);
            $changes = $this->buildSnapshotDiff($beforeSnapshot, $afterSnapshot);

            if (!empty($changes)) {
                $this->logProductActivity(
                    $product,
                    'updated',
                    'Producto actualizado.',
                    $changes
                );
            }

            DB::commit();

            return redirect()
                ->route('products.index')
                ->with('success', 'Producto actualizado correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();

            foreach ($storedPaths as $path) {
                Storage::disk('public')->delete($path);
            }

            throw $e;
        }
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->load(['categories', 'rates', 'images']);

        $beforeSnapshot = $this->getProductActivitySnapshot($product);

        $this->logProductActivity(
            $product,
            'deleted',
            'Producto enviado a la papelera.',
            [
                'before' => $beforeSnapshot,
            ]
        );

        $product->delete();

        return redirect()
            ->route('products.index')
            ->with('success', 'Producto enviado a la papelera correctamente.');
    }

    public function trash(Request $request): View
    {
        $search = trim((string) $request->query('search'));

        $productsQuery = Product::onlyTrashed()
            ->with(['categories', 'images'])
            ->orderByDesc('deleted_at')
            ->orderByDesc('id');

        if ($search !== '') {
            $productsQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('code', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        $products = $productsQuery
            ->paginate(15)
            ->withQueryString();

        return view('backoffice.products.trash', compact('products', 'search'));
    }

    public function restore(int $productId): RedirectResponse
    {
        $product = Product::onlyTrashed()->findOrFail($productId);

        $product->restore();

        $product->refresh()->load(['categories', 'rates', 'images']);

        $this->logProductActivity(
            $product,
            'restored',
            'Producto restaurado desde la papelera.',
            [
                'after' => $this->getProductActivitySnapshot($product),
            ]
        );

        return redirect()
            ->route('products.trash')
            ->with('success', 'Producto restaurado correctamente.');
    }

    public function exportXls()
    {
        return Excel::download(new ProductsExport, 'products.xlsx');
    }

    public function exportPdf(Product $product)
    {
        $product->load(['categories', 'rates', 'images']);

        $today = now()->toDateString();

        $currentRate = $product->rates
            ->filter(function ($rate) use ($today) {
                $start = $rate->start_date?->format('Y-m-d');
                $end = $rate->end_date?->format('Y-m-d');

                return $start <= $today && ($end === null || $end >= $today);
            })
            ->sortByDesc('start_date')
            ->first();

        $pdf = Pdf::loadView('backoffice.products.pdf', [
            'product' => $product,
            'currentRate' => $currentRate,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('ficha-producto-' . $product->code . '.pdf');
    }

    public function duplicate(Product $product): View
    {
        $product->load(['categories', 'rates', 'images']);

        $categories = Category::orderBy('name')->get();

        $selectedCategories = $product->categories->pluck('id')->toArray();

        $rates = $product->rates
            ->sortBy('start_date')
            ->map(function ($rate) {
                return [
                    'start_date' => optional($rate->start_date)->format('Y-m-d'),
                    'end_date' => optional($rate->end_date)->format('Y-m-d'),
                    'price' => $rate->price,
                ];
            })
            ->values()
            ->toArray();

        if (empty($rates)) {
            $rates = [
                [
                    'start_date' => '',
                    'end_date' => '',
                    'price' => '',
                ]
            ];
        }

        $duplicatedProduct = new Product([
            'code' => $this->generateDuplicateCode($product->code),
            'name' => $product->name . ' (copia)',
            'description' => $product->description,
        ]);

        $existingImages = [];
        $sourceImages = $product->images;
        $duplicateSourceProductId = $product->id;

        return view('backoffice.products.create', [
            'product' => $duplicatedProduct,
            'categories' => $categories,
            'selectedCategories' => $selectedCategories,
            'rates' => $rates,
            'existingImages' => $existingImages,
            'sourceImages' => $sourceImages,
            'duplicateSourceProductId' => $duplicateSourceProductId,
        ]);
    }

    public function history(Product $product): View
    {
        $product->load(['categories', 'images']);

        $purchaseOrdersQuery = $product->purchaseOrders()
            ->with('purchaseOrderReturns')
            ->orderByDesc('order_date')
            ->orderByDesc('id');

        $purchaseOrders = (clone $purchaseOrdersQuery)
            ->paginate(15, ['*'], 'orders_page')
            ->withQueryString();

        $activityLogsQuery = $product->activityLogs()->with('user');

        $activityLogs = (clone $activityLogsQuery)
            ->paginate(10, ['*'], 'activity_page')
            ->withQueryString();

        $totalOrders = $product->purchaseOrders()->count();
        $totalUnits = (int) $product->purchaseOrders()->sum('units');
        $totalRevenue = (float) $product->purchaseOrders()->sum('total_price');

        $lastOrder = $product->purchaseOrders()
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->first();

        $averageOrderValue = $totalOrders > 0
            ? $totalRevenue / $totalOrders
            : 0;

        $totalActivities = (clone $activityLogsQuery)->count();
        $lastActivity = (clone $activityLogsQuery)->first();

        return view('backoffice.products.history', compact(
            'product',
            'purchaseOrders',
            'totalOrders',
            'totalUnits',
            'totalRevenue',
            'lastOrder',
            'averageOrderValue',
            'activityLogs',
            'totalActivities',
            'lastActivity'
        ));
    }

    private function productFilterKeys(): array
    {
        return [
            'search',
            'code',
            'category_id',
            'min_price',
            'max_price',
            'rate_status',
            'image_status',
            'sort',
        ];
    }

    private function defaultProductFilters(): array
    {
        return [
            'search' => '',
            'code' => '',
            'category_id' => '',
            'min_price' => '',
            'max_price' => '',
            'rate_status' => '',
            'image_status' => '',
            'sort' => 'name_asc',
        ];
    }

    private function getSavedProductFilters(Request $request): array
    {
        return $this->normalizeProductFilters(
            (array) $request->session()->get(self::PRODUCT_FILTERS_SESSION_KEY, [])
        );
    }

    private function normalizeProductFilters(array $filters): array
    {
        $filters = array_merge($this->defaultProductFilters(), $filters);

        $filters['search'] = trim((string) ($filters['search'] ?? ''));
        $filters['code'] = trim((string) ($filters['code'] ?? ''));
        $filters['category_id'] = trim((string) ($filters['category_id'] ?? ''));

        $filters['min_price'] = $this->normalizeDecimalFilter($filters['min_price'] ?? '');
        $filters['max_price'] = $this->normalizeDecimalFilter($filters['max_price'] ?? '');

        if (!in_array($filters['rate_status'], ['', 'with_current', 'without_current'], true)) {
            $filters['rate_status'] = '';
        }

        if (!in_array($filters['image_status'], ['', 'with_images', 'without_images'], true)) {
            $filters['image_status'] = '';
        }

        if (!in_array($filters['sort'], ['name_asc', 'name_desc', 'code_asc', 'code_desc', 'newest', 'oldest'], true)) {
            $filters['sort'] = 'name_asc';
        }

        return $filters;
    }

    private function normalizeDecimalFilter(mixed $value): string
    {
        $value = str_replace(',', '.', trim((string) $value));

        if ($value === '' || !is_numeric($value)) {
            return '';
        }

        return number_format((float) $value, 2, '.', '');
    }

    private function hasActiveProductFilters(array $filters): bool
    {
        $filters = $this->normalizeProductFilters($filters);
        $defaults = $this->defaultProductFilters();

        foreach ($defaults as $key => $defaultValue) {
            if ((string) $filters[$key] !== (string) $defaultValue) {
                return true;
            }
        }

        return false;
    }

    private function buildProductFilterBadges(array $filters, $categories): array
    {
        $filters = $this->normalizeProductFilters($filters);

        $badges = [];

        if ($filters['search'] !== '') {
            $badges[] = 'Texto: ' . Str::limit($filters['search'], 30);
        }

        if ($filters['code'] !== '') {
            $badges[] = 'Código: ' . Str::limit($filters['code'], 20);
        }

        if ($filters['category_id'] !== '') {
            $categoryName = optional($categories->firstWhere('id', (int) $filters['category_id']))->name
                ?? ('ID ' . $filters['category_id']);

            $badges[] = 'Categoría: ' . $categoryName;
        }

        if ($filters['min_price'] !== '') {
            $badges[] = 'Precio mín.: ' . number_format((float) $filters['min_price'], 2, ',', '.') . ' €';
        }

        if ($filters['max_price'] !== '') {
            $badges[] = 'Precio máx.: ' . number_format((float) $filters['max_price'], 2, ',', '.') . ' €';
        }

        if ($filters['rate_status'] === 'with_current') {
            $badges[] = 'Tarifa: Con tarifa vigente';
        } elseif ($filters['rate_status'] === 'without_current') {
            $badges[] = 'Tarifa: Sin tarifa vigente';
        }

        if ($filters['image_status'] === 'with_images') {
            $badges[] = 'Imágenes: Con imágenes';
        } elseif ($filters['image_status'] === 'without_images') {
            $badges[] = 'Imágenes: Sin imágenes';
        }

        $sortLabels = [
            'name_asc' => 'Orden: Nombre A-Z',
            'name_desc' => 'Orden: Nombre Z-A',
            'code_asc' => 'Orden: Código A-Z',
            'code_desc' => 'Orden: Código Z-A',
            'newest' => 'Orden: Más recientes',
            'oldest' => 'Orden: Más antiguos',
        ];

        if (($filters['sort'] ?? 'name_asc') !== 'name_asc') {
            $badges[] = $sortLabels[$filters['sort']] ?? 'Orden personalizado';
        }

        return $badges;
    }

    private function resequenceProductImages(Product $product): void
    {
        $images = ProductImage::where('product_id', $product->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $counter = 1;

        foreach ($images as $image) {
            if ((int) $image->sort_order !== $counter) {
                $image->update(['sort_order' => $counter]);
            }

            $counter++;
        }
    }

    private function applyPrimaryImage(
        Product $product,
        ?string $primaryImageSource,
        array $createdFromSourceMap = [],
        array $createdFromNewUploadMap = []
        ): void {
        $images = ProductImage::where('product_id', $product->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($images->isEmpty()) {
            return;
        }

        $primaryImageId = null;

        if ($primaryImageSource) {
            if (str_starts_with($primaryImageSource, 'existing:')) {
                $candidateId = (int) substr($primaryImageSource, strlen('existing:'));

                $primaryImageId = ProductImage::where('product_id', $product->id)
                    ->where('id', $candidateId)
                    ->value('id');
            } elseif (str_starts_with($primaryImageSource, 'source:')) {
                $sourceImageId = (int) substr($primaryImageSource, strlen('source:'));
                $primaryImageId = $createdFromSourceMap[$sourceImageId] ?? null;
            } elseif (str_starts_with($primaryImageSource, 'new:')) {
                $newIndex = (int) substr($primaryImageSource, strlen('new:'));
                $primaryImageId = $createdFromNewUploadMap[$newIndex] ?? null;
            }
        }

        if (!$primaryImageId) {
            $primaryImageId = $images->first()->id;
        }

        ProductImage::where('product_id', $product->id)->update(['is_primary' => false]);

        ProductImage::where('product_id', $product->id)
            ->where('id', $primaryImageId)
            ->update(['is_primary' => true]);
    }

    private function validateProduct(Request $request, ?Product $product = null): array
    {
        $validator = Validator::make($request->all(), [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('products', 'code')->ignore($product?->id),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'min_stock' => [
                'required',
                'integer',
                'min:0',
            ],
            'categories' => [
                'required',
                'array',
                'min:1',
            ],
            'categories.*' => [
                'integer',
                'exists:categories,id',
            ],
            'rates' => [
                'required',
                'array',
                'min:1',
            ],
            'rates.*.start_date' => [
                'required',
                'date',
            ],
            'rates.*.end_date' => [
                'nullable',
                'date',
            ],
            'rates.*.price' => [
                'required',
                'numeric',
                'min:0',
            ],
            'images' => [
                'nullable',
                'array',
                'max:10',
            ],
            'images.*' => [
                File::image()->max(5 * 1024),
            ],
            'existing_images' => [
                'nullable',
                'array',
            ],
            'existing_images.*.sort_order' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'delete_images' => [
                'nullable',
                'array',
            ],
            'delete_images.*' => [
                'integer',
            ],
            'duplicate_source_product_id' => [
                'nullable',
                'integer',
                'exists:products,id',
            ],
            'copy_source_images' => [
                'nullable',
                'array',
            ],
            'copy_source_images.*' => [
                'integer',
            ],
            'primary_image_source' => [
                'nullable',
                'string',
                'max:50',
            ],
        ]);

        $validator->after(function ($validator) use ($request, $product) {
            $rates = $request->input('rates', []);

            $normalized = [];

            foreach ($rates as $index => $rate) {
                $start = $rate['start_date'] ?? null;
                $end = $rate['end_date'] ?? null;
                $price = $rate['price'] ?? null;

                if (!$start || $price === null || $price === '') {
                    continue;
                }

                $startTs = strtotime($start);
                $endTs = $end ? strtotime($end) : strtotime('9999-12-31');

                if ($end && $endTs < $startTs) {
                    $validator->errors()->add(
                        "rates.$index.end_date",
                        'La fecha fin no puede ser anterior a la fecha inicio.'
                    );
                    continue;
                }

                $normalized[] = [
                    'original_index' => $index,
                    'start_ts' => $startTs,
                    'end_ts' => $endTs,
                ];
            }

            usort($normalized, fn ($a, $b) => $a['start_ts'] <=> $b['start_ts']);

            for ($i = 1; $i < count($normalized); $i++) {
                $previous = $normalized[$i - 1];
                $current = $normalized[$i];

                if ($current['start_ts'] <= $previous['end_ts']) {
                    $validator->errors()->add(
                        "rates.{$current['original_index']}.start_date",
                        'Esta tarifa se solapa con otra tarifa del mismo producto.'
                    );
                }
            }

            if ($product) {
                $validImageIds = $product->images()->pluck('id')->all();

                foreach ((array) $request->input('delete_images', []) as $imageId) {
                    if (!in_array((int) $imageId, $validImageIds, true)) {
                        $validator->errors()->add(
                            'delete_images',
                            'Has intentado borrar una imagen que no pertenece a este producto.'
                        );
                    }
                }

                foreach ((array) $request->input('existing_images', []) as $imageId => $payload) {
                    if (!in_array((int) $imageId, $validImageIds, true)) {
                        $validator->errors()->add(
                            'existing_images',
                            'Has intentado reordenar una imagen que no pertenece a este producto.'
                        );
                    }
                }
            }

        $primaryImageSource = trim((string) $request->input('primary_image_source', ''));
        $deleteIds = collect((array) $request->input('delete_images', []))
            ->map(fn ($id) => (int) $id)
            ->all();

        $validSourceImageIds = [];

        $duplicateSourceProductId = $request->input('duplicate_source_product_id');

        if ($duplicateSourceProductId) {
            $sourceProduct = Product::with('images')->find($duplicateSourceProductId);
            $validSourceImageIds = $sourceProduct?->images->pluck('id')->all() ?? [];

            foreach ((array) $request->input('copy_source_images', []) as $imageId) {
                if (!in_array((int) $imageId, $validSourceImageIds, true)) {
                    $validator->errors()->add(
                        'copy_source_images',
                        'Has intentado copiar una imagen que no pertenece al producto original.'
                    );
                }
            }
        }

        if ($primaryImageSource !== '') {
            $validSelections = [];

            foreach ($request->file('images', []) as $index => $uploadedImage) {
                $validSelections[] = 'new:' . $index;
            }

            if ($product) {
                $validImageIds = $product->images()->pluck('id')->all();

                foreach ($validImageIds as $imageId) {
                    if (!in_array((int) $imageId, $deleteIds, true)) {
                        $validSelections[] = 'existing:' . (int) $imageId;
                    }
                }
            }

            foreach ((array) $request->input('copy_source_images', []) as $imageId) {
                if (in_array((int) $imageId, $validSourceImageIds, true)) {
                    $validSelections[] = 'source:' . (int) $imageId;
                }
            }

            if (!in_array($primaryImageSource, $validSelections, true)) {
                $validator->errors()->add(
                    'primary_image_source',
                    'La imagen principal seleccionada no es válida.'
                );
            }
        }   
        });

        return $validator->validate();
    }

    private function logProductActivity(
        Product $product,
        string $action,
        string $description,
        ?array $changes = null
    ): void {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'entity_type' => 'product',
            'entity_id' => $product->id,
            'action' => $action,
            'description' => $description,
            'changes' => $changes,
        ]);
    }

    private function getProductActivitySnapshot(Product $product): array
    {
        $product->loadMissing(['categories', 'rates', 'images']);

        return [
            'code' => (string) $product->code,
            'name' => (string) $product->name,
            'description' => (string) ($product->description ?? ''),
            'categories' => $product->categories
                ->sortBy('name')
                ->map(function ($category) {
                    return [
                        'id' => (int) $category->id,
                        'name' => (string) $category->name,
                    ];
                })
                ->values()
                ->all(),
            'rates' => $product->rates
                ->sortBy(function ($rate) {
                    return sprintf(
                        '%s|%s|%s',
                        optional($rate->start_date)->format('Y-m-d') ?? '',
                        optional($rate->end_date)->format('Y-m-d') ?? '',
                        number_format((float) $rate->price, 2, '.', '')
                    );
                })
                ->map(function ($rate) {
                    return [
                        'start_date' => optional($rate->start_date)->format('Y-m-d'),
                        'end_date' => optional($rate->end_date)->format('Y-m-d'),
                        'price' => number_format((float) $rate->price, 2, '.', ''),
                    ];
                })
                ->values()
                ->all(),
            'images' => $product->images
                ->sortBy(function ($image) {
                    return sprintf('%05d-%05d', (int) $image->sort_order, (int) $image->id);
                })
                ->map(function ($image) {
                    return [
                        'id' => (int) $image->id,
                        'original_name' => (string) ($image->original_name ?? ''),
                        'sort_order' => (int) $image->sort_order,
                        'is_primary' => (bool) $image->is_primary,
                    ];
                })
                ->values()
                ->all(),
        ];
    }

    private function buildSnapshotDiff(array $before, array $after): array
    {
        $changes = [];

        foreach ($after as $key => $afterValue) {
            $beforeValue = $before[$key] ?? null;

            if ($beforeValue != $afterValue) {
                $changes[$key] = [
                    'before' => $beforeValue,
                    'after' => $afterValue,
                ];
            }
        }

        return $changes;
    }

    private function generateDuplicateCode(string $originalCode): string
    {
        $suffixBase = '-COPIA';
        $counter = 1;

        do {
            $suffix = $counter === 1 ? $suffixBase : $suffixBase . '-' . $counter;
            $maxBaseLength = 50 - strlen($suffix);
            $candidate = substr($originalCode, 0, $maxBaseLength) . $suffix;
            $exists = Product::where('code', $candidate)->exists();
            $counter++;
        } while ($exists);

        return $candidate;
    }
}
