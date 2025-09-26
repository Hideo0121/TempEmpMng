<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ログイン | 短期派遣受入管理システム</title>
    @if (app()->runningUnitTests())
        <style>
            :root { color-scheme: light; }
            body { font-family: 'Nunito Sans', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
        </style>
    @else
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-100 p-6">
    <div class="w-full max-w-md rounded-3xl bg-white p-8 shadow-xl">
        <h1 class="text-2xl font-bold text-slate-900">短期派遣受入管理システム</h1>
        <p class="mt-2 text-sm text-slate-600">社内アカウントでログインしてください。</p>

        <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-6">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-slate-700">メールアドレス</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 text-base shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" />
                @error('email')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-slate-700">パスワード</label>
                <input type="password" name="password" id="password" required autocomplete="current-password" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2 text-base shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" />
                @error('password')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between">
                <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    ログイン状態を保持する
                </label>
            </div>

            <button type="submit" class="w-full rounded-xl bg-blue-600 px-4 py-2 text-base font-semibold text-white transition hover:bg-blue-500">ログイン</button>
        </form>

        <p class="mt-6 text-xs text-slate-400">© {{ date('Y') }} TempEmpMng. 内部利用専用。</p>
    </div>
</body>
</html>
