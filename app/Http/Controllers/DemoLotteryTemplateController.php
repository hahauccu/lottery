<?php

namespace App\Http\Controllers;

use App\Models\DemoLotteryTemplate;
use App\Support\AnimationStyles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DemoLotteryTemplateController extends Controller
{
    private const CATEGORIES = [
        'lunch' => '午餐吃什麼',
        'drinks' => '飲料喝什麼',
        'party' => '聚餐去哪裡',
        'company' => '公司活動',
        'lottery' => '尾牙抽獎',
        'daily' => '日常選擇',
        'other' => '其他',
    ];

    public function index(Request $request): View
    {
        $category = $request->query('category');
        $templates = DemoLotteryTemplate::query()
            ->publiclyVisible()
            ->with(['publicReports' => fn ($query) => $query->latest()->limit(2)])
            ->withCount(['publicReports as visible_reports_count'])
            ->when(isset(self::CATEGORIES[$category]), fn ($query) => $query->where('category', $category))
            ->orderByDesc('is_featured')
            ->orderByDesc('last_used_at')
            ->orderByDesc('created_at')
            ->simplePaginate(18)
            ->withQueryString();

        return view('demo.templates.index', [
            'templates' => $templates,
            'categories' => self::CATEGORIES,
            'currentCategory' => isset(self::CATEGORIES[$category]) ? $category : null,
        ]);
    }

    public function show(DemoLotteryTemplate $template): View
    {
        abort_unless($template->is_public && (! $template->expires_at || $template->expires_at->isFuture()), 404);

        return view('demo.templates.show', [
            'template' => $template->load(['publicReports' => fn ($query) => $query->latest()->limit(20)]),
            'categories' => self::CATEGORIES,
        ]);
    }

    public function store(Request $request, string $slug): JsonResponse
    {
        $style = $this->resolveStyle($slug);
        $validated = $request->validate([
            'title' => 'required|string|max:60',
            'category' => 'nullable|string|in:'.implode(',', array_keys(self::CATEGORIES)),
            'description' => 'nullable|string|max:160',
            'names' => 'nullable|string|max:10000',
            'draw_count' => 'nullable|integer|min:1|max:50',
            'draw_mode' => 'nullable|in:all_at_once,one_by_one',
            'is_public' => 'nullable|boolean',
        ]);

        $options = $this->parseOptions($validated['names'] ?? '');
        if (empty($options)) {
            $options = DemoLotteryController::DEMO_NAMES;
        }

        $template = DemoLotteryTemplate::create([
            'token' => Str::lower(Str::random(10)),
            'title' => $this->cleanText($validated['title'], 60),
            'category' => $validated['category'] ?? 'other',
            'description' => $this->cleanText($validated['description'] ?? null, 160),
            'options' => $options,
            'animation_style' => $style,
            'draw_count' => (int) ($validated['draw_count'] ?? 1),
            'draw_mode' => $validated['draw_mode'] ?? 'all_at_once',
            'is_public' => $request->boolean('is_public', true),
            'expires_at' => now()->addDays(30),
        ]);

        return response()->json([
            'message' => '已發佈到大家都抽什麼',
            'url' => route('demo.lottery.templates.show', $template),
            'templatesUrl' => route('demo.lottery.templates.index'),
            'template' => [
                'token' => $template->token,
                'title' => $template->title,
                'url' => route('demo.lottery.templates.show', $template),
                'reportUrl' => route('demo.lottery.templates.report', $template),
            ],
        ]);
    }

    public function apply(Request $request, DemoLotteryTemplate $template): JsonResponse|RedirectResponse
    {
        abort_unless($template->is_public && (! $template->expires_at || $template->expires_at->isFuture()), 404);

        $validated = $request->validate([
            'names' => 'nullable|string|max:10000',
            'use_template_options' => 'nullable|boolean',
        ]);

        $customOptions = $this->parseOptions($validated['names'] ?? '');
        $options = ! empty($customOptions)
            ? $customOptions
            : ($template->options ?: DemoLotteryController::DEMO_NAMES);

        $sessionKey = $this->sessionKey($template->animation_style);
        $data = session($sessionKey, []);
        $newPrizeId = ($data['prize_id'] ?? 0) + 1;

        session([$sessionKey => [
            'animation_style' => $template->animation_style,
            'prize_id' => $newPrizeId,
            'winners' => [],
            'eligible_names' => array_values($options),
            'draw_count' => min((int) $template->draw_count, max(1, count($options))),
            'draw_mode' => $template->draw_mode,
            'is_custom' => true,
            'template_token' => $template->token,
            'template_title' => $template->title,
            'template_source' => 'applied',
        ]]);

        $template->increment('uses_count');
        $template->forceFill(['last_used_at' => now()])->save();

        $redirectUrl = route('demo.lottery.style', ['slug' => $template->styleSlug()]);

        if ($request->expectsJson()) {
            return response()->json(['redirectUrl' => $redirectUrl]);
        }

        return redirect($redirectUrl);
    }

    public function report(Request $request, DemoLotteryTemplate $template): JsonResponse|RedirectResponse
    {
        abort_unless($template->is_public, 404);

        $validated = $request->validate([
            'result_text' => 'required|string|max:120',
            'author_name' => 'nullable|string|max:40',
            'comment' => 'nullable|string|max:120',
            'is_public' => 'nullable|boolean',
        ]);

        $template->reports()->create([
            'result_text' => $this->cleanText($validated['result_text'], 120),
            'author_name' => $this->cleanText($validated['author_name'] ?? null, 40),
            'comment' => $this->cleanText($validated['comment'] ?? null, 120),
            'is_public' => $request->boolean('is_public', true),
            'ip_hash' => $request->ip() ? hash('sha256', $request->ip().'|'.config('app.key')) : null,
        ]);

        $template->increment('reports_count');

        if ($request->expectsJson()) {
            return response()->json(['message' => '已送出抽獎成果']);
        }

        return back()->with('status', '已送出抽獎成果');
    }

    private function resolveStyle(string $slug): string
    {
        $style = AnimationStyles::slugToKey()[$slug] ?? null;
        abort_unless($style, 404);

        return $style;
    }

    private function sessionKey(string $style): string
    {
        return "demo_lottery_{$style}";
    }

    private function parseOptions(string $raw): array
    {
        return collect(preg_split('/[\r\n,，]+/u', $raw))
            ->map(fn ($value) => $this->cleanText($value, 30))
            ->filter()
            ->unique()
            ->take(50)
            ->values()
            ->all();
    }

    private function cleanText(?string $value, int $limit): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = preg_replace('/https?:\/\/\S+|www\.\S+/iu', '', strip_tags($value));
        $value = trim(preg_replace('/\s+/u', ' ', $value ?? ''));

        return $value === '' ? null : Str::limit($value, $limit, '');
    }
}
