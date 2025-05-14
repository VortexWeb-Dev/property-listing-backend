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
            "name" => "required|string",
            "email" => "required|email|unique:developers",
            "phone" => "required|string|unique:developers",
            "website" => "nullable|url",
        ]);

        return response()->json(Developer::create($validated), 201);
    }

    public function show($developer_id)
    {
        $developer = Developer::find($developer_id);

        if (!$developer) {
            return response()->json(
                ["message" => "Developer not found with this ID"],
                404
            );
        }

        return response()->json([
            "message" => "Developer retrieved successfully",
            "data" => $developer,
        ]);
    }

    public function update(Request $request, $developer_id)
    {
        $developer = Developer::find($developer_id);

        if (!$developer) {
            return response()->json(
                ["error" => "Data not found with this id"],
                404
            );
        }

        $validatedData = $request->validate([
            "name" => "string",
            "email" => "email|unique:developers,email," . $developer_id,
            "phone" => "string",
            "website" => "nullable|url",
        ]);

        $developer->update($validatedData);

        return response()->json([
            "message" => "Developer updated successfully.",
            "data" => $developer,
        ]);
    }

    public function destroy(Request $request)
    {
        $ids = $request->input('ids');
    
        if (!is_array($ids) || empty($ids)) {
            return response()->json([
                "message" => "Please provide an array of developer IDs."
            ], 400);
        }
    
        // Optional: Validate all are numeric
        foreach ($ids as $id) {
            if (!is_numeric($id)) {
                return response()->json([
                    "message" => "Invalid ID in array: $id"
                ], 400);
            }
        }
    
        // Get existing IDs from DB
        $existingIds = Developer::whereIn('id', $ids)->pluck('id')->toArray();
    
        // Check if all requested IDs exist
        $missingIds = array_diff($ids, $existingIds);
    
        if (!empty($missingIds)) {
            return response()->json([
                "message" => "Some IDs do not exist in the database.",
                "missing_ids" => array_values($missingIds)
            ], 404);
        }
    
        // All IDs found, proceed to delete
        Developer::whereIn('id', $ids)->delete();
    
        return response()->json([
            "message" => "Developer(s) deleted successfully.",
            "deleted_ids" => $ids
        ]);
    }


        public function bulkUploadDevelopers(Request $request)
    {
        $request->validate([
            "csv_file" => "required|file|mimes:csv,txt",
        ]);

        $file = $request->file("csv_file");
        $csvData = array_map("str_getcsv", file($file));

        $headers = array_map("trim", array_shift($csvData));
        $requiredHeaders = ["Name", "Email", "Phone", "Website"];

        if (array_diff($requiredHeaders, $headers)) {
            return response()->json([
                "status" => "error",
                "message" => "CSV must include headers: " . implode(", ", $requiredHeaders),
            ], 422);
        }

        $insertData = [];
        $skippedEmails = [];

        foreach ($csvData as $index => $row) {
            $rowData = array_combine($headers, $row);

            if (empty($rowData["Email"])) {
                return response()->json([
                    "status" => "error",
                    "message" => "Row " . ($index + 2) . " is missing required 'Email' field.",
                ], 422);
            }

            $email = trim($rowData["Email"]);

            // Skip if email already exists in DB
            if (Developer::where("email", $email)->exists()) {
                $skippedEmails[] = $email;
                continue;
            }

            // Handle phone format
            $rawPhone = $rowData["Phone"] ?? null;
            $phone = trim((string)$rawPhone);

            if (preg_match('/e\+?/i', $phone)) {
                return response()->json([
                    'status' => 'error',
                    'message' => "The phone number in row " . ($index + 2) . " is in wrong format."
                ], 422);
            }

            $insertData[] = [
                "name" => $rowData["Name"] ?? null,
                "email" => $email,
                "phone" => $phone,
                "website" => $rowData["Website"] ?? null,
                "created_at" => now(),
                "updated_at" => now(),
            ];
        }

        if (!empty($insertData)) {
            // Remove duplicates within CSV
            $uniqueData = [];
            $seenEmails = [];

            foreach ($insertData as $row) {
                if (!in_array($row['email'], $seenEmails)) {
                    $seenEmails[] = $row['email'];
                    $uniqueData[] = $row;
                } else {
                    $skippedEmails[] = $row['email'];
                }
            }

            try {
                Developer::insert($uniqueData);
            } catch (\Illuminate\Database\QueryException $e) {
                preg_match("/Duplicate entry '([^']+)'/", $e->getMessage(), $matches);
                $duplicateEmail = $matches[1] ?? null;

                return response()->json([
                    'status' => 'error',
                    'message' => 'Some developers could not be imported due to duplicate entries.',
                    'details' => $duplicateEmail
                        ? "The email '$duplicateEmail' already exists in the system."
                        : 'Duplicate entry found.',
                    'suggestion' => 'Please check your CSV file and remove or correct duplicate entries.',
                    'error_code' => 1062,
                ], 409);
            } catch (\Exception $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'An unexpected error occurred while importing developers.',
                    'error' => $e->getMessage(),
                ], 500);
            }
        }

        return response()->json([
            "status" => "success",
            "message" => "Developers imported successfully.",
            "inserted" => isset($uniqueData) ? count($uniqueData) : 0,
            "skipped" => count($skippedEmails),
            "skipped_emails" => array_values(array_unique($skippedEmails)),
        ]);
    }

    
}