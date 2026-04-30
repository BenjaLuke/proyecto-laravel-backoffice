{{-- Fecha usada para colocar el pedido en el calendario. --}}
<div class="mb-3">
    <label for="order_date" class="form-label">Fecha del pedido</label>
    <input
        type="date"
        name="order_date"
        id="order_date"
        class="form-control"
        value="{{ old('order_date', $purchaseOrder?->order_date->format('Y-m-d') ?? $selectedDate ?? '') }}"
        required
    >
</div>

{{-- Producto asociado al pedido; se muestra el stock actual para decidir unidades. --}}
<div class="mb-3">
    <label for="product_id" class="form-label">Producto</label>
    <select name="product_id" id="product_id" class="form-select" required>
        <option value="">Selecciona un producto</option>
        @foreach($products as $product)
            <option
                value="{{ $product->id }}"
                @selected(old('product_id', $purchaseOrder->product_id ?? '') == $product->id)
            >
                {{ $product->name }} · Stock actual: {{ $product->current_stock }}
            </option>
        @endforeach
    </select>
</div>

{{-- Cantidad solicitada en el pedido. --}}
<div class="mb-3">
    <label for="units" class="form-label">Unidades</label>
    <input
        type="number"
        min="1"
        name="units"
        id="units"
        class="form-control"
        value="{{ old('units', $purchaseOrder->units ?? 1) }}"
        required
    >
</div>

{{-- Estado operativo del pedido y su impacto en stock. --}}
<div class="mb-3">
    <label for="status" class="form-label">Estado</label>
    <select name="status" id="status" class="form-select" required>
        @foreach($statuses as $value => $label)
            <option value="{{ $value }}" @selected(old('status', $purchaseOrder->status ?? 'pendiente') === $value)>
                {{ $label }}
            </option>
        @endforeach
    </select>
    <div class="form-text">
        Pendiente no descuenta stock. Servido descuenta stock si hay unidades suficientes. Cancelado solo se usa para pedidos no servidos.
    </div>
</div>

{{-- Observaciones internas del pedido. --}}
<div class="mb-3">
    <label for="notes" class="form-label">Notas</label>
    <textarea
        name="notes"
        id="notes"
        class="form-control"
        rows="3"
    >{{ old('notes', $purchaseOrder->notes ?? '') }}</textarea>
</div>

{{-- Acciones finales del formulario. --}}
<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary">Guardar</button>
    <a href="{{ route('calendar.index') }}" class="btn btn-secondary">Cancelar</a>
</div>
