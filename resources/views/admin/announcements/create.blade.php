<x-admin-layout>
    <h1 class="text-xl font-semibold mb-5">New announcement</h1>
    <x-admin.announcement-form :categories="$categories" :action="route('admin.announcements.store')" />
</x-admin-layout>
