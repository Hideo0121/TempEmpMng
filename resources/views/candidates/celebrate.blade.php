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
            background: hsla(calc(var(--i) * 17deg), 90%, 65%, 0.9);
            left: calc((var(--i) * 3.5%) - 5%);
            animation: confetti-fall calc(5.5s + (var(--i) * 0.1s)) linear infinite;
            opacity: 0.75;
            transform: rotate(15deg);
            animation-delay: calc(var(--i) * -0.12s);
        }

        #celebration-root .confetti-piece:nth-child(3n) {
            width: 10px;
            height: 16px;
            background: hsla(calc(var(--i) * 21deg), 95%, 72%, 0.85);
        }

        #celebration-root .confetti-piece:nth-child(4n) {
            width: 8px;
            height: 24px;
            border-radius: 0.75rem;
            background: hsla(calc(var(--i) * 13deg), 88%, 58%, 0.9);
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
        class="relative isolate mx-auto w-full max-w-3xl overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-600 via-purple-500 to-pink-500 px-6 py-16 shadow-2xl text-white sm:max-w-2xl sm:px-10 md:px-16"
    >
        <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
            @for ($i = 0; $i < 36; $i++)
                <span class="confetti-piece" style="--i: {{ $i }};"></span>
            @endfor
        </div>

    <div class="relative z-10 mx-auto flex max-w-2xl flex-col items-center text-center sm:px-4">
            <div class="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-2 text-xs font-semibold uppercase tracking-[0.3em] text-white/90">
                <span class="h-2 w-2 rounded-full bg-emerald-300"></span>
                <span>{{ $statusLabel }}</span>
            </div>

            <h1 class="mt-8 text-3xl font-bold leading-tight sm:text-4xl">
                就業決定おめでとうございます！
            </h1>

            <p class="mt-6 text-base text-white/80">
                このページは <span id="celebration-countdown">{{ $delaySeconds }}</span> 秒後に自動で閉じます。
            </p>
            <p class="mt-2 text-sm text-white/70">
                自動的に遷移しない場合は <a href="{{ $redirectUrl }}" class="font-semibold text-white underline decoration-white/60 underline-offset-4 hover:text-yellow-200 hover:decoration-yellow-200">こちら</a> から移動してください。
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
