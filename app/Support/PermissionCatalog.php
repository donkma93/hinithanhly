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
                'label' => 'Danh muc',
                'permissions' => [
                    'categories.view' => 'Xem danh muc',
                    'categories.create' => 'Them danh muc',
                    'categories.update' => 'Sua danh muc',
                    'categories.delete' => 'Dua danh muc vao thung rac',
                    'categories.manage' => 'Quan ly danh muc',
                ],
            ],
            'suppliers' => [
                'label' => 'Nha cung cap',
                'permissions' => [
                    'suppliers.view' => 'Xem nha cung cap',
                    'suppliers.create' => 'Them nha cung cap',
                    'suppliers.update' => 'Sua nha cung cap',
                    'suppliers.delete' => 'Dua nha cung cap vao thung rac',
                    'suppliers.manage' => 'Quan ly nha cung cap',
                ],
            ],
            'consignments' => [
                'label' => 'Phieu ky gui',
                'permissions' => [
                    'consignments.view' => 'Xem phieu ky gui',
                    'consignments.create' => 'Them phieu ky gui',
                    'consignments.update' => 'Sua phieu ky gui',
                    'consignments.delete' => 'Dua phieu ky gui vao thung rac',
                    'consignments.manage' => 'Quan ly phieu ky gui',
                ],
            ],
            'products' => [
                'label' => 'San pham',
                'permissions' => [
                    'products.view' => 'Xem san pham',
                    'products.create' => 'Them san pham',
                    'products.update' => 'Sua san pham',
                    'products.delete' => 'Dua san pham vao thung rac',
                    'products.manage' => 'Quan ly san pham',
                ],
            ],
            'sold-products' => [
                'label' => 'San pham da ban',
                'permissions' => [
                    'sales.records.view' => 'Xem san pham da ban',
                ],
            ],
            'revenue' => [
                'label' => 'Doanh thu',
                'permissions' => [
                    'sales.revenue.view' => 'Xem doanh thu',
                ],
            ],
            'logs' => [
                'label' => 'Nhat ky',
                'permissions' => [
                    'logs.view' => 'Xem nhat ky nguoi dung',
                    'system-logs.view' => 'Xem log he thong',
                ],
            ],
            'users' => [
                'label' => 'Tai khoan',
                'permissions' => [
                    'users.view' => 'Xem tai khoan',
                    'users.create' => 'Them tai khoan',
                    'users.update' => 'Sua tai khoan',
                    'users.delete' => 'Dua tai khoan vao thung rac',
                    'users.manage' => 'Quan ly tai khoan',
                ],
            ],
            'permissions' => [
                'label' => 'Phan quyen',
                'permissions' => [
                    'permissions.view' => 'Xem phan quyen',
                    'permissions.create' => 'Them quyen',
                    'permissions.update' => 'Sua quyen',
                    'permissions.delete' => 'Dua quyen vao thung rac',
                    'permissions.manage' => 'Quan ly phan quyen',
                ],
            ],
            'settings' => [
                'label' => 'Cai dat',
                'permissions' => [
                    'settings.manage' => 'Quan ly cai dat',
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
                'label' => 'Tuy chinh',
                'permissions' => $customPermissions,
            ];
        }

        return $grouped;
    }
}
