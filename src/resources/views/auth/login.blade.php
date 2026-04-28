<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login Backoffice</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body{
            margin:0;
            font-family:Arial, sans-serif;
            background:#111827;
            color:#f9fafb;
            display:flex;
            justify-content:center;
            align-items:center;
            min-height:100vh;
        }
        .box{
            width:100%;
            max-width:420px;
            background:#1f2937;
            padding:32px;
            border-radius:16px;
            box-shadow:0 10px 30px rgba(0,0,0,.35);
        }
        h1{
            margin-top:0;
            margin-bottom:24px;
            font-size:28px;
        }
        label{
            display:block;
            margin-bottom:8px;
            font-size:14px;
        }
        input{
            width:100%;
            padding:12px;
            margin-bottom:18px;
            border-radius:10px;
            border:1px solid #374151;
            background:#111827;
            color:#fff;
            box-sizing:border-box;
        }
        button{
            width:100%;
            padding:12px;
            border:0;
            border-radius:10px;
            background:#2563eb;
            color:white;
            font-size:16px;
            cursor:pointer;
        }
        button:hover{
            background:#1d4ed8;
        }
        .error{
            background:#7f1d1d;
            color:#fecaca;
            padding:10px;
            border-radius:10px;
            margin-bottom:16px;
        }
        .remember{
            display:flex;
            align-items:center;
            gap:8px;
            margin-bottom:18px;
        }
        .remember input{
            width:auto;
            margin:0;
        }
    </style>
</head>
<body>
    <div class="box">
        <h1>Backoffice</h1>

        @if ($errors->any())
            <div class="error">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}">
            @csrf

            <label for="username">Usuario</label>
            <input
                type="text"
                name="username"
                id="username"
                value="{{ old('username') }}"
                required
                autofocus
            >

            <label for="password">Contraseña</label>
            <input
                type="password"
                name="password"
                id="password"
                required
            >

            <div class="remember">
                <input type="checkbox" name="remember" id="remember">
                <label for="remember" style="margin:0;">Recordarme</label>
            </div>

            <button type="submit">Entrar</button>
        </form>
    </div>
</body>
</html>