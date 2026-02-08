@extends('layouts.app')

@section('title', 'System Settings')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">System Settings</h1>
        <p class="mt-2 text-gray-600">Configure procurement system parameters and preferences</p>
    </div>

    <!-- Settings Navigation -->
    <div class="flex space-x-2 mb-6 border-b border-gray-200">
        <button onclick="switchTab('general')" class="tab-btn active px-4 py-3 border-b-2 border-primary-600 text-primary-600 font-medium">
            General
        </button>
        <button onclick="switchTab('finance')" class="tab-btn px-4 py-3 border-b-2 border-transparent text-gray-600 hover:text-gray-900 font-medium">
            Finance
        </button>
        <button onclick="switchTab('notification')" class="tab-btn px-4 py-3 border-b-2 border-transparent text-gray-600 hover:text-gray-900 font-medium">
            Notifications
        </button>
        <button onclick="switchTab('email')" class="tab-btn px-4 py-3 border-b-2 border-transparent text-gray-600 hover:text-gray-900 font-medium">
            Email
        </button>
    </div>

    <!-- General Settings -->
    <div id="general-tab" class="tab-content">
        <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
            @csrf
            <input type="hidden" name="section" value="general">

            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">General Configuration</h3>
                <div class="space-y-4">
                    <div>
                        <label for="app_name" class="block text-sm font-medium text-gray-700 mb-2">Application Name</label>
                        <input type="text" id="app_name" name="app_name" value="{{ config('app.name') }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>

                    <div>
                        <label for="organization_name" class="block text-sm font-medium text-gray-700 mb-2">Organization Name</label>
                        <input type="text" id="organization_name" name="organization_name" value="{{ setting('organization_name', 'Kenya School') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>

                    <div>
                        <label for="fiscal_year_start" class="block text-sm font-medium text-gray-700 mb-2">Fiscal Year Start Month</label>
                        <select id="fiscal_year_start" name="fiscal_year_start" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" @selected(setting('fiscal_year_start', 1) == $m)>
                                    {{ \Carbon\Carbon::createFromFormat('m', $m)->format('F') }}
                                </option>
                            @endfor
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Kenya typically uses July as fiscal year start</p>
                    </div>

                    <div>
                        <label for="currency" class="block text-sm font-medium text-gray-700 mb-2">Base Currency</label>
                        <select id="currency" name="currency" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                            <option value="KES" @selected(setting('currency', 'KES') == 'KES')>KES - Kenyan Shilling</option>
                            <option value="USD" @selected(setting('currency', 'KES') == 'USD')>USD - US Dollar</option>
                            <option value="EUR" @selected(setting('currency', 'KES') == 'EUR')>EUR - Euro</option>
                        </select>
                    </div>

                    <div>
                        <label for="vat_rate" class="block text-sm font-medium text-gray-700 mb-2">VAT Rate (%)</label>
                        <input type="number" id="vat_rate" name="vat_rate" value="{{ setting('vat_rate', 16) }}" step="0.01" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <p class="text-xs text-gray-500 mt-1">Kenya standard VAT rate is 16%</p>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" id="maintenance_mode" name="maintenance_mode" @if(setting('maintenance_mode')) checked @endif class="w-4 h-4 text-primary-600 rounded focus:ring-2 focus:ring-primary-500">
                        <label for="maintenance_mode" class="ml-2 text-gray-700">Maintenance Mode</label>
                        <p class="text-xs text-gray-500 ml-4">Only admins can access the system</p>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                    Save Settings
                </button>
            </div>
        </form>
    </div>

    <!-- Finance Settings -->
    <div id="finance-tab" class="tab-content hidden">
        <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
            @csrf
            <input type="hidden" name="section" value="finance">

            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Finance Configuration</h3>
                <div class="space-y-4">
                    <div>
                        <label for="wht_threshold" class="block text-sm font-medium text-gray-700 mb-2">Invoice Amount Threshold for WHT (KES)</label>
                        <input type="number" id="wht_threshold" name="wht_threshold" value="{{ setting('wht_threshold', 50000) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <p class="text-xs text-gray-500 mt-1">Invoices below this amount are WHT exempt</p>
                    </div>

                    <div>
                        <label for="wht_rate_standard" class="block text-sm font-medium text-gray-700 mb-2">Standard WHT Rate (%)</label>
                        <input type="number" id="wht_rate_standard" name="wht_rate_standard" value="{{ setting('wht_rate_standard', 5) }}" step="0.01" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>

                    <div>
                        <label for="wht_rate_services" class="block text-sm font-medium text-gray-700 mb-2">WHT Rate for Services (%)</label>
                        <input type="number" id="wht_rate_services" name="wht_rate_services" value="{{ setting('wht_rate_services', 5) }}" step="0.01" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>

                    <div>
                        <label for="invoice_tolerance" class="block text-sm font-medium text-gray-700 mb-2">Invoice Amount Tolerance (%)</label>
                        <input type="number" id="invoice_tolerance" name="invoice_tolerance" value="{{ setting('invoice_tolerance', 2) }}" step="0.01" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <p class="text-xs text-gray-500 mt-1">Variance allowed during three-way matching</p>
                    </div>

                    <div>
                        <label for="budget_threshold_warning" class="block text-sm font-medium text-gray-700 mb-2">Budget Threshold Warning (%)</label>
                        <input type="number" id="budget_threshold_warning" name="budget_threshold_warning" value="{{ setting('budget_threshold_warning', 80) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <p class="text-xs text-gray-500 mt-1">Alert department when utilization exceeds this %</p>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                    Save Settings
                </button>
            </div>
        </form>
    </div>

    <!-- Notification Settings -->
    <div id="notification-tab" class="tab-content hidden">
        <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
            @csrf
            <input type="hidden" name="section" value="notification">

            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Notification Channels</h3>
                <div class="space-y-4">
                    <div class="flex items-center">
                        <input type="checkbox" id="notify_email" name="notify_email" @if(setting('notify_email', true)) checked @endif class="w-4 h-4 text-primary-600 rounded focus:ring-2 focus:ring-primary-500">
                        <label for="notify_email" class="ml-2 text-gray-700">Email Notifications</label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" id="notify_sms" name="notify_sms" @if(setting('notify_sms', true)) checked @endif class="w-4 h-4 text-primary-600 rounded focus:ring-2 focus:ring-primary-500">
                        <label for="notify_sms" class="ml-2 text-gray-700">SMS Notifications</label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" id="notify_slack" name="notify_slack" @if(setting('notify_slack', false)) checked @endif class="w-4 h-4 text-primary-600 rounded focus:ring-2 focus:ring-primary-500">
                        <label for="notify_slack" class="ml-2 text-gray-700">Slack Integration</label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" id="notify_dashboard" name="notify_dashboard" @if(setting('notify_dashboard', true)) checked @endif class="w-4 h-4 text-primary-600 rounded focus:ring-2 focus:ring-primary-500">
                        <label for="notify_dashboard" class="ml-2 text-gray-700">In-App Notifications</label>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                    Save Settings
                </button>
            </div>
        </form>
    </div>

    <!-- Email Settings -->
    <div id="email-tab" class="tab-content hidden">
        <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
            @csrf
            <input type="hidden" name="section" value="email">

            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Email Configuration</h3>
                <div class="space-y-4">
                    <div>
                        <label for="mail_driver" class="block text-sm font-medium text-gray-700 mb-2">Mail Driver</label>
                        <select id="mail_driver" name="mail_driver" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                            <option value="smtp" @selected(config('mail.driver') == 'smtp')>SMTP</option>
                            <option value="mailgun" @selected(config('mail.driver') == 'mailgun')>Mailgun</option>
                            <option value="sendgrid" @selected(config('mail.driver') == 'sendgrid')>SendGrid</option>
                        </select>
                    </div>

                    <div>
                        <label for="mail_from_address" class="block text-sm font-medium text-gray-700 mb-2">From Email Address</label>
                        <input type="email" id="mail_from_address" name="mail_from_address" value="{{ config('mail.from.address') }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>

                    <div>
                        <label for="mail_from_name" class="block text-sm font-medium text-gray-700 mb-2">From Name</label>
                        <input type="text" id="mail_from_name" name="mail_from_name" value="{{ config('mail.from.name') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">SMTP Settings</label>
                        <p class="text-xs text-gray-500 mb-4">These are configured in your .env file</p>
                        <div class="bg-gray-50 rounded p-4 text-sm font-mono text-gray-700 space-y-1">
                            <p>MAIL_HOST={{ config('mail.mailers.smtp.host') }}</p>
                            <p>MAIL_PORT={{ config('mail.mailers.smtp.port') }}</p>
                            <p>MAIL_ENCRYPTION={{ config('mail.mailers.smtp.encryption') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                    Save Settings
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function switchTab(tab) {
        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(t => t.classList.add('hidden'));
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active', 'border-primary-600', 'text-primary-600');
            btn.classList.add('border-transparent', 'text-gray-600');
        });

        // Show selected tab
        document.getElementById(`${tab}-tab`).classList.remove('hidden');
        event.target.classList.add('active', 'border-primary-600', 'text-primary-600');
        event.target.classList.remove('border-transparent', 'text-gray-600');
    }
</script>
@endsection

