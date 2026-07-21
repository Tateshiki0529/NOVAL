<?php

namespace App\Http\Controllers;

use App\Application\Exceptions\InputRejected;
use App\Application\UseCases\CreateProtocolDraft;
use App\Application\UseCases\PublishProtocolVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use JsonException;

class ProtocolController extends Controller
{
    public function store(Request $request, CreateProtocolDraft $create): RedirectResponse
    {
        $data = $request->validate([
            'slug' => ['required', 'string', 'max:64'],
            'version' => ['required', 'string', 'max:32'],
            'schema' => ['required', 'string', 'max:1048576'],
            'metadata' => ['required', 'string', 'max:1048576'],
        ]);
        try {
            $create->execute($request->user()->id, [
                'slug' => $data['slug'],
                'version' => $data['version'],
                'schema' => json_decode($data['schema'], true, 128, JSON_THROW_ON_ERROR),
                'metadata' => json_decode($data['metadata'], true, 128, JSON_THROW_ON_ERROR),
            ]);
        } catch (JsonException $error) {
            return back()->withErrors(['schema' => 'Schemaまたはmetadataが有効なJSONではありません: '.$error->getMessage()])->withInput();
        }

        return back()->with('status', 'Protocol Draftを保存しました。');
    }

    public function publish(Request $request, string $version, PublishProtocolVersion $publish): RedirectResponse
    {
        try {
            $publish->execute($request->user()->id, $version);
        } catch (InputRejected $error) {
            $messages = array_map(static fn ($issue): string => $issue->path.': '.$issue->code, $error->result->errors);

            return back()->withErrors(['protocol' => $messages]);
        }

        return back()->with('status', 'Protocol VersionをPublishしました。');
    }
}
