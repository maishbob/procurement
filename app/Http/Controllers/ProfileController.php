<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * Display user profile
     */
    public function show(Request $request)
    {
        $user = $request->user();
        $department = $user->department;
        $roles = $user->roles()->get();

        return view('profile.show', compact('user', 'department', 'roles'));
    }

    /**
     * Show edit profile form
     */
    public function edit(Request $request)
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    /**
     * Update user profile
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $request->user()->id,
            'phone' => 'nullable|string|max:20',
        ]);

        try {
            $request->user()->update($validated);

            return redirect()->route('profile.show')
                ->with('success', 'Profile updated successfully');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to update profile: ' . $e->getMessage());
        }
    }

    /**
     * Delete user account
     */
    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'password' => 'required|current_password',
        ]);

        $user = $request->user();

        try {
            // Log the deletion
            \App\Core\Audit\AuditService::log(
                action: 'ACCOUNT_DELETED',
                status: 'success',
                model_type: 'User',
                model_id: $user->id,
                description: "User {$user->email} deleted their account"
            );

            // Delete all personal data
            $user->delete();

            return redirect('/')->with('success', 'Account deleted successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete account: ' . $e->getMessage());
        }
    }

    /**
     * Display user preferences
     */
    public function preferences(Request $request)
    {
        $user = $request->user();
        $prefs = $user->getUserPreferences();
        $departments = \App\Models\Department::all();

        return view('profile.preferences', compact('user', 'prefs', 'departments'));
    }

    /**
     * Update user preferences
     */
    public function updatePreferences(Request $request)
    {
        $validated = $request->validate([
            'theme' => 'required|in:light,dark,auto',
            'locale' => 'required|in:en,sw',
            'timezone' => 'required|timezone',
            'notification_email' => 'boolean',
            'notification_sms' => 'boolean',
            'notification_in_app' => 'boolean',
            'auto_logout_timeout' => 'nullable|numeric|min:5|max:480',
        ]);

        try {
            $request->user()->setUserPreferences($validated);

            return back()->with('success', 'Preferences updated successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update preferences: ' . $e->getMessage());
        }
    }

    /**
     * Update user password
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $request->user()->update([
                'password' => Hash::make($validated['password']),
            ]);

            return back()->with('success', 'Password updated successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update password: ' . $e->getMessage());
        }
    }

    /**
     * Get user's approval authority via AJAX
     */
    public function getApprovalAuthority(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user_id' => $user->id,
            'name' => $user->name,
            'roles' => $user->roles()->pluck('name')->toArray(),
            'approval_limit' => $user->approval_limit,
            'department' => $user->department?->name,
        ]);
    }

    /**
     * Get user's notification preferences via AJAX
     */
    public function getNotificationPreferences(Request $request)
    {
        $prefs = $request->user()->getUserPreferences();

        return response()->json([
            'email' => $prefs['notification_email'] ?? true,
            'sms' => $prefs['notification_sms'] ?? true,
            'in_app' => $prefs['notification_in_app'] ?? true,
            'locale' => $prefs['locale'] ?? 'en',
            'theme' => $prefs['theme'] ?? 'auto',
        ]);
    }

    /**
     * Download user data (GDPR compliance)
     */
    public function downloadData(Request $request)
    {
        $user = $request->user();

        $data = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'created_at' => $user->created_at,
            ],
            'roles' => $user->roles()->pluck('name')->toArray(),
            'department' => $user->department?->name,
            'audit_logs' => \App\Models\AuditLog::where('user_id', $user->id)
                ->latest()
                ->limit(100)
                ->get()
                ->toArray(),
        ];

        $filename = "user-data-{$user->id}-" . now()->format('Y-m-d-His') . '.json';

        return response()->json($data)
            ->header('Content-Disposition', "attachment; filename={$filename}");
    }
}
