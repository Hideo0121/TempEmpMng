<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Master\Concerns\HandlesCsv;
use App\Http\Requests\CandidateStatusRequest;
use App\Http\Requests\MasterCsvImportRequest;
use App\Models\CandidateStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CandidateStatusController extends Controller
{
    use HandlesCsv;

    public function index(): View
    {
        $statuses = CandidateStatus::query()
            ->orderBy('sort_order')
            ->orderBy('label')
            ->paginate(20);

        return view('masters.candidate-statuses.index', compact('statuses'));
    }

    public function create(): View
    {
        return view('masters.candidate-statuses.create', [
            'status' => new CandidateStatus([
                'is_active' => true,
                'sort_order' => 0,
                'color_code' => '#E8F0FE',
            ]),
        ]);
    }

    public function store(CandidateStatusRequest $request): RedirectResponse
    {
        CandidateStatus::create($request->validated());

        return redirect()
            ->route('masters.candidate-statuses.index')
            ->with('status', 'ステータスを登録しました。');
    }

    public function edit(CandidateStatus $candidateStatus): View
    {
        return view('masters.candidate-statuses.edit', [
            'status' => $candidateStatus,
        ]);
    }

    public function update(CandidateStatusRequest $request, CandidateStatus $candidateStatus): RedirectResponse
    {
        $data = $request->validated();
        unset($data['code']);

        $candidateStatus->update($data);

        return redirect()
            ->route('masters.candidate-statuses.index')
            ->with('status', 'ステータスを更新しました。');
    }

    public function export(): StreamedResponse
    {
        $filename = 'candidate_statuses_' . now()->format('Ymd_His') . '.csv';

        $callback = function () {
            $handle = fopen('php://output', 'wb');

            fputcsv($handle, ['code', 'label', 'color_code', 'sort_order', 'is_active']);

            CandidateStatus::query()
                ->orderBy('sort_order')
                ->orderBy('label')
                ->chunk(200, function ($chunk) use ($handle) {
                    foreach ($chunk as $status) {
                        fputcsv($handle, [
                            $status->code,
                            $status->label,
                            $status->color_code,
                            $status->sort_order,
                            $status->is_active ? 1 : 0,
                        ]);
                    }
                });

            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function import(MasterCsvImportRequest $request): RedirectResponse
    {
        $path = $request->file('file')->getRealPath();
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return back()->withErrors(['file' => 'CSVファイルを開けませんでした。']);
        }

        $expected = ['code', 'label', 'color_code', 'sort_order', 'is_active'];
        $header = fgetcsv($handle);

        if ($header === false) {
            fclose($handle);

            return back()->withErrors(['file' => 'CSVファイルにデータがありません。']);
        }

        $header = array_map(function ($value) {
            $value = ltrim((string) $value, "\xEF\xBB\xBF");

            return strtolower(trim($value));
        }, $header);

        if ($header !== $expected) {
            fclose($handle);

            return back()->withErrors([
                'file' => 'CSVヘッダが一致しません。期待する順序: code,label,color_code,sort_order,is_active',
            ]);
        }

        $line = 1;
        $records = [];
        $errors = [];

        while (($row = fgetcsv($handle)) !== false) {
            $line++;

            if ($this->isRowEmpty($row)) {
                continue;
            }

            $row = array_slice($row, 0, count($expected));
            $row = array_pad($row, count($expected), null);
            $row = array_map(fn ($value) => $value === null ? null : trim($value), $row);

            $data = array_combine($expected, $row);

            if (empty($data['code'])) {
                $errors[] = "行{$line}: コードは必須です。";
                continue;
            }

            if (empty($data['label'])) {
                $errors[] = "行{$line}: 表示名は必須です。";
                continue;
            }

            $code = Str::snake($data['code']);
            $color = strtoupper($data['color_code'] ?: '#E8F0FE');
            if (!Str::startsWith($color, '#')) {
                $color = '#' . ltrim($color, '#');
            }

            $records[] = [
                'code' => $code,
                'label' => $data['label'],
                'color_code' => $color,
                'sort_order' => is_numeric($data['sort_order']) ? (int) $data['sort_order'] : 0,
                'is_active' => $this->normalizeBoolean($data['is_active'], true),
            ];
        }

        fclose($handle);

        if (!empty($errors)) {
            return back()->withErrors(['file' => implode(' / ', $errors)]);
        }

        if (empty($records)) {
            return back()->withErrors(['file' => '有効なデータ行が見つかりませんでした。']);
        }

        DB::transaction(function () use ($records) {
            foreach ($records as $record) {
                CandidateStatus::updateOrCreate(
                    ['code' => $record['code']],
                    [
                        'label' => $record['label'],
                        'color_code' => $record['color_code'],
                        'sort_order' => $record['sort_order'],
                        'is_active' => $record['is_active'],
                    ]
                );
            }
        });

        return redirect()
            ->route('masters.candidate-statuses.index')
            ->with('status', 'CSVを取り込みました。（' . count($records) . '件）');
    }
}
