<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    public function create(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'name' => 'required|string|min:3|max:50',
        ]);

        $user = User::create([
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'name' => $validated['name'],
        ]);

        // Kirim email ke user dan admin
        Mail::to($user->email)->send(new \App\Mail\UserCreated($user));
        Mail::to('admin@example.com')->send(new \App\Mail\AdminNotification($user));

        return response()->json($user->only(['id', 'email', 'name', 'created_at']), 201);
    }

    public function index(Request $request)
    {
        $query = User::query()->withCount('orders');

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        }

        if ($sortBy = $request->input('sortBy')) {
            $query->orderBy($sortBy, 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $users = $query->paginate(10);

        return response()->json([
            'page' => $users->currentPage(),
            'users' => $users->items(),
        ]);
    }
}

