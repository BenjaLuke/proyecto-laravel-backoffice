@extends('backoffice.layout')

@section('title', 'Historial del producto')

@section('content')
    @php
        // Etiquetas, colores y helpers para el historial visual de cambios.
        $actionLabels = [
            'created' => 'Creación',
            'updated' => 'Edición',
            'duplicated' => 'Duplicado',
            'duplicate_source_used' => 'Usado como base',
            'deleted' => 'Eliminación',
            'restored' => 'Restauración',
        ];

        $actionBadgeClasses = [
            'created' => 'text-bg-success',
            'updated' => 'text-bg-warning',
            'duplicated' => 'text-bg-primary',
            'duplicate_source_used' => 'text-bg-info',
            'deleted' => 'text-bg-danger',
            'restored' => 'text-bg-success',
        ];

        $fieldLabels = [
            'code' => 'Código',
            'name' => 'Nombre',
            'description' => 'Descripción',
            'categories' => 'Categorías',
            'rates' => 'Tarifas',
            'images' => 'Imágenes',
        ];

        $formatValue = function ($value) {
            if ($value === null || $value === '') {
                return '—';
            }

            if (is_bool($value)) {
                return $value ? 'Sí' : 'No';
            }

            if (is_scalar($value)) {
                return (string) $value;
            }

            return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        };
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

    {{-- Cabecera de ficha histórica y accesos relacionados. --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Historial del producto</h1>
            <p class="text-muted mb-0">
                <strong>{{ $product->name }}</strong> ({{ $product->code }})
            </p>
        </div>

        <div class="d-flex gap-2">
            @if(auth()->user()->hasPermission('products_manage'))
                <a href="{{ route('products.edit', $product) }}" class="btn btn-warning">
                    Editar producto
                </a>
            @endif

            @if(auth()->user()->hasPermission('activity_view'))
                <a href="{{ route('activity.products.history', $product->id) }}" class="btn btn-outline-primary">
                    Ver historial archivado
                </a>
            @endif

            @if(auth()->user()->hasPermission('products_manage'))
                <a href="{{ route('stock-entries.create', ['product_id' => $product->id]) }}" class="btn btn-outline-primary">
                    Nueva entrada de stock
                </a>
            @endif            
            
            @if(auth()->user()->hasPermission('products_view'))
                <a href="{{ route('products.index') }}" class="btn btn-secondary">
                    Volver a productos
                </a>
            @endif

        </div>
    </div>

    @php
        // Estado de stock mostrado en las tarjetas resumen.
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

    {{-- Datos principales del producto. --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-start">
                <div class="col-md-2">
                    @if($product->images->count())
                        <img
                            src="{{ asset('storage/' . $product->images->first()->path) }}"
                            alt="{{ $product->name }}"
                            class="img-fluid rounded"
                            style="max-height: 140px; object-fit: cover;"
                        >
                    @else
                        <div class="border rounded d-flex align-items-center justify-content-center text-muted"
                             style="height: 140px;">
                            Sin imagen
                        </div>
                    @endif
                </div>

                <div class="col-md-10">
                    <h2 class="h5 mb-2">{{ $product->name }}</h2>
                    <p class="mb-2"><strong>Código:</strong> {{ $product->code }}</p>

                    <p class="mb-2">
                        <strong>Categorías:</strong>
                        @forelse($product->categories as $category)
                            <span class="badge text-bg-secondary">{{ $category->name }}</span>
                        @empty
                            -
                        @endforelse
                    </p>

                    <p class="mb-0">
                        <strong>Descripción:</strong> {{ $product->description ?: '-' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Métricas comerciales acumuladas. --}}
    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Pedidos totales</div>
                    <div class="fs-2 fw-bold">{{ $totalOrders }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Unidades vendidas</div>
                    <div class="fs-2 fw-bold">{{ $totalUnits }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Ingresos generados</div>
                    <div class="fs-5 fw-bold">{{ number_format($totalRevenue, 2, ',', '.') }} €</div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Ticket medio</div>
                    <div class="fs-5 fw-bold">{{ number_format($averageOrderValue, 2, ',', '.') }} €</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Métricas de stock y últimos eventos. --}}
    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Stock actual</div>
                    <div class="fs-2 fw-bold">{{ $currentStock }}</div>
                    <div class="small text-muted">Mínimo: {{ $minStock }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Estado de stock</div>
                    <div class="mt-2">
                        <span class="badge {{ $stockBadgeClass }}">{{ $stockLabel }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Último pedido</div>
                    <div class="fs-5 fw-bold">
                        {{ $lastOrder ? $lastOrder->order_date->format('d/m/Y') : 'Todavía no hay pedidos' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Última actividad registrada</div>
                    <div class="fs-5 fw-bold">
                        {{ $lastActivity ? $lastActivity->created_at->format('d/m/Y H:i') : 'Sin actividad todavía' }}
                    </div>
                    @if($lastActivity)
                        <div class="mt-2">
                            <span class="badge {{ $actionBadgeClasses[$lastActivity->action] ?? 'text-bg-secondary' }}">
                                {{ $actionLabels[$lastActivity->action] ?? ucfirst($lastActivity->action) }}
                            </span>
                            <span class="ms-2 text-muted">{{ $lastActivity->description }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Stock actual</div>
                    <div class="fs-2 fw-bold">{{ $currentStock }}</div>
                    <div class="small text-muted">Mínimo: {{ $minStock }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Estado de stock</div>
                    <div class="mt-2">
                        <span class="badge {{ $stockBadgeClass }}">{{ $stockLabel }}</span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Historial de actividad y detalle desplegable de cambios. --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5 mb-3">Actividad y cambios</h2>

            @if($activityLogs->count())
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <strong>{{ $totalActivities }}</strong> actividad(es) registrada(s)
                    </div>
                </div>

                <div class="d-flex flex-column gap-3">
                    @foreach($activityLogs as $activity)
                        <div class="border rounded p-3">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                                <div>
                                    <span class="badge {{ $actionBadgeClasses[$activity->action] ?? 'text-bg-secondary' }}">
                                        {{ $actionLabels[$activity->action] ?? ucfirst($activity->action) }}
                                    </span>
                                    <strong class="ms-2">{{ $activity->description }}</strong>
                                </div>

                                <div class="text-muted small">
                                    {{ $activity->created_at->format('d/m/Y H:i') }}
                                    ·
                                    {{ $activity->user?->name ?? 'Sistema' }}
                                </div>
                            </div>

                            @if(!empty($activity->changes))
                                <details>
                                    <summary class="text-primary" style="cursor: pointer;">
                                        Ver detalle de cambios
                                    </summary>

                                    <div class="mt-3 d-flex flex-column gap-3">
                                        @foreach($activity->changes as $field => $change)
                                            <div class="border rounded p-3 bg-light">
                                                <div class="fw-semibold mb-2">
                                                    {{ $fieldLabels[$field] ?? ucfirst(str_replace('_', ' ', $field)) }}
                                                </div>

                                                @if(is_array($change) && array_key_exists('before', $change) && array_key_exists('after', $change))
                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <div class="small text-muted mb-1">Antes</div>
                                                            @if(is_array($change['before']))
                                                                <pre class="small mb-0 bg-light border rounded p-2">{{ $formatValue($change['before']) }}</pre>
                                                            @else
                                                                <div class="border rounded p-2 bg-light">{{ $formatValue($change['before']) }}</div>
                                                            @endif
                                                        </div>

                                                        <div class="col-md-6">
                                                            <div class="small text-muted mb-1">Después</div>
                                                            @if(is_array($change['after']))
                                                                <pre class="small mb-0 bg-light border rounded p-2">{{ $formatValue($change['after']) }}</pre>
                                                            @else
                                                                <div class="border rounded p-2 bg-light">{{ $formatValue($change['after']) }}</div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @else
                                                    @if(is_array($change))
                                                        <pre class="small mb-0 bg-light border rounded p-2">{{ $formatValue($change) }}</pre>
                                                    @else
                                                        <div class="border rounded p-2 bg-light">{{ $formatValue($change) }}</div>
                                                    @endif
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </details>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="mt-3">
                    {{ $activityLogs->links() }}
                </div>
            @else
                <p class="mb-0">Todavía no hay actividad registrada para este producto.</p>
            @endif
        </div>
    </div>

    {{-- Pedidos asociados al producto y acciones sobre cada pedido. --}}
    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="h5 mb-3">Listado de pedidos</h2>

            @if($purchaseOrders->count())
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Unidades</th>
                                <th>Precio unitario</th>
                                <th>Total</th>
                                <th>Devuelto</th>
                                <th>Notas</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchaseOrders as $purchaseOrder)
                                @php
                                    $returnedUnits = (int) $purchaseOrder->purchaseOrderReturns->sum('returned_units');
                                @endphp
                                <tr>
                                    <td>{{ $purchaseOrder->id }}</td>
                                    <td>{{ $purchaseOrder->order_date->format('d/m/Y') }}</td>
                                    <td>
                                        @if($purchaseOrder->status === 'servido')
                                            <span class="badge text-bg-success">Servido</span>
                                        @elseif($purchaseOrder->status === 'cancelado')
                                            <span class="badge text-bg-danger">Cancelado</span>
                                        @else
                                            <span class="badge text-bg-secondary">Pendiente</span>
                                        @endif
                                    </td>
                                    <td>{{ $purchaseOrder->units }}</td>
                                    <td>{{ number_format((float) $purchaseOrder->unit_price, 2, ',', '.') }} €</td>
                                    <td>{{ number_format((float) $purchaseOrder->total_price, 2, ',', '.') }} €</td>
                                    <td>
                                        @if($returnedUnits > 0)
                                            <span class="badge text-bg-info">{{ $returnedUnits }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $purchaseOrder->notes ?: '-' }}</td>
                                    <td class="text-end">
                                        @if(auth()->user()->hasPermission('calendar_manage'))
                                            <a href="{{ route('calendar.edit', $purchaseOrder) }}" class="btn btn-sm btn-warning">
                                                Editar pedido
                                            </a>
                                        @endif

                                        @if(auth()->user()->hasPermission('calendar_manage') && $purchaseOrder->status === 'servido')
                                            <a href="{{ route('purchase-order-returns.create', $purchaseOrder) }}" class="btn btn-sm btn-outline-primary">
                                                Devolución
                                            </a>
                                        @endif

                                        @if(auth()->user()->hasPermission('calendar_view'))
                                            <a href="{{ route('calendar.index', ['month' => $purchaseOrder->order_date->format('Y-m')]) }}"
                                            class="btn btn-sm btn-primary">
                                                Ver mes
                                            </a>
                                        @endif

                                        @if(
                                            !auth()->user()->hasPermission('calendar_manage') &&
                                            !auth()->user()->hasPermission('calendar_view')
                                        )
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $purchaseOrders->links() }}
                </div>
            @else
                <p class="mb-0">Este producto todavía no tiene pedidos registrados.</p>
            @endif
        </div>
    </div>
@endsection
