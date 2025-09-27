<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\SkillSheet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CandidateSkillSheetController extends Controller
{
    public function download(Request $request, Candidate $candidate, SkillSheet $skillSheet)
    {
        if ($skillSheet->candidate_id !== $candidate->id) {
            abort(404);
        }

        if (!Storage::disk('local')->exists($skillSheet->file_path)) {
            abort(404, 'ファイルが見つかりません。');
        }

        return Storage::disk('local')->download(
            $skillSheet->file_path,
            $skillSheet->original_name,
            ['Content-Type' => 'application/pdf']
        );
    }

    public function preview(Request $request, Candidate $candidate, SkillSheet $skillSheet)
    {
        if ($skillSheet->candidate_id !== $candidate->id) {
            abort(404);
        }

        if (!Storage::disk('local')->exists($skillSheet->file_path)) {
            abort(404, 'ファイルが見つかりません。');
        }

        $headers = [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . addslashes($skillSheet->original_name) . '"',
        ];

        return Storage::disk('local')->response($skillSheet->file_path, null, $headers);
    }
}
