<?php

namespace App\Http\Controllers;

use App\Application\Exceptions\DefinitionConflict;
use App\Application\Exceptions\InputRejected;
use App\Application\Exceptions\RevisionConflict;
use App\Application\UseCases\CreateProtocolRecord;
use App\Application\UseCases\DeleteProtocolRecord;
use App\Application\UseCases\UpdateProtocolRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use JsonException;

class ProtocolRecordController extends Controller
{
    public function store(Request $request, string $logbook, CreateProtocolRecord $create): RedirectResponse
    {
        $data = $request->validate([
            'protocolVersionId' => ['required', 'uuid'],
            'occurredAt' => ['required', 'date'],
            'payload' => ['required', 'string', 'max:262144'],
        ]);
        try {
            $record = $create->execute($request->user()->id, $logbook, [
                'protocolVersionId' => $data['protocolVersionId'],
                'occurredAt' => $data['occurredAt'],
                'payload' => json_decode($data['payload'], true, 128, JSON_THROW_ON_ERROR),
                'source' => ['type' => 'web'],
            ]);
        } catch (JsonException $error) {
            return back()->withErrors(['payload' => 'Payloadが有効なJSONではありません: '.$error->getMessage()])->withInput();
        } catch (InputRejected $error) {
            return back()->withErrors(['payload' => array_map(static fn ($issue): string => $issue->path.': '.$issue->code, $error->result->errors)])->withInput();
        } catch (DefinitionConflict) {
            return back()->withErrors(['payload' => 'LogBookのProtocol Versionが変更されています。再読み込みしてください。']);
        }

        return back()->with('status', 'Protocol Record '.$record['id'].' を保存しました。');
    }

    public function update(Request $request, string $logbook, string $record, UpdateProtocolRecord $update): RedirectResponse
    {
        $data = $request->validate([
            'baseRevisionId' => ['required', 'uuid'],
            'occurredAt' => ['required', 'date'],
            'payload' => ['required', 'string', 'max:262144'],
        ]);
        try {
            $update->execute($request->user()->id, $logbook, $record, [
                'baseRevisionId' => $data['baseRevisionId'],
                'occurredAt' => $data['occurredAt'],
                'payload' => json_decode($data['payload'], true, 128, JSON_THROW_ON_ERROR),
            ]);
        } catch (JsonException $error) {
            return back()->withErrors(['payload' => 'Payloadが有効なJSONではありません: '.$error->getMessage()]);
        } catch (InputRejected $error) {
            return back()->withErrors(['payload' => array_map(static fn ($issue): string => $issue->path.': '.$issue->code, $error->result->errors)]);
        } catch (RevisionConflict $conflict) {
            return back()->withErrors(['record' => 'Revisionが競合しました。現在値: '.$conflict->currentRevisionId]);
        }

        return back()->with('status', 'Protocol Recordを更新しました。');
    }

    public function destroy(Request $request, string $logbook, string $record, DeleteProtocolRecord $delete): RedirectResponse
    {
        $data = $request->validate(['baseRevisionId' => ['required', 'uuid']]);
        try {
            $delete->execute($request->user()->id, $logbook, $record, $data['baseRevisionId']);
        } catch (RevisionConflict $conflict) {
            return back()->withErrors(['record' => 'Revisionが競合しました。現在値: '.$conflict->currentRevisionId]);
        }

        return back()->with('status', 'Protocol Recordを論理削除しました。');
    }
}
