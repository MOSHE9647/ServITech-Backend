@extends('layouts.app')

@section('content')
<style>
    .requirement-icon {
        transition: all 0.3s ease;
    }
    
    .form-field {
        transition: all 0.3s ease;
    }
    
    .form-field:focus {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    
    .strength-bar {
        transition: all 0.5s ease;
    }
    
    .pulse-success {
        animation: pulse-green 2s infinite;
    }
    
    @keyframes pulse-green {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.7;
        }
    }
    
    .slide-in {
        animation: slideIn 0.3s ease-out;
    }
    
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<section class="bg-gray-50 dark:bg-gray-900 min-h-screen">
    <div class="flex flex-col items-center justify-center px-6 py-8 mx-auto md:h-screen lg:py-0">
        <a href="{{ config('app.url') }}" class="flex flex-col items-center mb-6 text-2xl font-semibold text-gray-900 dark:text-white transition-all duration-300 hover:scale-105">
            <img class="w-50 h-50" src="{{ asset('img/Logo.svg') }}" alt="Logo ServITech">
        </a>
        <div class="w-full p-6 bg-white rounded-lg shadow-xl dark:border md:mt-0 sm:max-w-md dark:bg-gray-800 dark:border-gray-700 sm:p-8 transition-all duration-300">
            
            @if (isset($email) && isset($authToken))
                <div class="text-center mb-6">
                    <h2 class="text-xl font-bold leading-tight tracking-tight text-gray-900 md:text-2xl dark:text-white">
                        @lang('Change Password')
                    </h2>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        @lang('Create a strong password for your account')
                    </p>
                </div>

                <form id="reset-password-form" class="space-y-6" method="POST" action="{{ route('localized.auth.reset-password', ['locale' => app()->getLocale()]) }}">
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="token" value="{{ $authToken }}">

                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                            @lang('Email')
                        </label>
                        <div class="relative">
                            <input type="email" name="email" id="email"
                                class="form-field bg-gray-50 border border-gray-300 text-gray-500 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-400 dark:focus:ring-blue-500 dark:focus:border-blue-500 cursor-not-allowed"
                                value="{{ $email }}" readonly>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                        </div>
                        <p id="error_email" class="mt-2 text-xs text-red-600 dark:text-red-400 hidden"></p>
                    </div>

                    <!-- Password Field -->
                    <div>
                        <label for="password" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                            @lang('New Password')
                        </label>
                        <div class="relative">
                            <input type="password" name="password" id="password" placeholder="••••••••"
                                class="form-field bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 pr-10 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 transition-colors duration-200"
                                required autocomplete="new-password">
                            <button type="button" id="toggle-password" class="absolute inset-y-0 right-0 flex items-center pr-3">
                                <svg id="eye-open" class="w-4 h-4 text-gray-400 hover:text-gray-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                <svg id="eye-closed" class="w-4 h-4 text-gray-400 hover:text-gray-600 transition-colors hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
                                </svg>
                            </button>
                        </div>
                        
                        <!-- Password Strength Indicator -->
                        <div class="mt-2">
                            <div class="flex space-x-1">
                                <div id="strength-bar-1" class="strength-bar h-1 w-1/4 bg-gray-200 rounded transition-colors duration-300"></div>
                                <div id="strength-bar-2" class="strength-bar h-1 w-1/4 bg-gray-200 rounded transition-colors duration-300"></div>
                                <div id="strength-bar-3" class="strength-bar h-1 w-1/4 bg-gray-200 rounded transition-colors duration-300"></div>
                                <div id="strength-bar-4" class="strength-bar h-1 w-1/4 bg-gray-200 rounded transition-colors duration-300"></div>
                            </div>
                            <p id="password-strength-text" class="mt-1 text-xs text-gray-500 dark:text-gray-400"></p>
                        </div>
                        
                        <p id="error_password" class="mt-2 text-xs text-red-600 dark:text-red-400 hidden"></p>
                        
                        <!-- Password Requirements -->
                        <div class="mt-3 text-xs text-gray-600 dark:text-gray-400">
                            <p class="font-medium mb-1">@lang('Password must contain'):</p>
                            <ul class="space-y-1">
                                <li id="req-length" class="flex items-center">
                                    <span class="requirement-icon w-3 h-3 mr-2">✗</span>
                                    @lang('At least 8 characters')
                                </li>
                                <li id="req-uppercase" class="flex items-center">
                                    <span class="requirement-icon w-3 h-3 mr-2">✗</span>
                                    @lang('One uppercase letter')
                                </li>
                                <li id="req-lowercase" class="flex items-center">
                                    <span class="requirement-icon w-3 h-3 mr-2">✗</span>
                                    @lang('One lowercase letter')
                                </li>
                                <li id="req-number" class="flex items-center">
                                    <span class="requirement-icon w-3 h-3 mr-2">✗</span>
                                    @lang('One number')
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Password Confirmation Field -->
                    <div>
                        <label for="password_confirmation" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                            @lang('Confirm Password')
                        </label>
                        <div class="relative">
                            <input type="password" name="password_confirmation" id="password_confirmation" placeholder="••••••••"
                                class="form-field bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 pr-10 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 transition-colors duration-200"
                                required autocomplete="new-password">
                            <div id="match-indicator" class="absolute inset-y-0 right-0 items-center pr-3 hidden">
                                <svg id="match-success" class="w-4 h-4 text-green-500 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <svg id="match-error" class="w-4 h-4 text-red-500 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </div>
                        </div>
                        <p id="error_password_confirmation" class="mt-2 text-xs text-red-600 dark:text-red-400 hidden"></p>
                        <p id="match-text" class="mt-2 text-xs text-gray-500 dark:text-gray-400 hidden"></p>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" id="submit-btn"
                        class="w-full text-white bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span id="submit-text">@lang('Reset Password')</span>
                        <svg id="loading-spinner" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white hidden" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                </form>

                <script>
                    class PasswordResetForm {
                        constructor() {
                            this.form = document.getElementById('reset-password-form');
                            this.passwordField = document.getElementById('password');
                            this.confirmField = document.getElementById('password_confirmation');
                            this.submitBtn = document.getElementById('submit-btn');
                            this.submitText = document.getElementById('submit-text');
                            this.loadingSpinner = document.getElementById('loading-spinner');
                            this.togglePasswordBtn = document.getElementById('toggle-password');
                            
                            this.requirements = {
                                length: { element: document.getElementById('req-length'), test: password => password.length >= 8 },
                                uppercase: { element: document.getElementById('req-uppercase'), test: password => /[A-Z]/.test(password) },
                                lowercase: { element: document.getElementById('req-lowercase'), test: password => /[a-z]/.test(password) },
                                number: { element: document.getElementById('req-number'), test: password => /\d/.test(password) }
                            };
                            
                            this.init();
                        }
                        
                        init() {
                            this.setupEventListeners();
                            this.updateButtonState();
                        }
                        
                        setupEventListeners() {
                            // Form submission
                            this.form.addEventListener('submit', this.handleSubmit.bind(this));
                            
                            // Password visibility toggle
                            this.togglePasswordBtn.addEventListener('click', this.togglePasswordVisibility.bind(this));
                            
                            // Password validation
                            this.passwordField.addEventListener('input', this.handlePasswordInput.bind(this));
                            this.confirmField.addEventListener('input', this.handleConfirmPasswordInput.bind(this));
                            
                            // Real-time validation
                            this.passwordField.addEventListener('blur', this.validatePassword.bind(this));
                            this.confirmField.addEventListener('blur', this.validatePasswordConfirmation.bind(this));
                        }
                        
                        togglePasswordVisibility() {
                            const eyeOpen = document.getElementById('eye-open');
                            const eyeClosed = document.getElementById('eye-closed');
                            
                            if (this.passwordField.type === 'password') {
                                this.passwordField.type = 'text';
                                eyeOpen.classList.add('hidden');
                                eyeClosed.classList.remove('hidden');
                            } else {
                                this.passwordField.type = 'password';
                                eyeOpen.classList.remove('hidden');
                                eyeClosed.classList.add('hidden');
                            }
                        }
                        
                        handlePasswordInput() {
                            this.updatePasswordStrength();
                            this.updatePasswordRequirements();
                            this.updatePasswordConfirmation();
                            this.updateButtonState();
                        }
                        
                        handleConfirmPasswordInput() {
                            this.updatePasswordConfirmation();
                            this.updateButtonState();
                        }
                        
                        updatePasswordStrength() {
                            const password = this.passwordField.value;
                            const strengthBars = [1, 2, 3, 4].map(i => document.getElementById(`strength-bar-${i}`));
                            const strengthText = document.getElementById('password-strength-text');
                            
                            let score = 0;
                            let feedback = '';
                            
                            // Calculate strength score
                            if (password.length >= 8) score++;
                            if (/[a-z]/.test(password)) score++;
                            if (/[A-Z]/.test(password)) score++;
                            if (/\d/.test(password)) score++;
                            if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) score++;
                            
                            // Reset all bars
                            strengthBars.forEach(bar => {
                                bar.className = bar.className.replace(/bg-(red|yellow|blue|green)-\d+/, 'bg-gray-200');
                            });
                            
                            // Update bars based on score
                            const colors = ['bg-red-500', 'bg-yellow-500', 'bg-blue-500', 'bg-green-500'];
                            const labels = ['@lang("Very Weak")', '@lang("Weak")', '@lang("Good")', '@lang("Strong")'];
                            
                            if (password.length > 0) {
                                const strengthLevel = Math.min(score - 1, 3);
                                if (strengthLevel >= 0) {
                                    for (let i = 0; i <= strengthLevel; i++) {
                                        strengthBars[i].className = strengthBars[i].className.replace('bg-gray-200', colors[strengthLevel]);
                                    }
                                    feedback = labels[strengthLevel];
                                }
                            }
                            
                            strengthText.textContent = feedback;
                        }
                        
                        updatePasswordRequirements() {
                            const password = this.passwordField.value;
                            
                            Object.values(this.requirements).forEach(req => {
                                const icon = req.element.querySelector('.requirement-icon');
                                const isValid = req.test(password);
                                
                                if (isValid) {
                                    icon.textContent = '✓';
                                    icon.className = 'requirement-icon w-3 h-3 mr-2 text-green-500 font-bold';
                                    req.element.className = req.element.className.replace('text-gray-600 dark:text-gray-400', 'text-green-600 dark:text-green-400');
                                } else {
                                    icon.textContent = '✗';
                                    icon.className = 'requirement-icon w-3 h-3 mr-2 text-red-500 font-bold';
                                    req.element.className = req.element.className.replace('text-green-600 dark:text-green-400', 'text-gray-600 dark:text-gray-400');
                                }
                            });
                        }
                        
                        updatePasswordConfirmation() {
                            const password = this.passwordField.value;
                            const confirmation = this.confirmField.value;
                            const matchIndicator = document.getElementById('match-indicator');
                            const matchSuccess = document.getElementById('match-success');
                            const matchError = document.getElementById('match-error');
                            const matchText = document.getElementById('match-text');
                            
                            if (confirmation.length === 0) {
                                matchIndicator.classList.add('hidden');
                                matchText.classList.add('hidden');
                                return;
                            }
                            
                            matchIndicator.classList.remove('hidden');
                            matchIndicator.classList.add('flex');
                            matchText.classList.remove('hidden');
                            
                            if (password === confirmation) {
                                matchSuccess.classList.remove('hidden');
                                matchError.classList.add('hidden');
                                matchText.textContent = '@lang("Passwords match")';
                                matchText.className = 'mt-2 text-xs text-green-600 dark:text-green-400';
                            } else {
                                matchSuccess.classList.add('hidden');
                                matchError.classList.remove('hidden');
                                matchText.textContent = '@lang("Passwords do not match")';
                                matchText.className = 'mt-2 text-xs text-red-600 dark:text-red-400';
                            }
                        }
                        
                        validatePassword() {
                            const password = this.passwordField.value;
                            const errorElement = document.getElementById('error_password');
                            
                            if (password.length < 8) {
                                this.showFieldError('password', '@lang("Password must be at least 8 characters")');
                                return false;
                            }
                            
                            this.hideFieldError('password');
                            return true;
                        }
                        
                        validatePasswordConfirmation() {
                            const password = this.passwordField.value;
                            const confirmation = this.confirmField.value;
                            
                            if (confirmation && password !== confirmation) {
                                this.showFieldError('password_confirmation', '@lang("Password confirmation does not match")');
                                return false;
                            }
                            
                            this.hideFieldError('password_confirmation');
                            return true;
                        }
                        
                        isFormValid() {
                            const password = this.passwordField.value;
                            const confirmation = this.confirmField.value;
                            
                            // Check password requirements
                            const passwordValid = Object.values(this.requirements).every(req => req.test(password));
                            const confirmationValid = password === confirmation && confirmation.length > 0;
                            
                            return passwordValid && confirmationValid;
                        }
                        
                        updateButtonState() {
                            const isValid = this.isFormValid();
                            this.submitBtn.disabled = !isValid;
                            
                            if (isValid) {
                                this.submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                                this.submitBtn.classList.add('hover:bg-primary-700');
                            } else {
                                this.submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                                this.submitBtn.classList.remove('hover:bg-primary-700');
                            }
                        }
                        
                        showFieldError(field, message) {
                            const errorElement = document.getElementById(`error_${field}`);
                            if (errorElement) {
                                errorElement.innerHTML = `<span class='font-medium'>${message}</span>`;
                                errorElement.classList.remove('hidden');
                                errorElement.classList.add('slide-in');
                            }
                        }
                        
                        hideFieldError(field) {
                            const errorElement = document.getElementById(`error_${field}`);
                            if (errorElement) {
                                errorElement.textContent = '';
                                errorElement.classList.add('hidden');
                            }
                        }
                        
                        clearAllErrors() {
                            document.querySelectorAll('p[id^="error_"]').forEach(p => {
                                p.textContent = '';
                                p.classList.add('hidden');
                            });
                        }
                        
                        setLoading(loading) {
                            if (loading) {
                                this.submitBtn.disabled = true;
                                this.submitText.textContent = '@lang("Processing...")';
                                this.loadingSpinner.classList.remove('hidden');
                                this.loadingSpinner.classList.add('inline');
                            } else {
                                this.submitBtn.disabled = !this.isFormValid();
                                this.submitText.textContent = '@lang("Reset Password")';
                                this.loadingSpinner.classList.add('hidden');
                                this.loadingSpinner.classList.remove('inline');
                            }
                        }
                        
                        async handleSubmit(event) {
                            event.preventDefault();
                            
                            // Clear previous errors
                            this.clearAllErrors();
                            
                            // Validate form
                            if (!this.isFormValid()) {
                                return;
                            }
                            
                            this.setLoading(true);
                            
                            const formData = new FormData(this.form);
                            
                            try {
                                const response = await fetch(this.form.action, {
                                    method: this.form.method,
                                    headers: {
                                        'X-CSRF-TOKEN': formData.get('_token'),
                                        'Accept': 'application/json',
                                    },
                                    body: formData
                                });
                                
                                if (response.headers.get('content-type')?.includes('application/json')) {
                                    const data = await response.json();
                                    
                                    if (data.errors) {
                                        for (const [field, messages] of Object.entries(data.errors)) {
                                            const message = Array.isArray(messages) ? messages[0] : messages;
                                            
                                            if (field === 'password' && message.includes('@lang("validation.confirmed")')) {
                                                this.showFieldError('password_confirmation', '@lang("Password confirmation does not match")');
                                            } else {
                                                this.showFieldError(field, message);
                                            }
                                        }
                                    } else if (data.message) {
                                        // Success or other message
                                        console.log('Success:', data.message);
                                    }
                                } else {
                                    // If the response is not JSON, it's likely a redirect or HTML response
                                    const text = await response.text();
                                    if (response.ok) {
                                        // Success - replace page content
                                        document.open();
                                        document.write(text);
                                        document.close();
                                    } else {
                                        throw new Error('Server error');
                                    }
                                }
                            } catch (error) {
                                console.error('Error:', error);
                                // Fallback to normal form submission
                                this.form.submit();
                            } finally {
                                this.setLoading(false);
                            }
                        }
                    }
                    
                    // Initialize the form when DOM is loaded
                    document.addEventListener('DOMContentLoaded', function() {
                        new PasswordResetForm();
                    });
                </script>

            @else
                <div class="flex gap-3 align-items-center justify-center">
                    <h2 class="text-xl font-bold md:text-2xl {{ $message['type'] == 'success' ? 'text-green-500 dark:text-green-500' : 'text-red-500 dark:text-red-500' }}">
                        {{ $message['type'] == 'success' ? __('Success') : __('Error') }}:
                    </h2>
                    
                    <h4 class="text-xl font-bold text-gray-900 md:text-2xl dark:text-white">
                        {{ $message['title'] }}
                    </h4>
                </div>
            @endif

        </div>
    </div>
</section>
@endsection