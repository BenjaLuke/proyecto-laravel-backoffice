<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ficha de producto</title>
    {{-- Estilos compatibles con DomPDF para la ficha de producto. --}}
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #222;
            margin: 30px;
        }

        h1, h2, h3 {
            margin: 0 0 10px 0;
        }

        .header {
            border-bottom: 2px solid #333;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }

        .section {
            margin-bottom: 24px;
        }

        .label {
            font-weight: bold;
        }

        .box {
            border: 1px solid #ccc;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 10px;
        }

        .images {
            margin-top: 10px;
        }

        .image-item {
            margin-bottom: 18px;
        }

        .image-item img {
            width: 220px;
            height: auto;
            border: 1px solid #ccc;
            padding: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        table th,
        table td {
            border: 1px solid #bbb;
            padding: 8px;
            text-align: left;
        }

        table th {
            background: #efefef;
        }

        .small {
            font-size: 11px;
            color: #555;
        }
    </style>
</head>
<body>
    {{-- Cabecera identificativa del producto exportado. --}}
    <div class="header">
        <h1>Ficha de producto</h1>
        <div class="small">Código: {{ $product->code }}</div>
    </div>

    {{-- Datos base del producto. --}}
    <div class="section">
        <h2>Datos generales</h2>

        <div class="box">
            <p><span class="label">Nombre:</span> {{ $product->name }}</p>
            <p>
                <span class="label">Categorías:</span>
                {{ $product->categories->pluck('name')->implode(', ') ?: '-' }}
            </p>
            <p><span class="label">Descripción:</span> {{ $product->description ?: '-' }}</p>
        </div>
    </div>

    <div class="section">
        <h2>Tarifa actual</h2>

        <div class="box">
            @if($currentRate)
                <p><span class="label">Precio vigente:</span> {{ number_format((float) $currentRate->price, 2, ',', '.') }} €</p>
                <p><span class="label">Desde:</span> {{ $currentRate->start_date?->format('d/m/Y') }}</p>
                <p><span class="label">Hasta:</span> {{ $currentRate->end_date?->format('d/m/Y') ?? 'Sin fecha fin' }}</p>
            @else
                <p>No hay una tarifa vigente hoy.</p>
            @endif
        </div>
    </div>

    <div class="section">
        <h2>Histórico de tarifas</h2>

        @if($product->rates->count())
            <table>
                <thead>
                    <tr>
                        <th>Inicio</th>
                        <th>Fin</th>
                        <th>Precio</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($product->rates->sortBy('start_date') as $rate)
                        <tr>
                            <td>{{ $rate->start_date?->format('d/m/Y') }}</td>
                            <td>{{ $rate->end_date?->format('d/m/Y') ?? 'Sin fecha fin' }}</td>
                            <td>{{ number_format((float) $rate->price, 2, ',', '.') }} €</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p>No hay tarifas registradas.</p>
        @endif
    </div>

    <div class="section images">
        <h2>Imágenes</h2>

        @if($product->images->count())
            @foreach($product->images as $image)
                <div class="image-item">
                    <p class="small">
                        {{ $image->original_name ?: 'Imagen' }}
                        @if($image->sort_order)
                            · Orden {{ $image->sort_order }}
                        @endif
                    </p>

                    <img src="{{ public_path('storage/' . $image->path) }}" alt="{{ $product->name }}">
                </div>
            @endforeach
        @else
            <p>Este producto no tiene imágenes.</p>
        @endif
    </div>
</body>
</html>
