<?php

namespace App\Http\Controllers;

use App\Application\Exceptions\RevisionConflict;
use App\Application\UseCases\CreateOpenRecord;
use App\Application\UseCases\DeleteOpenRecord;
use App\Application\UseCases\UpdateOpenRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OpenRecordController extends Controller
{
    public function store(Request $request, CreateOpenRecord $create): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:65536'],
            'tags' => ['nullable', 'string', 'max:1500'],
            'categoryId' => ['nullable', 'uuid'],
            'occurredAt' => ['required', 'date'],
        ]);
        $data['tags'] = array_values(array_filter(array_map('trim', explode(',', (string) ($data['tags'] ?? '')))));
        $create->execute($request->user()->id, $data);

        return back()->with('status', 'Open Recordを保存しました。');
    }

    public function update(Request $request, string $record, UpdateOpenRecord $update): RedirectResponse
    {
        $data = $request->validate([
            'baseRevisionId' => ['required', 'uuid'],
            'title' => ['nullable', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:65536'],
            'tags' => ['nullable', 'string', 'max:1500'],
            'categoryId' => ['nullable', 'uuid'],
            'occurredAt' => ['required', 'date'],
        ]);
        $data['tags'] = array_values(array_filter(array_map('trim', explode(',', (string) ($data['tags'] ?? '')))));
        try {
            $update->execute($request->user()->id, $record, $data);
        } catch (RevisionConflict $conflict) {
            return back()->withErrors(['record' => 'Revisionが競合しました。現在値: '.$conflict->currentRevisionId]);
        }

        return back()->with('status', 'Open Recordを更新しました。');
    }

    public function destroy(Request $request, string $record, DeleteOpenRecord $delete): RedirectResponse
    {
        $data = $request->validate(['baseRevisionId' => ['required', 'uuid']]);
        try {
            $delete->execute($request->user()->id, $record, $data['baseRevisionId']);
        } catch (RevisionConflict $conflict) {
            return back()->withErrors(['record' => 'Revisionが競合しました。現在値: '.$conflict->currentRevisionId]);
        }

        return back()->with('status', 'Open Recordを論理削除しました。');
    }
}
