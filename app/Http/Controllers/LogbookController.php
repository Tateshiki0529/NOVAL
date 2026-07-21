<?php

namespace App\Http\Controllers;

use App\Application\UseCases\CreateLogbook;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LogbookController extends Controller
{
    public function store(Request $request, CreateLogbook $create): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:4000'],
            'categoryId' => ['nullable', 'uuid'],
            'protocolVersionId' => ['required', 'uuid'],
        ]);
        $create->execute($request->user()->id, $data);

        return back()->with('status', 'LogBookを作成しました。');
    }
}
