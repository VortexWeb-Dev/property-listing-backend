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
            "name" => "sometimes|string",
            "email" => "sometimes|email|unique:developers,email," . $developer_id,
            "phone" => "sometimes|string",
            "website" => "sometimes|nullable|url",
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
        $path = $file->getRealPath();
    
        $handle = fopen($path, 'r');
        if (!$handle) {
            return response()->json([
                'status' => 'error',
                'message' => 'Could not open the CSV file.',
            ], 500);
        }
    
        $headers = fgetcsv($handle);
        $headers = array_map('trim', $headers);
        $requiredHeaders = ["Name", "Email", "Phone", "Website"];
    
        if (array_diff($requiredHeaders, $headers)) {
            fclose($handle);
            return response()->json([
                "status" => "error",
                "message" => "CSV must include headers: " . implode(", ", $requiredHeaders),
            ], 422);
        }
    
        $insertData = [];
        $seenEmails = [];
        $skippedEmails = [];
        $rowIndex = 1;
    
        while (($row = fgetcsv($handle)) !== false) {
            $rowIndex++;
    
            if (count($row) != count($headers)) {
                $skippedEmails[] = "Row $rowIndex (column mismatch)";
                continue;
            }
    
            $rowData = array_combine($headers, $row);
            $email = trim($rowData["Email"]);
    
            if (empty($email)) {
                $skippedEmails[] = "Row $rowIndex (missing email)";
                continue;
            }
    
            // Check if already in DB
            if (Developer::where("email", $email)->exists() || in_array($email, $seenEmails)) {
                $skippedEmails[] = "Row $rowIndex ($email duplicate)";
                continue;
            }
    
            $rawPhone = $rowData["Phone"] ?? null;
            $phone = trim((string)$rawPhone);
    
            if (preg_match('/e\+?/i', $phone)) {
                $skippedEmails[] = "Row $rowIndex (invalid phone format)";
                continue;
            }
    
            $insertData[] = [
                "name" => $rowData["Name"] ?? null,
                "email" => $email,
                "phone" => $phone,
                "website" => $rowData["Website"] ?? null,
                "created_at" => now(),
                "updated_at" => now(),
            ];
    
            $seenEmails[] = $email;
    
            if (count($insertData) >= 1000) {
                Developer::insert($insertData);
                $insertData = [];
            }
        }
    
        fclose($handle);
    
        if (!empty($insertData)) {
            Developer::insert($insertData);
        }
    
        return response()->json([
            "status" => "success",
            "message" => "Developers imported successfully.",
            "inserted" => count($seenEmails),
            "skipped" => count($skippedEmails),
            // "skipped_details" => $skippedEmails,
        ]);
    }

    
}