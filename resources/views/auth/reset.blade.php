@extends('layouts.app')

@section('content')
<section class="bg-gray-50 dark:bg-gray-900">
    <div class="flex flex-col items-center justify-center px-6 py-8 mx-auto md:h-screen lg:py-0">
        <a href="{{ config('app.url') }}" class="flex flex-col items-center mb-6 text-2xl font-semibold text-gray-900 dark:text-white">
            <img class="w-50 h-50" src="{{ asset('img/Logo.svg') }}" alt="Logo">
        </a>
        <div class="w-full p-6 bg-white rounded-lg shadow dark:border md:mt-0 sm:max-w-md dark:bg-gray-800 dark:border-gray-700 sm:p-8">
            
            @if (isset($user_email) && isset($reset_token))
                <h2 class="mb-1 text-xl font-bold leading-tight tracking-tight text-gray-900 md:text-2xl dark:text-white">
                    @lang('Change Password')
                </h2>

                <form class="mt-4 space-y-4 lg:mt-5 md:space-y-5" method="POST" action="{{ route('reset-password') }}">
                    @csrf
                    <input type="hidden" name="reset_token" value="{{ $reset_token }}">

                    <div>
                        <label for="email" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                            @lang('Your email')
                        </label>
                        <input type="email" name="email" id="email"
                            class="bg-gray-50 border border-gray-300 text-[#99a1af] text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                            value="{{ $user_email }}" readonly>
                        @error('email')
                            <p class="text-red-500 text-xs italic mt-2">{{ $errors->first('email') }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="password" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                            @lang('New Password')
                        </label>
                        <input type="password" name="password" id="password" placeholder="••••••••"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                            required>
                        @error('password')
                            <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="password_confirmation"
                            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                            @lang('Confirm Password')
                        </label>
                        <input type="password" name="password_confirmation" id="password_confirmation" placeholder="••••••••"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                            required>
                        @error('password_confirmation')
                            <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit"
                        class="w-full text-white bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">
                        @lang('Reset Password')
                    </button>
                </form>

            @else

                <div class="flex gap-3 align-items-center justify-center">
                    <h2 class="text-xl font-bold md:text-2xl {{ $swal['type'] == 'success' ? 'text-green-500 dark:text-green-500' : 'text-red-500 dark:text-red-500' }}">
                        {{ $swal['type'] == 'success' ? __('Success') : __('Error') }}:
                    </h2>
                    
                    <h4 class="text-xl font-bold text-gray-900 md:text-2xl dark:text-white">
                        {{ $swal['title'] }}
                    </h4>
                </div>

            @endif

        </div>
    </div>
</section>
@endsection