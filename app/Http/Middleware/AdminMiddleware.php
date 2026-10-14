<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // ログインしていない、または管理者権限（admin_status）がない場合
        if (!Auth::check() || !Auth::user()->admin_status) {
            // 管理者ログイン画面へリダイレクト
            return redirect('/admin/login');
        }
        return $next($request);
    }
}
