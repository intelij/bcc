<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        return view('admin.settings.index', [
            'settings' => SystemSetting::orderBy('key')->get(),
            'faqs' => Faq::orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(['settings' => 'required|array', 'settings.*' => 'nullable|string|max:1000']);

        foreach ($data['settings'] as $key => $value) {
            SystemSetting::put($key, $value ?? '');
        }

        return back()->with('success', 'Settings saved.');
    }

    public function storeFaq(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required|string|max:5000',
            'category' => 'required|string|max:100',
        ]);

        Faq::create($data + ['is_published' => true]);

        return back()->with('success', 'FAQ added.');
    }

    public function destroyFaq(Faq $faq): RedirectResponse
    {
        $faq->delete();

        return back()->with('success', 'FAQ removed.');
    }
}
