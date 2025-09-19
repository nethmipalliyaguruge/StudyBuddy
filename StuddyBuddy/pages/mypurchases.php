<?php 
require_once __DIR__."/../config/config.php";
require_once __DIR__."/../config/helpers.php";
require_login();

$title = "My Purchases - StudyBuddy APIIT";
include 'header.php'; ?>
<!-- Top summary cards -->
  <section class="bg-slate-50">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-6">
      <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
        <!-- Total Orders -->
        <div class="card rounded-xl border border-border bg-gradient-to-br from-blue-50 to-white p-5">
          <div class="flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-blue-600/10 text-blue-600 flex items-center justify-center">
              <i class="fa-solid fa-cart-shopping"></i>
            </div>
            <div>
              <p class="text-slate-500 text-sm">Total Orders</p>
              <p class="text-2xl font-bold">5</p>
            </div>
          </div>
        </div>

        <!-- Total Spent -->
        <div class="card rounded-xl border border-border bg-gradient-to-br from-emerald-50 to-white p-5">
          <div class="flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-emerald-600/10 text-emerald-700 flex items-center justify-center">
              <i class="fa-solid fa-dollar-sign"></i>
            </div>
            <div>
              <p class="text-slate-500 text-sm">Total Spent</p>
              <p class="text-2xl font-bold">LKR 10,800</p>
            </div>
          </div>
        </div>

        <!-- Completed Orders -->
        <div class="card rounded-xl border border-border bg-gradient-to-br from-purple-50 to-white p-5">
          <div class="flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-purple-600/10 text-purple-700 flex items-center justify-center">
              <i class="fa-regular fa-clipboard"></i>
            </div>
            <div>
              <p class="text-slate-500 text-sm">Completed Orders</p>
              <p class="text-2xl font-bold">4</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Order History -->
  <main class="max-w-6xl mx-auto px-4 sm:px-6 pb-16">
    <div class="mb-4">
      <h2 class="text-xl sm:text-2xl font-extrabold">Order History</h2>
      <p class="text-slate-600">View and download your purchased study materials</p>
    </div>

    <div class="rounded-2xl border border-border overflow-hidden shadow-sm">
      <div class="overflow-x-auto">
        <table class="min-w-full">
          <thead class="bg-slate-50 text-slate-600">
            <tr class="text-left text-xs font-semibold uppercase tracking-wide">
              <th class="px-6 py-3">Order ID</th>
              <th class="px-6 py-3">Material</th>
              <th class="px-6 py-3">Seller</th>
              <th class="px-6 py-3">Date</th>
              <th class="px-6 py-3">Amount</th>
              <th class="px-6 py-3">Status</th>
              <th class="px-6 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-border bg-white text-sm">
            <!-- Row 1 -->
            <tr>
              <td class="px-6 py-4">
                <a href="#" class="text-primary font-medium hover:underline">ORD-001</a>
              </td>
              <td class="px-6 py-4">Python Basics</td>
              <td class="px-6 py-4">John Doe</td>
              <td class="px-6 py-4">2025-08-15</td>
              <td class="px-6 py-4">LKR 500.00</td>
              <td class="px-6 py-4">
                <span class="inline-flex items-center rounded-full bg-primary text-white px-2.5 py-1 text-xs font-semibold">Completed</span>
              </td>
              <td class="px-6 py-4 text-right">
                <button class="inline-flex items-center gap-2 rounded-md border border-border px-3 py-1.5 hover:bg-slate-50">
                  <i class="fa-solid fa-download"></i> Download
                </button>
              </td>
            </tr>

            <!-- Row 2 -->
            <tr>
              <td class="px-6 py-4"><a href="#" class="text-primary font-medium hover:underline">ORD-002</a></td>
              <td class="px-6 py-4">Calculus I - Complete Notes</td>
              <td class="px-6 py-4">Jane Smith</td>
              <td class="px-6 py-4">2025-08-10</td>
              <td class="px-6 py-4">LKR 1,800.00</td>
              <td class="px-6 py-4">
                <span class="inline-flex items-center rounded-full bg-primary text-white px-2.5 py-1 text-xs font-semibold">Completed</span>
              </td>
              <td class="px-6 py-4 text-right">
                <button class="inline-flex items-center gap-2 rounded-md border border-border px-3 py-1.5 hover:bg-slate-50">
                  <i class="fa-solid fa-download"></i> Download
                </button>
              </td>
            </tr>

            <!-- Row 3 -->
            <tr>
              <td class="px-6 py-4"><a href="#" class="text-primary font-medium hover:underline">ORD-003</a></td>
              <td class="px-6 py-4">Database Design Fundamentals</td>
              <td class="px-6 py-4">Mike Johnson</td>
              <td class="px-6 py-4">2025-07-08</td>
              <td class="px-6 py-4">LKR 3,200.00</td>
              <td class="px-6 py-4">
                <span class="inline-flex items-center rounded-full bg-amber-100 text-amber-800 px-2.5 py-1 text-xs font-semibold ring-1 ring-amber-200">Processing</span>
              </td>
              <td class="px-6 py-4 text-right">
                <button class="inline-flex items-center gap-2 rounded-md border border-border px-3 py-1.5 text-slate-400 cursor-not-allowed" disabled>
                  <i class="fa-solid fa-download"></i> Download
                </button>
              </td>
            </tr>

            <!-- Row 4 -->
            <tr>
              <td class="px-6 py-4"><a href="#" class="text-primary font-medium hover:underline">ORD-004</a></td>
              <td class="px-6 py-4">Ethical Hacking - Semester 1</td>
              <td class="px-6 py-4">Sarah Wilson</td>
              <td class="px-6 py-4">2025-02-05</td>
              <td class="px-6 py-4">LKR 2,000.00</td>
              <td class="px-6 py-4">
                <span class="inline-flex items-center rounded-full bg-primary text-white px-2.5 py-1 text-xs font-semibold">Completed</span>
              </td>
              <td class="px-6 py-4 text-right">
                <button class="inline-flex items-center gap-2 rounded-md border border-border px-3 py-1.5 hover:bg-slate-50">
                  <i class="fa-solid fa-download"></i> Download
                </button>
              </td>
            </tr>

            <!-- Row 5 -->
            <tr>
              <td class="px-6 py-4"><a href="#" class="text-primary font-medium hover:underline">ORD-005</a></td>
              <td class="px-6 py-4">Software Engineering Principles</td>
              <td class="px-6 py-4">David Brown</td>
              <td class="px-6 py-4">2024-12-01</td>
              <td class="px-6 py-4">LKR 2,800.00</td>
              <td class="px-6 py-4">
                <span class="inline-flex items-center rounded-full bg-primary text-white px-2.5 py-1 text-xs font-semibold">Completed</span>
              </td>
              <td class="px-6 py-4 text-right">
                <button class="inline-flex items-center gap-2 rounded-md border border-border px-3 py-1.5 hover:bg-slate-50">
                  <i class="fa-solid fa-download"></i> Download
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    </main>
<?php include 'footer.php'; ?>
