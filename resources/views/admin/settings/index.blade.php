@extends('layouts.app')

@section('content')
<div class="bg-white rounded-lg shadow-sm p-6">
    <div class="border-b border-gray-200 pb-5 mb-5">
        <h3 class="text-lg font-medium leading-6 text-gray-900">System Settings</h3>
        <p class="mt-2 text-sm text-gray-500">Manage general system configuration.</p>
    </div>

    <form>
        <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
            <div class="sm:col-span-4">
                <label for="company_name" class="block text-sm font-medium text-gray-700">Company / Organization Name</label>
                <div class="mt-1">
                    <input type="text" name="company_name" id="company_name" autocomplete="organization" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" value="Kenya School Procurement System">
                </div>
            </div>

            <div class="sm:col-span-4">
                <label for="contact_email" class="block text-sm font-medium text-gray-700">Contact Email</label>
                <div class="mt-1">
                    <input type="email" name="contact_email" id="contact_email" autocomplete="email" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" value="admin@procurement.local">
                </div>
            </div>

            <div class="sm:col-span-6">
                <label for="about" class="block text-sm font-medium text-gray-700">System Announcement</label>
                <div class="mt-1">
                    <textarea id="about" name="about" rows="3" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"></textarea>
                </div>
                <p class="mt-2 text-sm text-gray-500">This message will be displayed on the dashboard if set.</p>
            </div>
        </div>

        <div class="pt-5">
            <div class="flex justify-end">
                <button type="button" class="rounded-md border border-gray-300 bg-white py-2 px-4 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">Cancel</button>
                <button type="submit" class="ml-3 inline-flex justify-center rounded-md border border-transparent bg-primary-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">Save</button>
            </div>
        </div>
    </form>
</div>
@endsection
