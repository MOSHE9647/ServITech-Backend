<x-mail::message>
# @lang('Hello') {{ $user_name }},

@lang('You are receiving this email because we received a password reset request for your account.')

<x-mail::button :url="$url" color="primary">
@lang('Reset Password')
</x-mail::button>

@lang('If you did not request a password reset, no further action is required.')<br>

@lang('Regards,')  
@lang('The :name Team', ['name' => config('app.name')])
</x-mail::message>