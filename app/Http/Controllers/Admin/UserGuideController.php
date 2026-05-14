<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserGuideController extends Controller
{
    public function index(Request $request)
    {
        return view('bis.user-guide', [
            'host' => $request->query('host'),
        ]);
    }
}
