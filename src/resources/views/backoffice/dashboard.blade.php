@extends('backoffice.layout')

@section('title', 'Dashboard')

@section('content')
    @php
        $catalogStatusClass = 'ok';
        $catalogStatusText = 'Correcto';
        $catalogStatusIcon = 'bi-check-circle-fill';
        $today = now()->toDateString();

        if ($productsWithoutActiveRateCount > 0) {
            $catalogStatusClass = 'alert';
            $catalogStatusText = 'Crítico';
            $catalogStatusIcon = 'bi-exclamation-octagon-fill';
        } elseif (
            $productsWithoutImagesCount > 0 ||
            $categoriesWithoutProductsCount > 0 ||
            $ratesExpiringSoonCount > 0
        ) {
            $catalogStatusClass = 'warn';
            $catalogStatusText = 'Atención';
            $catalogStatusIcon = 'bi-exclamation-triangle-fill';
        }
    @endphp

    <div class="hero-panel p-4 mb-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="status-pill {{ $catalogStatusClass }}">
                        <i class="bi {{ $catalogStatusIcon }}"></i>
                        {{ $catalogStatusText }}
                    </span>
                    <span class="muted-note">Estado general del catálogo</span>
                </div>

                <h1 class="h3 mb-1">Hola, {{ auth()->user()->name }}</h1>
                <p class="text-muted mb-1">Bienvenido al backoffice.</p>
                <p class="mb-0"><strong>Usuario:</strong> {{ auth()->user()->username }}</p>
            </div>

            <div class="text-lg-end">
                <div class="section-title mb-2">Resumen rápido</div>
                <div class="fw-semibold">Productos: {{ $productsCount }} · Categorías: {{ $categoriesCount }}</div>
                <div class="text-muted">Pedidos hoy: {{ $ordersToday }} · Pedidos del mes: {{ $ordersThisMonth }}</div>
            </div>
        </div>
    </div>

    <div class="section-title">Actividad comercial</div>
    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card-lift p-3 h-100">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <div class="metric-label">Productos</div>
                        <div class="metric-value">{{ $productsCount }}</div>
                    </div>
                    <span class="metric-icon icon-primary">
                        <i class="bi bi-box-seam"></i>
                    </span>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card-lift p-3 h-100">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <div class="metric-label">Categorías</div>
                        <div class="metric-value">{{ $categoriesCount }}</div>
                    </div>
                    <span class="metric-icon icon-secondary">
                        <i class="bi bi-diagram-3"></i>
                    </span>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            @if(auth()->user()->hasPermission('calendar_view'))
                <a href="{{ route('calendar.index', ['date_from' => $today, 'date_to' => $today]) }}" class="card-lift metric-link-card p-3 h-100 d-block text-decoration-none">
            @else
                <div class="card-lift p-3 h-100">
            @endif
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <div class="metric-label">Pedidos hoy</div>
                        <div class="metric-value">{{ $ordersToday }}</div>
                    </div>
                    <span class="metric-icon icon-success">
                        <i class="bi bi-bag-check"></i>
                    </span>
                </div>
            @if(auth()->user()->hasPermission('calendar_view'))
                </a>
            @else
                </div>
            @endif
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card-lift p-3 h-100">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <div class="metric-label">Facturación hoy</div>
                        <div class="metric-subvalue">{{ number_format($revenueToday, 2, ',', '.') }} €</div>
                    </div>
                    <span class="metric-icon icon-info">
                        <i class="bi bi-cash-stack"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card-lift p-3 h-100">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <div class="metric-label">Pedidos este mes</div>
                        <div class="metric-value">{{ $ordersThisMonth }}</div>
                    </div>
                    <span class="metric-icon icon-primary">
                        <i class="bi bi-calendar3"></i>
                    </span>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card-lift p-3 h-100">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <div class="metric-label">Unidades este mes</div>
                        <div class="metric-value">{{ $unitsThisMonth }}</div>
                    </div>
                    <span class="metric-icon icon-success">
                        <i class="bi bi-box2-heart"></i>
                    </span>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card-lift p-3 h-100">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <div class="metric-label">Facturación este mes</div>
                        <div class="metric-subvalue">{{ number_format($revenueThisMonth, 2, ',', '.') }} €</div>
                    </div>
                    <span class="metric-icon icon-info">
                        <i class="bi bi-graph-up-arrow"></i>
                    </span>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card-lift p-3 h-100">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <div class="metric-label">Ticket medio mensual</div>
                        <div class="metric-subvalue">{{ number_format($averageTicketThisMonth, 2, ',', '.') }} €</div>
                    </div>
                    <span class="metric-icon icon-warning">
                        <i class="bi bi-receipt"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="section-title">Control interno</div>
    
    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3">
            @if(auth()->user()->hasPermission('products_view'))
                <a href="{{ route('products.index', ['image_status' => 'without_images']) }}" class="card-lift metric-link-card p-3 h-100 d-block text-decoration-none">
            @else
                <div class="card-lift p-3 h-100">
            @endif
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <div class="metric-label">Productos sin imágenes</div>
                        <div class="metric-value {{ $productsWithoutImagesCount === 0 ? 'text-success' : 'text-warning' }}">
                            {{ $productsWithoutImagesCount }}
                        </div>
                    </div>
                    <span class="metric-icon {{ $productsWithoutImagesCount === 0 ? 'icon-success' : 'icon-warning' }}">
                        <i class="bi bi-image"></i>
                    </span>
                </div>
            @if(auth()->user()->hasPermission('products_view'))
                </a>
            @else
                </div>
            @endif
        </div>

        <div class="col-md-6 col-xl-3">
            @if(auth()->user()->hasPermission('products_view'))
                <a href="{{ route('products.index', ['rate_status' => 'without_current']) }}" class="card-lift metric-link-card p-3 h-100 d-block text-decoration-none">
            @else
                <div class="card-lift p-3 h-100">
            @endif
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <div class="metric-label">Productos sin tarifa activa</div>
                        <div class="metric-value {{ $productsWithoutActiveRateCount === 0 ? 'text-success' : 'text-warning' }}">
                            {{ $productsWithoutActiveRateCount }}
                        </div>
                    </div>
                    <span class="metric-icon {{ $productsWithoutActiveRateCount === 0 ? 'icon-success' : 'icon-warning' }}">
                        <i class="bi bi-exclamation-diamond"></i>
                    </span>
                </div>
            @if(auth()->user()->hasPermission('products_view'))
                </a>
            @else
                </div>
            @endif
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card-lift p-3 h-100">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <div class="metric-label">Categorías sin productos</div>
                        <div class="metric-value">{{ $categoriesWithoutProductsCount }}</div>
                    </div>
                    <span class="metric-icon icon-secondary">
                        <i class="bi bi-folder-x"></i>
                    </span>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            @if(auth()->user()->hasPermission('products_view'))
                <a href="{{ route('products.index', ['rate_status' => 'expiring_soon']) }}" class="card-lift metric-link-card p-3 h-100 d-block text-decoration-none">
            @else
                <div class="card-lift p-3 h-100">
            @endif
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <div class="metric-label">Tarifas que caducan en 7 días</div>
                        <div class="metric-value text-info">{{ $ratesExpiringSoonCount }}</div>
                    </div>
                    <span class="metric-icon icon-info">
                        <i class="bi bi-alarm"></i>
                    </span>
                </div>
            @if(auth()->user()->hasPermission('products_view'))
                </a>
            @else
                </div>
            @endif
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card-lift p-4 h-100">
                <h2 class="h5 mb-3">Semáforo del sistema</h2>

                <div class="d-flex flex-column gap-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="signal-dot {{ $productsWithoutImagesCount > 0 ? 'warn' : 'ok' }}"></span>
                            Imágenes de productos
                        </div>
                        <strong>{{ $productsWithoutImagesCount > 0 ? 'Atención' : 'Correcto' }}</strong>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="signal-dot {{ $productsWithoutActiveRateCount > 0 ? 'danger' : 'ok' }}"></span>
                            Tarifas activas
                        </div>
                        <strong>{{ $productsWithoutActiveRateCount > 0 ? 'Crítico' : 'Correcto' }}</strong>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="signal-dot {{ $categoriesWithoutProductsCount > 0 ? 'warn' : 'ok' }}"></span>
                            Categorías con contenido
                        </div>
                        <strong>{{ $categoriesWithoutProductsCount > 0 ? 'Revisar' : 'Correcto' }}</strong>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="signal-dot {{ $ratesExpiringSoonCount > 0 ? 'info' : 'ok' }}"></span>
                            Caducidad de tarifas
                        </div>
                        <strong>{{ $ratesExpiringSoonCount > 0 ? 'Próxima' : 'Estable' }}</strong>
                    </div>
                </div>

                <hr>

                @if(
                    $productsWithoutImagesCount === 0 &&
                    $productsWithoutActiveRateCount === 0 &&
                    $categoriesWithoutProductsCount === 0 &&
                    $ratesExpiringSoonCount === 0
                )
                    <div class="alert alert-success mb-0">
                        El sistema está limpio. Ahora mismo no hay avisos pendientes.
                    </div>
                @else
                    <div class="alert alert-light border mb-0">
                        Hay elementos que conviene revisar. Nada dramático… salvo que las tarifas empiecen a desaparecer.
                    </div>
                @endif
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card-lift p-4 h-100">
                <h2 class="h5 mb-3">Accesos rápidos</h2>

                <div class="d-flex flex-wrap gap-2">
                    @if(auth()->user()->hasPermission('categories_view'))
                        <a href="{{ route('categories.index') }}" class="quick-link">
                            <i class="bi bi-tags"></i>
                            Categorías
                        </a>
                    @endif

                    @if(auth()->user()->hasPermission('products_view'))
                        <a href="{{ route('products.index') }}" class="quick-link">
                            <i class="bi bi-box-seam"></i>
                            Productos
                        </a>
                    @endif

                    @if(auth()->user()->hasPermission('calendar_view'))
                        <a href="{{ route('calendar.index') }}" class="quick-link">
                            <i class="bi bi-calendar-event"></i>
                            Calendario
                        </a>
                    @endif
                </div>

                <hr>

                <h3 class="h6 mb-3">Producto más vendido del mes</h3>

                @if($topProductThisMonth && $topProductThisMonth->product)
                    <div class="d-flex align-items-start gap-3">
                        <span class="metric-icon icon-success">
                            <i class="bi bi-trophy"></i>
                        </span>
                        <div>
                            <p class="mb-1">
                                <strong>{{ $topProductThisMonth->product->name }}</strong>
                                <span class="text-muted">({{ $topProductThisMonth->product->code }})</span>
                            </p>
                            <p class="mb-1"><strong>Unidades:</strong> {{ (int) $topProductThisMonth->total_units }}</p>
                            <p class="mb-0"><strong>Ingresos:</strong> {{ number_format((float) $topProductThisMonth->total_revenue, 2, ',', '.') }} €</p>
                        </div>
                    </div>
                @else
                    <p class="mb-0 text-muted">Todavía no hay pedidos este mes.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card-lift p-4 h-100">
                <h2 class="h5 mb-3">Tarifas próximas a caducar</h2>

                @if($ratesExpiringSoon->count())
                    <ul class="list-group list-group-flush">
                        @foreach($ratesExpiringSoon as $rate)
                            <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{ $rate->product?->name ?? 'Producto eliminado' }}</strong>
                                    <div class="text-muted small">
                                        {{ $rate->product?->code ?? 'Sin código' }}
                                    </div>
                                </div>
                                <span class="count-badge warn">
                                    {{ $rate->end_date?->format('d/m/Y') }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="mb-0 text-muted">No hay tarifas próximas a caducar.</p>
                @endif
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card-lift p-4 h-100">
                <h2 class="h5 mb-3">Últimos productos creados</h2>

                @if($latestProducts->count())
                    <div class="table-responsive">
                        <table class="table table-clean align-middle">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Imágenes</th>
                                    <th>Tarifas</th>
                                    <th>Categorías</th>
                                    <th>Alta</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($latestProducts as $product)
                                    <tr>
                                        <td>
                                            <strong>{{ $product->name }}</strong><br>
                                            <span class="text-muted small">{{ $product->code }}</span>
                                        </td>
                                        <td>
                                            <span class="count-badge {{ $product->images_count > 0 ? 'good' : 'zero' }}">
                                                {{ $product->images_count }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="count-badge {{ $product->rates_count > 0 ? 'good' : 'danger' }}">
                                                {{ $product->rates_count }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="count-badge {{ $product->categories_count > 0 ? 'good' : 'zero' }}">
                                                {{ $product->categories_count }}
                                            </span>
                                        </td>
                                        <td>{{ $product->created_at?->format('d/m/Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="mb-0 text-muted">Todavía no hay productos registrados.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="card-lift p-4">
        <h2 class="h5 mb-3">Últimos 5 pedidos</h2>

        @if($latestOrders->count())
            <div class="table-responsive">
                <table class="table table-clean align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Producto</th>
                            <th>Unidades</th>
                            <th>Total</th>
                            <th class="text-end">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($latestOrders as $order)
                            <tr>
                                <td>{{ $order->id }}</td>
                                <td>{{ $order->order_date->format('d/m/Y') }}</td>
                                <td>{{ $order->product?->name ?? '-' }}</td>
                                <td>
                                    <span class="count-badge good">{{ $order->units }}</span>
                                </td>
                                <td><strong>{{ number_format((float) $order->total_price, 2, ',', '.') }} €</strong></td>
                                <td class="text-end">
                                    @if(auth()->user()->hasPermission('calendar_manage'))
                                        <a href="{{ route('calendar.edit', $order) }}" class="btn btn-sm btn-warning">
                                            Editar
                                        </a>
                                    @elseif(auth()->user()->hasPermission('calendar_view'))
                                        <a href="{{ route('calendar.index', ['month' => $order->order_date->format('Y-m')]) }}" class="btn btn-sm btn-outline-primary">
                                            Ver mes
                                        </a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="mb-0 text-muted">Todavía no hay pedidos registrados.</p>
        @endif
    </div>
@endsection
