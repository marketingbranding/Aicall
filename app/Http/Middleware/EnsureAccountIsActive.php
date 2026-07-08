<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        if ($user->isPendingApproval()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Akun belum disetujui.'], 403);
            }

            return redirect()->route('account.pending');
        }

        if ($user->isSuspended()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Akun sedang ditangguhkan.'], 403);
            }

            return redirect()->route('account.suspended');
        }

        return $next($request);
    }
}
