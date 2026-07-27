<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->get('q', ''));
        $students = collect();

        if ($q !== '') {
            $students = Student::with('programme')
                ->where(function ($query) use ($q) {
                    $query->where('reg_no', 'like', "%{$q}%")
                        ->orWhere('nactvet_reg_no', 'like', "%{$q}%")
                        ->orWhere('first_name', 'like', "%{$q}%")
                        ->orWhere('middle_name', 'like', "%{$q}%")
                        ->orWhere('last_name', 'like', "%{$q}%");
                })
                ->orderByRaw('LOWER(last_name)')
                ->orderByRaw('LOWER(first_name)')
                ->limit(50)
                ->get();
        }

        return view('search.index', compact('q', 'students'));
    }
}
