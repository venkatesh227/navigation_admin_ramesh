<nav class="fixed bottom-0 inset-x-0 bg-white border-t border-gray-200 flex justify-around py-3 pb-safe shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)] z-50">
    <a href="dashboard.php" class="flex flex-col items-center <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'text-blue-600' : 'text-gray-400 hover:text-blue-500' ?>">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
        <span class="text-xs font-medium mt-1">Home</span>
    </a>
    <a href="members.php" class="flex flex-col items-center <?= basename($_SERVER['PHP_SELF']) == 'members.php' ? 'text-blue-600' : 'text-gray-400 hover:text-blue-500' ?>">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
        <span class="text-xs font-medium mt-1">Members</span>
    </a>
    <a href="reports.php" class="flex flex-col items-center <?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'text-blue-600' : 'text-gray-400 hover:text-blue-500' ?>">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
        <span class="text-xs font-medium mt-1">Reports</span>
    </a>
    <a href="profile.php" class="flex flex-col items-center <?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'text-blue-600' : 'text-gray-400 hover:text-blue-500' ?>">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
        <span class="text-xs font-medium mt-1">Profile</span>
    </a>
</nav>
