<?php

namespace App\Http\Controllers\Epasien\menu;

use App\Http\Controllers\Controller;
use App\Http\Requests\Epasien\Menu\StorePromotionRequest;
use App\Http\Requests\Epasien\Menu\UpdatePromotionRequest;
use App\Models\Promotion;
use App\Models\PromotionConfiguration;
use App\Notifications\PromotionPublishedNotification;
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
        $this->service->deleteExpired();

        $canManage = $request->user()->can('EPASIEN.MENU.PROMOSI.KELOLA');
        $now = now();

        $filters = $request->validate([
            'category' => ['nullable', 'in:all,promotion,information'],
            ...($canManage ? [
                'q' => ['nullable', 'string', 'max:100'],
                'status' => ['nullable', 'in:all,draft,published,archived,active,scheduled'],
            ] : []),
        ]);
        $category = $filters['category'] ?? 'all';

        if ($canManage) {
            $query = Promotion::query()
                ->with('creator:id,name')
                ->withCount('views')
                ->orderByDesc('id');
            $search = trim((string) ($filters['q'] ?? ''));
            $status = $filters['status'] ?? 'all';

            $query->when($category !== 'all', fn ($query) => $query->where('category', $category));

            $query->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', '%'.$search.'%')
                        ->orWhere('caption', 'like', '%'.$search.'%');
                });
            });

            match ($status) {
                'draft', 'published', 'archived' => $query->where('status', $status),
                'active' => $query->active($now),
                'scheduled' => $query->where('status', Promotion::STATUS_PUBLISHED)->where('starts_at', '>', $now),
                default => null,
            };

            $summary = Promotion::query()
                ->selectRaw('COUNT(*) AS total')
                ->selectRaw(
                    'COALESCE(SUM(CASE WHEN status = ? AND starts_at <= ? AND ends_at > ? THEN 1 ELSE 0 END), 0) AS active',
                    [Promotion::STATUS_PUBLISHED, $now, $now],
                )
                ->selectRaw(
                    'COALESCE(SUM(CASE WHEN status = ? AND starts_at > ? THEN 1 ELSE 0 END), 0) AS scheduled',
                    [Promotion::STATUS_PUBLISHED, $now],
                )
                ->selectRaw(
                    'COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) AS draft',
                    [Promotion::STATUS_DRAFT],
                )
                ->firstOrFail();

            return view('e-pasien.menu.promotions.index', [
                'canManage' => true,
                'promotions' => $query->paginate(9)->withQueryString(),
                'filters' => ['q' => $search, 'status' => $status, 'category' => $category],
                'summary' => [
                    'total' => (int) $summary->total,
                    'active' => (int) $summary->active,
                    'scheduled' => (int) $summary->scheduled,
                    'draft' => (int) $summary->draft,
                ],
            ]);
        }

        $this->markNotificationsAsRead($request);

        $query = Promotion::query()
            ->active($now)
            ->when($category !== 'all', fn ($query) => $query->where('category', $category))
            ->latest('starts_at');
        $featuredQuery = clone $query;
        $promotions = $query->paginate(9)->withQueryString();

        return view('e-pasien.menu.promotions.index', [
            'canManage' => false,
            'promotions' => $promotions,
            'featured' => $promotions->currentPage() === 1
                ? $promotions->first()
                : $featuredQuery->first(),
            'filters' => ['category' => $category],
        ]);
    }

    public function show(Request $request, Promotion $promotion): View
    {
        $this->deleteIfExpired($promotion);

        abort_unless($promotion->is_active || $request->user()->can('EPASIEN.MENU.PROMOSI.KELOLA'), 404);

        if (! $request->user()->can('EPASIEN.MENU.PROMOSI.KELOLA')) {
            $this->service->recordView($promotion, $request->user());
            $this->markNotificationsAsRead($request, $promotion);
        }

        return view('e-pasien.menu.promotions.show', compact('promotion'));
    }

    public function viewers(Promotion $promotion): View
    {
        $this->deleteIfExpired($promotion);

        $views = $promotion->views()
            ->with('user:id,name,username,email,profile_photo_path')
            ->latest('viewed_at')
            ->paginate(20);

        return view('e-pasien.menu.promotions.viewers', compact('promotion', 'views'));
    }

    public function create(): View
    {
        $configuration = PromotionConfiguration::current();

        return view('e-pasien.menu.promotions.form', [
            'promotion' => new Promotion([
                'category' => Promotion::CATEGORY_PROMOTION,
                'starts_at' => now(Promotion::TIMEZONE)->addMinutes(5)->startOfMinute(),
                'duration_value' => $configuration->default_duration_value,
                'duration_unit' => $configuration->default_duration_unit,
                'status' => Promotion::STATUS_DRAFT,
            ]),
        ]);
    }

    public function store(StorePromotionRequest $request): RedirectResponse
    {
        $promotion = $this->service->create($request->validated(), $request->user());

        return redirect()->route('promotions.index')
            ->with('success', $promotion->status === Promotion::STATUS_PUBLISHED
                ? 'Konten berhasil diterbitkan.'
                : 'Draf konten berhasil disimpan.');
    }

    public function edit(Promotion $promotion): View
    {
        $this->deleteIfExpired($promotion);

        return view('e-pasien.menu.promotions.form', compact('promotion'));
    }

    public function update(UpdatePromotionRequest $request, Promotion $promotion): RedirectResponse
    {
        $this->deleteIfExpired($promotion);

        $this->service->update($promotion, $request->validated());

        return redirect()->route('promotions.index')->with('success', 'Konten berhasil diperbarui.');
    }

    public function archive(Promotion $promotion): RedirectResponse
    {
        $this->deleteIfExpired($promotion);

        $this->service->archive($promotion);

        return back()->with('success', 'Konten dipindahkan ke arsip.');
    }

    public function restore(Promotion $promotion): RedirectResponse
    {
        $this->deleteIfExpired($promotion);

        $this->service->restore($promotion);

        return back()->with('success', 'Konten dikembalikan sebagai draf.');
    }

    public function destroy(Promotion $promotion): RedirectResponse
    {
        $this->service->delete($promotion);

        return redirect()->route('promotions.index')->with('success', 'Konten berhasil dihapus.');
    }

    private function markNotificationsAsRead(Request $request, ?Promotion $promotion = null): void
    {
        $notifications = $request->user()->unreadNotifications()
            ->where('type', PromotionPublishedNotification::class);

        if ($promotion) {
            $notifications->where('data->promotion_id', $promotion->getKey());
        }

        $notifications->update(['read_at' => now()]);
    }

    private function deleteIfExpired(Promotion $promotion): void
    {
        if (in_array($promotion->status, [Promotion::STATUS_PUBLISHED, Promotion::STATUS_ARCHIVED], true)
            && $promotion->ends_at->lte(now())) {
            $this->service->delete($promotion);
            abort(404);
        }
    }
}
