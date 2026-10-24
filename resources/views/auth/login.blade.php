<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login SIIP</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100">
    <div class="flex min-h-screen items-center justify-center px-4">
        <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-lg">
            <div class="mb-6 text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">SIIP</p>
                <h1 class="mt-2 text-2xl font-bold text-slate-900">Masuk ke Sistem</h1>
            </div>

            @if ($errors->any())
                <div class="mb-4 rounded border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.submit') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required class="w-full rounded border border-slate-300 px-3 py-2 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200">
                </div>

                <div>
                    <label for="password" class="mb-1 block text-sm font-medium text-slate-700">Password</label>
                    <input id="password" type="password" name="password" required class="w-full rounded border border-slate-300 px-3 py-2 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200">
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-slate-300">
                        Ingat saya
                    </label>
                </div>

                <button type="submit" class="w-full rounded bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-500">Masuk</button>
            </form>
            @if(config('sso.enabled'))<a href="{{ route('sso.login') }}" class="mt-5 block w-full rounded border border-sky-600 px-4 py-2.5 text-center text-sm font-semibold text-sky-700">Masuk dengan akun SIVMI</a>@endif
        </div>
    </div>
</body>
</html>
