<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\Validator;

class PresignedController extends Controller
{
    public function getPresignedUrl(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'fileName' => 'required|string',
            'fileType' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid input', 'details' => $validator->errors()], 422);
        }

        $fileName = $request->fileName;
        $fileType = $request->fileType;

        try {
            $s3Client = new S3Client([
                'region' => env('AWS_DEFAULT_REGION'),
                'version' => 'latest',
                'credentials' => [
                    'key' => env('AWS_ACCESS_KEY_ID'),
                    'secret' => env('AWS_SECRET_ACCESS_KEY'),
                ],
            ]);

            $bucket = env('AWS_BUCKET');
            $key = 'uploads/' . $fileName; // folder + filename

            $cmd = $s3Client->getCommand('PutObject', [
                'Bucket' => $bucket,
                'Key' => $key,
                'ContentType' => $fileType,
            ]);

            // Generate the presigned URL - expires in 10 minutes
            $request = $s3Client->createPresignedRequest($cmd, '+10 minutes');

            $presignedUrl = (string) $request->getUri();
            $objectUrl = "https://{$bucket}.s3.amazonaws.com/{$key}";

            return response()->json([
                'uploadUrl' => $presignedUrl,
                'fileUrl' => $objectUrl
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to generate URL', 'message' => $e->getMessage()], 500);
        }
    }
}