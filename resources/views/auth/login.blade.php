@extends('auth.layout')

@section('title', 'Login')

@section('slot')
<div class="w-full max-w-md">
    <div class="bg-white dark:bg-[#161615] rounded-lg shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d] p-8">
        <div class="mb-8 text-center">
            <h1 class="text-2xl font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Welcome back</h1>
            <p class="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">Sign in to your account</p>
        </div>

        <form method="POST" action="{{ route('login') }}" class="space-y-6">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC] mb-1">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                    class="w-full rounded-sm border border-[#e3e3e0] dark:border-[#3E3E3A] bg-white dark:bg-[#0a0a0a] px-3 py-2 text-sm text-[#1b1b18] dark:text-[#EDEDEC] placeholder:text-[#a1a09a] focus:border-black focus:ring-black dark:focus:border-white dark:focus:ring-white">
                @error('email')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC] mb-1">Password</label>
                <input id="password" type="password" name="password" required
                    class="w-full rounded-sm border border-[#e3e3e0] dark:border-[#3E3E3A] bg-white dark:bg-[#0a0a0a] px-3 py-2 text-sm text-[#1b1b18] dark:text-[#EDEDEC] placeholder:text-[#a1a09a] focus:border-black focus:ring-black dark:focus:border-white dark:focus:ring-white">
                @error('password')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="remember" class="rounded border-[#e3e3e0] dark:border-[#3E3E3A]">
                    <span class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Remember me</span>
                </label>
            </div>

            <button type="submit" class="w-full rounded-sm bg-[#1b1b18] dark:bg-white px-4 py-2 text-sm font-medium text-white dark:text-[#1b1b18] hover:bg-black dark:hover:bg-[#e3e3e0]">
                Sign in
            </button>

            <p class="text-center text-sm text-[#706f6c] dark:text-[#A1A09A]">
                Don't have an account?
                <a href="{{ route('register') }}" class="font-medium text-[#f53003] dark:text-[#FF4433] hover:underline">Register</a>
            </p>
        </form>
    </div>
</div>
@endsection
