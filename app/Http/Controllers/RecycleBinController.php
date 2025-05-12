<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecycleBinController extends Controller
{
    // Read all deleted listings with optional filters
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'super_admin') {
            return response()->json(['error' => 'Unauthorized. Super admin access only.'], 403);
        }
        
        $query = Listing::where('status', 'deleted');

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('updated_at', [$request->from_date, $request->to_date]);
        }

        $listings = $query->with('company')->orderBy('updated_at', 'desc')->get();

        return response()->json($listings);
    }

    // Restore a deleted listing
    public function restore($id)
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'super_admin') {
            return response()->json(['error' => 'Unauthorized. Super admin access only.'], 403);
        }
        
        $listing = Listing::where('status', 'deleted')->findOrFail($id);
        $listing->status = 'live';
        $listing->save();

        return response()->json(['message' => 'Listing restored successfully.']);
    }

    // Permanently delete a listing
    public function destroy($id)
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'super_admin') {
            return response()->json(['error' => 'Unauthorized. Super admin access only.'], 403);
        }
        
        $listing = Listing::where('status', 'deleted')->findOrFail($id);
        $listing->delete();

        return response()->json(['message' => 'Listing permanently deleted.']);
    }
}