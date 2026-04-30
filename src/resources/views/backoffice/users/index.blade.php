@extends('backoffice.layout')

@section('title', 'Usuarios')

@section('content')
    {{-- Cabecera del listado y acceso al alta de usuarios. --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Usuarios</h1>

        <a href="{{ route('users.create') }}" class="btn btn-primary">
            Nuevo usuario
        </a>
    </div>

    {{-- Tabla de usuarios con rol, permisos efectivos y acciones. --}}
    <div class="card shadow-sm">
        <div class="card-body">
            @if($users->count())
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Usuario</th>
                                <th>Email</th>
                                <th>Rol</th>
                                <th>Permisos</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                                {{-- Los permisos se muestran como badges para lectura rápida. --}}
                                @php($permissions = $user->getPermissions())

                                <tr>
                                    <td>{{ $user->id }}</td>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->username }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        @if($user->isAdmin())
                                            <span class="badge text-bg-danger">Administrador</span>
                                        @else
                                            <span class="badge text-bg-secondary">Usuario</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach($permissions as $key => $enabled)
                                                @if($enabled)
                                                    <span class="badge text-bg-success">{{ $key }}</span>
                                                @endif
                                            @endforeach

                                            @if(collect($permissions)->filter()->isEmpty())
                                                <span class="text-muted">Sin permisos</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex flex-wrap justify-content-end gap-2">
                                            <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-warning">
                                                Editar
                                            </a>

                                            <form action="{{ route('users.destroy', $user) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-danger"
                                                    onclick="return confirm('¿Seguro que quieres eliminar este usuario?')"
                                                >
                                                    Borrar
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $users->links() }}
                </div>
            @else
                <p class="mb-0">Todavía no hay usuarios registrados.</p>
            @endif
        </div>
    </div>
@endsection
