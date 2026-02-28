<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * @method static void middleware(string|array $middleware, array $options = [])
 */
class BillplzController extends Controller
{
    public function createBill(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string',
            'classroom_name' => 'required|string',
            'classroom_code' => 'required|string|unique:class_rooms,code',
            'classroom_description' => 'required|string',
        ]);

        $apiKey = env('BILLPLZ_API_KEY');
        $collectionId = env('BILLPLZ_COLLECTION_ID');
        $url = env('BILLPLZ_SANDBOX_URL') . '/bills';

        $response = Http::withBasicAuth($apiKey, '')
            ->asForm()
            ->post($url, [
                'collection_id' => $collectionId,
                'email' => $validated['email'],
                'name' => $validated['name'],
                'amount' => $validated['amount'] * 100,
                'reference_1_label' => 'Classroom Detail',
                'reference_1' => json_encode([
                    'name' => $validated['classroom_name'],
                    'code' => $validated['classroom_code'],
                    'description' => $validated['classroom_description'],
                    'educator_id' => User::where('email', $validated['email'])->first()?->id,
                ]),
                'redirect_url' => route('api.v1.billplz.redirect'),
                'callback_url' => route('api.v1.billplz.callback'),
                'description' => $validated['description'],
            ]);

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create bill',
                'error' => $response->json(),
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $response->json(),
        ]);
    }

    public function getBill($billId)
    {
        $apiKey = env('BILLPLZ_API_KEY');
        $url = env('BILLPLZ_SANDBOX_URL') . '/bills/' . $billId;

        $response = Http::withBasicAuth($apiKey, '')
            ->get($url);

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve bill',
                'error' => $response->json(),
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $response->json(),
        ]);
    }

    public function redirect(Request $request)
    {
        $data = $request->all()['billplz'];

        $billStatus = filter_var($data['paid'], FILTER_VALIDATE_BOOLEAN);
        $billId = $data['id'];

        $apiKey = env('BILLPLZ_API_KEY');
        $url = env('BILLPLZ_SANDBOX_URL') . '/bills/' . $billId;

        $response = Http::withBasicAuth($apiKey, '')->get($url);

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve bill',
                'error' => $response->json(),
            ], 400);
        }

        if ($billStatus === true) {
            
            $data = $response->json();
            $classroomData = json_decode($data['reference_1'], true);

            ClassRoom::create([
                'name' => $classroomData['name'],
                'code' => $classroomData['code'],
                'description' => $classroomData['description'],
                'educator_id' => $classroomData['educator_id'],
                'metadata' => $response->json(),
            ]);

            $assignUser = User::find($classroomData['educator_id']);
            if ($assignUser) {
                $assignUser->assignRole('educator');
            }

            return redirect(env('APP_VUE_APP_URL') . '/protected/onboarding/educator/redirect?status=success');
        }

        if ($billStatus === false) {
            return redirect(env('APP_VUE_APP_URL') . '/protected/onboarding/educator/redirect?status=failed');
        }

        return redirect(env('APP_VUE_APP_URL') . '/protected/onboarding/educator/redirect?status=unknown');
    }

    public function callback(Request $request)
    {
        Log::info('Billplz Callback:', $request->all());

        return response()->json(['success' => true]);
    }
}
