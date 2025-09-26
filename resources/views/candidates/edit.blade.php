@extends('layouts.app')

@section('pageTitle', '紹介者編集')
@section('pageDescription', '既存の候補者情報を更新し、見学スケジュールやステータスをメンテナンスします。')

@section('content')
    <section class="rounded-3xl border border-amber-200 bg-amber-50 px-6 py-4 text-sm text-amber-700">
        <p class="font-semibold">プレビュー用ダミーデータ</p>
        <p class="mt-1">現在編集中: 候補者ID #000123 ・ ステータス「判定中」 ・ 見学確定 2025-09-26 10:00</p>
    </section>

    @include('candidates.partials.form', [
        'mode' => 'edit',
        'candidate' => $candidate,
        'agencies' => $agencies,
        'jobCategories' => $jobCategories,
        'handlers' => $handlers,
        'candidateStatuses' => $candidateStatuses,
    ])
@endsection
