<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
        public function index(Request $request)
    {
        $type = $request->query('type');
        $search = $request->query('search');

        // Enforce required 'type' filter (either 'pf' or 'bayut')
        if (!$type || !in_array($type, ['pf', 'bayut'])) {
            return response()->json([
                'error' => 'The type parameter is required and must be either "pf" or "bayut".'
            ], 422);
        }

        $locationsQuery = Location::where('type', $type);

        // Optional search by 'location' column
        if ($search) {
            $locationsQuery->where('location', 'like', '%' . $search . '%');
        }

        // Apply pagination and append query strings to pagination URLs
        $locations = $locationsQuery->paginate(50)->appends($request->query());

        return response()->json($locations);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            "city" => "required|string",
            "community" => "required|string",
            "sub_community" => "nullable|string",
            "building" => "nullable|string",
            "location" => "required|string",
            "type" => "required|string|in:pf,bayut",
        ]);

        return response()->json(Location::create($validated), 201);
    }

    public function show($location_id)
    {
        $location = Location::find($location_id);

        if (!$location) {
            return response()->json(["message" => "Location not found"], 404);
        }

        return response()->json($location);
    }

    public function update(Request $request, $location_id)
    {
        $location = Location::find($location_id);

        if (!$location) {
            return response()->json(["message" => "Location not found"], 404);
        }

        $validated = $request->validate([
            "city" => "sometimes|string",
            "community" => "sometimes|string",
            "sub_community" => "nullable|string",
            "building" => "nullable|string",
            "location" => "sometimes|string",
            "type" => "sometimes|string|in:pf,bayut",
        ]);

        $location->update($validated);

        return response()->json([
            "message" => "Location updated successfully.",
            "data" => $location,
        ]);
    }


    public function destroy(Request $request)
    {
        $ids = $request->input('ids');

        // Validate input
        if (!is_array($ids) || empty($ids)) {
            return response()->json([
                "message" => "Please provide a non-empty array of location IDs."
            ], 400);
        }

        foreach ($ids as $id) {
            if (!is_numeric($id)) {
                return response()->json([
                    "message" => "Invalid ID in array: $id"
                ], 400);
            }
        }

        // Find existing locations
        $locations = Location::whereIn('id', $ids)->get();
        $existingIds = $locations->pluck('id')->toArray();
        $missingIds = array_diff($ids, $existingIds);

        if (!empty($missingIds)) {
            return response()->json([
                "message" => "Some location IDs were not found.",
                "missing_ids" => array_values($missingIds)
            ], 404);
        }

        // Delete locations
        Location::whereIn('id', $existingIds)->delete();

        return response()->json([
            "message" => "Location(s) deleted successfully.",
            "deleted_ids" => $existingIds
        ]);
    }


    public function bulkUploadLocations(Request $request)
    {
        $request->validate([
            "csv_file" => "required|file|mimes:csv,txt",
        ]);
    
        $file = $request->file("csv_file");
        $path = $file->getRealPath();
    
        $handle = fopen($path, 'r');
        if (!$handle) {
            return response()->json(['status' => 'error', 'message' => 'Could not open the file.'], 500);
        }
    
        $headers = fgetcsv($handle);
        $headers = array_map('trim', $headers);
    
        $requiredHeaders = [
            "City",
            "Community",
            "Sub Community",
            "Building",
            "Location",
            "Type",
        ];
    
        if (array_diff($requiredHeaders, $headers)) {
            fclose($handle);
            return response()->json([
                "status" => "error",
                "message" => "CSV must include headers: " . implode(", ", $requiredHeaders),
            ], 422);
        }
    
        $insertData = [];
        $rowIndex = 1; // Header is row 1
        $skippedRows = [];
    
        while (($row = fgetcsv($handle)) !== false) {
            $rowIndex++;
    
            if (count($row) != count($headers)) {
                $skippedRows[] = $rowIndex;
                continue; // Skip rows with wrong number of columns
            }
    
            $rowData = array_combine($headers, $row);
    
            // Validate required fields
            $missingFields = [];
            foreach (["City", "Community", "Location", "Type"] as $requiredField) {
                if (empty($rowData[$requiredField])) {
                    $missingFields[] = $requiredField;
                }
            }
    
            if (!empty($missingFields)) {
                $skippedRows[] = $rowIndex;
                continue; // Skip invalid row
            }
    
            // Validate 'Type'
            $type = strtolower(trim($rowData["Type"]));
            if (!in_array($type, ["pf", "bayut"])) {
                $skippedRows[] = $rowIndex;
                continue; // Skip invalid row
            }
    
            $insertData[] = [
                "city" => $rowData["City"],
                "community" => $rowData["Community"],
                "sub_community" => $rowData["Sub Community"] ?? null,
                "building" => $rowData["Building"] ?? null,
                "location" => $rowData["Location"],
                "type" => $type,
                "created_at" => now(),
                "updated_at" => now(),
            ];
    
            if (count($insertData) >= 1000) {
                Location::insert($insertData);
                $insertData = [];
            }
        }
    
        fclose($handle);
    
        // Insert remaining rows
        if (!empty($insertData)) {
            Location::insert($insertData);
        }
    
        return response()->json([
            "status" => "success",
            "message" => "Locations imported successfully.",
            "skipped_rows" => count($skippedRows),
        ]);
    }

}