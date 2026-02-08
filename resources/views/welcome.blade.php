<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kenya School Procurement System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full space-y-8 p-8 bg-white rounded-lg shadow-lg">
            <div class="text-center">
                <h1 class="text-4xl font-bold text-gray-900 mb-2">
                    Kenya School Procurement System
                </h1>
                <p class="text-gray-600 mb-8">
                    Streamlined procurement management for educational institutions
                </p>
                
                <div class="space-y-4">
                    <a href="{{ route('login') }}" 
                       class="block w-full bg-primary-600 hover:bg-primary-700 text-white font-bold py-3 px-4 rounded transition duration-150">
                        Login
                    </a>
                    
                    <a href="{{ route('register') }}" 
                       class="block w-full bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-3 px-4 rounded transition duration-150">
                        Register
                    </a>
                </div>
                
                <div class="mt-8 text-sm text-gray-500">
                    <p>System Status: <span class="text-green-600 font-semibold">Online</span></p>
                    <p class="mt-2">Database: <span class="text-green-600 font-semibold">Connected</span></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

