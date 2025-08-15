<?php
// app/Http/Controllers/AdminController.php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Cryptocurrency;
use App\Models\PortfolioHolding;
use App\Models\PriceAlert;
use App\Models\UserWatchlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function dashboard()
    {
        return view('admin.dashboard');
    }

    // Dashboard stats API
    public function dashboardStats()
    {
        $stats = [
            'users' => [
                'total' => User::count(),
                'premium' => User::where('premium', true)->count(),
                'verified' => User::whereNotNull('email_verified_at')->count(),
                'today' => User::whereDate('created_at', today())->count(),
            ],
            'revenue' => [
                'monthly' => User::where('premium', true)->count() * 19, // 19 PLN per user
                'annual' => User::where('premium', true)->count() * 19 * 12,
                'conversion_rate' => User::count() > 0 ?
                    round((User::where('premium', true)->count() / User::count()) * 100, 2) : 0,
            ],
            'activity' => [
                'portfolio_items' => PortfolioHolding::count(),
                'active_alerts' => PriceAlert::where('is_active', true)->count(),
                'watchlist_items' => UserWatchlist::count(), // ZMIENIONO
                'cryptocurrencies' => Cryptocurrency::count(),
            ],
            'engagement' => [
                'users_with_portfolio' => User::whereHas('portfolioHoldings')->count(),
                'users_with_alerts' => User::whereHas('priceAlerts')->count(),
                'users_with_watchlist' => User::whereHas('watchlist')->count(),
                'avg_portfolio_size' => round(PortfolioHolding::count() / max(User::whereHas('portfolioHoldings')->count(), 1), 2), // POPRAWKA
            ]
        ];

        return response()->json($stats);
    }

    // Users management
    public function users(Request $request)
    {
        $query = User::query();

        // Filters
        if ($request->filter) {
            switch ($request->filter) {
                case 'premium':
                    $query->where('premium', true);
                    break;
                case 'free':
                    $query->where('premium', false);
                    break;
                case 'unverified':
                    $query->whereNull('email_verified_at');
                    break;
            }
        }

        // Search
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        // Sort
        $sortBy = $request->sort_by ?? 'created_at';
        $sortDir = $request->sort_dir ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        $users = $query->with(['portfolioHoldings', 'priceAlerts', 'watchlist'])
            ->paginate(20);

        // Add calculated stats for each user
        $users->getCollection()->transform(function ($user) {
            $user->stats = [
                'portfolio_count' => $user->portfolioHoldings->count(),
                'alerts_count' => $user->priceAlerts->count(),
                'watchlist_count' => $user->watchlist->count(),
                'portfolio_value' => $user->portfolioHoldings->sum(function ($holding) {
                    return $holding->amount * ($holding->cryptocurrency->current_price ?? 0);
                }),
            ];
            return $user;
        });

        return response()->json($users);
    }

    // Toggle user premium status
    public function togglePremium(User $user)
    {
        $user->premium = !$user->premium;

        if ($user->premium) {
            $user->premium_expires_at = now()->addMonth();
        } else {
            $user->premium_expires_at = null;
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => $user->premium ? 'Premium activated' : 'Premium deactivated',
            'user' => $user
        ]);
    }

    // User details
    public function userDetails(User $user)
    {
        $user->load([
            'portfolioHoldings.cryptocurrency',
            'priceAlerts.cryptocurrency',
            'watchlist.cryptocurrency'
        ]);

        $userStats = [
            'portfolio_value' => $user->portfolioHoldings->sum(function ($holding) {
                return $holding->amount * ($holding->cryptocurrency->current_price ?? 0);
            }),
            'total_invested' => $user->portfolioHoldings->sum(function ($holding) {
                return $holding->amount * ($holding->average_buy_price ?? 0);
            }),
            'profit_loss' => 0, // Will be calculated
        ];

        $userStats['profit_loss'] = $userStats['portfolio_value'] - $userStats['total_invested'];
        $userStats['profit_loss_percent'] = $userStats['total_invested'] > 0 ?
            (($userStats['profit_loss'] / $userStats['total_invested']) * 100) : 0;

        return response()->json([
            'user' => $user,
            'stats' => $userStats
        ]);
    }

    // System stats for charts
    public function systemStats()
    {
        // User registration trend (last 30 days)
        $userRegistrations = User::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Premium conversions (last 30 days)
        $premiumConversions = User::selectRaw('DATE(updated_at) as date, COUNT(*) as count')
            ->where('premium', true)
            ->where('updated_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Most popular cryptocurrencies in portfolios
        $popularCryptos = PortfolioHolding::select('cryptocurrency_id')
            ->selectRaw('COUNT(DISTINCT user_id) as user_count')
            ->selectRaw('SUM(amount) as total_amount')
            ->with('cryptocurrency:id,name,symbol')
            ->groupBy('cryptocurrency_id')
            ->orderByDesc('user_count')
            ->limit(10)
            ->get();

        // Alert distribution
        $alertTypes = PriceAlert::selectRaw('type, COUNT(*) as count')
            ->where('is_active', true)
            ->groupBy('type')
            ->get();

        return response()->json([
            'user_registrations' => $userRegistrations,
            'premium_conversions' => $premiumConversions,
            'popular_cryptos' => $popularCryptos,
            'alert_types' => $alertTypes
        ]);
    }


    // Cryptocurrency management
    public function cryptocurrencies(Request $request)
    {
        $query = Cryptocurrency::query();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('symbol', 'like', '%' . $request->search . '%');
            });
        }

        // POPRAWKA: Użyj prawidłowych nazw relacji
        $cryptos = $query->withCount([
            'portfolioHoldings',
            'priceAlerts',
            'watchlistedBy as watchlists_count'  // ZMIENIONO Z 'watchlists'
        ])->paginate(50);

        return response()->json($cryptos);
    }


    // System logs/activities (placeholder for future implementation)
    public function systemLogs()
    {
        // This could be implemented with a proper activity log package
        // For now, return recent user activities
        $recentActivities = [
            [
                'type' => 'user_registration',
                'description' => 'New user registered',
                'data' => User::latest()->limit(5)->get(['id', 'name', 'email', 'created_at'])
            ],
            [
                'type' => 'premium_upgrade',
                'description' => 'Premium upgrades',
                'data' => User::where('premium', true)
                    ->latest('updated_at')
                    ->limit(5)
                    ->get(['id', 'name', 'email', 'updated_at'])
            ]
        ];

        return response()->json($recentActivities);
    }
    public function cryptoDetails(Cryptocurrency $cryptocurrency)
    {
        $crypto = $cryptocurrency->load([
            'portfolioHoldings.user:id,name,email',
            'priceAlerts.user:id,name,email',
            'watchlistedBy.user:id,name,email',
            'trendAnalyses' => function ($query) {
                $query->latest()->limit(10);
            }
        ]);

        // Calculate statistics
        $stats = [
            'total_holders' => $crypto->portfolioHoldings->count(),
            'total_amount_held' => $crypto->portfolioHoldings->sum('amount'),
            'total_value_held' => $crypto->portfolioHoldings->sum(function ($holding) {
                return $holding->amount * ($crypto->current_price_pln ?? 0);
            }),
            'average_holding' => $crypto->portfolioHoldings->count() > 0 ?
                $crypto->portfolioHoldings->avg('amount') : 0,

            'active_alerts_count' => $crypto->priceAlerts->where('is_active', true)->count(),
            'total_alerts_count' => $crypto->priceAlerts->count(),
            'alerts_above_count' => $crypto->priceAlerts->where('type', 'above')->count(),
            'alerts_below_count' => $crypto->priceAlerts->where('type', 'below')->count(),

            'watchlist_count' => $crypto->watchlistedBy->count(),
            'watchlist_with_notifications' => $crypto->watchlistedBy->where('notifications_enabled', true)->count(),

            'sentiment_stats' => [
                'current_sentiment' => $crypto->current_sentiment ?? 0,
                'sentiment_change_24h' => $crypto->sentiment_change_24h ?? 0,
                'daily_mentions' => $crypto->daily_mentions ?? 0,
                'trending_score' => $crypto->trending_score ?? 0,
                'last_sentiment_update' => $crypto->sentiment_updated_at?->diffForHumans() ?? 'Never',
            ],

            'price_stats' => [
                'current_price_pln' => $crypto->current_price_pln ?? 0,
                'current_price_usd' => $crypto->current_price_usd ?? 0,
                'price_change_24h' => $crypto->price_change_24h ?? 0,
                'last_price_update' => $crypto->last_updated?->diffForHumans() ?? 'Never',
            ]
        ];

        // Recent trend analyses
        $recentAnalyses = $crypto->trendAnalyses->map(function ($analysis) {
            return [
                'id' => $analysis->id,
                'sentiment_avg' => $analysis->sentiment_avg,
                'mention_count' => $analysis->mention_count,
                'trend_direction' => $analysis->trend_direction,
                'confidence_score' => $analysis->confidence_score,
                'created_at' => $analysis->created_at->diffForHumans(),
                'period_start' => $analysis->analysis_period_start?->format('Y-m-d H:i'),
                'period_end' => $analysis->analysis_period_end?->format('Y-m-d H:i'),
            ];
        });

        // Top holders
        $topHolders = $crypto->portfolioHoldings()
            ->with('user:id,name,email')
            ->orderByDesc('amount')
            ->limit(10)
            ->get()
            ->map(function ($holding) use ($crypto) {
                return [
                    'user' => [
                        'id' => $holding->user->id,
                        'name' => $holding->user->name,
                        'email' => $holding->user->email,
                    ],
                    'amount' => $holding->amount,
                    'average_buy_price' => $holding->average_buy_price,
                    'current_value' => $holding->amount * ($crypto->current_price_pln ?? 0),
                    'profit_loss' => $holding->average_buy_price ?
                        ($holding->amount * ($crypto->current_price_pln ?? 0)) - ($holding->amount * $holding->average_buy_price) : 0,
                ];
            });

        // Recent alerts
        $recentAlerts = $crypto->priceAlerts()
            ->with('user:id,name,email')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($alert) {
                return [
                    'id' => $alert->id,
                    'user' => [
                        'id' => $alert->user->id,
                        'name' => $alert->user->name,
                        'email' => $alert->user->email,
                    ],
                    'type' => $alert->type,
                    'target_price' => $alert->target_price,
                    'currency' => $alert->currency,
                    'is_active' => $alert->is_active,
                    'triggered_at' => $alert->triggered_at?->diffForHumans(),
                    'created_at' => $alert->created_at->diffForHumans(),
                ];
            });

        // Watchlist users
        $watchlistUsers = $crypto->watchlistedBy()
            ->with('user:id,name,email')
            ->latest()
            ->get()
            ->map(function ($watchlist) {
                return [
                    'user' => [
                        'id' => $watchlist->user->id,
                        'name' => $watchlist->user->name,
                        'email' => $watchlist->user->email,
                    ],
                    'notifications_enabled' => $watchlist->notifications_enabled,
                    'added_at' => $watchlist->created_at->diffForHumans(),
                ];
            });

        return response()->json([
            'cryptocurrency' => [
                'id' => $crypto->id,
                'name' => $crypto->name,
                'symbol' => $crypto->symbol,
                'image' => $crypto->image,
                'coingecko_id' => $crypto->coingecko_id,
                'created_at' => $crypto->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $crypto->updated_at->format('Y-m-d H:i:s'),
            ],
            'stats' => $stats,
            'recent_analyses' => $recentAnalyses,
            'top_holders' => $topHolders,
            'recent_alerts' => $recentAlerts,
            'watchlist_users' => $watchlistUsers,
        ]);
    }

    /**
     * Update cryptocurrency data manually (for admin)
     */
    public function updateCrypto(Request $request, Cryptocurrency $cryptocurrency)
    {
        $request->validate([
            'current_price_pln' => 'nullable|numeric|min:0',
            'current_price_usd' => 'nullable|numeric|min:0',
            'price_change_24h' => 'nullable|numeric',
            'current_sentiment' => 'nullable|numeric|between:-1,1',
            'daily_mentions' => 'nullable|integer|min:0',
            'trending_score' => 'nullable|integer|between:0,100',
        ]);

        $updateData = array_filter($request->only([
            'current_price_pln',
            'current_price_usd',
            'price_change_24h',
            'current_sentiment',
            'daily_mentions',
            'trending_score'
        ]), function ($value) {
            return $value !== null;
        });

        if (!empty($updateData)) {
            $updateData['last_updated'] = now();
            if (isset($updateData['current_sentiment']) || isset($updateData['daily_mentions'])) {
                $updateData['sentiment_updated_at'] = now();
            }

            $cryptocurrency->update($updateData);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cryptocurrency updated successfully',
            'cryptocurrency' => $cryptocurrency->fresh()
        ]);
    }
}
