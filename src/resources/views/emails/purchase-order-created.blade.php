<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo pedido creado</title>
</head>
<body style="font-family: Arial, sans-serif; color: #222;">
    {{-- Cabecera del email de notificación de nuevo pedido. --}}
    <h1>Nuevo pedido creado</h1>

    <p>Se ha registrado un nuevo pedido en el sistema.</p>

    {{-- Resumen principal del pedido creado. --}}
    <ul>
        <li><strong>ID del pedido:</strong> {{ $purchaseOrder->id }}</li>
        <li><strong>Fecha del pedido:</strong> {{ $purchaseOrder->order_date?->format('Y-m-d') }}</li>
        <li><strong>Producto:</strong> {{ $purchaseOrder->product?->name }}</li>
        <li><strong>Unidades:</strong> {{ $purchaseOrder->units }}</li>
        <li><strong>Precio unitario:</strong> {{ number_format((float) $purchaseOrder->unit_price, 2, ',', '.') }} €</li>
        <li><strong>Precio total:</strong> {{ number_format((float) $purchaseOrder->total_price, 2, ',', '.') }} €</li>
        <li><strong>Estado:</strong> {{ $purchaseOrder->status }}</li>
    </ul>

    {{-- Notas opcionales añadidas al pedido. --}}
    @if($purchaseOrder->notes)
        <p><strong>Notas:</strong> {{ $purchaseOrder->notes }}</p>
    @endif

    {{-- Pie automático del correo. --}}
    <p>Mensaje generado automáticamente por Laravel.</p>
</body>
</html>
