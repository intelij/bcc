<x-admin-layout>
    <h1 class="text-xl font-semibold mb-5">Edit announcement</h1>
    <x-admin.announcement-form :announcement="$announcement" :categories="$categories" :action="route('admin.announcements.update', $announcement)" method="PATCH" />
</x-admin-layout>
