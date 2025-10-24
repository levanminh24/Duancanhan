{{-- resources/views/layouts/admin/thongke/sanpham.blade.php --}}
@extends('layouts.admin.index')
@section('content')
<div class="container-fluid">
    <!-- Thống kê tổng quan -->
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <iconify-icon icon="solar:cart-large-2-bold-duotone" class="fs-32 text-success avatar-title me-3"></iconify-icon>
                        <div>
                            <p class="text-muted mb-0">🧾 Tổng số sản phẩm</p>
                            <h3 class="text-dark mb-0">{{ $totalProducts ?? 0 }}</h3>
                            <small class="text-muted">Đếm tổng số sản phẩm hiện có trong hệ thống (kể cả còn hàng / hết hàng)</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <iconify-icon icon="solar:star-bold-duotone" class="fs-32 text-warning avatar-title me-3"></iconify-icon>
                        <div>
                            <p class="text-muted mb-0">📊 Sản phẩm bán chạy nhất</p>
                            <h3 class="text-dark mb-0">{{ $bestSellerName ?? '-' }}</h3>
                            <small class="text-muted">Top 5–10 sản phẩm có số lượng bán nhiều nhất</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <iconify-icon icon="solar:box-minimalistic-broken" class="fs-32 text-danger avatar-title me-3"></iconify-icon>
                        <div>
                            <p class="text-muted mb-0">📉 Sản phẩm bán chậm / tồn kho cao</p>
                            <h3 class="text-dark mb-0">{{ $slowSellingProducts->count() ?? 0 }}</h3>
                            <small class="text-muted">Các sản phẩm ít người mua hoặc còn hàng nhiều</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <iconify-icon icon="solar:box-minimalistic-broken" class="fs-32 text-secondary avatar-title me-3"></iconify-icon>
                        <div>
                            <p class="text-muted mb-0">📦 Tình trạng tồn kho</p>
                            <h3 class="text-dark mb-0">{{ $inventoryStatus->count() ?? 0 }}</h3>
                            <small class="text-muted">Số lượng còn lại trong kho (dựa trên trường soluong)</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Thống kê chi tiết -->
    <div class="row mt-4">
        <!-- Số lượng sản phẩm theo danh mục -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">🏷️ Số lượng sản phẩm theo danh mục</h5>
                    <p class="text-muted">Ví dụ: Điện thoại (20 sp), Laptop (15 sp), Phụ kiện (10 sp)...</p>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Danh mục</th>
                                    <th class="text-end">Số lượng</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($productsByCategory as $category)
                                <tr>
                                    <td>{{ $category->category_name }}</td>
                                    <td class="text-end">{{ $category->product_count }} sp</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted">Chưa có dữ liệu</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tổng doanh thu theo sản phẩm -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">💰 Tổng doanh thu theo sản phẩm</h5>
                    <p class="text-muted">Tổng tiền thu được từ từng sản phẩm đã bán (số lượng × giá)</p>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Sản phẩm</th>
                                    <th class="text-end">Doanh thu</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($revenueByProduct as $product)
                                <tr>
                                    <td>{{ Str::limit($product->product_name, 30) }}</td>
                                    <td class="text-end">{{ number_format($product->total_revenue) }}đ</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted">Chưa có dữ liệu</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sản phẩm bán chạy nhất -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">📊 Sản phẩm bán chạy nhất</h5>
                    <p class="text-muted">Top 5–10 sản phẩm có số lượng bán nhiều nhất</p>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Sản phẩm</th>
                                    <th class="text-end">Đã bán</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($bestSellingProducts as $product)
                                <tr>
                                    <td>{{ Str::limit($product->product_name, 30) }}</td>
                                    <td class="text-end">{{ $product->total_sold }} sp</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted">Chưa có dữ liệu</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sản phẩm bán chậm / tồn kho cao -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">📉 Sản phẩm bán chậm / tồn kho cao</h5>
                    <p class="text-muted">Các sản phẩm ít người mua hoặc còn hàng nhiều</p>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Sản phẩm</th>
                                    <th class="text-end">Đã bán</th>
                                    <th class="text-end">Tồn kho</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($slowSellingProducts as $product)
                                <tr>
                                    <td>{{ Str::limit($product['product_name'], 25) }}</td>
                                    <td class="text-end">{{ $product['total_sold'] }} sp</td>
                                    <td class="text-end">{{ $product['total_stock'] }} sp</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">Chưa có dữ liệu</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tình trạng tồn kho -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">📦 Tình trạng tồn kho</h5>
                    <p class="text-muted">Số lượng còn lại trong kho (dựa trên trường soluong)</p>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Sản phẩm</th>
                                    <th class="text-end">Số lượng tồn kho</th>
                                    <th class="text-center">Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($inventoryStatus->take(20) as $item)
                                <tr>
                                    <td>{{ Str::limit($item->product_name, 40) }}</td>
                                    <td class="text-end">{{ $item->quantity }} sp</td>
                                    <td class="text-center">
                                        @if($item->quantity == 0)
                                            <span class="badge bg-danger">Hết hàng</span>
                                        @elseif($item->quantity <= 5)
                                            <span class="badge bg-warning">Sắp hết</span>
                                        @elseif($item->quantity <= 20)
                                            <span class="badge bg-info">Còn ít</span>
                                        @else
                                            <span class="badge bg-success">Còn nhiều</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">Chưa có dữ liệu</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                        @if($inventoryStatus->count() > 20)
                        <div class="text-center mt-2">
                            <small class="text-muted">Hiển thị 20/{{ $inventoryStatus->count() }} sản phẩm</small>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
        
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="card-title mb-0">Top 5 sản phẩm bán chạy</h5>
                        <div class="dropdown">
                            <button class="btn btn-light btn-sm dropdown-toggle" type="button" id="barChartFilter" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bx bx-menu"></i> <span id="barChartFilterLabel">
                                    @if($barFilter === 'day') Theo ngày
                                    @elseif($barFilter === 'month') Theo tháng
                                    @else Theo năm @endif
                                </span>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="barChartFilter">
                                <li><a class="dropdown-item bar-filter-option" href="#" data-filter="day">Theo ngày</a></li>
                                <li><a class="dropdown-item bar-filter-option" href="#" data-filter="month">Theo tháng</a></li>
                                <li><a class="dropdown-item bar-filter-option" href="#" data-filter="year">Theo năm</a></li>
                            </ul>
                        </div>
                    </div>
                    <div id="bar-chart" style="height: 350px;"></div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Tỷ lệ sản phẩm còn hàng / hết hàng</h5>
                    <div id="pie-chart" style="height: 350px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    // Dropdown filter cho biểu đồ cột
    document.querySelectorAll('.bar-filter-option').forEach(function(item) {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            var filter = this.getAttribute('data-filter');
            var label = this.textContent;
            document.getElementById('barChartFilterLabel').textContent = label;
            // Reload lại trang với tham số filter (hoặc có thể dùng Ajax nếu muốn)
            var url = new URL(window.location.href);
            url.searchParams.set('bar_filter', filter);
            window.location.href = url.toString();
        });
    });

    // Biểu đồ cột - Top 5 sản phẩm bán chạy
    var barOptions = {
        chart: {
            type: 'bar',
            height: 350
        },
        series: [{
            name: 'Số lượng bán',
            data: @json($barData)
        }],
        xaxis: {
            categories: @json($barLabels)
        },
        colors: ['#1e84c4'],
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '50%'
            }
        }
    };
    var barChart = new ApexCharts(document.querySelector("#bar-chart"), barOptions);
    barChart.render();

    // Biểu đồ tròn - Tỷ lệ sản phẩm còn hàng/hết hàng
    var pieOptions = {
        chart: {
            type: 'pie',
            height: 350
        },
        series: [{{ $inStock }}, {{ $outStock }}],
        labels: ['Còn hàng', 'Hết hàng'],
        colors: ['#28a745', '#dc3545']
    };
    var pieChart = new ApexCharts(document.querySelector("#pie-chart"), pieOptions);
    pieChart.render();
</script>
@endsection 