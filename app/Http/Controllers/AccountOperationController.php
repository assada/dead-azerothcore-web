<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountOperationController extends Controller
{
    public function index(Request $request): View
    {
        return view('profile.history', [
            'operations' => $request->user()->operations()->latest()->orderByDesc('id')->paginate(20),
        ]);
    }
}
