<?php

namespace App\Http\Controllers;

use App\Support\SiteFaqs;

class FaqController extends Controller
{
    public function index()
    {
        return view('faq', [
            'categories' => SiteFaqs::all(),
            'flatFaqs' => SiteFaqs::flat(),
        ]);
    }
}
