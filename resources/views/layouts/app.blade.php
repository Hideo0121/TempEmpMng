<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? '短期派遣受入管理システム' }}</title>
    @if (app()->runningUnitTests())
        <style>
            :root {
                color-scheme: light;
            }
            body { font-family: 'Nunito Sans', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
        </style>
    @else
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen bg-slate-100 text-slate-800">
    <header class="bg-blue-700 text-white shadow-lg">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
            <div class="text-xl font-semibold">
                <a href="{{ route('dashboard') }}" class="transition hover:text-blue-100">短期派遣受入管理システム</a>
            </div>
            @auth
                <nav class="flex items-center gap-3 text-sm font-medium">
                    <a href="{{ route('candidates.create') }}" class="rounded-full bg-white/10 px-4 py-2 transition hover:bg-white/20">紹介者登録</a>
                    <a href="{{ route('candidates.index') }}" class="rounded-full bg-white/10 px-4 py-2 transition hover:bg-white/20">紹介者一覧</a>
                    @if (auth()->user()?->isManager())
                        <a href="{{ route('masters.index') }}" class="rounded-full bg-white/10 px-4 py-2 transition hover:bg-white/20">マスタ管理</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-full bg-white px-4 py-2 text-blue-700 transition hover:bg-blue-50">ログアウト</button>
                    </form>
                </nav>
            @endauth
        </div>
    </header>

    <main class="mx-auto w-full max-w-7xl px-6 py-10">
        @hasSection('pageTitle')
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-slate-900">@yield('pageTitle')</h1>
                @hasSection('pageDescription')
                    <p class="mt-2 text-sm text-slate-600">@yield('pageDescription')</p>
                @endif
            </div>
        @endif

        <div class="space-y-8">
            @yield('content')
        </div>
    </main>

    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4 text-xs text-slate-500">
            <span>© {{ date('Y') }} TempEmpMng. 内部利用専用。</span>
            <span>Ver. 0.1.0-prototype</span>
        </div>
    </footer>
</body>
</html>
