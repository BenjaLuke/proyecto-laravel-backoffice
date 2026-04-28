<div class="mb-3">
    <label for="code" class="form-label">Código</label>
    <input
        type="text"
        name="code"
        id="code"
        class="form-control"
        value="{{ old('code', $category->code ?? '') }}"
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
        value="{{ old('name', $category->name ?? '') }}"
        required
    >
</div>

<div class="mb-3">
    <label for="parent_id" class="form-label">Categoría padre</label>
    <select name="parent_id" id="parent_id" class="form-select">
        <option value="">Sin categoría padre</option>
        @foreach($parents as $parent)
            <option
                value="{{ $parent->id }}"
                @selected(old('parent_id', $category->parent_id ?? '') == $parent->id)
            >
                {{ $parent->name }}
            </option>
        @endforeach
    </select>
</div>

<div class="mb-3">
    <label for="description" class="form-label">Descripción</label>
    <textarea
        name="description"
        id="description"
        class="form-control"
        rows="4"
    >{{ old('description', $category->description ?? '') }}</textarea>
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary">Guardar</button>
    <a href="{{ route('categories.index') }}" class="btn btn-secondary">Cancelar</a>
</div>