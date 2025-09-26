@extends('layouts.app')

@section('pageTitle', '紹介者登録')
@section('pageDescription', '派遣会社から紹介された候補者の情報を入力し、職場見学候補日や対応者を設定します。')

@section('content')
    @include('candidates.partials.form', [
        'mode' => 'create',
        'candidate' => null,
        'agencies' => $agencies,
        'jobCategories' => $jobCategories,
        'handlers' => $handlers,
        'candidateStatuses' => $candidateStatuses,
    ])
@endsection
