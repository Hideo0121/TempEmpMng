<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Master\Concerns\HandlesCsv;
use App\Http\Requests\AgencyRequest;
use App\Http\Requests\MasterCsvImportRequest;
use App\Models\Agency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AgencyController extends Controller
{
    use HandlesCsv;

    public function index(): View
    {
        $agencies = Agency::query()
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(20);

        return view('masters.agencies.index', compact('agencies'));
    }

    public function create(): View
    {
        return view('masters.agencies.create', [
            'agency' => new Agency([
                'is_active' => true,
            ]),
        ]);
    }

    public function store(AgencyRequest $request): RedirectResponse
    {
        Agency::create($request->validated());

        return redirect()
            ->route('masters.agencies.index')
            ->with('status', '派遣会社を登録しました。');
    }

    public function edit(Agency $agency): View
    {
        return view('masters.agencies.edit', compact('agency'));
    }

    public function update(AgencyRequest $request, Agency $agency): RedirectResponse
    {
        $agency->update($request->validated());

        return redirect()
            ->route('masters.agencies.index')
            ->with('status', '派遣会社を更新しました。');
    }

    public function export(): StreamedResponse
    {
        $filename = 'agencies_' . now()->format('Ymd_His') . '.csv';

        $callback = function () {
            $handle = fopen('php://output', 'wb');

            fputcsv($handle, ['id', 'name', 'contact_person', 'email', 'phone', 'note', 'is_active']);

            Agency::query()
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->chunk(200, function ($chunk) use ($handle) {
                    foreach ($chunk as $agency) {
                        fputcsv($handle, [
                            $agency->id,
                            $agency->name,
                            $agency->contact_person,
                            $agency->email,
                            $agency->phone,
                            $agency->note,
                            $agency->is_active ? 1 : 0,
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

        $expectedWithId = ['id', 'name', 'contact_person', 'email', 'phone', 'note', 'is_active'];
        $expectedWithoutId = ['name', 'contact_person', 'email', 'phone', 'note', 'is_active'];
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
                'file' => 'CSVヘッダが一致しません。期待する順序: "id,name,contact_person,email,phone,note,is_active" または "name,contact_person,email,phone,note,is_active"',
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

            $email = $data['email'] !== null && $data['email'] !== '' ? $data['email'] : null;

            if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "行{$line}: メールアドレスの形式が正しくありません。";
                continue;
            }

            $records[] = [
                'id' => $hasIdColumn && isset($data['id']) && is_numeric($data['id']) ? (int) $data['id'] : null,
                'name' => $data['name'],
                'contact_person' => $data['contact_person'] !== '' ? $data['contact_person'] : null,
                'email' => $email,
                'phone' => $data['phone'] !== '' ? $data['phone'] : null,
                'note' => $data['note'] !== '' ? $data['note'] : null,
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
                if ($record['id'] && $agency = Agency::find($record['id'])) {
                    $agency->update([
                        'name' => $record['name'],
                        'contact_person' => $record['contact_person'],
                        'email' => $record['email'],
                        'phone' => $record['phone'],
                        'note' => $record['note'],
                        'is_active' => $record['is_active'],
                    ]);

                    continue;
                }

                Agency::updateOrCreate(
                    ['name' => $record['name']],
                    [
                        'contact_person' => $record['contact_person'],
                        'email' => $record['email'],
                        'phone' => $record['phone'],
                        'note' => $record['note'],
                        'is_active' => $record['is_active'],
                    ]
                );
            }
        });

        return redirect()
            ->route('masters.agencies.index')
            ->with('status', 'CSVを取り込みました。（' . count($records) . '件）');
    }
}
