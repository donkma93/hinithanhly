<?php

namespace App\Http\Controllers;

use App\Support\BranchPortalReader;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierPortalController extends Controller
{
    public function __construct(private readonly BranchPortalReader $portalReader)
    {
    }

    public function index(Request $request): View
    {
        $phone = $this->normalizePhone((string) $request->input('phone', ''));
        $searchPerformed = $phone !== '';

        $branches = $this->portalReader->branches();
        $isMultiBranch = count($branches) > 1;

        $requestedBranch = strtolower(trim((string) $request->input('branch', 'all')));
        $validBranchKeys = array_column($branches, 'key');
        $selectedBranch = ($isMultiBranch && in_array($requestedBranch, $validBranchKeys, true))
            ? $requestedBranch
            : 'all';

        $allBranchResults = $searchPerformed
            ? $this->portalReader->searchByPhone($phone)
            : [];

        $branchOptions = [];
        if ($isMultiBranch) {
            $resultsByKey = collect($allBranchResults)->keyBy('branch_key');
            $totalFoundStock = collect($allBranchResults)->sum('total_stock');
            $matchedBranchCount = collect($allBranchResults)->filter(fn (array $row): bool => $row['supplier'] !== null)->count();

            $branchOptions[] = [
                'key' => 'all',
                'name' => 'Tất cả cơ sở',
                'matched' => $matchedBranchCount > 0,
                'total_stock' => (int) $totalFoundStock,
            ];

            foreach ($branches as $branch) {
                $branchResult = $resultsByKey->get($branch['key']);
                $branchOptions[] = [
                    'key' => $branch['key'],
                    'name' => $branch['name'],
                    'matched' => $branchResult !== null && $branchResult['supplier'] !== null,
                    'total_stock' => (int) ($branchResult['total_stock'] ?? 0),
                ];
            }
        }

        $visibleBranchResults = collect($allBranchResults)
            ->when(
                $selectedBranch !== 'all',
                fn ($collection) => $collection->where('branch_key', $selectedBranch)
            )
            ->values();

        $matchedBranchResults = $visibleBranchResults
            ->filter(fn (array $row): bool => $row['supplier'] !== null)
            ->values();

        $unmatchedBranchNames = $visibleBranchResults
            ->filter(fn (array $row): bool => $row['available'] && $row['supplier'] === null)
            ->pluck('branch_name')
            ->values()
            ->all();

        $firstMatched = $matchedBranchResults->first();
        $hasAnyMatch = $matchedBranchResults->isNotEmpty();

        $storeLocations = $this->portalReader->storeLocations();
        $currentLocation = collect($storeLocations)->firstWhere('is_current', true)
            ?? ($storeLocations[0] ?? null);

        return view('welcome', [
            'supplier' => $firstMatched['supplier'] ?? null,
            'inventorySummaries' => $firstMatched['inventory_summaries'] ?? collect(),
            'phone' => $phone,
            'searchPerformed' => $searchPerformed,
            'searchError' => $searchPerformed && ! $hasAnyMatch
                ? 'Không tìm thấy nhà cung cấp phù hợp. Hãy kiểm tra lại số điện thoại đã đăng ký.'
                : null,
            'isMultiBranch' => $isMultiBranch,
            'selectedBranch' => $selectedBranch,
            'branchOptions' => $branchOptions,
            'matchedBranchResults' => $matchedBranchResults,
            'unmatchedBranchNames' => $unmatchedBranchNames,
            'storeLocations' => $storeLocations,
            'portalAddress' => $currentLocation['address'] ?? 'Địa chỉ cửa hàng đang được cập nhật',
            'portalHotline' => $currentLocation['hotline'] ?? 'Liên hệ trực tiếp cửa hàng để được hỗ trợ',
            'portalHours' => $currentLocation['hours'] ?? '08:30 - 21:00 mỗi ngày',
            'portalMapUrl' => $currentLocation['map_url'] ?? '',
        ]);
    }

    private function normalizePhone(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }
}
