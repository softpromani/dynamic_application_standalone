<?php

namespace Softpro\Core\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminSettingsController extends Controller
{
    public function index()
    {
        $tenant = function_exists('tenant') ? tenant() : null;
        return Inertia::render('Admin/Settings/Index', [
            'tenant' => $tenant,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'name'                  => 'required|string|max:255',
            'landing_page_html'     => 'nullable|string',
            'header_address'        => 'nullable|string|max:255',
            'header_subtext_prefix' => 'nullable|string|max:255',
            'logo'                  => 'nullable|image|max:2048',
        ]);

        $tenant = function_exists('tenant') ? tenant() : null;
        if ($tenant) {
            $tenant->name = $request->input('name');
            $tenant->landing_page_html = $request->input('landing_page_html');
            $tenant->header_address = $request->input('header_address');
            $tenant->header_subtext_prefix = $request->input('header_subtext_prefix');

            if ($request->hasFile('logo')) {
                $path = $request->file('logo')->store($tenant->id . '/logos', 'public');
                $tenant->logo_path = $path;
            }

            $tenant->save();
        }

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }
}
