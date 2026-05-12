<?php

namespace Softpro\Core\Http\Controllers;

use Softpro\Core\Models\CustomEntity;
use Softpro\Core\Models\CustomEntityValue;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class CustomEntityController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/CustomEntities/Index', [
            'entities' => CustomEntity::withCount('values')->get()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'display_name' => 'required|string|max:255',
            'description'  => 'nullable|string',
        ]);

        CustomEntity::create([
            'name'         => Str::slug($request->display_name, '_'),
            'display_name' => $request->display_name,
            'description'  => $request->description,
        ]);

        return redirect()->back()->with('success', 'Entity created successfully.');
    }

    public function show(CustomEntity $entity)
    {
        return Inertia::render('Admin/CustomEntities/Show', [
            'entity' => $entity->load('values')
        ]);
    }

    public function update(Request $request, CustomEntity $entity)
    {
        $request->validate([
            'display_name' => 'required|string|max:255',
            'description'  => 'nullable|string',
        ]);

        $entity->update([
            'display_name' => $request->display_name,
            'description'  => $request->description,
        ]);

        return redirect()->back()->with('success', 'Entity updated successfully.');
    }

    public function destroy(CustomEntity $entity)
    {
        $entity->delete();
        return redirect()->route('admin.custom-entities.index')->with('success', 'Entity deleted successfully.');
    }

    public function storeValue(Request $request, CustomEntity $entity)
    {
        $request->validate([
            'label' => 'required|string|max:255',
            'value' => 'required|string|max:255',
            'sort_order' => 'nullable|integer',
        ]);

        $entity->values()->create($request->all());

        return redirect()->back()->with('success', 'Value added successfully.');
    }

    public function updateValue(Request $request, CustomEntityValue $value)
    {
        $request->validate([
            'label' => 'required|string|max:255',
            'value' => 'required|string|max:255',
            'sort_order' => 'nullable|integer',
        ]);

        $value->update($request->all());

        return redirect()->back()->with('success', 'Value updated successfully.');
    }

    public function destroyValue(CustomEntityValue $value)
    {
        $value->delete();
        return redirect()->back()->with('success', 'Value deleted successfully.');
    }
}
