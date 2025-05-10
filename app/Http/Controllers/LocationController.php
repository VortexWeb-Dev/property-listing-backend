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

    public function destroy($location_id)
    {
        $location = Location::find($location_id);

        if (!$location) {
            return response()->json(["message" => "Location not found"], 404);
        }

        $location->delete();

        return response()->json(["message" => "Location deleted successfully"]);
    }

    public function bulkUploadLocations(Request $request)
    {
        $request->validate([
            "csv_file" => "required|file|mimes:csv,txt",
        ]);

        $file = $request->file("csv_file");
        $csvData = array_map("str_getcsv", file($file));

        $headers = array_map("trim", array_shift($csvData));
        $requiredHeaders = [
            "City",
            "Community",
            "Sub Community",
            "Building",
            "Location",
            "Type",
        ];

        if (array_diff($requiredHeaders, $headers)) {
            return response()->json(
                [
                    "status" => "error",
                    "message" =>
                        "CSV must include headers: " .
                        implode(", ", $requiredHeaders),
                ],
                422
            );
        }

        $insertData = [];

        foreach ($csvData as $index => $row) {
            $rowData = array_combine($headers, $row);

            // Check required fields
            $missingFields = [];
            foreach (
                ["City", "Community", "Location", "Type"]
                as $requiredField
            ) {
                if (empty($rowData[$requiredField])) {
                    $missingFields[] = $requiredField;
                }
            }

            if (!empty($missingFields)) {
                return response()->json(
                    [
                        "status" => "error",
                        "message" =>
                            "Row " .
                            ($index + 2) .
                            " is missing fields: " .
                            implode(", ", $missingFields),
                    ],
                    422
                );
            }

            // Validate 'Type'
            $type = strtolower(trim($rowData["Type"]));
            if (!in_array($type, ["pf", "bayut"])) {
                return response()->json(
                    [
                        "status" => "error",
                        "message" =>
                            "Row " .
                            ($index + 2) .
                            ": 'Type' must be 'pf' or 'bayut'.",
                    ],
                    422
                );
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
        }

        Location::insert($insertData);

        return response()->json([
            "status" => "success",
            "message" => "Locations imported successfully.",
        ]);
    }
}