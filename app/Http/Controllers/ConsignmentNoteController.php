<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ConsignmentNote;
use App\Models\Supplier;
use App\Repositories\Contracts\ConsignmentNoteRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ConsignmentNoteController extends Controller
{
    public function __construct(private readonly ConsignmentNoteRepositoryInterface $consignments)
    {
        $this->middleware('permission:consignments.view')->only('index');
        $this->middleware('permission:consignments.create|consignments.manage')->only('store');
        $this->middleware('permission:consignments.update|consignments.manage')->only('update');
        $this->middleware('permission:consignments.delete')->only('destroy');
    }

    public function index(Request $request): View
    {
        $publicId = trim($request->string('public_id')->toString());
        $perPage = $this->resolvePerPage($request);

        $consignmentRoundMap = $this->resolveSendRounds(
            ConsignmentNote::query()
                ->orderBy('supplier_id')
                ->orderBy('sent_date')
                ->orderBy('id')
                ->get(['id', 'supplier_id', 'sent_date'])
        );

        $consignments = ConsignmentNote::query()
            ->select(['id', 'public_id', 'responsible_user_id', 'responsible_name', 'supplier_id', 'sent_date', 'quantity', 'created_at'])
            ->with([
                'supplier:id,public_id,name,type',
            ])
            ->when($publicId !== '', fn ($query) => $query->where('public_id', $publicId))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $consignments->setCollection(
            $consignments->getCollection()->map(function (ConsignmentNote $consignment) use ($consignmentRoundMap): ConsignmentNote {
                $consignment->setAttribute('send_round', $consignmentRoundMap[$consignment->id] ?? 1);

                return $consignment;
            })
        );

        return view('consignments.index', [
            'consignments' => $consignments,
            'suppliers' => Supplier::query()
                ->whereIn('type', Supplier::MANUAL_CONSIGNMENT_TYPES)
                ->orderBy('name')
                ->get(['id', 'public_id', 'name', 'type']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'responsible_name' => ['required', 'string', 'max:255'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'sent_date' => ['required', 'date'],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->resolveManualConsignmentSupplier($data['supplier_id']);

        $this->consignments->create($data);

        AuditLog::record([
            'user_id' => $request->user()?->id,
            'action' => 'consignments.store',
            'method' => $request->method(),
            'route_name' => 'consignments.store',
            'path' => $request->path(),
            'status_code' => 302,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload' => [
                'supplier_id' => $data['supplier_id'],
                'responsible_name' => $data['responsible_name'],
                'sent_date' => $data['sent_date'],
                'quantity' => $data['quantity'],
            ],
        ]);

        return redirect()->route('consignments.index')->with('status', 'Đã tạo phiếu ký gửi.');
    }

    public function edit(ConsignmentNote $consignment): View
    {
        $this->ensureManualConsignment($consignment);

        return view('consignments.edit', [
            'consignment' => $consignment,
            'suppliers' => Supplier::query()
                ->whereIn('type', Supplier::MANUAL_CONSIGNMENT_TYPES)
                ->orderBy('name')
                ->get(['id', 'public_id', 'name', 'type']),
        ]);
    }

    public function update(Request $request, ConsignmentNote $consignment): RedirectResponse
    {
        $this->ensureManualConsignment($consignment);

        $data = $request->validate([
            'responsible_name' => ['required', 'string', 'max:255'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'sent_date' => ['required', 'date'],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->resolveManualConsignmentSupplier($data['supplier_id']);

        $this->consignments->update($consignment->id, $data);

        AuditLog::record([
            'user_id' => $request->user()?->id,
            'action' => 'consignments.update',
            'method' => $request->method(),
            'route_name' => 'consignments.update',
            'path' => $request->path(),
            'status_code' => 302,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload' => [
                'consignment_id' => $consignment->id,
                'supplier_id' => $data['supplier_id'],
                'responsible_name' => $data['responsible_name'],
                'sent_date' => $data['sent_date'],
                'quantity' => $data['quantity'],
            ],
        ]);

        return redirect()->route('consignments.index')->with('status', 'Đã cập nhật phiếu ký gửi.');
    }

    public function destroy(ConsignmentNote $consignment): RedirectResponse
    {
        $this->ensureManualConsignment($consignment);

        $payload = [
            'consignment_id' => $consignment->id,
            'supplier_id' => $consignment->supplier_id,
            'responsible_name' => $consignment->responsible_name,
            'sent_date' => $consignment->sent_date,
            'quantity' => $consignment->quantity,
        ];

        $this->consignments->delete($consignment->id);

        AuditLog::record([
            'user_id' => request()->user()?->id,
            'action' => 'consignments.destroy',
            'method' => request()->method(),
            'route_name' => 'consignments.destroy',
            'path' => request()->path(),
            'status_code' => 302,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'payload' => $payload,
        ]);

        return redirect()->route('consignments.index')->with('status', 'Đã xoá phiếu ký gửi.');
    }

    private function resolveManualConsignmentSupplier(int $supplierId): Supplier
    {
        $supplier = Supplier::query()->findOrFail($supplierId);

        if (! $supplier->requiresManualConsignment()) {
            throw ValidationException::withMessages([
                'supplier_id' => 'Nhà cung cấp này không cần tạo phiếu ký gửi thủ công.',
            ]);
        }

        return $supplier;
    }

    private function ensureManualConsignment(ConsignmentNote $consignment): void
    {
        abort_if($consignment->isAutoGenerated(), 404);
    }

    /**
     * @param  iterable<ConsignmentNote>  $consignments
     * @return array<int, int>
     */
    private function resolveSendRounds(iterable $consignments): array
    {
        $sendRounds = [];

        $lastBySupplier = [];

        foreach ($consignments as $consignment) {
            $supplierId = (int) $consignment->supplier_id;
            $sentDate = $consignment->sent_date;

            if (! isset($lastBySupplier[$supplierId])) {
                $sendRounds[$consignment->id] = 1;
                $lastBySupplier[$supplierId] = [
                    'date' => $sentDate,
                    'round' => 1,
                ];

                continue;
            }

            $lastDate = $lastBySupplier[$supplierId]['date'];
            $currentRound = $lastBySupplier[$supplierId]['round'];
            $daysSincePrevious = $lastDate->diffInDays($sentDate);

            if ($daysSincePrevious <= 15) {
                $sendRounds[$consignment->id] = $currentRound;
            } else {
                $currentRound++;
                $sendRounds[$consignment->id] = $currentRound;
            }

            $lastBySupplier[$supplierId] = [
                'date' => $sentDate,
                'round' => $sendRounds[$consignment->id],
            ];
        }

        return $sendRounds;
    }
}
