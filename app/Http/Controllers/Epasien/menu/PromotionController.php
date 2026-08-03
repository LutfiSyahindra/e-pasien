<?php

namespace App\Http\Controllers\Epasien\menu;

use App\Http\Controllers\Controller;
use App\Http\Requests\Epasien\Menu\StorePromotionRequest;
use App\Http\Requests\Epasien\Menu\UpdatePromotionRequest;
use App\Models\Promotion;
use App\Services\epasien\menu\PromotionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PromotionController extends Controller
{
    public function __construct(
        private readonly PromotionService $service,
    ) {}

    public function index(Request $request): View
    {
        $canManage = $request->user()->can('EPASIEN.MENU.PROMOSI.KELOLA');

        if ($canManage) {
            $filters = $request->validate([
                'q' => ['nullable', 'string', 'max:100'],
                'status' => ['nullable', 'in:all,draft,published,archived,active,scheduled,expired'],
            ]);

            $query = Promotion::query()->with('creator:id,name')->latest();
            $search = trim((string) ($filters['q'] ?? ''));
            $status = $filters['status'] ?? 'all';

            $query->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', '%'.$search.'%')
                        ->orWhere('caption', 'like', '%'.$search.'%');
                });
            });

            match ($status) {
                'draft', 'published', 'archived' => $query->where('status', $status),
                'active' => $query->active(),
                'scheduled' => $query->where('status', Promotion::STATUS_PUBLISHED)->where('starts_at', '>', now()),
                'expired' => $query->where('status', Promotion::STATUS_PUBLISHED)->where('ends_at', '<=', now()),
                default => null,
            };

            return view('e-pasien.menu.promotions.index', [
                'canManage' => true,
                'promotions' => $query->paginate(9)->withQueryString(),
                'filters' => ['q' => $search, 'status' => $status],
                'summary' => [
                    'total' => Promotion::query()->count(),
                    'active' => Promotion::query()->active()->count(),
                    'scheduled' => Promotion::query()->where('status', Promotion::STATUS_PUBLISHED)
                        ->where('starts_at', '>', now())->count(),
                    'draft' => Promotion::query()->where('status', Promotion::STATUS_DRAFT)->count(),
                ],
            ]);
        }

        return view('e-pasien.menu.promotions.index', [
            'canManage' => false,
            'promotions' => Promotion::query()->active()->latest('starts_at')->paginate(9),
            'featured' => Promotion::query()->active()->latest('starts_at')->first(),
        ]);
    }

    public function show(Request $request, Promotion $promotion): View
    {
        abort_unless($promotion->is_active || $request->user()->can('EPASIEN.MENU.PROMOSI.KELOLA'), 404);

        return view('e-pasien.menu.promotions.show', compact('promotion'));
    }

    public function create(): View
    {
        return view('e-pasien.menu.promotions.form', [
            'promotion' => new Promotion([
                'starts_at' => now()->addMinutes(5)->startOfMinute(),
                'duration_value' => 1,
                'duration_unit' => 'day',
                'status' => Promotion::STATUS_DRAFT,
            ]),
        ]);
    }

    public function store(StorePromotionRequest $request): RedirectResponse
    {
        $promotion = $this->service->create($request->validated(), $request->user());

        return redirect()->route('promotions.index')
            ->with('success', $promotion->status === Promotion::STATUS_PUBLISHED
                ? 'Promosi berhasil diterbitkan.'
                : 'Draf promosi berhasil disimpan.');
    }

    public function edit(Promotion $promotion): View
    {
        return view('e-pasien.menu.promotions.form', compact('promotion'));
    }

    public function update(UpdatePromotionRequest $request, Promotion $promotion): RedirectResponse
    {
        $this->service->update($promotion, $request->validated());

        return redirect()->route('promotions.index')->with('success', 'Promosi berhasil diperbarui.');
    }

    public function archive(Promotion $promotion): RedirectResponse
    {
        $this->service->archive($promotion);

        return back()->with('success', 'Promosi dipindahkan ke arsip.');
    }

    public function restore(Promotion $promotion): RedirectResponse
    {
        $this->service->restore($promotion);

        return back()->with('success', 'Promosi dikembalikan sebagai draf.');
    }

    public function destroy(Promotion $promotion): RedirectResponse
    {
        $this->service->delete($promotion);

        return redirect()->route('promotions.index')->with('success', 'Promosi berhasil dihapus.');
    }
}
