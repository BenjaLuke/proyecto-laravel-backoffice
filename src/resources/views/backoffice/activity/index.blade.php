@extends('backoffice.layout')

@section('title', 'Actividad')

@section('content')
    @php
        // Etiquetas y colores para acciones registradas en el log.
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
    @endphp

    {{-- Cabecera del log global de actividad de productos. --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Actividad del backoffice</h1>
            <p class="text-muted mb-0">Registro global de actividad sobre productos.</p>
        </div>
    </div>

    {{-- Filtro por tipo de acción registrada. --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('activity.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Filtrar por acción</label>
                        <select name="action" class="form-select">
                            <option value="">Todas</option>
                            <option value="created" @selected($action === 'created')>Creación</option>
                            <option value="updated" @selected($action === 'updated')>Edición</option>
                            <option value="duplicated" @selected($action === 'duplicated')>Duplicado</option>
                            <option value="duplicate_source_used" @selected($action === 'duplicate_source_used')>Usado como base</option>
                            <option value="deleted" @selected($action === 'deleted')>Eliminación</option>
                            <option value="restored" @selected($action === 'restored')>Restauración</option>
                        </select>
                    </div>

                    <div class="col-md-auto">
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                    </div>

                    <div class="col-md-auto">
                        <a href="{{ route('activity.index') }}" class="btn btn-outline-secondary">Limpiar</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabla del log, incluyendo productos activos y archivados. --}}
    <div class="card shadow-sm">
        <div class="card-body">
            @if($activityLogs->count())
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Acción</th>
                                <th>Producto</th>
                                <th>Estado</th>
                                <th>Usuario</th>
                                <th>Descripción</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activityItems as $item)
                                @php($log = $item['log'])

                                <tr>
                                    <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>

                                    <td>
                                        <span class="badge {{ $actionBadgeClasses[$log->action] ?? 'text-bg-secondary' }}">
                                            {{ $actionLabels[$log->action] ?? ucfirst($log->action) }}
                                        </span>
                                    </td>

                                    <td>
                                        <strong>{{ $item['name'] }}</strong><br>
                                        <span class="text-muted small">{{ $item['code'] }}</span>
                                    </td>

                                    <td>
                                        @if($item['is_deleted'])
                                            <span class="badge text-bg-danger">Eliminado</span>
                                        @else
                                            <span class="badge text-bg-success">Activo</span>
                                        @endif
                                    </td>

                                    <td>{{ $log->user?->name ?? 'Sistema' }}</td>

                                    <td>{{ $log->description }}</td>

                                    <td class="text-end">
                                        <a href="{{ route('activity.products.history', $log->entity_id) }}" class="btn btn-sm btn-primary">
                                            Ver historial archivado
                                        </a>

                                        @if(!$item['is_deleted'] && auth()->user()->hasPermission('products_view'))
                                            <a href="{{ route('products.history', $log->entity_id) }}" class="btn btn-sm btn-outline-secondary">
                                                Ver ficha viva
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $activityLogs->links() }}
                </div>
            @else
                <p class="mb-0">Todavía no hay actividad registrada.</p>
            @endif
        </div>
    </div>
@endsection
