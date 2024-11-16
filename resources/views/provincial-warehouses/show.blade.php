@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Cột trái - Thông tin kho -->
        <div class="col-md-7">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Thông tin Kho Tổng</h4>
                        <div>
                            @can('update', $provincialWarehouse)
                            <a href="{{ route('provincial-warehouses.edit', $provincialWarehouse) }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-edit"></i> Chỉnh sửa
                            </a>
                            @endcan
                            <a href="{{ route('provincial-warehouses.index') }}" class="btn btn-secondary btn-sm">
                                <i class="fas fa-arrow-left"></i> Quay lại
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Thông tin cơ bản -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <tr>
                                        <th class="bg-light" style="width: 150px;">Tên kho:</th>
                                        <td>{{ $provincialWarehouse->name }}</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light">Địa chỉ:</th>
                                        <td>{{ $provincialWarehouse->address }}</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light">Quận/Huyện:</th>
                                        <td>{{ $provincialWarehouse->district }}</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light">Tỉnh/Thành phố:</th>
                                        <td>{{ $provincialWarehouse->province }}</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light">Tọa độ:</th>
                                        <td id="coordinates">Đang tải...</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Thống kê -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h6 class="card-title">Tổng nhân viên</h6>
                                    <h2 class="mb-0">{{ $provincialWarehouse->users->count() }}</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h6 class="card-title">Quản lý kho</h6>
                                    <h2 class="mb-0">{{ $staffCounts['warehouse_manager'] }}</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h6 class="card-title">Nhân viên phân phối nội thành</h6> 
                                    <h2 class="mb-0">{{ $staffCounts['warehouse_local_distributor'] }}</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h6 class="card-title">Nhân viên phân phối ngoại thành</h6>
                                    <h2 class="mb-0">{{ $staffCounts['warehouse_remote_distributor'] }}</h2>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bản đồ -->
                    <div class="row">
                        <div class="col-md-12">
                            <div id="map" style="width: 100%; height: 400px; border-radius: 4px;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cột phải - Quản lý nhân viên -->
        <div class="col-md-5">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <!-- Form phân công nhân viên -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user-plus"></i> Phân công nhân viên mới</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('provincial-warehouses.staff.assign_warehouse', ':userId') }}" 
                          method="POST" 
                          id="assignStaffForm">
                        @csrf
                        <input type="hidden" name="provincial_warehouse_id" value="{{ $provincialWarehouse->id }}">
                        
                        <div class="form-group">
                            <label for="userSearch">
                                <i class="fas fa-search"></i> Tìm kiếm nhân viên:
                            </label>
                            <select class="form-control" id="userSearch" name="user_id" style="width: 100%" required>
                                <option value="">Tìm theo tên hoặc email</option>
                            </select>
                            @error('user_id')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="role">
                                <i class="fas fa-user-tag"></i> Vai trò:
                            </label>
                            <select name="role" class="form-control" required>
                                <option value="warehouse_staff">Nhân viên kho</option>
                                <option value="warehouse_manager">Quản lý kho</option>
                                <option value="warehouse_local_distributor">Nhân viên phân phối nội thành</option>
                                <option value="warehouse_remote_distributor">Nhân viên phân phối ngoại thành</option>
                            </select>
                            @error('role')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Phân công nhân viên
                        </button>
                    </form>
                </div>
            </div>

          <!-- Danh sách nhân viên -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-users"></i> Danh sách nhân viên</h5>
                </div>
                <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @forelse($provincialWarehouse->users as $warehouseUser)
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-top">
                            <div>
                                <h6 class="mb-1">{{ $warehouseUser->user->name }}</h6>
                                <small class="text-muted d-block">{{ $warehouseUser->user->email }}</small>
                                @if($warehouseUser->user->roles)
                                    @foreach($warehouseUser->user->roles as $role)
                                        @php
                                            $badgeColor = match($role->name) {
                                                'warehouse_manager' => 'success',
                                                'warehouse_staff' => 'primary',
                                                'warehouse_local_distributor' => 'info',
                                                'warehouse_remote_distributor' => 'warning',
                                                default => 'secondary'
                                            };

                                            $roleDisplay = match($role->name) {
                                                'warehouse_manager' => 'Quản lý kho',
                                                'warehouse_staff' => 'Nhân viên kho',
                                                'warehouse_local_distributor' => 'NV phân phối nội thành',
                                                'warehouse_remote_distributor' => 'NV phân phối ngoại thành',
                                                default => 'Chưa phân quyền'
                                            };
                                        @endphp
                                        <span class="badge badge-{{ $badgeColor }} role-badge">{{ $roleDisplay }}</span>                                    @endforeach
                                @endif
                                <small class="text-muted d-block mt-1">Mã nhân viên: {{ $warehouseUser->staff_code }}</small>
                            </div>
                            <form action="{{ route('provincial-warehouses.staff.remove_from_warehouse', $warehouseUser->user) }}" 
                                method="POST" 
                                class="delete-staff-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    @empty
                        <div class="list-group-item text-center text-muted">
                            <i class="fas fa-info-circle"></i> Chưa có nhân viên nào
                        </div>
                    @endforelse
                </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href='https://api.mapbox.com/mapbox-gl-js/v2.9.1/mapbox-gl.css' rel='stylesheet' />
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container .select2-selection--single {
        height: 38px;
        line-height: 38px;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 38px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
    .badge {
        font-size: 85%;
    }
    #map {
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    .card {
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    .list-group-item {
        transition: all 0.2s;
    }
    .list-group-item:hover {
        background-color: #f8f9fa;
    }
    .list-group-item .badge {
        font-size: 0.8rem;
        padding: 0.3em 0.6em;
    }
    
    .list-group-item .badge + .text-muted {
        margin-left: 0.5rem;
    }
    
    .list-group-item h6 {
        margin-right: 0.5rem;
    }
    
    .list-group-item .d-flex {
        gap: 0.5rem;
    }
    .role-badge {
    display: inline-block;
    padding: 0.35em 0.65em;
    font-size: 0.75em;
    font-weight: 500;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: 0.25rem;
    margin-right: 0.5rem;
    margin-top: 0.5rem;
}

/* Màu nền và chữ cho từng loại badge */
.badge-success {
    background-color: #28a745 !important;
    color: #fff !important;
}

.badge-primary {
    background-color: #0d6efd !important;
    color: #fff !important;
}

.badge-info {
    background-color: #17a2b8 !important;
    color: #fff !important;
}

.badge-warning {
    background-color: #ffc107 !important;
    color: #000 !important;  /* Chữ màu đen cho badge màu vàng */
}

.badge-secondary {
    background-color: #6c757d !important;
    color: #fff !important;
}

/* Hiệu ứng hover */
.role-badge:hover {
    opacity: 0.9;
}
</style>
@endpush

@push('scripts')
<script src='https://api.mapbox.com/mapbox-gl-js/v2.9.1/mapbox-gl.js'></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('#userSearch').select2({
    ajax: {
        url: '{{ route("provincial-warehouses.staff.search") }}',
        dataType: 'json',
        delay: 250,
        data: function(params) {
            return {
                q: params.term, 
                warehouse_id: '{{ $provincialWarehouse->id }}'
            };
        },
        processResults: function(data) {
            return {
                results: $.map(data, function(user) {
                    return {
                        id: user.id,
                        text: user.name + ' (' + user.email + ')'
                    };
                })
            };
        },
        cache: true
    },
    placeholder: 'Tìm kiếm nhân viên...',
    minimumInputLength: 2,
    language: {
        inputTooShort: function() {
            return 'Vui lòng nhập ít nhất 2 ký tự';
        },
        noResults: function() {
            return 'Không tìm thấy kết quả';
        },
        searching: function() {
            return 'Đang tìm kiếm...';
        }
    }
});

    // Cập nhật action URL khi chọn user
    $('#userSearch').on('select2:select', function(e) {
        var userId = e.params.data.id;
        var formAction = $('#assignStaffForm').attr('action');
        formAction = formAction.replace(':userId', userId);
        $('#assignStaffForm').attr('action', formAction);
    });

    // Xác nhận xóa nhân viên
    $('.delete-staff-form').on('submit', function(e) {
        e.preventDefault();
        if (confirm('Bạn có chắc chắn muốn xóa nhân viên này?')) {
            this.submit();
        }
    });

    // Tự động ẩn thông báo sau 5 giây
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);
});

// Khởi tạo Mapbox
document.addEventListener('DOMContentLoaded', function() {
    initializeMap();
});

function initializeMap() {
    var mapboxToken = '{{ env('MAPBOX_ACCESS_TOKEN') }}';
    if (!mapboxToken) {
        showMapError('Mapbox token is missing');
        return;
    }

    mapboxgl.accessToken = mapboxToken;
    var coordinates = @json($provincialWarehouse->coordinates);
    
    try {
        coordinates = parseCoordinates(coordinates);
        if (!coordinates) return;

        var map = new mapboxgl.Map({
            container: 'map',
            style: 'mapbox://styles/mapbox/streets-v11',
            center: coordinates,
            zoom: 15
        });

        addMapControls(map, coordinates);
    } catch (error) {
        showMapError(error.message);
    }
}

function parseCoordinates(coordinates) {
    if (typeof coordinates === 'string') {
        try {
            coordinates = JSON.parse(coordinates);
        } catch (error) {
            showMapError('Invalid coordinates format');
            return null;
        }
    }

    if (!Array.isArray(coordinates) || coordinates.length !== 2 || 
        typeof coordinates[0] !== 'number' || typeof coordinates[1] !== 'number') {
        showMapError('Invalid coordinates data');
        return null;
    }

    document.getElementById('coordinates').textContent = 
        `Longitude: ${coordinates[0].toFixed(6)}, Latitude: ${coordinates[1].toFixed(6)}`;

    return coordinates;
}

function addMapControls(map, coordinates) {
        map.addControl(new mapboxgl.NavigationControl());

        var marker = new mapboxgl.Marker()
            .setLngLat(coordinates)
            .addTo(map);

        var popup = new mapboxgl.Popup({ offset: 25 })
            .setHTML(`
                <div class="p-2">
                    <h6 class="mb-1">${@json($provincialWarehouse->name)}</h6>
                    <p class="mb-0 text-muted">${@json($provincialWarehouse->address)}</p>
                </div>
            `);

        marker.setPopup(popup);

        map.on('load', function() {
            marker.togglePopup();
            
            // Thêm layer cho khu vực xung quanh
            map.addLayer({
                'id': 'warehouse-area',
                'type': 'circle',
                'source': {
                    'type': 'geojson',
                    'data': {
                        'type': 'Feature',
                        'properties': {},
                        'geometry': {
                            'type': 'Point',
                            'coordinates': coordinates
                        }
                    }
                },
                'paint': {
                    'circle-radius': 50,
                    'circle-color': '#FF9800',
                    'circle-opacity': 0.1,
                    'circle-stroke-width': 2,
                    'circle-stroke-color': '#FF9800'
                }
            });
        });

        // Thêm controls
        map.addControl(new mapboxgl.FullscreenControl());
        map.addControl(new mapboxgl.GeolocateControl({
            positionOptions: {
                enableHighAccuracy: true
            },
            trackUserLocation: true
        }));

        // Thêm scale control
        map.addControl(new mapboxgl.ScaleControl({
            maxWidth: 100,
            unit: 'metric'
        }));

        // Xử lý click trên map
        map.on('click', function(e) {
            var features = map.queryRenderedFeatures(e.point, {
                layers: ['warehouse-area']
            });

            if (features.length) {
                popup.setHTML(`
                    <div class="p-2">
                        <h6 class="mb-1">${@json($provincialWarehouse->name)}</h6>
                        <p class="mb-0 text-muted">${@json($provincialWarehouse->address)}</p>
                        <small class="text-primary">
                            ${e.lngLat.lng.toFixed(6)}, ${e.lngLat.lat.toFixed(6)}
                        </small>
                    </div>
                `).setLngLat(e.lngLat).addTo(map);
            }
        });

        // Thêm hover effect
        map.on('mouseenter', 'warehouse-area', function() {
            map.getCanvas().style.cursor = 'pointer';
        });

        map.on('mouseleave', 'warehouse-area', function() {
            map.getCanvas().style.cursor = '';
        });
    }

    function showMapError(message) {
        console.error('Map error:', message);
        document.getElementById('map').innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                Error: ${message}
            </div>
        `;
    }

    // Thêm xử lý form
    document.getElementById('assignStaffForm').addEventListener('submit', function(e) {
        var userSelect = document.getElementById('userSearch');
        if (!userSelect.value) {
            e.preventDefault();
            alert('Vui lòng chọn nhân viên');
            return false;
        }
    });

    // Thêm hiệu ứng cho danh sách nhân viên
    document.querySelectorAll('.list-group-item').forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(5px)';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
        });
    });
</script>
@endpush

@push('scripts')
<script>
// Thêm các animation và hiệu ứng
$(document).ready(function() {
    // Animation cho các card thống kê
    $('.card').each(function(index) {
        $(this).delay(100 * index).animate({
            opacity: 1,
            top: 0
        }, 500);
    });

    // Tooltip cho các nút
    $('[data-toggle="tooltip"]').tooltip();

    // Xử lý form submit
    $('#assignStaffForm').on('submit', function(e) {
        var submitButton = $(this).find('button[type="submit"]');
        submitButton.prop('disabled', true);
        submitButton.html('<i class="fas fa-spinner fa-spin"></i> Đang xử lý...');
    });

    // Hiệu ứng cho badges
    $('.badge').hover(
        function() {
            $(this).css('transform', 'scale(1.1)');
        },
        function() {
            $(this).css('transform', 'scale(1)');
        }
    );

    // Custom styling cho Select2
    $('.select2-container--default .select2-selection--single').css({
        'border-color': '#ced4da',
        'border-radius': '0.25rem'
    });

    // Thêm loading indicator
    $(document).on('select2:open', function() {
        $('.select2-results:not(:has(a))').append(
            '<div class="loading-indicator text-center p-2">' +
            '<i class="fas fa-spinner fa-spin"></i> Đang tải...</div>'
        );
    });

    // Xử lý scroll to top
    $(window).scroll(function() {
        if ($(this).scrollTop() > 100) {
            $('#scroll-to-top').fadeIn();
        } else {
            $('#scroll-to-top').fadeOut();
        }
    });

    // Thêm nút scroll to top
    $('body').append(
        '<button id="scroll-to-top" class="btn btn-primary rounded-circle position-fixed" ' +
        'style="bottom: 20px; right: 20px; display: none;">' +
        '<i class="fas fa-arrow-up"></i></button>'
    );

    $('#scroll-to-top').click(function() {
        $('html, body').animate({ scrollTop: 0 }, 'slow');
        return false;
    });
});
</script>
@endpush

@push('styles')
<style>
    /* Thêm các style bổ sung */
    .card {
        opacity: 0;
        position: relative;
        top: 20px;
        transition: transform 0.3s ease;
    }

    .card:hover {
        transform: translateY(-5px);
    }

    .badge {
        transition: transform 0.2s ease;
    }

    .loading-indicator {
        color: #6c757d;
    }

    #scroll-to-top {
        z-index: 1000;
        width: 40px;
        height: 40px;
        padding: 0;
        line-height: 40px;
    }

    .list-group-item {
        transition: all 0.3s ease;
    }

    .select2-container {
        width: 100% !important;
    }

    .mapboxgl-popup-content {
        padding: 0;
        border-radius: 0.25rem;
    }

    .mapboxgl-ctrl-group {
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
</style>
@endpush