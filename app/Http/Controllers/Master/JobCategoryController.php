<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Master\Concerns\HandlesCsv;
use App\Http\Requests\JobCategoryRequest;
use App\Http\Requests\MasterCsvImportRequest;
use App\Models\JobCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JobCategoryController extends Controller
{
    use HandlesCsv;

    public function index(): View
    {
        $categories = JobCategory::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);

        return view('masters.job-categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('masters.job-categories.create', [
            'category' => new JobCategory([
                'sort_order' => 0,
                'is_active' => true,
            ]),
        ]);
    }

    public function store(JobCategoryRequest $request): RedirectResponse
    {
        JobCategory::create($request->validated());

        return redirect()
            ->route('masters.job-categories.index')
            ->with('status', '希望職種を登録しました。');
    }

    public function edit(JobCategory $jobCategory): View
    {
        return view('masters.job-categories.edit', [
            'category' => $jobCategory,
        ]);
    }

    public function update(JobCategoryRequest $request, JobCategory $jobCategory): RedirectResponse
    {
        $jobCategory->update($request->validated());

        return redirect()
            ->route('masters.job-categories.index')
            ->with('status', '希望職種を更新しました。');
    }

    public function export(): StreamedResponse
    {
        $filename = 'job_categories_' . now()->format('Ymd_His') . '.csv';

        $callback = function () {
            $handle = fopen('php://output', 'wb');

            fputcsv($handle, ['id', 'name', 'sort_order', 'is_active']);

            JobCategory::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->chunk(200, function ($chunk) use ($handle) {
                    foreach ($chunk as $category) {
                        fputcsv($handle, [
                            $category->id,
                            $category->name,
                            $category->sort_order,
                            $category->is_active ? 1 : 0,
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

        $expectedWithId = ['id', 'name', 'sort_order', 'is_active'];
        $expectedWithoutId = ['name', 'sort_order', 'is_active'];
        $header = fgetcsv($handle);

        if ($header === false) {
            fclose($handle);

            return back()->withErrors(['file' => 'CSVファイルにデータがありません。']);
        }

        $header = array_map(function ($value) {
            $value = ltrim((string) $value, "\xEF\xBB\xBF");

            return strtolower(trim($value));
        }, $header);

        $columns = $header;
        $hasIdColumn = false;

        if ($header === $expectedWithId) {
            $hasIdColumn = true;
        } elseif ($header !== $expectedWithoutId) {
            fclose($handle);

            return back()->withErrors([
                'file' => 'CSVヘッダが一致しません。期待する順序: "id,name,sort_order,is_active" または "name,sort_order,is_active"',
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

            $row = array_slice($row, 0, count($columns));
            $row = array_pad($row, count($columns), null);
            $row = array_map(fn ($value) => $value === null ? null : trim($value), $row);

            $data = array_combine($columns, $row);

            if (empty($data['name'])) {
                $errors[] = "行{$line}: 名称は必須です。";
                continue;
            }

            $records[] = [
                'id' => $hasIdColumn && isset($data['id']) && is_numeric($data['id']) ? (int) $data['id'] : null,
                'name' => $data['name'],
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
                if ($record['id'] && $category = JobCategory::find($record['id'])) {
                    $category->update([
                        'name' => $record['name'],
                        'sort_order' => $record['sort_order'],
                        'is_active' => $record['is_active'],
                    ]);

                    continue;
                }

                JobCategory::updateOrCreate(
                    ['name' => $record['name']],
                    [
                        'sort_order' => $record['sort_order'],
                        'is_active' => $record['is_active'],
                    ]
                );
            }
        });

        return redirect()
            ->route('masters.job-categories.index')
            ->with('status', 'CSVを取り込みました。（' . count($records) . '件）');
    }
}
