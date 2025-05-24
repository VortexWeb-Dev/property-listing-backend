<?php

namespace App\Http\Controllers;
use App\Models\Lead;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index()
    {
        return response()->json(Lead::with(['responsible', 'company'])->get());
    }

    public function show($id)
    {
        $lead = Lead::with(['responsible', 'company'])->findOrFail($id);
        return response()->json($lead);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'client_name' => 'required|string',
            'client_email' => 'required|email',
            'client_phone' => 'required|string',
            'property_reference' => 'required|string',
            'property_link' => 'required|url',
            'tracking_link' => 'required|url',
            'comment' => 'nullable|string',
            'responsible_person' => 'required|exists:users,id',
            'company_id' => 'required|exists:companies,id',
            'source' => 'required|in:PF Email,PF WhatsApp,PF Call,Bayut Email,Bayut WhatsApp,Bayut Call,Dubizzle Email,Dubizzle WhatsApp,Dubizzle Call,Website Form',
            'stage' => 'required|in:New,In Progress,Contacted,Success,Fail',
        ]);

        $lead = Lead::create($validated);
        return response()->json($lead, 201);
    }

    public function update(Request $request, $id)
    {
        $lead = Lead::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string',
            'client_name' => 'sometimes|string',
            'client_email' => 'sometimes|email',
            'client_phone' => 'sometimes|string',
            'property_reference' => 'sometimes|string',
            'property_link' => 'sometimes|url',
            'tracking_link' => 'sometimes|url',
            'comment' => 'nullable|string',
            'responsible_person' => 'sometimes|exists:users,id',
            'company_id' => 'sometimes|exists:companies,id',
            'source' => 'sometimes|in:PF Email,PF WhatsApp,PF Call,Bayut Email,Bayut WhatsApp,Bayut Call,Dubizzle Email,Dubizzle WhatsApp,Dubizzle Call,Website Form',
            'stage' => 'sometimes|in:New,In Progress,Contacted,Success,Fail',
        ]);

        $lead->update($validated);
        return response()->json($lead);
    }

    public function destroy($id)
    {
        $lead = Lead::findOrFail($id);
        $lead->delete();
        return response()->json(['message' => 'Lead deleted']);
    }
}