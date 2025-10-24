<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\User;
use App\Models\Favorite;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StatisticController extends Controller
{
    public function sanpham(Request $request)
    {
        $barFilter = $request->get('bar_filter', 'year');
        
        // 🧾 Tổng số sản phẩm (kể cả còn hàng / hết hàng)
        $totalProducts = Product::count();
        
        // 🏷️ Số lượng sản phẩm theo danh mục
        $productsByCategory = Product::join('categories', 'products.categories_id', '=', 'categories.ID')
            ->select('categories.Name as category_name', DB::raw('COUNT(products.id) as product_count'))
            ->groupBy('categories.ID', 'categories.Name')
            ->orderByDesc('product_count')
            ->get();
        
        // 💰 Tổng doanh thu theo sản phẩm (số lượng × giá)
        $revenueByProduct = OrderDetail::join('product_variants', 'order_detail.product_variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->join('orders', 'order_detail.order_id', '=', 'orders.id')
            ->whereIn('orders.status', [5, 15]) // Chỉ tính đơn đã giao/đã giao thành công
            ->select('products.name as product_name', DB::raw('SUM(order_detail.quantity * order_detail.price) as total_revenue'))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();
        
        // 📊 Sản phẩm bán chạy nhất (Top 5-10)
        $bestSellingProducts = OrderDetail::join('product_variants', 'order_detail.product_variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->select('products.name as product_name', DB::raw('SUM(order_detail.quantity) as total_sold'))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_sold')
            ->limit(10)
            ->get();
        
        $bestSellerName = $bestSellingProducts->first() ? $bestSellingProducts->first()->product_name : null;
        
        // 📉 Sản phẩm bán chậm / tồn kho cao
        $slowSellingProducts = Product::with(['variants'])
            ->get()
            ->map(function($product) {
                $totalStock = $product->variants->sum('quantity');
                // Tính tổng số lượng đã bán từ order_details thông qua product_variants
                $variantIds = $product->variants->pluck('id');
                $totalSold = OrderDetail::whereIn('product_variant_id', $variantIds)
                    ->whereHas('order', function($q) {
                        $q->whereIn('status', [5, 15]); // Chỉ tính đơn đã giao/đã giao thành công
                    })
                    ->sum('quantity');
                
                return [
                    'product_name' => $product->name,
                    'total_stock' => $totalStock,
                    'total_sold' => $totalSold
                ];
            })
            ->filter(function($item) {
                return $item['total_sold'] < 5 || $item['total_stock'] > 50;
            })
            ->sortBy('total_sold')
            ->take(10);
        
        // 📦 Tình trạng tồn kho
        $inventoryStatus = ProductVariant::join('products', 'product_variants.product_id', '=', 'products.id')
            ->select('products.name as product_name', 'product_variants.quantity')
            ->orderBy('product_variants.quantity')
            ->get();
        
        // Sản phẩm sắp hết hàng (quantity <= 5)
        $lowStockCount = ProductVariant::where('quantity', '<=', 5)->where('quantity', '>', 0)->count();
        // Sản phẩm hết hàng
        $outOfStockCount = ProductVariant::where('quantity', 0)->count();
        // Sản phẩm chưa được mua lần nào
        $soldVariantIds = OrderDetail::pluck('product_variant_id')->unique();
        $neverSoldCount = ProductVariant::whereNotIn('id', $soldVariantIds)->count();
        // Sản phẩm đang giảm giá
        $discountCount = ProductVariant::whereColumn('price_sale', '<', 'price')->count();

        // Dữ liệu cho biểu đồ cột: Top 5 sản phẩm bán chạy theo filter
        $topProductsQuery = OrderDetail::select('product_variant_id', DB::raw('SUM(quantity) as total_sold'));
        if ($barFilter === 'day') {
            $topProductsQuery->whereDate('created_at', now()->toDateString());
        } elseif ($barFilter === 'month') {
            $topProductsQuery->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
        } else { // year
            $topProductsQuery->whereYear('created_at', now()->year);
        }
        $topProducts = $topProductsQuery->groupBy('product_variant_id')
            ->orderByDesc('total_sold')
            ->take(5)
            ->get();
        $barLabels = $topProducts->map(function($item){
            return optional(optional(ProductVariant::find($item->product_variant_id))->product)->name;
        });
        $barData = $topProducts->pluck('total_sold');

        // Dữ liệu cho biểu đồ tròn: Tỷ lệ sản phẩm còn hàng/hết hàng
        $stock = ProductVariant::select('product_id', DB::raw('SUM(quantity) as stock'))
            ->groupBy('product_id')
            ->get();
        $inStock = $stock->where('stock', '>', 0)->count();
        $outStock = $stock->where('stock', '=', 0)->count();

        return view('layouts.admin.thongke.sanpham', compact(
            'totalProducts', 'bestSellerName', 'lowStockCount', 'outOfStockCount', 'neverSoldCount', 'discountCount',
            'barLabels', 'barData', 'inStock', 'outStock', 'barFilter',
            'productsByCategory', 'revenueByProduct', 'bestSellingProducts', 'slowSellingProducts', 'inventoryStatus'
        ));
    }

    public function nguoidung()
    {
        $totalUsers = User::count();
        $newUsersToday = User::whereDate('created_at', Carbon::today())->count();
        $newUsersMonth = User::whereMonth('created_at', Carbon::now()->month)->whereYear('created_at', Carbon::now()->year)->count();
        // Người dùng mua hàng nhiều nhất
        $topBuyer = Order::select('user_id', DB::raw('COUNT(*) as order_count'))
            ->where('user_id', '>', 0)
            ->groupBy('user_id')
            ->orderByDesc('order_count')
            ->first();
        $topBuyerName = $topBuyer ? optional(User::find($topBuyer->user_id))->name : null;
        // Phân loại vai trò
        $adminCount = User::whereHas('role', function($q){ $q->where('name', 'admin'); })->count();
        $staffCount = User::whereHas('role', function($q){ $q->where('name', 'staff'); })->count();
        $customerCount = User::whereHas('role', function($q){ $q->where('name', 'customer'); })->count();
        return view('layouts.admin.thongke.nguoidung', compact(
            'totalUsers', 'newUsersToday', 'newUsersMonth', 'topBuyerName', 'adminCount', 'staffCount', 'customerCount'
        ));
    }

    public function yeuthich()
    {
        // Sản phẩm được thích nhiều nhất
        $mostLiked = Favorite::select('product_id', DB::raw('COUNT(*) as like_count'))
            ->groupBy('product_id')
            ->orderByDesc('like_count')
            ->first();
        $mostLikedProductName = $mostLiked ? optional(Product::find($mostLiked->product_id))->name : null;
        // Thống kê lượt thích theo từng sản phẩm
        $productStats = Product::withCount('favorites')->get()->map(function($product) {
            return [
                'name' => $product->name,
                'likes' => $product->favorites_count,
                'avg_rating' => '-', // Không có dữ liệu đánh giá
                'review_count' => 0, // Không có dữ liệu đánh giá
            ];
        });
        // Sản phẩm được đánh giá cao nhất: không có dữ liệu
        $topRatedProductName = '-';
        return view('layouts.admin.thongke.yeuthich', compact(
            'mostLikedProductName', 'topRatedProductName', 'productStats'
        ));
    }

    public function donhang(Request $request)
    {
        $filter = $request->get('filter', 'all'); // all, today, week, month, year
        $dateParam = $request->get('date'); // format: YYYY-MM-DD
        $monthParam = $request->get('month'); // format: YYYY-MM
        $yearParam = $request->get('year'); // format: YYYY

        // Base filtered orders query for all metrics
        $filteredOrders = Order::query();
        
        // Query tổng quan theo thời điểm tạo đơn (cho các chỉ số không doanh thu)
        $query = Order::query();
        // Chỉ tính doanh thu cho đơn hàng đã giao hàng (status = 5)
        $deliveredBase = Order::whereIn('status', [5, 15]);
        $deliveredOrdersFiltered = clone $deliveredBase;

        // Allow explicit date/month/year filters to override quick filters
        if (!empty($dateParam)) {
            $query->whereDate('created_at', $dateParam);
            $filteredOrders->whereDate('created_at', $dateParam);
            $start = \Carbon\Carbon::parse($dateParam)->startOfDay()->toDateTimeString();
            $end = \Carbon\Carbon::parse($dateParam)->endOfDay()->toDateTimeString();
            $deliveredOrdersFiltered->whereBetween(\DB::raw('COALESCE(delivered_at, updated_at)'), [$start, $end]);
        } elseif (!empty($monthParam)) {
            // monthParam expected as YYYY-MM
            [$y, $m] = explode('-', $monthParam);
            $query->whereYear('created_at', (int)$y)->whereMonth('created_at', (int)$m);
            $filteredOrders->whereYear('created_at', (int)$y)->whereMonth('created_at', (int)$m);
            $start = \Carbon\Carbon::create((int)$y, (int)$m, 1)->startOfMonth()->startOfDay()->toDateTimeString();
            $end = \Carbon\Carbon::create((int)$y, (int)$m, 1)->endOfMonth()->endOfDay()->toDateTimeString();
            $deliveredOrdersFiltered->whereBetween(\DB::raw('COALESCE(delivered_at, updated_at)'), [$start, $end]);
        } elseif (!empty($yearParam)) {
            $query->whereYear('created_at', (int)$yearParam);
            $filteredOrders->whereYear('created_at', (int)$yearParam);
            $start = \Carbon\Carbon::create((int)$yearParam, 1, 1)->startOfYear()->startOfDay()->toDateTimeString();
            $end = \Carbon\Carbon::create((int)$yearParam, 12, 31)->endOfYear()->endOfDay()->toDateTimeString();
            $deliveredOrdersFiltered->whereBetween(\DB::raw('COALESCE(delivered_at, updated_at)'), [$start, $end]);
        } else {
            // Apply quick time filter
            switch($filter) {
                case 'today':
                    $query->whereDate('created_at', today());
                    $filteredOrders->whereDate('created_at', today());
                    $start = now()->startOfDay()->toDateTimeString();
                    $end = now()->endOfDay()->toDateTimeString();
                    $deliveredOrdersFiltered->whereBetween(\DB::raw('COALESCE(delivered_at, updated_at)'), [$start, $end]);
                    break;
                case 'week':
                    $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                    $filteredOrders->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                    $start = now()->startOfWeek()->startOfDay()->toDateTimeString();
                    $end = now()->endOfWeek()->endOfDay()->toDateTimeString();
                    $deliveredOrdersFiltered->whereBetween(\DB::raw('COALESCE(delivered_at, updated_at)'), [$start, $end]);
                    break;
                case 'month':
                    $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
                    $filteredOrders->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
                    $start = now()->startOfMonth()->startOfDay()->toDateTimeString();
                    $end = now()->endOfMonth()->endOfDay()->toDateTimeString();
                    $deliveredOrdersFiltered->whereBetween(\DB::raw('COALESCE(delivered_at, updated_at)'), [$start, $end]);
                    break;
                case 'year':
                    $query->whereYear('created_at', now()->year);
                    $filteredOrders->whereYear('created_at', now()->year);
                    $start = now()->startOfYear()->startOfDay()->toDateTimeString();
                    $end = now()->endOfYear()->endOfDay()->toDateTimeString();
                    $deliveredOrdersFiltered->whereBetween(\DB::raw('COALESCE(delivered_at, updated_at)'), [$start, $end]);
                    break;
            }
        }

        // Define delivered status (5 = Đã giao hàng, 15 = Đã giao thành công)
        $deliveredStatuses = [5, 15];

        // Basic order statistics (respect filter)
        $totalOrders = (clone $filteredOrders)->count();
        $pendingOrders = (clone $filteredOrders)->where('status', 0)->count();
        $processedOrders = (clone $filteredOrders)->where('status', 1)->count();
        // Only delivered orders count for display purpose
        $deliveredCount = (clone $deliveredOrdersFiltered)->count();

        // Total products sold (đơn đã giao theo bộ lọc thời gian)
        $totalProductsSold = OrderDetail::whereIn('order_id', (clone $deliveredOrdersFiltered)->select('id'))
            ->sum('quantity');
        
        // Total revenue: chỉ tính đơn đã giao theo bộ lọc thời gian (theo updated_at)
        $totalRevenue = (clone $deliveredOrdersFiltered)
            ->sum('total_amount');
            
        // Revenue by payment method (đơn đã giao theo bộ lọc)
        $revenueByPayment = (clone $deliveredOrdersFiltered)
            ->select('payment_method', DB::raw('SUM(total_amount) as revenue'))
            ->groupBy('payment_method')
            ->get();
            
        // Revenue by order status (đơn đã giao theo bộ lọc)
        $revenueByStatus = (clone $deliveredOrdersFiltered)
            ->select('status', DB::raw('SUM(total_amount) as revenue'), DB::raw('COUNT(*) as order_count'))
            ->groupBy('status')
            ->get();
            
        // Average order value (đơn đã giao theo bộ lọc)
        $avgOrderValue = (clone $deliveredOrdersFiltered)
            ->avg('total_amount');
            
        // Top customers by revenue (đơn đã giao theo bộ lọc)
        $topCustomers = (clone $deliveredOrdersFiltered)
            ->where('user_id', '>', 0)
            ->select('user_id', DB::raw('SUM(total_amount) as total_spent'), DB::raw('COUNT(*) as order_count'))
            ->groupBy('user_id')
            ->orderByDesc('total_spent')
            ->limit(5)
            ->with('user')
            ->get();
            
        // Refund statistics (respect filter by order's created_at)
        $totalRefunds = \App\Models\RefundRequest::whereHas('order', function($q) use ($filter) {
            switch($filter) {
                case 'today':
                    $q->whereDate('created_at', today());
                    break;
                case 'week':
                    $q->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'month':
                    $q->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
                    break;
                case 'year':
                    $q->whereYear('created_at', now()->year);
                    break;
            }
        })->count();

        $refundAmount = \App\Models\RefundRequest::whereHas('order', function($q) use ($filter) {
            $q->where('status', 9); // Completed refunds
            switch($filter) {
                case 'today':
                    $q->whereDate('created_at', today());
                    break;
                case 'week':
                    $q->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'month':
                    $q->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
                    break;
                case 'year':
                    $q->whereYear('created_at', now()->year);
                    break;
            }
        })->with('order')->get()->sum('order.total_amount');
        
        // Monthly revenue chart data (tính theo tháng giao hàng - ưu tiên delivered_at, fallback updated_at)
        $chartYear = !empty($yearParam) ? (int)$yearParam : (!empty($monthParam) ? (int)explode('-', $monthParam)[0] : now()->year);
        $monthlyRevenue = Order::select(
                DB::raw('YEAR(COALESCE(delivered_at, updated_at)) as year'),
                DB::raw('MONTH(COALESCE(delivered_at, updated_at)) as month'),
                DB::raw('SUM(total_amount) as revenue')
            )
            ->whereIn('status', [5, 15])
            ->whereRaw('YEAR(COALESCE(delivered_at, updated_at)) = ?', [$chartYear])
            ->groupBy('year', 'month')
            ->orderBy('month')
            ->get();
            
        $monthlyLabels = $monthlyRevenue->map(function($item) {
            return Carbon::create($item->year, $item->month)->format('M Y');
        });
        $monthlyData = $monthlyRevenue->pluck('revenue');
        
        // Order status distribution - consistent: only delivered orders
        $statusDistribution = (clone $deliveredOrdersFiltered)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();
            
        $statusLabels = $statusDistribution->map(function($item) {
            $statusNames = [
                0 => 'Chờ xử lý',
                1 => 'Đã xác nhận', 
                2 => 'Đang xử lý',
                3 => 'Đã giao cho vận chuyển',
                4 => 'Đang vận chuyển',
                5 => 'Đã giao hàng',
                6 => 'Đã hủy',
                7 => 'Xác nhận yêu cầu hoàn hàng',
                8 => 'Hoàn hàng',
                9 => 'Hoàn tiền',
                10 => 'Không xác nhận yêu cầu hoàn hàng'
            ];
            return $statusNames[$item->status] ?? 'Không xác định';
        });
        $statusData = $statusDistribution->pluck('count');
        
        return view('layouts.admin.thongke.donhang', compact(
            'totalOrders', 'pendingOrders', 'processedOrders', 'totalProductsSold',
            'totalRevenue', 'revenueByPayment', 'revenueByStatus', 'avgOrderValue',
            'topCustomers', 'totalRefunds', 'refundAmount', 'filter',
            'monthlyLabels', 'monthlyData', 'statusLabels', 'statusData', 'deliveredCount',
            'dateParam', 'monthParam', 'yearParam', 'chartYear'
        ));
    }

    public function revenueOrders(Request $request)
    {
        $filter = $request->get('filter', 'all'); // all, today, week, month, year
        
        // Query orders that contribute to revenue: only delivered successfully
        $query = Order::with(['user', 'orderDetails.product'])
            ->whereIn('status', [5, 15]);
        
        // Apply time filter
        switch($filter) {
            case 'today':
                $start = now()->startOfDay()->toDateTimeString();
                $end = now()->endOfDay()->toDateTimeString();
                $query->whereBetween(DB::raw('COALESCE(delivered_at, updated_at)'), [$start, $end]);
                break;
            case 'week':
                $start = now()->startOfWeek()->startOfDay()->toDateTimeString();
                $end = now()->endOfWeek()->endOfDay()->toDateTimeString();
                $query->whereBetween(DB::raw('COALESCE(delivered_at, updated_at)'), [$start, $end]);
                break;
            case 'month':
                $start = now()->startOfMonth()->startOfDay()->toDateTimeString();
                $end = now()->endOfMonth()->endOfDay()->toDateTimeString();
                $query->whereBetween(DB::raw('COALESCE(delivered_at, updated_at)'), [$start, $end]);
                break;
            case 'year':
                $start = now()->startOfYear()->startOfDay()->toDateTimeString();
                $end = now()->endOfYear()->endOfDay()->toDateTimeString();
                $query->whereBetween(DB::raw('COALESCE(delivered_at, updated_at)'), [$start, $end]);
                break;
        }
        
        // Get orders with pagination
        $orders = $query->orderByRaw('COALESCE(delivered_at, updated_at) DESC')
            ->paginate(20);
        
        // Calculate total revenue for this filter
        $totalRevenue = (clone $query)->sum('total_amount');
        
        // Get filter options for display
        $filterOptions = [
            'all' => 'Tất cả',
            'today' => 'Hôm nay',
            'week' => 'Tuần này', 
            'month' => 'Tháng này',
            'year' => 'Năm nay'
        ];
        
        return view('layouts.admin.thongke.revenue-orders', compact(
            'orders', 'totalRevenue', 'filter', 'filterOptions'
        ));
    }
} 