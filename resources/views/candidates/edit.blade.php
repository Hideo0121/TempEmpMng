@extends('layouts.app')

@section('pageTitle', '紹介者編集')
@section('pageDescription', '既存の候補者情報を更新し、見学スケジュールやステータスをメンテナンスします。')

@section('content')
    @include('candidates.partials.form', [
        'mode' => 'edit',
        'candidate' => $candidate,
        'agencies' => $agencies,
        'jobCategories' => $jobCategories,
        'handlers' => $handlers,
        'candidateStatuses' => $candidateStatuses,
        'skillSheets' => $candidate->skillSheets,
        'formAction' => $formAction ?? route('candidates.update', $candidate),
        'httpMethod' => $httpMethod ?? 'PUT',
        'confirmedInterview' => $confirmedInterview,
        'backUrl' => $backUrl ?? null,
    ])
@endsection
