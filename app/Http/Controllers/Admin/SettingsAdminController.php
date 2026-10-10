<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CrmSetting;
use Illuminate\Http\Request;

class SettingsAdminController extends Controller
{
    public function index()
    {
        $codEnabled = (bool) CrmSetting::get('cod_enabled', true);
        return view('admin.settings.index', compact('codEnabled'));
    }

    public function update(Request $request)
    {
        $request->validate(['cod_enabled' => 'nullable|boolean']);
        CrmSetting::set('cod_enabled', $request->boolean('cod_enabled'));
        return back()->with('success', 'Settings updated.');
    }
}
