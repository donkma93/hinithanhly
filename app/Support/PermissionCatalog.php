<?php

namespace App\Support;

use Illuminate\Support\Collection;

class PermissionCatalog
{
    public static function groups(): array
    {
        return [
            'dashboard' => [
                'label' => 'Dashboard',
                'permissions' => [
                    'dashboard.view' => 'Xem dashboard',
                ],
            ],
            'categories' => [
                'label' => 'Danh mục',
                'permissions' => [
                    'categories.view' => 'Xem danh mục',
                    'categories.create' => 'Thêm danh mục',
                    'categories.update' => 'Sửa danh mục',
                    'categories.delete' => 'Xoá danh mục',
                    'categories.manage' => 'Quản lý danh mục',
                ],
            ],
            'suppliers' => [
                'label' => 'Nhà cung cấp',
                'permissions' => [
                    'suppliers.view' => 'Xem nhà cung cấp',
                    'suppliers.create' => 'Thêm nhà cung cấp',
                    'suppliers.update' => 'Sửa nhà cung cấp',
                    'suppliers.delete' => 'Xoá nhà cung cấp',
                    'suppliers.manage' => 'Quản lý nhà cung cấp',
                ],
            ],
            'consignments' => [
                'label' => 'Phiếu ký gửi',
                'permissions' => [
                    'consignments.view' => 'Xem phiếu ký gửi',
                    'consignments.create' => 'Thêm phiếu ký gửi',
                    'consignments.update' => 'Sửa phiếu ký gửi',
                    'consignments.delete' => 'Xoá phiếu ký gửi',
                    'consignments.manage' => 'Quản lý phiếu ký gửi',
                ],
            ],
            'products' => [
                'label' => 'Sản phẩm',
                'permissions' => [
                    'products.view' => 'Xem sản phẩm',
                    'products.create' => 'Thêm sản phẩm',
                    'products.update' => 'Sửa sản phẩm',
                    'products.delete' => 'Xoá sản phẩm',
                    'products.manage' => 'Quản lý sản phẩm',
                ],
            ],
            'logs' => [
                'label' => 'Nhật ký',
                'permissions' => [
                    'logs.view' => 'Xem nhật ký',
                ],
            ],
            'users' => [
                'label' => 'Tài khoản',
                'permissions' => [
                    'users.view' => 'Xem tài khoản',
                    'users.create' => 'Thêm tài khoản',
                    'users.update' => 'Sửa tài khoản',
                    'users.delete' => 'Xoá tài khoản',
                    'users.manage' => 'Quản lý tài khoản',
                ],
            ],
            'permissions' => [
                'label' => 'Phân quyền',
                'permissions' => [
                    'permissions.view' => 'Xem phân quyền',
                    'permissions.create' => 'Thêm quyền',
                    'permissions.update' => 'Sửa quyền',
                    'permissions.delete' => 'Xoá quyền',
                    'permissions.manage' => 'Quản lý phân quyền',
                ],
            ],
        ];
    }

    public static function names(): array
    {
        return collect(self::groups())
            ->flatMap(static fn (array $group) => array_keys($group['permissions']))
            ->values()
            ->all();
    }

    public static function grouped(Collection $permissions): array
    {
        $catalogNames = collect(self::groups())
            ->flatMap(static fn (array $group) => array_keys($group['permissions']))
            ->all();

        $grouped = [];

        foreach (self::groups() as $key => $group) {
            $grouped[] = [
                'key' => $key,
                'label' => $group['label'],
                'permissions' => collect($group['permissions'])->map(static function (string $label, string $name) use ($permissions) {
                    $permission = $permissions->firstWhere('name', $name);

                    return [
                        'name' => $name,
                        'label' => $label,
                        'exists' => $permission !== null,
                    ];
                })->values()->all(),
            ];
        }

        $customPermissions = $permissions
            ->reject(static fn ($permission) => in_array($permission->name, $catalogNames, true))
            ->map(static fn ($permission) => [
                'name' => $permission->name,
                'label' => $permission->name,
                'exists' => true,
            ])
            ->values()
            ->all();

        if ($customPermissions !== []) {
            $grouped[] = [
                'key' => 'custom',
                'label' => 'Tùy chỉnh',
                'permissions' => $customPermissions,
            ];
        }

        return $grouped;
    }
}
