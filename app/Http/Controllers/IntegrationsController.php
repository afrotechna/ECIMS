<?php

namespace App\Http\Controllers;

class IntegrationsController extends Controller
{
    public function index()
    {
        return view('integrations.index', [
            'moodleUrl' => config('college.integrations.moodle_url'),
            'libraryUrl' => config('college.integrations.library_url'),
            'gepgNote' => config('college.integrations.gepg_note'),
        ]);
    }
}
