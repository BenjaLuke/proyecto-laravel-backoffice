<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pedidos {{ $titleLabel }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #222;
        }

        h1 {
            margin-bottom: 4px;
        }

        .subtitle {
            margin-bottom: 20px;
            color: #555;
        }

        .summary {
            margin-bottom: 20px;
        }

        .summary p {
            margin: 3px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 6px;
            vertical-align: top;
        }

        th {
            background: #f2f2f2;
            text-align: left;
        }

        .small {
            font-size: 10px;
        }
    </style>
</head>
<body>
    <h1>Informe de pedidos</h1>
    <div class="subtitle">{{ $titleLabel }}</div>

    <div class="summary">
        <p><strong>Total de pedidos:</strong> {{ $orders->count() }}</p>
        <p><strong>Total de unidades:</strong> {{ $totalUnits }}</p>
        <p><strong>Facturación total:</strong> {{ number_format($totalRevenue, 2, ',', '.') }} €</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Fecha</th>
                <th>Producto</th>
                <th>Unidades</th>
                <th>Precio unitario</th>
                <th>Total</th>
                <th>Estado</th>
                <th>Notas</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
                <tr>
                    <td>{{ $order->id }}</td>
                    <td>{{ $order->order_date?->format('d/m/Y') }}</td>
                    <td>{{ $order->product?->name ?? '-' }}</td>
                    <td>{{ $order->units }}</td>
                    <td>{{ number_format((float) $order->unit_price, 2, ',', '.') }} €</td>
                    <td>{{ number_format((float) $order->total_price, 2, ',', '.') }} €</td>
                    <td>
                        @switch($order->status)
                            @case('pending') Pendiente @break
                            @case('confirmed') Confirmado @break
                            @case('prepared') Preparado @break
                            @case('delivered') Entregado @break
                            @case('cancelled') Cancelado @break
                            @default Sin estado
                        @endswitch
                    </td>
                    <td class="small">{{ $order->notes ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">No hay pedidos en este mes.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>