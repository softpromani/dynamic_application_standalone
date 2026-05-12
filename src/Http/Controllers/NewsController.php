<?php

namespace Softpro\Core\Http\Controllers;

use Illuminate\Http\Request;
use Softpro\Core\Models\News;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class NewsController extends Controller
{
    public function index()
    {
        return Inertia::render('News/Index', [
            'news' => News::orderBy('sort_order')->latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:file,link',
            'file' => 'nullable|required_if:type,file|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
            'link_url' => 'nullable|required_if:type,link|url|max:255',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $data = $validated;
        unset($data['file']);

        if ($request->hasFile('file')) {
            // Check if tenant helper exists (SaaS mode)
            $path = function_exists('tenant') ? tenant('id') . '/news' : 'news';
            $data['file_path'] = $request->file('file')->store($path, 'public');
        }

        News::create($data);

        return redirect()->route('news.index')->with('success', 'News created successfully.');
    }

    public function update(Request $request, News $news)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:file,link',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
            'link_url' => 'nullable|required_if:type,link|url|max:255',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $data = $validated;
        unset($data['file']);

        if ($request->hasFile('file')) {
            if ($news->file_path) {
                Storage::disk('public')->delete($news->file_path);
            }
            $path = function_exists('tenant') ? tenant('id') . '/news' : 'news';
            $data['file_path'] = $request->file('file')->store($path, 'public');
        }

        if ($data['type'] === 'link') {
            if ($news->file_path) {
                Storage::disk('public')->delete($news->file_path);
                $data['file_path'] = null;
            }
        } else {
            $data['link_url'] = null;
        }

        $news->update($data);

        return redirect()->route('news.index')->with('success', 'News updated successfully.');
    }

    public function destroy(News $news)
    {
        if ($news->file_path) {
            Storage::disk('public')->delete($news->file_path);
        }
        $news->delete();

        return redirect()->route('news.index')->with('success', 'News deleted successfully.');
    }
}
