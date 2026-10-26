<?php

namespace App\Http\Controllers;

use App\Models\LowStockNotification;
use App\Models\OutletOrder;
use App\Models\OutletReturn;
use App\Models\Product;
use App\Models\SystemNotification;
use App\Services\LowStockNotificationService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class LowStockController extends Controller
{
    private function canViewStock(Request $request): bool
    {
        return in_array($request->user()->role, ['Manager', 'Admin Prolog'], true);
    }

    private function visibleSystemNotifications(Request $request)
    {
        return SystemNotification::whereJsonContains('audience', $request->user()->role);
    }

    public function index(Request $request, LowStockNotificationService $notificationSync)
    {
        $notificationSync->sync();
        $canViewStock = $this->canViewStock($request);
        $search = trim($request->string('search')->toString());
        $category = $request->input('category', 'all');

        $stockQuery = LowStockNotification::with('product.category');
        if ($canViewStock) {
            if ($request->input('status') === 'unread' || $category === 'unread') {
                $stockQuery->whereNull('read_at');
            } elseif ($request->input('status') === 'active') {
                $stockQuery->whereNull('resolved_at');
            }
            if ($category === 'stock_critical') {
                $stockQuery->whereIn('severity', ['critical', 'out']);
            } elseif ($category === 'stock_low') {
                $stockQuery->where('severity', 'low');
            } elseif (in_array($category, ['purchase_order', 'distribution', 'return'], true)) {
                $stockQuery->whereRaw('1 = 0');
            }
            if ($request->filled('severity')) {
                $stockQuery->where('severity', $request->input('severity'));
            }
            if ($search !== '') {
                $stockQuery->whereHas('product', function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            }
            if ($request->filled('start_date')) {
                $stockQuery->whereDate('notified_at', '>=', $request->input('start_date'));
            }
            if ($request->filled('end_date')) {
                $stockQuery->whereDate('notified_at', '<=', $request->input('end_date'));
            }
        } else {
            $stockQuery->whereRaw('1 = 0');
        }

        $systemQuery = $this->visibleSystemNotifications($request);
        if ($category === 'unread' || $request->input('status') === 'unread') {
            $systemQuery->whereNull('read_at');
        }
        if (in_array($category, ['purchase_order', 'distribution', 'return'], true)) {
            $systemQuery->where('category', $category);
        } elseif (in_array($category, ['stock_low', 'stock_critical'], true)) {
            $systemQuery->whereRaw('1 = 0');
        }
        if ($search !== '') {
            $systemQuery->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }
        if ($request->filled('start_date')) {
            $systemQuery->whereDate('created_at', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $systemQuery->whereDate('created_at', '<=', $request->input('end_date'));
        }

        $stockItems = $stockQuery->latest('notified_at')->latest('id')->get()
            ->map(fn (LowStockNotification $notification) => $this->stockItem($notification));
        $activityItems = $systemQuery->latest()->get()
            ->map(fn (SystemNotification $notification) => $this->activityItem($notification));
        $allItems = $stockItems->concat($activityItems)->sortByDesc('timestamp')->values();

        $perPage = 15;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $notifications = new LengthAwarePaginator(
            $allItems->forPage($page, $perPage)->values(),
            $allItems->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $stockCount = $canViewStock
            ? LowStockNotification::whereNull('resolved_at')->count()
            : 0;
        $totalCount = ($canViewStock ? LowStockNotification::count() : 0)
            + $this->visibleSystemNotifications($request)->count();
        $unreadCount = ($canViewStock ? LowStockNotification::whereNull('read_at')->count() : 0)
            + $this->visibleSystemNotifications($request)->whereNull('read_at')->count();
        $criticalCount = $canViewStock
            ? LowStockNotification::whereNull('resolved_at')->whereIn('severity', ['critical', 'out'])->count()
            : 0;
        $pendingOrders = $canViewStock ? OutletOrder::where('status', 'requested')->count() : 0;
        $processingReturns = OutletReturn::whereIn('status', ['requested', 'approved', 'dispatched'])->count();
        $restockPriorities = $canViewStock
            ? Product::whereColumn('stock', '<=', 'minimum_stock')
                ->orderBy('stock')
                ->limit(5)
                ->get(['id', 'code', 'name', 'unit', 'stock', 'minimum_stock'])
            : collect();

        return view('notifications.low-stock', compact(
            'notifications',
            'category',
            'totalCount',
            'unreadCount',
            'stockCount',
            'criticalCount',
            'pendingOrders',
            'processingReturns',
            'restockPriorities',
            'canViewStock'
        ));
    }

    public function feed(Request $request, LowStockNotificationService $notificationSync)
    {
        $notificationSync->sync();
        $canViewStock = $this->canViewStock($request);
        $stockItems = $canViewStock
            ? LowStockNotification::with('product')
                ->latest('notified_at')
                ->latest('id')
                ->limit(20)
                ->get()
                ->map(fn (LowStockNotification $notification) => $this->stockItem($notification))
            : collect();
        $activityItems = $this->visibleSystemNotifications($request)
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (SystemNotification $notification) => $this->activityItem($notification));
        $items = $stockItems->concat($activityItems)
            ->sortByDesc('timestamp')
            ->take(10)
            ->values();
        $unreadCount = ($canViewStock ? LowStockNotification::whereNull('read_at')->count() : 0)
            + $this->visibleSystemNotifications($request)->whereNull('read_at')->count();

        return response()->json([
            'unread_count' => $unreadCount,
            'items' => $items->map(fn (array $item) => collect($item)->except('timestamp')->all()),
        ]);
    }

    public function markRead(Request $request, LowStockNotification $notification)
    {
        abort_unless($this->canViewStock($request), 403);
        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }

        return response()->noContent();
    }

    public function markActivityRead(Request $request, SystemNotification $notification)
    {
        abort_unless(in_array($request->user()->role, $notification->audience ?? [], true), 403);
        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }

        return response()->noContent();
    }

    public function markAllRead(Request $request)
    {
        if ($this->canViewStock($request)) {
            LowStockNotification::whereNull('read_at')->update(['read_at' => now()]);
        }
        $this->visibleSystemNotifications($request)->whereNull('read_at')->update(['read_at' => now()]);

        return response()->noContent();
    }

    private function stockItem(LowStockNotification $notification): array
    {
        $productName = $notification->product->name;
        $severity = $notification->severity;
        $title = match ($severity) {
            'out' => 'Stok '.$productName.' telah habis',
            'critical' => 'Stok '.$productName.' tersisa '.$notification->current_stock.' pcs',
            default => 'Stok '.$productName.' mulai menipis',
        };
        $message = match ($severity) {
            'out' => 'Stok '.$productName.' telah habis. Batas minimum '
                .$notification->minimum_stock.' '.$notification->product->unit.'.',
            'critical' => 'Stok saat ini '.$notification->current_stock.' '
                .$notification->product->unit.', batas minimum '
                .$notification->minimum_stock.' '.$notification->product->unit.'. Segera periksa persediaan.',
            default => 'Stok saat ini '.$notification->current_stock.' '
                .$notification->product->unit.', batas minimum '
                .$notification->minimum_stock.' '.$notification->product->unit.'.',
        };

        return [
            'id' => $notification->id,
            'type' => 'stock',
            'category' => in_array($severity, ['critical', 'out'], true) ? 'stock_critical' : 'stock_low',
            'category_label' => match ($severity) {
                'out' => 'Stok habis',
                'critical' => 'Stok kritis',
                default => 'Stok menipis',
            },
            'tone' => $severity,
            'icon' => $severity === 'low' ? '!' : '!!',
            'severity' => $severity,
            'product' => $productName,
            'current_stock' => $notification->current_stock,
            'minimum_stock' => $notification->minimum_stock,
            'active' => $notification->resolved_at === null,
            'title' => $title,
            'message' => $message,
            'reference_number' => $notification->product->code,
            'location' => 'Gudang',
            'created_at' => $notification->notified_at->locale('id')->translatedFormat('d M Y H:i'),
            'timestamp' => $notification->notified_at->timestamp,
            'read' => $notification->read_at !== null,
            'action_url' => route('stock-mutations.index', ['search' => $notification->product->code]),
            'action_label' => 'Cek stok',
            'read_url' => route('notifications.read', $notification),
            'read_method' => 'PATCH',
        ];
    }

    private function activityItem(SystemNotification $notification): array
    {
        $target = match ($notification->reference_type) {
            'order' => $notification->category === 'distribution'
                ? route('distributions.index', ['search' => $notification->reference_number])
                : route('warehouse.orders.index', ['search' => $notification->reference_number]),
            'receipt' => route('warehouse.receipts.index', ['search' => $notification->reference_number]),
            'return' => route('warehouse.returns.index', ['search' => $notification->reference_number]),
            default => route('notifications.low-stock'),
        };

        return [
            'id' => $notification->id,
            'type' => 'activity',
            'category' => $notification->category,
            'category_label' => match ($notification->category) {
                'purchase_order' => 'Pemesanan',
                'distribution' => 'Distribusi',
                'return' => 'Retur',
                default => 'Aktivitas',
            },
            'tone' => $notification->category === 'return' ? 'warning' : 'info',
            'icon' => match ($notification->category) {
                'purchase_order' => 'PO',
                'distribution' => '↗',
                'return' => '↩',
                default => 'i',
            },
            'title' => $notification->title,
            'message' => $notification->message,
            'reference_number' => $notification->reference_number,
            'location' => null,
            'created_at' => $notification->created_at->locale('id')->translatedFormat('d M Y H:i'),
            'timestamp' => $notification->created_at->timestamp,
            'read' => $notification->read_at !== null,
            'action_url' => $target,
            'action_label' => match ($notification->category) {
                'purchase_order' => 'Detail pesanan',
                'distribution' => 'Detail distribusi',
                'return' => 'Detail retur',
                default => 'Lihat detail',
            },
            'read_url' => route('notifications.activity.read', $notification),
            'read_method' => 'PATCH',
        ];
    }
}
