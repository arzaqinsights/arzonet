<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $contactUsage = $user->getContactsUsage();
        $emailUsage = $user->getEmailsUsage();

        return view('profile.index', compact('user', 'contactUsage', 'emailUsage'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();
        
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone_number' => 'nullable|string',
            'company_name' => 'nullable|string',
            'gstin' => 'nullable|string',
            'address_street' => 'nullable|string',
            'address_city' => 'nullable|string',
            'address_state' => 'nullable|string',
            'address_country' => 'nullable|string',
            'address_zip' => 'nullable|string',
            'password' => 'nullable|min:8|confirmed',
        ]);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return back()->with('success', 'Profile updated successfully!');
    }
}
