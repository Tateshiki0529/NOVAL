<?php

namespace App\Http\Controllers;

use App\Application\UseCases\CreateCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function store(Request $request, CreateCategory $create): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:4000'],
        ]);
        $create->execute($request->user()->id, $data);

        return back()->with('status', 'Categoryを作成しました。');
    }
}
