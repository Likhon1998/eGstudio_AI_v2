<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdminCgiAuditController extends Controller
{
    public function index()
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized Access.');
        }

        // Fetch users who actually have generations, and load their history
        $users = User::whereHas('cgiGenerations')
            ->with(['cgiGenerations' => function($query) {
                $query->latest(); // Sort their generations newest first
            }])
            ->paginate(15);

        return view('admin.cgi_audit.index', compact('users'));
    }
}