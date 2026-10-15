<x-app-layout>
    <h1 class="text-xl font-semibold mb-1">Help & support</h1>
    <p class="text-sm text-slate-500 mb-5">Contact us: {{ config('city.support_email') }} · {{ config('city.support_phone') }}</p>

    @forelse ($faqs as $category => $items)
        <h2 class="font-semibold mt-6 mb-2">{{ ucfirst($category) }}</h2>
        <div class="space-y-2">
            @foreach ($items as $faq)
                <details class="rounded-xl border border-slate-200 bg-white p-4">
                    <summary class="font-medium text-sm cursor-pointer">{{ $faq->question }}</summary>
                    <p class="text-sm text-slate-600 mt-2 whitespace-pre-line">{{ $faq->answer }}</p>
                </details>
            @endforeach
        </div>
    @empty
        <x-empty-state title="No FAQs published yet" />
    @endforelse
</x-app-layout>
