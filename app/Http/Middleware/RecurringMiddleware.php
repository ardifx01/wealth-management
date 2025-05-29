<?php

namespace App\Http\Middleware;

use App\Services\RecurringTransactionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RecurringMiddleware
{
    public function __construct(
        protected RecurringTransactionService $recurringTransactionService,
    ) {}
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $this->recurringTransactionService->generate();
        }
        return $next($request);
    }
}
