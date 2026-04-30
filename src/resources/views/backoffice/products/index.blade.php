@extends('backoffice.layout')

@section('title', 'Productos')

    <style>
        .product-actions-cell {
            min-width: 260px;
        }

        .product-actions-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(110px, 1fr));
            gap: 0.5rem;
        }

        .product-actions-grid .btn,
        .product-actions-grid form {
            width: 100%;
            margin: 0;
        }

        .product-actions-grid .btn {
            min-height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            white-space: nowrap;
        }

        .product-actions-grid form .btn {
            width: 100%;
        }
    </style>

    <style>
        .product-actions-cell {
            min-width: 260px;
        }

        .product-actions-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(110px, 1fr));
            gap: 0.5rem;
        }

        .product-actions-grid .btn,
        .product-actions-grid form {
            width: 100%;
            margin: 0;
        }

        .product-actions-grid .btn {
            min-height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            white-space: nowrap;
        }

        .product-actions-grid form .btn {
            width: 100%;
        }

        .stock-stack {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            min-width: 130px;
        }

        .stock-number {
            font-size: 1.05rem;
            font-weight: 700;
            line-height: 1.1;
        }

        .stock-min {
            font-size: 0.85rem;
        }
    </style>

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Productos</h1>

        <div class="d-flex gap-2">
            @if(auth()->user()->hasPermission('products_view'))
                <a href="{{ route('products.export.xls') }}" class="btn btn-success">
                    Exportar XLS
                </a>
            @endif

            @if(auth()->user()->hasPermission('products_manage'))
                <a href="{{ route('products.create') }}" class="btn btn-primary">
                    Nuevo producto
                </a>
            @endif

            @if(auth()->user()->hasPermission('products_view'))
                <a href="{{ route('stock-entries.index') }}" class="btn btn-outline-primary">
                    Stock
                </a>
            @endif

            @if(auth()->user()->hasPermission('products_delete'))
                <a href="{{ route('products.trash') }}" class="btn btn-outline-danger">
                    Papelera
                </a>
            @endif
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('products.index') }}">
                <div class="row g-3">
                    <div class="col-md-6 col-xl-3">
                        <label class="form-label">Texto libre</label>
                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            placeholder="Nombre, código o descripción"
                            value="{{ $search ?? '' }}"
                        >
                    </div>

                    <div class="col-md-6 col-xl-2">
                        <label class="form-label">Código</label>
                        <input
                            type="text"
                            name="code"
                            class="form-control"
                            placeholder="Código"
                            value="{{ $code ?? '' }}"
                        >
                    </div>

                    <div class="col-md-6 col-xl-2">
                        <label class="form-label">Categoría</label>
                        <select name="category_id" class="form-select">
                            <option value="">Todas</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) $categoryId === (string) $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 col-xl-2">
                        <label class="form-label">Precio mínimo</label>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            name="min_price"
                            class="form-control"
                            value="{{ $minPrice ?? '' }}"
                        >
                    </div>

                    <div class="col-md-6 col-xl-2">
                        <label class="form-label">Precio máximo</label>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            name="max_price"
                            class="form-control"
                            value="{{ $maxPrice ?? '' }}"
                        >
                    </div>

                    <div class="col-md-6 col-xl-2">
                        <label class="form-label">Tarifa vigente</label>
                        <select name="rate_status" class="form-select">
                            <option value="">Todas</option>
                            <option value="with_current" @selected(($rateStatus ?? '') === 'with_current')>
                                Con tarifa vigente
                            </option>
                            <option value="without_current" @selected(($rateStatus ?? '') === 'without_current')>
                                Sin tarifa vigente
                            </option>
                            <option value="expiring_soon" @selected(($rateStatus ?? '') === 'expiring_soon')>
                                Caduca en 7 dias
                            </option>
                        </select>
                    </div>

                    <div class="col-md-6 col-xl-2">
                        <label class="form-label">Estado de imágenes</label>
                        <select name="image_status" class="form-select">
                            <option value="">Todas</option>
                            <option value="with_images" @selected(($imageStatus ?? '') === 'with_images')>
                                Con imágenes
                            </option>
                            <option value="without_images" @selected(($imageStatus ?? '') === 'without_images')>
                                Sin imágenes
                            </option>
                        </select>
                    </div>

                    <div class="col-md-6 col-xl-2">
                        <label class="form-label">Ordenar por</label>
                        <select name="sort" class="form-select">
                            <option value="name_asc" @selected(($sort ?? '') === 'name_asc')>Nombre A-Z</option>
                            <option value="name_desc" @selected(($sort ?? '') === 'name_desc')>Nombre Z-A</option>
                            <option value="code_asc" @selected(($sort ?? '') === 'code_asc')>Código A-Z</option>
                            <option value="code_desc" @selected(($sort ?? '') === 'code_desc')>Código Z-A</option>
                            <option value="newest" @selected(($sort ?? '') === 'newest')>Más recientes</option>
                            <option value="oldest" @selected(($sort ?? '') === 'oldest')>Más antiguos</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        Filtrar
                    </button>

                    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">
                        Limpiar filtros
                    </a>

                    @if($hasSavedFilters)
                        <a href="{{ route('products.index', ['use_saved_filters' => 1]) }}" class="btn btn-outline-info">
                            Cargar filtros guardados
                        </a>
                    @endif
                </div>
            </form>

            <div class="d-flex flex-wrap gap-2 mt-3">
                @if($hasActiveFilters)
                    <form method="POST" action="{{ route('products.filters.save') }}" class="d-inline">
                        @csrf
                        @foreach($filters as $filterKey => $filterValue)
                            @if($filterValue !== '')
                                <input type="hidden" name="{{ $filterKey }}" value="{{ $filterValue }}">
                            @endif
                        @endforeach

                        <button type="submit" class="btn btn-outline-success">
                            Guardar filtros actuales
                        </button>
                    </form>
                @endif

                @if($hasSavedFilters)
                    <form method="POST" action="{{ route('products.filters.clear') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger">
                            Borrar filtros guardados
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    @if($hasActiveFilters)
        <div class="card shadow-sm mb-3 border-primary">
            <div class="card-body py-3">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <strong>Filtros activos:</strong>

                    @foreach($activeFilterBadges as $badge)
                        <span class="badge text-bg-primary">{{ $badge }}</span>
                    @endforeach

                    @if($usingSavedFilters)
                        <span class="badge text-bg-info">Aplicando filtros guardados</span>
                    @endif
                </div>
            </div>
        </div>
    @endif

    @if($hasSavedFilters)
        <div class="card shadow-sm mb-4 border-success">
            <div class="card-body py-3">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <strong>Filtros guardados:</strong>

                    @foreach($savedFilterBadges as $badge)
                        <span class="badge text-bg-success">{{ $badge }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            @if($products->count())
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <strong>{{ $products->total() }}</strong> producto(s) encontrado(s)
                    </div>

                    @if($hasActiveFilters)
                        <small class="text-muted">Listado filtrado</small>
                    @endif
                </div>

                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Imagen</th>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Categorías</th>
                                <th>Tarifa vigente</th>
                                <th>Stock</th>
                                <th>Estado stock</th>
                                <th>Descripción</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($products as $product)
                                @php
                                    $currentRate = $product->rates->first();
                                    $currentStock = (int) $product->current_stock;
                                    $minStock = (int) $product->min_stock;

                                    if ($currentStock <= 0) {
                                        $stockLabel = 'Agotado';
                                        $stockBadgeClass = 'text-bg-danger';
                                    } elseif ($minStock > 0 && $currentStock <= $minStock) {
                                        $stockLabel = 'Stock bajo';
                                        $stockBadgeClass = 'text-bg-warning';
                                    } elseif ($minStock === 0) {
                                        $stockLabel = 'Sin mínimo';
                                        $stockBadgeClass = 'text-bg-secondary';
                                    } else {
                                        $stockLabel = 'Correcto';
                                        $stockBadgeClass = 'text-bg-success';
                                    }
                                @endphp
                                <tr>
                                    <td>{{ $product->id }}</td>

                                    <td>
                                        @if($product->images->count())
                                            <img
                                                src="{{ asset('storage/' . $product->images->first()->path) }}"
                                                alt="{{ $product->name }}"
                                                style="width: 70px; height: 70px; object-fit: cover; border-radius: 8px;"
                                            >
                                        @else
                                            -
                                        @endif
                                    </td>

                                    <td>{{ $product->code }}</td>
                                    <td>{{ $product->name }}</td>

                                    <td>
                                        @forelse($product->categories as $category)
                                            <span class="badge text-bg-secondary">{{ $category->name }}</span>
                                        @empty
                                            -
                                        @endforelse
                                    </td>

                                    <td>
                                        @if($currentRate)
                                            {{ number_format((float) $currentRate->price, 2, ',', '.') }} €
                                        @else
                                            <span class="text-muted">Sin tarifa vigente</span>
                                        @endif
                                    </td>

                                    <td>
                                        <div class="stock-stack">
                                            <div class="stock-number">{{ $currentStock }}</div>
                                            <div class="stock-min text-muted">
                                                mínimo: {{ $minStock }}
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <span class="badge {{ $stockBadgeClass }}">
                                            {{ $stockLabel }}
                                        </span>
                                    </td>

                                    <td>{{ $product->description ?: '-' }}</td>

                                    <td class="product-actions-cell">
                                        <div class="product-actions-grid">
                                            @if(auth()->user()->hasPermission('products_view'))
                                                <a href="{{ route('products.export.pdf', $product) }}" class="btn btn-sm btn-success">
                                                    PDF
                                                </a>
                                            @endif

                                            @if(auth()->user()->hasPermission('products_view'))
                                                <a href="{{ route('products.history', $product) }}" class="btn btn-sm btn-info">
                                                    Historial
                                                </a>
                                            @endif

                                            @if(auth()->user()->hasPermission('products_manage'))
                                                <a href="{{ route('stock-entries.create', ['product_id' => $product->id]) }}" class="btn btn-sm btn-outline-primary">
                                                    Entrada
                                                </a>
                                            @endif

                                            @if(auth()->user()->hasPermission('products_manage'))
                                                <a href="{{ route('products.duplicate', ['product' => $product->id]) }}" class="btn btn-sm btn-purple">
                                                    Duplicar
                                                </a>
                                            @endif

                                            @if(auth()->user()->hasPermission('products_manage'))
                                                <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-warning">
                                                    Editar
                                                </a>
                                            @endif

                                            @if(auth()->user()->hasPermission('products_delete'))
                                                <form action="{{ route('products.destroy', $product) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button
                                                        type="submit"
                                                        class="btn btn-sm btn-danger"
                                                        onclick="return confirm('¿Seguro que quieres eliminar este producto?')"
                                                    >
                                                        Borrar
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $products->links() }}
                </div>
            @else
                <p class="mb-1">No se han encontrado productos con los filtros actuales.</p>
                <p class="mb-0 text-muted">Prueba a limpiar filtros o a cargar otros guardados.</p>
            @endif
        </div>
    </div>
@endsection
