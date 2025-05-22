<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Listing;
use App\Models\Company;

class XmlController extends Controller
{
    public function propertyFinder($slug)
    {
        $company = Company::where('slug', $slug)->firstOrFail();
    
        $listings = Listing::with(['pfAgent', 'agent']) // Eager load to avoid N+1
            ->where('status', 'published')
            ->where('pf_enable', true)
            ->where('company_id', $company->id)
            ->get();
    
        $channel = 'propertyfinder';
        $xml = view('xml.propertyfinder', compact('listings', 'channel'))->render();
    
        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
    

    public function bayutDubizzle($slug)
    {
        $company = Company::where('slug', $slug)->firstOrFail();
    
        $listings = Listing::with(['bayutDubizzleAgent', 'agent'])
            ->where('status', 'published')
            ->where('company_id', $company->id)
            ->where(function ($q) {
                $q->where('bayut_enable', true)->orWhere('dubizzle_enable', true);
            })->get();
    
        $channel = 'bayut_dubizzle';
        $xml = view('xml.bayut_dubizzle', compact('listings', 'channel'))->render();
    
        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
    


    public function website($slug)
    {
        $company = Company::where('slug', $slug)->firstOrFail();
    
        $listings = Listing::with(['websiteAgent', 'agent'])
            ->where('status', 'published')
            ->where('website_enable', true)
            ->where('company_id', $company->id)
            ->get();
    
        $channel = 'website';
        $xml = view('xml.website', compact('listings', 'channel'))->render();
    
        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
    

}