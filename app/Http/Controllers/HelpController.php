<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Illuminate\View\View;

class HelpController extends Controller
{
    public function index(): View
    {
        return view('help.index', [
            'faqs' => Faq::where('is_published', true)->orderBy('sort_order')->get()->groupBy('category'),
        ]);
    }
}
