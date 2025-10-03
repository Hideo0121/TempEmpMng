@extends('layouts.app')

@php($title = '就業決定おめでとうございます')

@section('content')
    <style>
        #celebration-root {
            position: relative;
        }

        #celebration-root .confetti-piece {
            position: absolute;
            top: -10%;
            width: 12px;
            height: 20px;
            border-radius: 9999px;
            background: hsla(calc(var(--i) * 17deg), 85%, 76%, 0.65);
            left: calc((var(--i) * 3.5%) - 5%);
            animation: confetti-fall calc(5.5s + (var(--i) * 0.1s)) linear infinite;
            opacity: 0.6;
            transform: rotate(15deg);
            animation-delay: calc(var(--i) * -0.12s);
        }

        #celebration-root .confetti-piece:nth-child(3n) {
            width: 10px;
            height: 16px;
            background: hsla(calc(var(--i) * 21deg), 88%, 80%, 0.55);
        }

        #celebration-root .confetti-piece:nth-child(4n) {
            width: 8px;
            height: 24px;
            border-radius: 0.75rem;
            background: hsla(calc(var(--i) * 13deg), 80%, 68%, 0.6);
        }

        @keyframes confetti-fall {
            0% {
                transform: translate3d(0, -120%, 0) rotateZ(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            100% {
                transform: translate3d(0, 120vh, 0) rotateZ(360deg);
                opacity: 0;
            }
        }
    </style>

    <section
        id="celebration-root"
        data-redirect-url="{{ $redirectUrl }}"
        data-delay="{{ $delaySeconds }}"
    class="relative isolate mx-auto w-full max-w-3xl overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-300 via-violet-200 to-pink-200 px-6 py-16 shadow-2xl text-slate-900 sm:max-w-2xl sm:px-10 md:px-16"
    >
        <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
            @for ($i = 0; $i < 36; $i++)
                <span class="confetti-piece" style="--i: {{ $i }};"></span>
            @endfor
        </div>

    <div class="relative z-10 mx-auto flex max-w-2xl flex-col items-center text-center sm:px-4">
            <div class="inline-flex items-center gap-2 rounded-full bg-white/50 px-4 py-2 text-xs font-semibold uppercase tracking-[0.3em] text-slate-700">
                <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                <span>{{ $statusLabel }}</span>
            </div>

            <h1 class="mt-8 text-3xl font-bold leading-tight text-slate-900 sm:text-4xl">
                就業決定おめでとうございます！
            </h1>

            <p class="mt-6 text-base text-slate-700">
                このページは <span id="celebration-countdown">{{ $delaySeconds }}</span> 秒後に自動で閉じます。
            </p>
            <p class="mt-2 text-sm text-slate-600">
                自動的に遷移しない場合は <a href="{{ $redirectUrl }}" class="font-semibold text-rose-600 underline decoration-rose-300 underline-offset-4 hover:text-rose-500 hover:decoration-rose-500">こちら</a> から移動してください。
            </p>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const root = document.getElementById('celebration-root');
            if (!root) {
                return;
            }

            const redirectUrl = root.dataset.redirectUrl;
            let remaining = Number(root.dataset.delay || 5);
            remaining = Number.isFinite(remaining) && remaining > 0 ? Math.round(remaining) : 5;

            const countdownEl = document.getElementById('celebration-countdown');
            if (countdownEl) {
                countdownEl.textContent = remaining.toString();
            }

            const tick = () => {
                remaining -= 1;
                if (remaining <= 0) {
                    window.location.assign(redirectUrl);
                    return;
                }

                if (countdownEl) {
                    countdownEl.textContent = remaining.toString();
                }
            };

            setTimeout(() => {
                window.location.assign(redirectUrl);
            }, remaining * 1000);

            setInterval(tick, 1000);
        });
    </script>
@endsection
