@extends('index.admindashboard')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col-6">
                                <h3 class="card-title mb-0">{{ isset($isTrash) && $isTrash ? 'Thùng rác Categories' : 'Danh sách Categories' }}</h3>
                            </div>
                            <div class="col-6 text-end">
                                @if(isset($isTrash) && $isTrash)
                                    <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-list"></i> Danh sách
                                    </a>
                                @else
                                    <a href="{{ route('admin.categories.trash') }}" class="btn btn-warning btn-sm">
                                        <i class="fas fa-trash"></i> Thùng rác
                                    </a>
                                    <a href="{{ route('admin.categories.create') }}" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus"></i> Thêm mới
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tên</th>
                                    <th>Danh mục cha</th>
                                    <th>Trạng thái</th>
                                    <th>Hình ảnh</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                               @php
    $index = ($categories->currentPage() - 1) * $categories->perPage() + 1;
@endphp

                                @foreach ($categories as $category)
                                    <tr>
                                        <td>{{ $index++ }}</td>
                                        <td>{{ $category->Name }}</td>
                                        <td>
                                            @if ($category->parent)
                                                <span class="badge bg-info">{{ $category->parent->Name }}</span>
                                            @else
                                                <span class="badge bg-secondary">Danh mục gốc</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($category->Is_active)
                                                <span class="badge bg-success">Hiển thị</span>
                                            @else
                                                <span class="badge bg-danger">Ẩn</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($category->Image)
                                                @php
                                                    $img = $category->Image;
                                                    if (!str_starts_with($img, 'http')) {
                                                        $img = str_replace('public/', 'storage/', $img);
                                                        $img = asset($img);
                                                    }
                                                @endphp
                                                <img src="{{ str_starts_with($category->Image, 'http') ? $category->Image : $img }}" alt="{{ $category->Name }}" style="max-width: 50px; height: auto;">
                                            @else
                                                Không có ảnh
                                            @endif
                                        </td>
                                        <td>
                                            @if(isset($isTrash) && $isTrash)
                                                <form action="{{ route('admin.categories.restore', $category->ID) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Khôi phục danh mục này?')">
                                                        <i class="fas fa-undo"></i> Khôi phục
                                                    </button>
                                                </form>
                                                <form action="{{ route('admin.categories.force-delete', $category->ID) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Xóa vĩnh viễn? Hành động này không thể hoàn tác.')">
                                                        <i class="fas fa-times"></i> Xóa vĩnh viễn
                                                    </button>
                                                </form>
                                            @else
                                                <a href="{{ route('admin.categories.edit', $category->ID) }}" class="btn btn-info btn-sm">
                                                    <i class="fas fa-edit"></i> Sửa
                                                </a>
                                                <form action="{{ route('admin.categories.destroy', $category->ID) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc chắn muốn xóa?')">
                                                        <i class="fas fa-trash"></i> Xóa
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-center">
                            <nav aria-label="Page navigation">
                                {{ $categories->links('pagination::bootstrap-4') }}
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            .pagination {
                margin-bottom: 0;
            }

            .pagination .page-item .page-link {
                color: #6c757d;
                border-color: #dee2e6;
                padding: 0.5rem 0.75rem;
                margin: 0 2px;
                border-radius: 4px;
            }

            .pagination .page-item.active .page-link {
                background-color: #0d6efd;
                border-color: #0d6efd;
                color: #fff;
            }

            .pagination .page-item .page-link:hover {
                background-color: #e9ecef;
                color: #0d6efd;
            }

            .pagination .page-item.disabled .page-link {
                color: #6c757d;
                pointer-events: none;
                background-color: #fff;
                border-color: #dee2e6;
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            // Tự động ẩn thông báo sau 3 giây
            $(document).ready(function() {
                setTimeout(function() {
                    $('.alert').alert('close');
                }, 3000);
            });
        </script>
    @endpush
@endsection
