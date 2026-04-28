@php
    $oldRates = old('rates', $rates ?? []);
    $defaultPrimaryImageSource = old('primary_image_source');

    if (!$defaultPrimaryImageSource) {
        if (!empty($existingImages) && count($existingImages)) {
            $existingCollection = collect($existingImages);
            $primaryExisting = $existingCollection->firstWhere('is_primary', true) ?? $existingCollection->first();

            if ($primaryExisting) {
                $defaultPrimaryImageSource = 'existing:' . $primaryExisting->id;
            }
        } elseif (!empty($sourceImages) && count($sourceImages)) {
            $sourceCollection = collect($sourceImages);
            $primarySource = $sourceCollection->firstWhere('is_primary', true) ?? $sourceCollection->first();

            if ($primarySource) {
                $defaultPrimaryImageSource = 'source:' . $primarySource->id;
            }
        }
    }
@endphp

<div class="mb-3">
    <label for="code" class="form-label">Código</label>
    <input
        type="text"
        name="code"
        id="code"
        class="form-control"
        value="{{ old('code', $product->code ?? '') }}"
        required
    >
</div>

<div class="mb-3">
    <label for="name" class="form-label">Nombre</label>
    <input
        type="text"
        name="name"
        id="name"
        class="form-control"
        value="{{ old('name', $product->name ?? '') }}"
        required
    >
</div>

<div class="mb-3">
    <label for="min_stock" class="form-label">Stock mínimo</label>
    <input
        type="number"
        name="min_stock"
        id="min_stock"
        class="form-control"
        min="0"
        step="1"
        value="{{ old('min_stock', $product->min_stock ?? 0) }}"
        required
    >
    <div class="form-text">
        Umbral a partir del cual el producto se considerará en stock bajo.
    </div>
    <div class="alert alert-info mt-3">
        El stock actual no se modifica desde esta ficha. Se actualiza mediante entradas de stock, devoluciones y futuros movimientos del sistema.
    </div>
</div>

<div class="mb-3">
    <label class="form-label">Categorías</label>

    @forelse($categories as $category)
        <div class="form-check">
            <input
                class="form-check-input"
                type="checkbox"
                name="categories[]"
                value="{{ $category->id }}"
                id="category_{{ $category->id }}"
                @checked(in_array($category->id, old('categories', $selectedCategories ?? [])))
            >
            <label class="form-check-label" for="category_{{ $category->id }}">
                {{ $category->name }}
            </label>
        </div>
    @empty
        <p class="text-muted mb-0">No hay categorías disponibles. Crea antes una categoría.</p>
    @endforelse
</div>

<hr class="my-4">

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0">Tarifas por fechas</h2>
    <button type="button" class="btn btn-sm btn-outline-primary" id="add-rate-btn">
        Añadir tarifa
    </button>
</div>

<div id="rates-wrapper">
    @foreach($oldRates as $index => $rate)
        <div class="border rounded p-3 mb-3 rate-item">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Fecha inicio</label>
                    <input
                        type="date"
                        name="rates[{{ $index }}][start_date]"
                        class="form-control"
                        value="{{ $rate['start_date'] ?? '' }}"
                        required
                    >
                </div>

                <div class="col-md-4">
                    <label class="form-label">Fecha fin</label>
                    <input
                        type="date"
                        name="rates[{{ $index }}][end_date]"
                        class="form-control"
                        value="{{ $rate['end_date'] ?? '' }}"
                    >
                    <div class="form-text">Déjalo vacío si la tarifa sigue vigente.</div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Precio</label>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        name="rates[{{ $index }}][price]"
                        class="form-control"
                        value="{{ $rate['price'] ?? '' }}"
                        required
                    >
                </div>

                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-danger remove-rate-btn">
                        X
                    </button>
                </div>
            </div>
        </div>
    @endforeach
</div>

<hr class="my-4">

@if(!empty($duplicateSourceProductId) && !empty($sourceImages) && count($sourceImages))
    <input type="hidden" name="duplicate_source_product_id" value="{{ $duplicateSourceProductId }}">

    <div class="mb-4">
        <h2 class="h5 mb-3">Imágenes del producto original</h2>
        <p class="text-muted small">
            Marca las imágenes que quieras copiar y, si quieres, indica cuál será la imagen principal.
        </p>

        <div class="row g-3">
            @php
                $defaultSourceImageIds = old(
                    'copy_source_images',
                    collect($sourceImages)->pluck('id')->toArray()
                );
            @endphp

            @foreach($sourceImages as $image)
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm">
                        <img
                            src="{{ asset('storage/' . $image->path) }}"
                            class="card-img-top"
                            alt="{{ $image->original_name }}"
                            style="height: 220px; object-fit: cover;"
                        >

                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <p class="small text-muted mb-0">
                                    {{ $image->original_name ?: 'Imagen' }}
                                </p>

                                @if($image->is_primary)
                                    <span class="badge text-bg-primary">Principal original</span>
                                @endif
                            </div>

                            <div class="form-check mb-2">
                                <input
                                    class="form-check-input source-image-checkbox"
                                    type="checkbox"
                                    name="copy_source_images[]"
                                    value="{{ $image->id }}"
                                    id="copy_source_image_{{ $image->id }}"
                                    @checked(in_array($image->id, $defaultSourceImageIds))
                                >
                                <label class="form-check-label" for="copy_source_image_{{ $image->id }}">
                                    Copiar esta imagen
                                </label>
                            </div>

                            <div class="form-check">
                                <input
                                    class="form-check-input source-primary-radio"
                                    type="radio"
                                    name="primary_image_source"
                                    value="source:{{ $image->id }}"
                                    id="primary_source_image_{{ $image->id }}"
                                    @checked($defaultPrimaryImageSource === 'source:' . $image->id)
                                >
                                <label class="form-check-label" for="primary_source_image_{{ $image->id }}">
                                    Usar como imagen principal
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

<div class="mb-3">
    <label for="images" class="form-label">Nuevas imágenes</label>
    <input
        type="file"
        name="images[]"
        id="images"
        class="form-control"
        multiple
        accept="image/*"
    >
    <div class="form-text">
        Puedes subir varias imágenes a la vez. Verás una vista previa antes de guardar y podrás marcar una como principal.
    </div>
</div>

<div id="new-images-preview" class="row g-3 mb-4"></div>

@if(!empty($existingImages) && count($existingImages))
    <div class="mb-4">
        <h2 class="h5 mb-3">Imágenes actuales</h2>

        <div class="row g-3">
            @foreach($existingImages as $image)
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm">
                        <img
                            src="{{ asset('storage/' . $image->path) }}"
                            class="card-img-top"
                            alt="{{ $image->original_name }}"
                            style="height: 220px; object-fit: cover;"
                        >

                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <p class="small text-muted mb-0">
                                    {{ $image->original_name ?: 'Imagen' }}
                                </p>

                                @if($image->is_primary)
                                    <span class="badge text-bg-primary">Principal</span>
                                @endif
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Orden</label>
                                <input
                                    type="number"
                                    min="1"
                                    name="existing_images[{{ $image->id }}][sort_order]"
                                    class="form-control"
                                    value="{{ old("existing_images.{$image->id}.sort_order", $image->sort_order) }}"
                                >
                            </div>

                            <div class="form-check mb-2">
                                <input
                                    class="form-check-input existing-primary-radio"
                                    type="radio"
                                    name="primary_image_source"
                                    value="existing:{{ $image->id }}"
                                    id="primary_existing_image_{{ $image->id }}"
                                    @checked($defaultPrimaryImageSource === 'existing:' . $image->id)
                                >
                                <label class="form-check-label" for="primary_existing_image_{{ $image->id }}">
                                    Usar como imagen principal
                                </label>
                            </div>

                            <div class="form-check">
                                <input
                                    class="form-check-input existing-delete-checkbox"
                                    type="checkbox"
                                    name="delete_images[]"
                                    value="{{ $image->id }}"
                                    id="delete_image_{{ $image->id }}"
                                    @checked(in_array($image->id, old('delete_images', [])))
                                >
                                <label class="form-check-label" for="delete_image_{{ $image->id }}">
                                    Borrar esta imagen
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

<div class="mb-3">
    <label for="description" class="form-label">Descripción</label>
    <textarea
        name="description"
        id="description"
        class="form-control"
        rows="4"
    >{{ old('description', $product->description ?? '') }}</textarea>
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary">Guardar</button>
    <a href="{{ route('products.index') }}" class="btn btn-secondary">Cancelar</a>
</div>

<template id="rate-template">
    <div class="border rounded p-3 mb-3 rate-item">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Fecha inicio</label>
                <input type="date" name="__NAME__[start_date]" class="form-control" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">Fecha fin</label>
                <input type="date" name="__NAME__[end_date]" class="form-control">
                <div class="form-text">Déjalo vacío si la tarifa sigue vigente.</div>
            </div>

            <div class="col-md-3">
                <label class="form-label">Precio</label>
                <input type="number" step="0.01" min="0" name="__NAME__[price]" class="form-control" required>
            </div>

            <div class="col-md-1 d-flex align-items-end">
                <button type="button" class="btn btn-outline-danger remove-rate-btn">
                    X
                </button>
            </div>
        </div>
    </div>
</template>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const wrapper = document.getElementById('rates-wrapper');
        const addBtn = document.getElementById('add-rate-btn');
        const template = document.getElementById('rate-template').innerHTML;

        let rateIndex = wrapper.querySelectorAll('.rate-item').length;

        addBtn.addEventListener('click', function () {
            const html = template.replaceAll('__NAME__', `rates[${rateIndex}]`);
            wrapper.insertAdjacentHTML('beforeend', html);
            rateIndex++;
        });

        wrapper.addEventListener('click', function (event) {
            if (event.target.classList.contains('remove-rate-btn')) {
                const items = wrapper.querySelectorAll('.rate-item');

                if (items.length > 1) {
                    event.target.closest('.rate-item').remove();
                }
            }
        });

        const imagesInput = document.getElementById('images');
        const previewContainer = document.getElementById('new-images-preview');
        const oldPrimaryImageSource = @json(old('primary_image_source'));

        function renderImagePreviews() {
            previewContainer.innerHTML = '';

            const files = Array.from(imagesInput.files || []);
            if (!files.length) {
                return;
            }

            const alreadyChecked = !!document.querySelector('input[name="primary_image_source"]:checked');

            files.forEach((file, index) => {
                const reader = new FileReader();
                const radioValue = `new:${index}`;

                reader.onload = function (e) {
                    const checked = oldPrimaryImageSource
                        ? oldPrimaryImageSource === radioValue
                        : (!alreadyChecked && index === 0);

                    const col = document.createElement('div');
                    col.className = 'col-md-4';

                    col.innerHTML = `
                        <div class="card h-100 shadow-sm">
                            <img
                                src="${e.target.result}"
                                class="card-img-top"
                                alt="${file.name}"
                                style="height: 220px; object-fit: cover;"
                            >
                            <div class="card-body">
                                <p class="small text-muted mb-2">${file.name}</p>

                                <div class="form-check">
                                    <input
                                        class="form-check-input"
                                        type="radio"
                                        name="primary_image_source"
                                        value="${radioValue}"
                                        id="primary_new_image_${index}"
                                        ${checked ? 'checked' : ''}
                                    >
                                    <label class="form-check-label" for="primary_new_image_${index}">
                                        Usar como imagen principal
                                    </label>
                                </div>
                            </div>
                        </div>
                    `;

                    previewContainer.appendChild(col);
                };

                reader.readAsDataURL(file);
            });
        }

        function syncSourcePrimaryState() {
            document.querySelectorAll('.source-image-checkbox').forEach(function (checkbox) {
                const id = checkbox.value;
                const radio = document.getElementById(`primary_source_image_${id}`);

                if (!radio) {
                    return;
                }

                radio.disabled = !checkbox.checked;

                if (!checkbox.checked && radio.checked) {
                    radio.checked = false;
                }
            });
        }

        function syncExistingDeleteState() {
            document.querySelectorAll('.existing-delete-checkbox').forEach(function (checkbox) {
                const id = checkbox.value;
                const radio = document.getElementById(`primary_existing_image_${id}`);

                if (!radio) {
                    return;
                }

                if (checkbox.checked && radio.checked) {
                    radio.checked = false;
                }
            });
        }

        imagesInput.addEventListener('change', renderImagePreviews);

        document.querySelectorAll('.source-image-checkbox').forEach(function (checkbox) {
            checkbox.addEventListener('change', syncSourcePrimaryState);
        });

        document.querySelectorAll('.existing-delete-checkbox').forEach(function (checkbox) {
            checkbox.addEventListener('change', syncExistingDeleteState);
        });

        syncSourcePrimaryState();
        syncExistingDeleteState();
    });
</script>