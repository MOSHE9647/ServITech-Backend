@component('mail::message')
# {{ __('Password Reset Successful') }}

{{ __('Hello, :name!', ['name' => $user->name ?? __('User')]) }}

{{ __('This email confirms that your password has been successfully reset for your :app account.', ['app' => config('app.name')]) }}

{{ __('Your account is now secure with your new password.') }}

{{ __('If you did not perform this action, please contact our support team immediately as your account may have been compromised.') }}

{{ __('For security reasons, we recommend:') }}

@if(isset($recommendations))
<ul style="margin: 0; margin-bottom: 1em; padding-left: 2em;">
    @foreach($recommendations as $item)
        <li style="margin-bottom: 0; text-indent: 2em;">{{ $item }}</li>
    @endforeach
</ul>
@endif

{{ __('If you have any questions or need further assistance, feel free to reach out to our support team.') }}

{{ __('Regards,') }}<br>
{{ config('app.name') }}
@endcomponent