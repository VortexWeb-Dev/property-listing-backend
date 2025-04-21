<?php

namespace App\Http\Controllers;

use App\Models\Developer;
use Illuminate\Http\Request;

class DeveloperController extends Controller
{

    public function index()
    {
        return response()->json(Developer::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:developers',
            'phone' => 'required|string',
            'website' => 'nullable|url',
        ]);

        return response()->json(Developer::create($validated), 201);
    }

    public function show($developer_id)
    {
        $developer = Developer::find($developer_id);

        if (!$developer) {
            return response()->json(['message' => 'Developer not found with this ID'], 404);
        }

        return response()->json([
            'message' => 'Developer retrieved successfully',
            'data' => $developer,
        ]);
    }


    public function update(Request $request, $developer_id)
    {

        $developer = Developer::find($developer_id);

        if (!$developer) {
            return response()->json(['error' => 'Data not found with this id'], 404);
        }

        $validatedData = $request->validate([
            'name' => 'string',
            'email' => 'email|unique:developers,email,' . $developer_id,
            'phone' => 'string',
            'website' => 'nullable|url',
        ]);

        $developer->update($validatedData);

        return response()->json([
            'message' => 'Developer updated successfully.',
            'data' => $developer,
        ]);
    }

    public function destroy($developer_id)
    {

        $developer = Developer::find($developer_id);

        if (!$developer) 
        {
            return response()->json(['message' => 'Developer not found with this ID'], 404);
        }


        $developer->delete();

        return response()->json(['message' => 'Developer deleted successfully']);
    }


    public function bulkUploadDevelopers(Request $request)
    {
       
    $request->validate([
        'csv_file' => 'required|file|mimes:csv,txt',
    ]);

    $file = $request->file('csv_file');
    $csvData = array_map('str_getcsv', file($file));
    
    $headers = array_map('trim', array_shift($csvData));
    $requiredHeaders = ['Name', 'Email', 'Phone', 'Website'];

    // Validate headers
    if (array_diff($requiredHeaders, $headers)) 
    {
        return response()->json([
            'status' => 'error',
            'message' => 'CSV must include headers: ' . implode(', ', $requiredHeaders)
        ], 422);
    }

    $insertData = [];
    foreach ($csvData as $index => $row) {
        $rowData = array_combine($headers, $row);

        // Validate each row
        if (empty($rowData['Email'])) {
            return response()->json([
                'status' => 'error',
                'message' => "Row " . ($index + 2) . " is missing required 'Email' field."
            ], 422);
        }

        $insertData[] = [
            'name' => $rowData['Name'] ?? null,
            'email' => $rowData['Email'],
            'phone' => $rowData['Phone'] ?? null,
            'website' => $rowData['Website'] ?? null,
            'created_at' => now(),
            'updated_at' => now()
        ];
    }

    Developer::insert($insertData);

    return response()->json([
        'status' => 'success',
        'message' => 'Developers imported successfully.'
    ]);
    }
}