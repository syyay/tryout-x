<?php

namespace App\Http\Controllers;

use App\Models\Major;
use App\Models\University;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class UniversitasController extends Controller
{
    public function getUniversity():Response
    {
        try {
            $unv = University::select(['university_name', 'university_id'])->get();
            
            return response([
                'status' => True,
                'message' => 'get university successfully',
                'data' => $unv
            ]);
        } catch (\Throwable $th) {
            return Response([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function getMajors():Response
    {
        try {
            $maj = Major::select('university_id', 'major_name')->get();

            return response([
                'status' => true,
                'message' => 'getmajor successfully',
                'data' => $maj
            ]);
        } catch (\Throwable $th) {
            return Response([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function postUnivMaj (Request $request):Response
    {
        try {
            $validate = Validator::make($request->all(), [
                "university1" => "required",
                "major1" => "required",
                "university2" => "required",
                "major2" => "required",
            ]);
            if ($validate->fails()) {
                return response([
                    'status' => false,
                    'message' => 'validation_error',
                    'errors' => $validate->errors()
                    ],401);
                    }

                    $user = $request->user();

                    $user->update([
                        'university1' => $request->university1,
                        'major1' => $request->major1,
                        'university2' => $request->university2,
                        'major2' => $request->major2,
                    ]);

                    return response([
                        'status' => true,
                        'message' => 'University created successfully'
                    ], 200);

        } catch (\Throwable $th) {
            return Response([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
