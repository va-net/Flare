<?php
Page::setTitle('Home - ' . Page::$pageData->va_name);
require_once __DIR__ . '/../includes/header.php';
?>
<div id="content" x-data="{ data: { events: null, pireps: null, news: null } }" x-init="inithome(data)">
    <div class="w-full bg-primary text-primary-text h-36 p-5 pt-7 shadow-lg">
        <h2 class="text-4xl font-bold text-center">
            Welcome, <?= escape(Page::$pageData->user->data()->name) ?>
        </h2>
    </div>
    <div class="md:flex space-y-3 md:space-y-0 w-full flex-row gap-5 -mt-14 mb-7 px-5">
        <div class="bg-white flex-auto border rounded shadow-lg min-h-20 p-4 w-full flex items-center">
            <div class="rounded-full bg-red-600 text-white w-12 h-12 mr-3 flex">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 m-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-bold">PIREPs Filed</h3>
                <h5 class="font-semibold" x-text="data.pireps?.length || '...'"></h5>
            </div>
        </div>
        <div class="bg-white flex-auto border rounded shadow-lg min-h-20 w-full flex items-center p-4">
            <div class="rounded-full bg-blue-600 text-white w-12 h-12 mr-3 flex">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 m-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-bold">Flight Time</h3>
                <h5 class="font-semibold"><?= escape(Time::secsToString(Page::$pageData->user->getFlightTime())) ?></h5>
            </div>
        </div>
        <div class="bg-white flex-auto border rounded shadow-lg min-h-20 w-full flex items-center p-4">
            <div class="rounded-full bg-green-500 text-white w-12 h-12 mr-3 flex">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 m-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-bold">Rank</h3>
                <h5 class="font-semibold">
                    <?= escape(Page::$pageData->user->rank()) ?>
                </h5>
            </div>
        </div>
        <div class="bg-white border rounded shadow-lg min-h-20 w-full flex-auto flex items-center p-4">
            <div class="rounded-full bg-yellow-500 text-white w-12 h-12 mr-3 flex">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 m-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-bold">Last PIREP</h3>
                <?php $pirep = Page::$pageData->user->recentPireps(null, 1); ?>
                <h5 class="font-semibold" x-html="data.pireps ? (data.pireps[0] ? data.pireps[0].date : 'No PIREPs') : '...'"></h5>
            </div>
        </div>
    </div>
    <!-- Information Grid -->
    <div class="md:grid md:grid-cols-12 md:gap-4 space-y-4 md:space-y-0 px-5 w-full">
        <!-- Recent PIREPs (Silver) or Upcoming Events (Gold) -->
        <div class="rounded-lg shadow-lg col-span-7 p-4 border border-gray-200 dark:border-transparent dark:bg-white dark:bg-opacity-5 dark:text-white">
            <?php if (Page::$pageData->is_gold) : ?>
                <h3 class="text-2xl font-bold mb-3">
                    Upcoming Events
                </h3>
                <table class="table-auto w-full mb-2">
                    <thead class="bg-primary text-primary-text text-left">
                        <tr>
                            <th class="px-3 py-2">Name</th>
                            <th class="px-3 py-2 hidden md:table-cell">
                                Date
                            </th>
                            <th class="px-3 py-2">Airport</th>
                        </tr>
                    </thead>
                    <tbody x-html="eventstable(data.events)"></tbody>
                </table>
                <small class="mb-3 block text-center">
                    Click on any event to view details.
                </small>
            <?php else : ?>
                <h3 class="text-2xl font-bold mb-3">
                    Recent PIREPs
                </h3>
                <table class="table-auto w-full mb-2">
                    <thead class="bg-primary text-primary-text text-left">
                        <tr>
                            <th class="px-3 py-2">Route</th>
                            <th class="px-3 py-2 hidden md:table-cell">
                                Aircraft
                            </th>
                            <th class="px-3 py-2">Status</th>
                        </tr>
                    </thead>
                    <tbody x-html="pirepstable(data.pireps)"></tbody>
                </table>
            <?php endif; ?>
        </div>
        <!-- Route Search -->
        <div class="rounded-lg shadow-lg col-span-5 p-4 border border-gray-200 dark:border-transparent dark:bg-white dark:bg-opacity-5 dark:text-white">
            <h3 class="text-2xl font-bold mb-3">
                Find Flights
            </h3>
            <form action="/routes/search" method="get">
                <label class="block mb-2">
                    <span class="text-black dark:text-white">Departure ICAO</span>
                    <input required type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-transparent shadow-sm focus:shadow-md focus:ring-primary focus:ring-2 dark:bg-white dark:bg-opacity-10 dark:text-white" name="dep" placeholder="YMML" />
                </label>
                <label class="block mb-3">
                    <span class="text-black dark:text-white">Arrival ICAO</span>
                    <input required type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-transparent shadow-sm focus:shadow-md focus:ring-primary focus:ring-2 dark:bg-white dark:bg-opacity-10 dark:text-white" name="arr" placeholder="YSSY" />
                </label>
                <button type="submit" class="rounded-md bg-primary text-primary-text px-3 py-2 shadow-md focus:outline-none focus:ring-2 focus:ring-transparent focus:ring-offset-1 focus:ring-offset-black dark:focus:ring-offset-white">
                    Search
                </button>
            </form>
        </div>
        <!-- Statistics -->
        <div class="rounded-lg shadow-lg col-span-5 p-4 border border-gray-200 dark:border-transparent dark:bg-white dark:bg-opacity-5 dark:text-white">
            <h3 class="text-2xl font-bold mb-3">
                Your Statistics
            </h3>
            <ul x-html="pirepstats(data.pireps)"></ul>
        </div>
        <!-- News Feed -->
        <div class="rounded-lg shadow-lg col-span-7 p-4 border border-gray-200 dark:border-transparent dark:bg-white dark:bg-opacity-5 dark:text-white" x-html="newsfeed(data.news)">
            <h3 class="text-2xl font-bold mb-3">News Feed</h3>
            <p>Loading...</p>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>