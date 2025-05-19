<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Models\Listing;
use Illuminate\Support\Facades\DB;


class ListingAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        if (Gate::denies('view.analytics')) {
            return response()->json(['message' => 'Permission Denied For This Action'], 403);
        }

        // Status counts
        $statusCounts = Listing::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        // Platform counts
        $platformCounts = [
            'property_finder' => Listing::where('pf_enable', true)->count(),
            'bayut' => Listing::where('bayut_enable', true)->count(),
            'dubizzle' => Listing::where('dubizzle_enable', true)->count(),
            'website' => Listing::where('website_enable', true)->count(),
        ];

        // Category counts - assuming 'property_type' contains values like 'residential', 'commercial'
        $categoryCounts = Listing::select(
                DB::raw("CASE 
                    WHEN property_type IN ('Apartment', 'Villa', 'Townhouse') THEN 'Residential'
                    WHEN property_type IN ('Office', 'Shop', 'Warehouse') THEN 'Commercial'
                    ELSE 'Other' END AS category"),
                DB::raw('count(*) as total')
            )
            ->groupBy('category')
            ->pluck('total', 'category');

        // Purpose counts (offering_type = Sale / Rent)
        $purposeCounts = Listing::select('offering_type', DB::raw('count(*) as total'))
            ->groupBy('offering_type')
            ->pluck('total', 'offering_type');

        // Top 5 Property Types
        $topPropertyTypes = Listing::select('property_type', DB::raw('count(*) as total'))
            ->groupBy('property_type')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return response()->json([
            'status_counts' => $statusCounts,
            'platform_counts' => $platformCounts,
            'category_counts' => $categoryCounts,
            'purpose_counts' => $purposeCounts,
            'top_property_types' => $topPropertyTypes,
        ]);
    }
}