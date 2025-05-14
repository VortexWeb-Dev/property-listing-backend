<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Listing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ListingActionController extends Controller
{
        public function handleAction(Request $request)
        {
            if (Auth::user()->role === "agent") {
                return response()->json(['error' => 'You are not allowed to perform this action'], 403);
            }
        
            $request->validate([
                'action' => 'required|string',
                'propertyId' => 'required|array',
                'propertyId.*' => 'integer|exists:listings,id',
            ]);
        
            $listings = Listing::whereIn('id', $request->propertyId)->get();
        
            $manualStatusActions = ['archived', 'draft', 'live', 'pocket', 'deleted'];
        
            foreach ($listings as $listing) {
                switch ($request->action) {
                    case 'publish_pf':
                        $listing->pf_enable = true;
                        break;
                    case 'publish_bayut':
                        $listing->bayut_enable = true;
                        break;
                    case 'publish_dubizzle':
                        $listing->dubizzle_enable = true;
                        break;
                    case 'publish_website':
                        $listing->website_enable = true;
                        break;
                    case 'publish_all':
                        $listing->pf_enable = true;
                        $listing->bayut_enable = true;
                        $listing->dubizzle_enable = true;
                        $listing->website_enable = true;
                        break;
                    case 'unpublish_pf':
                        $listing->pf_enable = false;
                        break;
                    case 'unpublish_bayut':
                        $listing->bayut_enable = false;
                        break;
                    case 'unpublish_dubizzle':
                        $listing->dubizzle_enable = false;
                        break;
                    case 'unpublish_website':
                        $listing->website_enable = false;
                        break;
                    case 'unpublish_all':
                        $listing->pf_enable = false;
                        $listing->bayut_enable = false;
                        $listing->dubizzle_enable = false;
                        $listing->website_enable = false;
                        break;
                    case 'archived':
                    case 'draft':
                    case 'live':
                    case 'pocket':
                    case 'deleted':
                        $listing->status = $request->action;
                        break;
                    default:
                        return response()->json(['error' => 'Invalid action'], 400);
                }
        
                // Only update status based on enables if not a manual status action
                if (!in_array($request->action, $manualStatusActions)) {
                    $listing->status = (
                        $listing->pf_enable ||
                        $listing->bayut_enable ||
                        $listing->dubizzle_enable ||
                        $listing->website_enable
                    ) ? 'published' : 'unpublished';
                }
        
                $listing->save();
            }
        
            return response()->json([
                'status' => 'success',
                'action_performed' => $request->action,
                'updated_listings' => $listings
            ]);
        }
    


public function agentbulktransfer(Request $request)
{
    
    $request->validate([
        'action' => 'required|string',
        'propertyIds' => 'required|array',
        'propertyIds.*' => 'integer|exists:listings,id',
    ]);

    $user = Auth::user(); // Make sure user is authenticated
    
  
 
    if (!$user || $user->role !== "agent") {
        return response()->json(['error' => 'Unauthorized or missing role'], 403);
    }

    if (!$user->company_id) {
        return response()->json(['error' => 'Unauthorized or missing company ID'], 403);
    }

    if ($request->action === 'transfer_agent') {
        $request->validate([
            'agent_id' => 'required|integer|exists:users,id',
        ]);

        // Validate that target agent exists and belongs to same company
        $targetAgent = User::where('id', $request->agent_id)
            ->where('company_id', $user->company_id)
            ->where('role', 'agent')
            ->first();

        if (!$targetAgent) {
            return response()->json(['error' => 'Target agent does not exist or does not belong to the this company'], 403);
        }

        // Fetch listings that belong to this company and match provided property IDs
        $listings = Listing::whereIn('id', $request->propertyIds)
            ->where('agent_id', $user->id)
            ->whereHas('agent', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })
            ->get();

        if ($listings->isEmpty()) {
            return response()->json(['error' => 'No valid listings found for transfer'], 404);
        }

        foreach ($listings as $listing) {
            $listing->agent_id = $request->agent_id;
            $listing->save();
        }

        return response()->json([
            'message' => 'Listings transferred successfully',
            'transferred_listing_ids' => $listings->pluck('id'),
        ]);
    }


    // transfer owner

    if ($request->action === 'transfer_owner') {
        $request->validate([
            'propertyIds' => 'required|array',
            'propertyIds.*' => 'exists:listings,id',
            'owner_id' => 'required|exists:users,id',
        ]);
    
        $user = auth()->user();
    
        // Ensure current user is an agent and has a company
        if ($user->role !== 'agent' || !$user->company_id) {
            return response()->json(['error' => 'Unauthorized or missing company.'], 403);
        }
    
        // Verify the target owner belongs to the same company and has 'owner' role
        $targetOwner = \App\Models\User::where('id', $request->owner_id)
            ->where('company_id', $user->company_id)
            ->where('role', 'owner')
            ->first();
    
        if (!$targetOwner) {
            return response()->json(['error' => 'Invalid owner or not part of your company.'], 403);
        }
    
        // Update listings
        Listing::whereIn('id', $request->propertyIds)->update([
            'owner_id' => $request->owner_id
        ]);
    
        return response()->json(['message' => 'Ownership transferred successfully.']);
    }
    


}

 
}