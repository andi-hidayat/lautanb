<div class="overflow-x-auto">
    <table class="w-full min-w-max divide-y divide-gray-200 text-sm dark:divide-white/10">
        <thead class="bg-gray-50 dark:bg-white/5">
            <tr>
                <th scope="col" class="px-4 py-3 text-start font-semibold text-gray-950 dark:text-white">
                    Technical Specification
                </th>
                @foreach ($record->products ?? [] as $product)
                    <th scope="col" class="px-4 py-3 text-start font-semibold text-gray-950 dark:text-white">
                        <span class="block">{{ $product['product_name'] ?? 'Product' }}</span>
                        @if (filled($product['supplier_name'] ?? null))
                            <span class="mt-1 block font-normal text-gray-500 dark:text-gray-400">
                                {{ $product['supplier_name'] }}
                            </span>
                        @endif
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white dark:divide-white/10 dark:bg-gray-950">
            @forelse ($record->comparisonMatrix() as $row)
                <tr>
                    <th scope="row" class="px-4 py-3 text-start font-medium text-gray-950 dark:text-white">
                        {{ $row['name'] }}
                    </th>
                    @foreach ($record->products ?? [] as $index => $product)
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                            {{ $row['values'][$index] ?? '-' }}
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td
                        colspan="{{ count($record->products ?? []) + 1 }}"
                        class="px-4 py-3 text-center text-gray-500 dark:text-gray-400"
                    >
                        No technical specifications have been added.
                    </td>
                </tr>
            @endforelse
            <tr>
                <th scope="row" class="px-4 py-3 text-start font-medium text-gray-950 dark:text-white">
                    Product Attachments
                </th>
                @foreach ($record->products ?? [] as $product)
                    <td class="px-4 py-3">
                        <div class="flex flex-wrap gap-2">
                            @php
                                $attachments = $product['attachment'] ?? [];
                                $attachments = is_array($attachments) ? $attachments : [$attachments];
                            @endphp

                            @forelse ($attachments as $attachment)
                                @if (is_string($attachment) && filled($attachment))
                                    @php
                                        $storage = \Illuminate\Support\Facades\Storage::disk('public');
                                        $attachmentUrl = $storage->exists($attachment)
                                            ? $storage->url($attachment)
                                            : null;
                                        $extension = strtolower(pathinfo($attachment, PATHINFO_EXTENSION));
                                        $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);

                                        if ($attachmentUrl === null) {
                                            $storage = \Illuminate\Support\Facades\Storage::disk('local');
                                            $attachmentUrl = $storage->exists($attachment)
                                                ? $storage->temporaryUrl($attachment, now()->addHour())
                                                : null;
                                        }
                                    @endphp

                                    @if ($attachmentUrl !== null)
                                        @if ($isImage)
                                            <div
                                                x-data
                                            >
                                                <button
                                                    type="button"
                                                    @click="$refs.imagePreview.showModal()"
                                                    class="block cursor-zoom-in rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-600"
                                                    aria-label="View {{ $product['product_name'] ?? 'Product' }} picture"
                                                >
                                                    <img
                                                        src="{{ $attachmentUrl }}"
                                                        alt="{{ $product['product_name'] ?? 'Product' }} picture"
                                                        class="h-12 w-12 rounded-lg object-cover"
                                                    >
                                                </button>

                                                <dialog
                                                    x-ref="imagePreview"
                                                    @click="$event.target === $el && $el.close()"
                                                    class="fixed inset-0 m-auto max-h-[90vh] max-w-[90vw] overflow-visible bg-transparent p-0 backdrop:bg-gray-950/80"
                                                    aria-label="{{ $product['product_name'] ?? 'Product' }} picture preview"
                                                >
                                                    <div class="flex max-h-full max-w-full flex-col items-end gap-2">
                                                        <button
                                                            type="button"
                                                            @click="$refs.imagePreview.close()"
                                                            class="rounded-md bg-white px-3 py-1.5 text-sm font-medium text-gray-950 shadow"
                                                        >
                                                            Close
                                                        </button>
                                                        <img
                                                            src="{{ $attachmentUrl }}"
                                                            alt="{{ $product['product_name'] ?? 'Product' }} picture"
                                                            class="max-h-[85vh] max-w-[90vw] rounded-lg object-contain"
                                                        >
                                                    </div>
                                                </dialog>
                                            </div>
                                        @else
                                            <a
                                                href="{{ $attachmentUrl }}"
                                                download
                                                class="inline-flex items-center rounded-md border border-gray-300 px-2.5 py-1.5 text-sm text-primary-600 hover:bg-gray-50 dark:border-white/10 dark:hover:bg-white/5"
                                            >
                                                {{ basename($attachment) }}
                                            </a>
                                        @endif
                                    @endif
                                @endif
                            @empty
                                <span class="text-gray-500 dark:text-gray-400">-</span>
                            @endforelse
                        </div>
                    </td>
                @endforeach
            </tr>
        </tbody>
    </table>
</div>
