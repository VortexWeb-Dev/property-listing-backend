<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Listing;

class XmlController extends Controller
{
    public function propertyFinder()
    {
        $listings = Listing::where('status', 'published')->where('pf_enable', true)->get();

        $xml = view('xml.propertyfinder', compact('listings'))->render();

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    public function bayutDubizzle()
    {
        $listings = Listing::where('status', 'published')
            ->where(function ($q) {
                $q->where('bayut_enable', true)->orWhere('dubizzle_enable', true);
            })->get();

        $xml = view('xml.bayut_dubizzle', compact('listings'))->render();

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    public function website()
    {
        $listings = Listing::where('status', 'published')->where('website_enable', true)->get();

        $xml = view('xml.website', compact('listings'))->render();

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}