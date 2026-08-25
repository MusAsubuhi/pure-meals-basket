@extends('auth.layout')

@section('title', 'Verify Email')

@section('slot')
<div class="w-full max-w-md text-center">
    <h1 class="text-2xl font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Verify your email</h1>
    <p class="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">A verification link has been sent to your email address. Please check your inbox and click the link to verify your account.</p>
    <form method="POST" action="{{ route('logout') }}" class="mt-6">
        @csrf
        <button type="submit" class="rounded-sm border border-[#19140035] dark:border-[#3E3E3A] px-5 py-1.5 text-sm text-[#1b1b18] dark:text-[#EDEDEC] hover:border-[#1915014a] dark:hover:border-[#62605b]">
            Log out
        </button>
    </form>
</div>
@endsection
