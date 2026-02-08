<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Kenya School Procurement System')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">
    <div class="min-h-screen bg-gradient-to-br from-primary-50 via-primary-50 to-primary-100">
        <!-- Background decoration -->
        <div class="absolute top-0 right-0 w-96 h-96 bg-primary-200 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob"></div>
        <div class="absolute bottom-0 left-0 w-96 h-96 bg-primary-200 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-2000"></div>

        <!-- Content -->
        <div class="relative min-h-screen flex flex-col items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
            <!-- Logo/Header -->
            <div class="text-center mb-8">
                <div class="mb-4">
                    <img class="h-24 w-auto mx-auto" src="/images/st_c_logo.png" alt="St C Procurement">
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Kenya School Procurement</h1>
                <p class="text-gray-600">Professional Procurement Management System</p>
            </div>

            <!-- Form Container -->
            <div class="w-full max-w-md">
                <div class="bg-white rounded-2xl shadow-xl p-8">
                    {{ $slot }}
                </div>

                <!-- Help text -->
                <p class="mt-6 text-center text-sm text-gray-600">
                    @yield('footer')
                </p>
            </div>

            <!-- Footer info -->
            <div class="mt-12 text-center text-sm text-gray-600">
                <p>Â© {{ date('Y') }} Kenya School Procurement System. All rights reserved.</p>
                <p class="mt-2">For support, contact: <a href="mailto:support@procurement.local" class="text-primary-600 hover:text-primary-700 font-medium">support@procurement.local</a></p>
            </div>
        </div>
    </div>

    <style>
        @keyframes blob {
            0%, 100% {
                transform: translate(0, 0) scale(1);
            }
            33% {
                transform: translate(30px, -50px) scale(1.1);
            }
            66% {
                transform: translate(-20px, 20px) scale(0.9);
            }
        }

        .animate-blob {
            animation: blob 7s infinite;
        }

        .animation-delay-2000 {
            animation-delay: 2s;
        }
    </style>
</body>
</html>

