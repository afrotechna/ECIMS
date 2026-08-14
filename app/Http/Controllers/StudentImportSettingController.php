<?php

namespace App\Http\Controllers;

use App\Models\StudentImportSetting;
use Illuminate\Http\Request;

class StudentImportSettingController extends Controller
{
    public function edit()
    {
        return view('students.import-settings', [
            'setting' => StudentImportSetting::current()->load('updatedByUser'),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'restrict_single_programme' => ['nullable', 'boolean'],
        ]);

        $setting = StudentImportSetting::current();
        $restrict = $request->boolean('restrict_single_programme');

        $setting->fill([
            'restrict_single_programme' => $restrict,
            'updated_by' => auth()->id(),
        ]);
        $setting->save();
        StudentImportSetting::clearCache();

        return back()->with('success', $restrict
            ? 'Restricted: each admitted-students CSV upload must now contain a single programme and NTA level.'
            : 'Unrestricted: admitted-students CSV uploads may mix programmes and NTA levels again.');
    }
}
