<?php

namespace App\Http\Controllers\Hq;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $pendingUsers = User::where('status', User::STATUS_PENDING_APPROVAL)
            ->where('role', UserRole::Sales)
            ->orderBy('created_at', 'desc')
            ->get();

        $activeBranches = Branch::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('hq.users.index', [
            'pendingUsers' => $pendingUsers,
            'activeBranches' => $activeBranches,
        ]);
    }

    public function approve(Request $request, User $user): RedirectResponse
    {
        $this->authorize('approve', $user);

        $validated = $request->validate([
            'branch_id' => [
                'required',
                'exists:branches,id',
                function ($attribute, $value, $fail) {
                    $branch = Branch::find($value);
                    if ($branch && ! $branch->is_active) {
                        $fail('Cabang yang dipilih tidak aktif.');
                    }
                },
            ],
        ]);

        $branch = Branch::findOrFail($validated['branch_id']);

        $user->approve($branch, $request->user());

        return redirect()->route('hq.users.pending')
            ->with('success', 'Pengguna berhasil disetujui dan ditugaskan ke cabang ' . $branch->name . '.');
    }
}
