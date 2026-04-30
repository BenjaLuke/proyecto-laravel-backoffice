@extends('backoffice.layout')

@section('title', 'Historial archivado del producto')

@section('content')
    @php
        // Configuración visual y formateo para mostrar cambios archivados.
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
    @endphp

    {{-- Cabecera del historial archivado de un producto concreto. --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Historial archivado del producto</h1>
            <p class="text-muted mb-0">
                <strong>{{ $productMeta['name'] }}</strong> ({{ $productMeta['code'] }})
            </p>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('activity.index') }}" class="btn btn-secondary">
                Volver a actividad
            </a>

            @if($product && !$product->trashed() && auth()->user()->hasPermission('products_view'))
                <a href="{{ route('products.history', $product->id) }}" class="btn btn-outline-primary">
                    Ver ficha viva
                </a>
            @endif

            @if($product && $product->trashed() && auth()->user()->hasPermission('products_delete'))
                <form method="POST" action="{{ route('products.restore', $product->id) }}">
                    @csrf
                    <button type="submit" class="btn btn-success">
                        Restaurar producto
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Resumen del estado actual del producto y número de eventos. --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="badge {{ $product ? 'text-bg-success' : 'text-bg-danger' }}">
                    {{ $product ? 'Producto activo' : 'Producto eliminado' }}
                </span>

                <span class="text-muted">
                    {{ $totalActivities }} actividad(es) registrada(s)
                </span>
            </div>
        </div>
    </div>

    {{-- Línea temporal de eventos y detalle opcional de cambios. --}}
    <div class="card shadow-sm">
        <div class="card-body">
            @if($activityLogs->count())
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
                <p class="mb-0">No hay actividad registrada para este producto.</p>
            @endif
        </div>
    </div>
@endsection
