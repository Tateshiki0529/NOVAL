<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Logbook;
use App\Models\OpenRecord;
use App\Models\Protocol;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $ownerId = $request->user()->id;

        return view('dashboard', [
            'protocols' => Protocol::where('owner_id', $ownerId)->with('versions')->latest()->get(),
            'categories' => Category::where('owner_id', $ownerId)->orderBy('sort_order')->get(),
            'logbooks' => Logbook::where('owner_id', $ownerId)->with(['category', 'currentProtocolVersion', 'records.currentRevision'])->latest()->get(),
            'openRecords' => OpenRecord::where('owner_id', $ownerId)->with('currentRevision')->latest()->limit(20)->get(),
        ]);
    }
}
