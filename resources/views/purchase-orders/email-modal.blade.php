<div id="emailModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="emailModalTitle" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 py-12 text-center">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

        <div class="relative bg-white rounded-lg text-left shadow-xl transform transition-all sm:max-w-lg sm:w-full">
            <div class="bg-white px-6 pt-6 pb-4 rounded-t-lg">
                <div class="flex items-start justify-between">
                    <h3 class="text-lg font-semibold text-gray-900" id="emailModalTitle">
                        Send PO to Supplier
                    </h3>
                    <button type="button"
                            onclick="document.getElementById('emailModal').remove()"
                            class="text-gray-400 hover:text-gray-500">
                        <span class="sr-only">Close</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <form action="{{ route('purchase-orders.email', $purchaseOrder) }}" method="POST" class="px-6 pb-6">
                @csrf

                <div class="space-y-4">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">
                            Recipient Email <span class="text-red-500">*</span>
                        </label>
                        <input type="email" name="email" id="email"
                               value="{{ $purchaseOrder->supplier?->email }}"
                               required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    </div>

                    <div>
                        <label for="subject" class="block text-sm font-medium text-gray-700">
                            Subject <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="subject" id="subject"
                               value="Purchase Order {{ $purchaseOrder->po_number }} from {{ config('app.name') }}"
                               required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    </div>

                    <div>
                        <label for="message" class="block text-sm font-medium text-gray-700">
                            Message <span class="text-red-500">*</span>
                        </label>
                        <textarea name="message" id="message" rows="5" required
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">Dear {{ $purchaseOrder->supplier?->name ?? 'Supplier' }},

Please find attached Purchase Order {{ $purchaseOrder->po_number }} for your review and acknowledgment.

Kindly confirm receipt and your ability to deliver by {{ $purchaseOrder->expected_delivery_date?->format('d M Y') ?? 'the agreed date' }}.

Regards,
{{ config('app.name') }}</textarea>
                    </div>

                    <p class="text-xs text-gray-500">
                        The PO (PDF) will be attached automatically to this email.
                    </p>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button"
                            onclick="document.getElementById('emailModal').remove()"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700">
                        Send Email
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
