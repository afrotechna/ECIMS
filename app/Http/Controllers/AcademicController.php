<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Programme;
use App\Models\Semester;
use Illuminate\Http\Request;

class AcademicController extends Controller
{
    public function index(Request $request)
    {
        return redirect()->route('courses.index', $request->query());
    }
}
