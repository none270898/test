<?php

namespace App\Http\Controllers;

use App\Models\Cryptocurrency;
use App\Models\Portfolio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PortfolioController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $portfolios = auth()->user()->portfolios()->with('cryptocurrency')->get();
        $cryptocurrencies = Cryptocurrency::orderBy('name')->get();
        
        return view('portfolio.index', compact('portfolios', 'cryptocurrencies'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cryptocurrency_id' => 'required|exists:cryptocurrencies,id',
            'amount' => 'required|numeric|min:0.00000001',
            'average_buy_price' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $portfolio = Portfolio::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'cryptocurrency_id' => $request->cryptocurrency_id,
            ],
            [
                'amount' => $request->amount,
                'average_buy_price' => $request->average_buy_price,
            ]
        );

        return response()->json([
            'success' => true,
            'portfolio' => $portfolio->load('cryptocurrency')
        ]);
    }

    public function update(Request $request, Portfolio $portfolio)
    {
        $this->authorize('update', $portfolio);

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.00000001',
            'average_buy_price' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $portfolio->update($request->only(['amount', 'average_buy_price']));

        return response()->json([
            'success' => true,
            'portfolio' => $portfolio->load('cryptocurrency')
        ]);
    }

    public function destroy(Portfolio $portfolio)
    {
        $this->authorize('delete', $portfolio);
        
        $portfolio->delete();

        return response()->json(['success' => true]);
    }
}