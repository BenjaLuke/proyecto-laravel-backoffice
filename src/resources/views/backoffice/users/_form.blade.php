@php
    // Permisos actuales usados para marcar checkboxes al editar o revalidar.
    $currentPermissions = old('permissions', collect($user->getPermissions())
        ->filter()
        ->keys()
        ->toArray());
@endphp

{{-- Datos básicos de identidad del usuario. --}}
<div class="mb-3">
    <label for="name" class="form-label">Nombre</label>
    <input
        type="text"
        name="name"
        id="name"
        class="form-control"
        value="{{ old('name', $user->name ?? '') }}"
        required
    >
</div>

{{-- Nombre de acceso al backoffice. --}}
<div class="mb-3">
    <label for="username" class="form-label">Usuario</label>
    <input
        type="text"
        name="username"
        id="username"
        class="form-control"
        value="{{ old('username', $user->username ?? '') }}"
        required
    >
</div>

{{-- Email de contacto y login. --}}
<div class="mb-3">
    <label for="email" class="form-label">Email</label>
    <input
        type="email"
        name="email"
        id="email"
        class="form-control"
        value="{{ old('email', $user->email ?? '') }}"
        required
    >
</div>

{{-- Los administradores saltan la comprobación granular de permisos. --}}
<div class="form-check mb-4">
    <input
        class="form-check-input"
        type="checkbox"
        name="is_admin"
        id="is_admin"
        value="1"
        @checked(old('is_admin', $user->is_admin ?? false))
    >
    <label class="form-check-label" for="is_admin">
        Administrador
    </label>
</div>

{{-- Matriz de permisos agrupados por área funcional. --}}
<div class="mb-4">
    <h2 class="h5 mb-3">Permisos</h2>

    <div class="row g-3">
        @foreach($permissionGroups as $groupTitle => $groupPermissions)
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h3 class="h6 mb-3">{{ $groupTitle }}</h3>

                        @foreach($groupPermissions as $permissionKey => $permissionLabel)
                            <div class="form-check mb-2">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="permissions[]"
                                    value="{{ $permissionKey }}"
                                    id="{{ $permissionKey }}"
                                    @checked(in_array($permissionKey, $currentPermissions))
                                >
                                <label class="form-check-label" for="{{ $permissionKey }}">
                                    {{ $permissionLabel }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

{{-- Contraseña: obligatoria al crear, opcional al editar. --}}
<div class="row g-3">
    <div class="col-md-6">
        <label for="password" class="form-label">
            {{ isset($user->id) ? 'Nueva contraseña' : 'Contraseña' }}
        </label>
        <input
            type="password"
            name="password"
            id="password"
            class="form-control"
            {{ isset($user->id) ? '' : 'required' }}
        >
        @if(isset($user->id))
            <div class="form-text">Déjalo vacío si no quieres cambiar la contraseña.</div>
        @endif
    </div>

    <div class="col-md-6">
        <label for="password_confirmation" class="form-label">
            Confirmar contraseña
        </label>
        <input
            type="password"
            name="password_confirmation"
            id="password_confirmation"
            class="form-control"
            {{ isset($user->id) ? '' : 'required' }}
        >
    </div>
</div>

{{-- Acciones finales del formulario. --}}
<div class="d-flex gap-2 mt-4">
    <button type="submit" class="btn btn-primary">Guardar</button>
    <a href="{{ route('users.index') }}" class="btn btn-secondary">Cancelar</a>
</div>
