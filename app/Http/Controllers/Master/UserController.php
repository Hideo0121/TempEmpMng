<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Master\Concerns\HandlesCsv;
use App\Http\Requests\MasterCsvImportRequest;
use App\Http\Requests\UserMasterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    use HandlesCsv;

    public function index(): View
    {
        $users = User::query()
            ->orderByDesc('is_active')
            ->orderBy('role')
            ->orderBy('name')
            ->paginate(20);

        return view('masters.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('masters.users.create', [
            'user' => new User([
                'is_active' => true,
                'role' => 'staff',
            ]),
        ]);
    }

    public function store(UserMasterRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['password'] = Hash::make((string) $data['password']);

        User::create($data);

        return redirect()
            ->route('masters.users.index')
            ->with('status', 'ユーザを登録しました。');
    }

    public function edit(User $user): View
    {
        return view('masters.users.edit', compact('user'));
    }

    public function update(UserMasterRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if (! filled($data['password'] ?? null)) {
            $data = Arr::except($data, ['password']);
        } else {
            $data['password'] = Hash::make((string) $data['password']);
        }

        $user->update($data);

        return redirect()
            ->route('masters.users.index')
            ->with('status', 'ユーザを更新しました。');
    }

    public function export(): StreamedResponse
    {
        $filename = 'users_' . now()->format('Ymd_His') . '.csv';

        $callback = function () {
            $handle = fopen('php://output', 'wb');

            fputcsv($handle, ['id', 'name', 'email', 'role', 'is_active']);

            User::query()
                ->orderByDesc('is_active')
                ->orderBy('role')
                ->orderBy('name')
                ->chunk(200, function ($chunk) use ($handle) {
                    foreach ($chunk as $user) {
                        fputcsv($handle, [
                            $user->id,
                            $user->name,
                            $user->email,
                            $user->role,
                            $user->is_active ? 1 : 0,
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

        $expectedWithId = ['id', 'name', 'email', 'role', 'is_active', 'password'];
        $expectedWithoutId = ['name', 'email', 'role', 'is_active', 'password'];

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
        $hasId = false;

        if ($header === $expectedWithId) {
            $hasId = true;
        } elseif ($header !== $expectedWithoutId) {
            fclose($handle);

            return back()->withErrors([
                'file' => 'CSVヘッダが一致しません。期待する順序: "id,name,email,role,is_active,password" または "name,email,role,is_active,password"',
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
                $errors[] = "行{$line}: 氏名は必須です。";
                continue;
            }

            if (empty($data['email']) || ! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = "行{$line}: メールアドレスの形式が正しくありません。";
                continue;
            }

            $role = isset($data['role']) ? strtolower((string) $data['role']) : null;
            if (! in_array($role, ['manager', 'staff'], true)) {
                $errors[] = "行{$line}: 権限は manager または staff を指定してください。";
                continue;
            }

            $password = $data['password'] ?? null;
            $password = $password !== null && $password !== '' ? $password : null;

            $records[] = [
                'line' => $line,
                'id' => $hasId && isset($data['id']) && is_numeric($data['id']) ? (int) $data['id'] : null,
                'name' => $data['name'],
                'email' => $data['email'],
                'role' => $role,
                'is_active' => $this->normalizeBoolean($data['is_active'], true),
                'password' => $password,
            ];
        }

        fclose($handle);

        if (! empty($errors)) {
            return back()->withErrors(['file' => implode(' / ', $errors)]);
        }

        if (empty($records)) {
            return back()->withErrors(['file' => '有効なデータ行が見つかりませんでした。']);
        }

        $emailLines = [];
        foreach ($records as $record) {
            $key = Str::lower($record['email']);
            if (isset($emailLines[$key])) {
                $errors[] = "行{$record['line']}: メールアドレスがCSV内で重複しています。（行{$emailLines[$key]}と同じ）";
            } else {
                $emailLines[$key] = $record['line'];
            }
        }

        if (! empty($errors)) {
            return back()->withErrors(['file' => implode(' / ', $errors)]);
        }

        $emails = array_map(fn ($record) => $record['email'], $records);
        $existingUsers = User::query()
            ->whereIn('email', $emails)
            ->get()
            ->keyBy('email');

        foreach ($records as $record) {
            $existingByEmail = $existingUsers->get($record['email']);

            if ($existingByEmail && $record['id'] && $existingByEmail->id !== $record['id']) {
                $errors[] = "行{$record['line']}: メールアドレス {$record['email']} は別のユーザに既に割り当てられています。";
            }
        }

        if (! empty($errors)) {
            return back()->withErrors(['file' => implode(' / ', $errors)]);
        }

        DB::transaction(function () use ($records) {
            foreach ($records as $record) {
                $user = null;

                if ($record['id']) {
                    $user = User::find($record['id']);
                }

                if (! $user) {
                    $user = User::where('email', $record['email'])->first();
                }

                $payload = [
                    'name' => $record['name'],
                    'email' => $record['email'],
                    'role' => $record['role'],
                    'is_active' => $record['is_active'],
                ];

                if (! empty($record['password'])) {
                    $payload['password'] = Hash::make($record['password']);
                }

                if ($user) {
                    if (empty($record['password'])) {
                        $user->update(Arr::except($payload, ['password']));
                    } else {
                        $user->update($payload);
                    }

                    continue;
                }

                $payload['password'] = $payload['password'] ?? Hash::make(Str::random(16));

                User::create($payload);
            }
        });

        return redirect()
            ->route('masters.users.index')
            ->with('status', 'CSVを取り込みました。（' . count($records) . '件）');
    }
}
