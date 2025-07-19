<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\StockTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache; // أضف هذا السطر

class WarehouseController extends Controller
{
    // Helper function to map transaction types to Arabic names
    private function mapTransactionType($type)
    {
        $types = [
            'purchase' => 'شراء',
            'return' => 'ترجيع',
            'damage' => 'تلف',
            'fulfillment' => 'تلبية الأقسام'
        ];

        return $types[$type] ?? $type;
    }

    // Get summary data for the top cards (today's transactions)
    public function getSummary()
    {
        $today = Carbon::today()->toDateString();

        // استخدم selectRaw للتأكد من تجميع القيم بشكل صحيح
        $transactions = StockTransaction::whereDate('created_at', $today)
            ->selectRaw('transaction_type, SUM(COALESCE(quantity, 0)) as total')
            ->groupBy('transaction_type')
            ->get()
            ->keyBy('transaction_type'); // تحويل النتائج إلى مصفوفة ارتباطية

        // القيم الافتراضية مع تحديد المفاتيح
        $defaultData = [
            'purchase' => ['name' => 'شراء', 'value' => 0, 'icon' => 'shopping-cart'],
            'damage' => ['name' => 'تلف', 'value' => 0, 'icon' => 'trash'],
            'return' => ['name' => 'ترجيع', 'value' => 0, 'icon' => 'exchange-alt'],
            'fulfillment' => ['name' => 'تلبية الأقسام', 'value' => 0, 'icon' => 'hospital']
        ];

        // تعبئة القيم من قاعدة البيانات
        foreach ($transactions as $type => $transaction) {
            if (array_key_exists($type, $defaultData)) {
                $defaultData[$type]['value'] = (int) $transaction->total;
            }
        }

        return response()->json(array_values($defaultData));
    }

    // Get pie chart data (today's transactions)
    public function getPieData()
    {
        $today = Carbon::today();

        $transactions = StockTransaction::whereDate('created_at', $today)
            ->select('transaction_type', DB::raw('SUM(quantity) as total'))
            ->groupBy('transaction_type')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $this->mapTransactionType($item->transaction_type),
                    'value' => $item->total
                ];
            })
            ->toArray();

        // Ensure all types are represented even if zero
        $allTypes = [
            ['name' => 'شراء', 'value' => 0],
            ['name' => 'تلف', 'value' => 0],
            ['name' => 'ترجيع', 'value' => 0],
            ['name' => 'تلبية الأقسام', 'value' => 0]
        ];

        foreach ($transactions as $transaction) {
            foreach ($allTypes as &$type) {
                if ($type['name'] === $transaction['name']) {
                    $type['value'] = $transaction['value'];
                    break;
                }
            }
        }

        return response()->json($allTypes);
    }

    // Get line chart data (last 6 months)
    public function getLineData()
    {
        $sixMonthsAgo = Carbon::now()->subMonths(6)->startOfMonth();
        $now = Carbon::now()->endOfMonth();

        // Get purchase data
        $purchases = StockTransaction::where('transaction_type', 'purchase')
            ->whereBetween('created_at', [$sixMonthsAgo, $now])
            ->select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('YEAR(created_at) as year'),
                DB::raw('SUM(quantity) as total')
            )
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        // Get damage data
        $damages = StockTransaction::where('transaction_type', 'damage')
            ->whereBetween('created_at', [$sixMonthsAgo, $now])
            ->select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('YEAR(created_at) as year'),
                DB::raw('SUM(quantity) as total')
            )
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        // Generate all months in the range
        $months = [];
        $current = $sixMonthsAgo->copy();

        while ($current <= $now) {
            $months[] = [
                'name' => $current->translatedFormat('F'), // Month name in Arabic
                'month' => $current->month,
                'year' => $current->year,
                'شراء' => 0,
                'تلف' => 0
            ];
            $current->addMonth();
        }

        // Fill in purchase data
        foreach ($purchases as $purchase) {
            foreach ($months as &$month) {
                if ($month['month'] == $purchase->month && $month['year'] == $purchase->year) {
                    $month['شراء'] = $purchase->total;
                    break;
                }
            }
        }

        // Fill in damage data
        foreach ($damages as $damage) {
            foreach ($months as &$month) {
                if ($month['month'] == $damage->month && $month['year'] == $damage->year) {
                    $month['تلف'] = $damage->total;
                    break;
                }
            }
        }

        // Keep only the name and values for the response
        $result = array_map(function ($month) {
            return [
                'name' => $month['name'],
                'شراء' => $month['شراء'],
                'تلف' => $month['تلف']
            ];
        }, $months);

        return response()->json($result);
    }

    // Get details for a specific category

    public function getDetails($category)
    {
        $today = Carbon::today()->format('Y-m-d');
        $monthStart = Carbon::now()->startOfMonth();

        // خريطة تحويل الأسماء العربية إلى أنواع قاعدة البيانات
        $typeMap = [
            'شراء' => 'purchase',
            'تلف' => 'damage',
            'ترجيع' => 'return',
            'تلبية الأقسام' => 'fulfillment',
        ];

        $englishType = $typeMap[$category] ?? null;

        if (!$englishType) {
            return response()->json(['error' => 'Category not found'], 404);
        }

        // مفتاح التخزين المؤقت الفريد لكل نوع وتاريخ
        $cacheKey = "warehouse_details_{$englishType}_" . $today->format('Y-m-d');

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($englishType, $today, $monthStart) {
            // إحصائيات اليوم
            $todayTotal = StockTransaction::where('transaction_type', $englishType)
                ->whereDate('created_at', $today)
                ->sum('quantity');

            // إحصائيات الشهر
            $monthTotal = StockTransaction::where('transaction_type', $englishType)
                ->where('created_at', '>=', $monthStart)
                ->sum('quantity');

            // بناء التفاصيل حسب نوع العملية
            switch ($englishType) {
                case 'purchase':
                    $mostTransactedItem = StockTransaction::with(['item:id,name', 'supplier:id,name'])
                        ->where('transaction_type', 'purchase')
                        ->whereDate('created_at', $today)
                        ->select('item_id', 'supplier_id', DB::raw('SUM(quantity) as total'))
                        ->groupBy('item_id', 'supplier_id')
                        ->orderByDesc('total')
                        ->first();

                    $details = [
                        "تم شراء $todayTotal دواء اليوم",
                        $mostTransactedItem ?
                        "أكثر دواء تم شراؤه: {$mostTransactedItem->item->name} ({$mostTransactedItem->total} علبة)" :
                        "لا توجد مشتريات اليوم",
                        "إجمالي المشتريات هذا الشهر: $monthTotal دواء",
                        $mostTransactedItem?->supplier ?
                        "المورد الرئيسي: {$mostTransactedItem->supplier->name}" :
                        "لا يوجد بيانات المورد"
                    ];
                    break;

                case 'damage':
                    $mostDamagedItem = StockTransaction::with('item:id,name')
                        ->where('transaction_type', 'damage')
                        ->whereDate('created_at', $today)
                        ->select('item_id', DB::raw('SUM(quantity) as total'))
                        ->groupBy('item_id')
                        ->orderByDesc('total')
                        ->first();

                    $damageReasons = StockTransaction::where('transaction_type', 'damage')
                        ->whereDate('created_at', $today)
                        ->select('notes')
                        ->get()
                        ->map(function ($item) {
                            return strtolower($item->notes ?? '');
                        });

                    $expiredCount = $damageReasons->filter(fn($note) => str_contains($note, 'انتهاء') || str_contains($note, 'صلاحية'))->count();
                    $packagingCount = $damageReasons->filter(fn($note) => str_contains($note, 'تغليف') || str_contains($note, 'عبوة'))->count();
                    $totalReasons = $damageReasons->count();

                    $expiredPercent = $totalReasons > 0 ? round(($expiredCount / $totalReasons) * 100) : 0;
                    $packagingPercent = $totalReasons > 0 ? round(($packagingCount / $totalReasons) * 100) : 0;

                    $details = [
                        "تم تسجيل $todayTotal أدوية تالفة اليوم",
                        $mostDamagedItem ?
                        "أكثر دواء تالف: {$mostDamagedItem->item->name} ({$mostDamagedItem->total} علب)" :
                        "لا توجد أدوية تالفة اليوم",
                        "إجمالي التالف هذا الشهر: $monthTotal دواء",
                        "الأسباب الرئيسية: انتهاء صلاحية ($expiredPercent%)، تلف في التغليف ($packagingPercent%)"
                    ];
                    break;

                case 'return':
                    $mostReturnedItem = StockTransaction::with('item:id,name')
                        ->where('transaction_type', 'return')
                        ->whereDate('created_at', $today)
                        ->select('item_id', DB::raw('SUM(quantity) as total'))
                        ->groupBy('item_id')
                        ->orderByDesc('total')
                        ->first();

                    $returnReasons = StockTransaction::where('transaction_type', 'return')
                        ->whereDate('created_at', $today)
                        ->select('notes')
                        ->get()
                        ->map(function ($item) {
                            return strtolower($item->notes ?? '');
                        });

                    $expiredReturns = $returnReasons->filter(fn($note) => str_contains($note, 'انتهاء') || str_contains($note, 'صلاحية'))->count();
                    $totalReturns = $returnReasons->count();
                    $expiredPercent = $totalReturns > 0 ? round(($expiredReturns / $totalReturns) * 100) : 0;

                    $details = [
                        "تم ترجيع $todayTotal أدوية اليوم",
                        "سبب رئيسي للترجيع: انتهاء الصلاحية ($expiredPercent%)",
                        "إجمالي المرتجعات هذا الشهر: $monthTotal دواء",
                        $mostReturnedItem ?
                        "أكثر الأدوية مرتجعة: {$mostReturnedItem->item->name} ({$mostReturnedItem->total} علب)" :
                        "لا توجد مرتجعات اليوم"
                    ];
                    break;

                case 'fulfillment':
                    // عدد الطلبات اليومية
                    $requestsCount = StockTransaction::where('transaction_type', 'fulfillment')
                        ->whereDate('created_at', $today)
                        ->count();

                    // أكثر الأقسام طلباً
                    $mostRequestingDept = Department::select('id', 'name')
                        ->withCount([
                            'transactions' => function ($q) use ($today) {
                                $q->where('transaction_type', 'fulfillment')
                                    ->whereDate('created_at', $today);
                            }
                        ])
                        ->orderByDesc('transactions_count')
                        ->first();

                    // أكثر الأدوية طلباً
                    $mostRequestedItem = Item::select('id', 'name')
                        ->withSum([
                            'transactions' => function ($q) use ($today) {
                                $q->where('transaction_type', 'fulfillment')
                                    ->whereDate('created_at', $today);
                            }
                        ], 'quantity')
                        ->orderByDesc('transactions_sum_quantity')
                        ->first();

                    $details = [
                        "تم تلبية $requestsCount طلب اليوم",
                        $mostRequestingDept ?
                        "أكثر قسم طلب: {$mostRequestingDept->name} ({$mostRequestingDept->transactions_count} طلب)" :
                        "لا توجد طلبات اليوم",
                        "إجمالي الطلبات هذا الشهر: $monthTotal طلب",
                        $mostRequestedItem ?
                        "أكثر الأدوية طلباً: {$mostRequestedItem->name} ({$mostRequestedItem->transactions_sum_quantity} جرعة)" :
                        "لا توجد طلبات اليوم"
                    ];
                    break;

                default:
                    $details = ["لا توجد بيانات متاحة لهذا النوع"];
            }

            return $details;
        });
    }

}