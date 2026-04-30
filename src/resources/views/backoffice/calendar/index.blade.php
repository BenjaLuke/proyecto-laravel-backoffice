@extends('backoffice.layout')

@section('title', 'Calendario de pedidos')

@section('content')
    {{-- Cabecera con navegación, creación y exportaciones del calendario. --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Calendario de pedidos</h1>
            <p class="text-muted mb-0">{{ $titleLabel }}</p>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            @unless($isRangeMode)
                <a href="{{ route('calendar.index', ['month' => $currentMonth->copy()->subMonth()->format('Y-m')]) }}" class="btn btn-outline-secondary">
                    Mes anterior
                </a>
            @endunless

            <a href="{{ route('calendar.create', ['date' => $isRangeMode ? $dateFrom : $currentMonth->format('Y-m-d')]) }}" class="btn btn-primary">
                Nuevo pedido
            </a>

            <a href="{{ route('calendar.export.xls', [
                'month' => $currentMonth->format('Y-m'),
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ]) }}" class="btn btn-success">
                Exportar XLS
            </a>

            <a href="{{ route('calendar.export.pdf', [
                'month' => $currentMonth->format('Y-m'),
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ]) }}" class="btn btn-danger">
                Exportar PDF
            </a>

            @if($isRangeMode)
                <a href="{{ route('calendar.index') }}" class="btn btn-outline-secondary">
                    Quitar filtro
                </a>
            @else
                <a href="{{ route('calendar.index', ['month' => $currentMonth->copy()->addMonth()->format('Y-m')]) }}" class="btn btn-outline-secondary">
                    Mes siguiente
                </a>
            @endif
        </div>
    </div>

    {{-- Filtro por rango de fechas; cuando se usa, sustituye la vista mensual. --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('calendar.index') }}">
                <input type="hidden" name="month" value="{{ $currentMonth->format('Y-m') }}">

                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="date_from" class="form-label">Fecha desde</label>
                        <input
                            type="date"
                            name="date_from"
                            id="date_from"
                            class="form-control"
                            value="{{ $dateFrom ?? '' }}"
                        >
                    </div>

                    <div class="col-md-4">
                        <label for="date_to" class="form-label">Fecha hasta</label>
                        <input
                            type="date"
                            name="date_to"
                            id="date_to"
                            class="form-control"
                            value="{{ $dateTo ?? '' }}"
                        >
                    </div>

                    <div class="col-md-4">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                Filtrar rango
                            </button>

                            <a href="{{ route('calendar.index', ['month' => $currentMonth->format('Y-m')]) }}" class="btn btn-outline-secondary">
                                Limpiar
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Traducción visual de estados de pedido a badges de Bootstrap. --}}
    @php
        $statusClasses = [
            'pendiente' => 'secondary',
            'servido' => 'success',
            'cancelado' => 'danger',
        ];

        $statusLabels = [
            'pendiente' => 'Pendiente',
            'servido' => 'Servido',
            'cancelado' => 'Cancelado',
        ];
    @endphp

    {{-- Rejilla principal: cabecera semanal y tarjetas de cada día visible. --}}
    <div class="calendar-grid">
        @foreach(['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $weekday)
            <div class="calendar-weekday">{{ $weekday }}</div>
        @endforeach

        @foreach($days as $day)
            @php
                // Datos derivados de cada día para pintar estado, pedidos y resaltado.
                $dayKey = $day->format('Y-m-d');
                $dayOrders = $orders->get($dayKey, collect());
                $isCurrentMonth = $day->month === $currentMonth->month;
                $isToday = $day->isToday();
            @endphp

            <div class="calendar-day {{ $isCurrentMonth ? '' : 'calendar-day--muted' }} {{ $isToday ? 'calendar-day--today' : '' }}">
                <div class="calendar-day__header">
                    <strong>{{ $day->format('d') }}</strong>

                    <a href="{{ route('calendar.create', ['date' => $dayKey]) }}" class="btn btn-sm btn-outline-primary">
                        +
                    </a>
                </div>

                <div class="calendar-day__body">
                    @forelse($dayOrders as $order)
                        {{-- Tarjeta de pedido dentro del día, coloreada por estado. --}}
                        <div class="calendar-order calendar-order--{{ $order->status ?? 'pending' }}">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                <div class="small fw-bold">{{ $order->product->name }}</div>

                                <span class="badge text-bg-{{ $statusClasses[$order->status] ?? 'secondary' }}">
                                    {{ $statusLabels[$order->status] ?? 'Sin estado' }}
                                </span>
                            </div>

                            <div class="small">Unidades: {{ $order->units }}</div>
                            <div class="small">Total: {{ number_format((float) $order->total_price, 2, ',', '.') }} €</div>

                            <div class="mt-2 d-flex gap-1 flex-wrap">
                                <a href="{{ route('calendar.edit', $order) }}" class="btn btn-sm btn-warning">Editar</a>

                                <form action="{{ route('calendar.destroy', $order) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="submit"
                                        class="btn btn-sm btn-danger"
                                        onclick="return confirm('¿Seguro que quieres borrar este pedido?')"
                                    >
                                        Borrar
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="small text-muted">Sin pedidos</div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>

    {{-- Estilos propios del calendario: rejilla, tarjetas de día y pedidos. --}}
    <style>
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 12px;
        }

        .calendar-weekday {
            background: #212529;
            color: white;
            text-align: center;
            padding: 10px;
            border-radius: 10px;
            font-weight: 600;
        }

        .calendar-day {
            position: relative;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            min-height: 180px;
            display: flex;
            flex-direction: column;
            padding: 10px;
        }

        html[data-theme="dark"] .calendar-day {
            background: #1e293b;
            border-color: rgba(148, 163, 184, 0.28);
        }

        .calendar-day--today {
            z-index: 1;
        }

        .calendar-day--today::before {
            content: "";
            position: absolute;
            inset: -6px;
            z-index: -1;
            border-radius: 14px;
            background: rgba(25, 135, 84, 0.18);
            border: 1px solid rgba(25, 135, 84, 0.28);
        }

        html[data-theme="dark"] .calendar-day--today::before {
            background: rgba(34, 197, 94, 0.20);
            border-color: rgba(34, 197, 94, 0.36);
        }

        .calendar-day--muted {
            opacity: 0.45;
            background: #f8f9fa;
        }

        .calendar-day__header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .calendar-day__body {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .calendar-order {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 8px;
            background: #f8f9fa;
            border-left: 6px solid #6c757d;
            color: #111827;
        }

        .calendar-order--pendiente {
            background: #f1f3f5;
            border-color: #ced4da;
            border-left-color: #6c757d;
        }

        .calendar-order--servido {
            background: #d1e7dd;
            border-color: #a3cfbb;
            border-left-color: #198754;
        }

        .calendar-order--cancelado {
            background: #f8d7da;
            border-color: #f1aeb5;
            border-left-color: #dc3545;
        }

        @media (max-width: 992px) {
            .calendar-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .calendar-weekday {
                display: none;
            }
        }

        @media (max-width: 576px) {
            .calendar-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection
