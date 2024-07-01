<?php

namespace App\Http\Controllers;

use Log;
use DateTime;
use Throwable;
use App\Models\Payment;
use App\Models\UserPKG;
use App\Models\TryoutPKG;
use App\Models\TryoutSub;
use App\Models\UserAnswer;
use App\Jobs\ProcessTryout;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\TryoutQuestion;
use App\Models\QuestionChoice;
use App\Models\EssayAnswer;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;





class AdminController extends Controller
{
    public function dashboardAdmin(Request $request): Response
    {
        try {
            $currentYear = Carbon::now()->year;

            $payments = Payment::where('status', 'SETTLED')
                               ->whereYear('created_at', $currentYear)
                               ->get();

            $paymentsByMonth = $payments->groupBy(function($date) {
                return Carbon::parse($date->created_at)->format('m');
            });

            $paymentsByMonth = $paymentsByMonth->map(function ($monthPayments) {
                return $monthPayments->map(function ($item) {
                    return array_merge($item->toArray(), ['price' => 35000]);
                });
            });

                return response([
                    'status' => true,
                    'message' => 'Get data Payment Successfully',
                    'data' => $paymentsByMonth
                ],200);
        } catch (\Throwable $th) {
            return Response([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function getTryoutPkgs(Request $request):Response
    {
        try {
            $tryout = TryoutPKG::all();

            return response([
                'status' => true,
                'message' => 'Get tryout successfully',
                'data' => $tryout
            ]);
        } catch (\Throwable $th) {
            return Response([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }


    public function createTryoutPkg(Request $request): Response
    {
        set_time_limit(150);
        require_once base_path('app/Http/Controllers/Soal/Try_Out_8_SNBT_2023_Cornell/TOnSUB.php');
        try {
            $validateTryout = Validator::make($request->all(), [
                'title' => 'required',
                'start_time' => 'required|date',
                'end_time' => 'required|date',
                'desc_to' => 'required'
            ]);

            if ($validateTryout->fails()) {
                return response([
                    'status' => false,
                    'message' => 'validation_error',
                    'errors' => $validateTryout->errors()
                ],401);
            }

            $tryoutPkg = TryoutPKG::create([
                'title' => $request->title,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'desc_to' => $request->desc_to,
            ]);

            foreach ($tryoutSubPKG as $sub) {
                TryoutSub::create([
                    'tryout_id' => $tryoutPkg->tryout_id,
                    'sub_title' => $sub['sub_title'],
                    'sub_duration' => $sub['sub_duration'],
                    'sub_order' => $sub['sub_order'],
                    'mean_val' => $sub['mean_val'],
                    'std_val' => $sub['std_val'],
                ]);
            }
            return response([
                'status' => true,
                'message' => 'Tryout Package successfully'
            ]);
        } catch (\Throwable $th) {
            return Response([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function updateTryoutPKG(Request $request): Response
    {
        try {
            $validateTryout = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'start_time' => 'required|date',
                'end_time' => 'required|date',
                'desc_to' => 'required|string'

            ]);

            if ($validateTryout->fails()) {
                return response([
                    'status' => false,
                    'message' => 'validation_error',
                    'errors' => $validateTryout->errors()
                ],401);
            }

            $tryoutPkg = TryoutPKG::where('tryout_id', $request->tryout_id)->first();

            $tryoutPkg->title = $request->title;
            $tryoutPkg->start_time = $request->start_time;
            $tryoutPkg->end_time = $request->end_time;
            $tryoutPkg->desc_to = $request->desc_to;
            $tryoutPkg->save();

            return response([
                'status' => true,
                'message' => 'Tryout updated successfully',
                'tryout' => $tryoutPkg
            ], 200);
        } catch (\Throwable $th) {
            return Response([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function payment(Request $request): Response
    {
        try {
            $payment = Payment::all();

            return Response([
                'status' => true,
                'message' => 'get payment susscessfully',
                'data' => $payment
            ]);
        } catch (\Throwable $th) {
            return Response([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function indexQuestions($tryout_sub_id): Response
    {
        try {
            $questions = TryoutQuestion::where('tryout_sub_id', $tryout_sub_id)->get();
            return response([
                'status' => true,
                'data' => $questions,
            ], 200);
        } catch (Throwable $th) {
            return response([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function showQuestion($id): Response
    {
        try {
            $question = TryoutQuestion::findOrFail($id);
            return response([
                'status' => true,
                'data' => $question,
            ], 200);
        } catch (Throwable $th) {
            return response([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function storeQuestion(Request $request): Response
{
    try {
        // Validasi data yang dikirimkan oleh request
        $validateQuestion = Validator::make($request->all(), [
            'tryout_sub_id' => 'required|exists:tryout_subs,tryout_sub_id',
            'question_desc' => 'required',
            'question_type' => 'required|integer',
            'image' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Jika validasi gagal, kembalikan respon dengan pesan kesalahan
        if ($validateQuestion->fails()) {
            return response([
                'status' => false,
                'message' => 'validation_error',
                'errors' => $validateQuestion->errors()
            ], 401);
        }

        // Inisialisasi nama file gambar dengan null
        $imageName = null;

        // Jika request memiliki file gambar, proses pengunggahan gambar
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images'), $imageName);

            // Log informasi tentang pengunggahan gambar
            Log::info('Image uploaded: ' . $imageName);
        }

        // Buat entri baru di database untuk pertanyaan
        $question = TryoutQuestion::create([
            'tryout_sub_id' => $request->tryout_sub_id,
            'question_desc' => $request->question_desc,
            'question_type' => $request->question_type,
            'question_img' => $imageName, // Simpan nama file gambar ke dalam database
        ]);

        // Log informasi tentang penyimpanan database
        Log::info('Question created: ' . $question->id);

        // Kembalikan respon sukses dengan data pertanyaan yang baru dibuat
        return response([
            'status' => true,
            'message' => 'Question created successfully',
            'data' => [
                'question_id' => $question->id,
                'tryout_sub_id' => $question->tryout_sub_id,
                'question_desc' => $question->question_desc,
                'question_type' => $question->question_type,
                'question_img' => $question->question_img, // Menggunakan 'question_img' untuk field image di database
                'created_at' => $question->created_at->toDateTimeString(),
                'updated_at' => $question->updated_at->toDateTimeString(),
            ],
        ], 201);
    } catch (Throwable $th) {
        // Tangani setiap pengecualian dan kembalikan respon kesalahan
        Log::error('Error creating question: ' . $th->getMessage());
        return response([
            'status' => false,
            'message' => $th->getMessage()
        ], 500);
    }
}

public function updateQuestion(Request $request, $id): Response
{
    try {
        // Log request input for debugging
        Log::info('Request input:', $request->all());

        // Log all request fields to check their values
        Log::info('tryout_sub_id:', ['value' => $request->input('tryout_sub_id')]);
        Log::info('question_desc:', ['value' => $request->input('question_desc')]);
        Log::info('question_type:', ['value' => $request->input('question_type')]);

        $validateQuestion = Validator::make($request->all(), [
            'tryout_sub_id' => 'required|exists:tryout_subs,tryout_sub_id',
            'question_desc' => 'required',
            'question_type' => 'required|integer',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validateQuestion->fails()) {
            return response([
                'status' => false,
                'message' => 'validation_error',
                'errors' => $validateQuestion->errors()
            ], 401);
        }

        $question = TryoutQuestion::findOrFail($id);

        $imageName = $question->question_img;

        if ($request->hasFile('image')) {
            if ($imageName) {
                $oldImagePath = public_path('images') . '/' . $imageName;
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }

            $image = $request->file('image');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images'), $imageName);
        }

        $question->update([
            'tryout_sub_id' => $request->tryout_sub_id,
            'question_desc' => $request->question_desc,
            'question_type' => $request->question_type,
            'question_img' => $imageName,
        ]);

        return response([
            'status' => true,
            'message' => 'Question updated successfully',
            'data' => $question,
        ], 200);
    } catch (Throwable $th) {
        return response([
            'status' => false,
            'message' => $th->getMessage()
        ], 500);
    }
}

public function destroyQuestion($id): Response
{
    try {
        $question = TryoutQuestion::findOrFail($id);

        if ($question->question_img) {
            $imagePath = public_path('images') . '/' . $question->question_img;
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        $question->delete();

        return response([
            'status' => true,
            'message' => 'Question deleted successfully',
        ], 200);
    } catch (Throwable $th) {
        return response([
            'status' => false,
            'message' => $th->getMessage()
        ], 500);
    }
}




// crud question choices


public function indexQuestionChoices()
{
    try {
        $questionChoices = QuestionChoice::all();
        return response([
            'status' => true,
            'message' => 'Question choices retrieved successfully',
            'data' => $questionChoices
        ]);
    } catch (Throwable $th) {
        return response([
            'status' => false,
            'message' => $th->getMessage()
        ], 500);
    }
}


public function storeQuestionChoice(Request $request)
{
    $validator = Validator::make($request->all(), [
        'question_id' => 'required|integer|exists:tryout_questions,question_id',
        'choice_img' => 'nullable|string',
        'choice_desc' => 'required|string',
        'choice_val' => 'nullable|integer',
        'true_answer' => 'required|boolean',
        'explanation' => 'required|string',
    ]);

    if ($validator->fails()) {
        return response([
            'status' => false,
            'message' => $validator->errors()->first()
        ], 400);
    }

    try {
        $questionChoice = QuestionChoice::create($request->all());
        return response([
            'status' => true,
            'message' => 'Question choice inserted successfully',
            'data' => $questionChoice
        ], 201);
    } catch (\Exception $e) {
        return response([
            'status' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}

public function showQuestionChoice($id)
{
    try {
        $questionChoice = QuestionChoice::findOrFail($id);
        return response([
            'status' => true,
            'message' => 'Question choice retrieved successfully',
            'data' => $questionChoice
        ]);
    } catch (Throwable $th) {
        return response([
            'status' => false,
            'message' => 'Question choice not found'
        ], 404);
    }
}

public function updateQuestionChoice(Request $request, $choice_id)
{
    $validator = Validator::make($request->all(), [
        'question_id' => 'required|integer|exists:tryout_questions,question_id',
        'choice_img' => 'nullable|string',
        'choice_desc' => 'required|string',
        'choice_val' => 'nullable|integer',
        'true_answer' => 'required|boolean',
        'explanation' => 'required|string',
    ]);

    if ($validator->fails()) {
        return response([
            'status' => false,
            'message' => $validator->errors()->first()
        ], 400);
    }

    try {
        $questionChoice = QuestionChoice::find($choice_id);
        if (!$questionChoice) {
            return response([
                'status' => false,
                'message' => 'Question choice not found'
            ], 404);
        }

        $questionChoice->update($request->all());
        return response([
            'status' => true,
            'message' => 'Question choice updated successfully',
            'data' => $questionChoice
        ], 200);
    } catch (\Exception $e) {
        return response([
            'status' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}

public function destroyQuestionChoice($id)
{
    try {
        $questionChoice = QuestionChoice::findOrFail($id);
        $questionChoice->delete();

        return response([
            'status' => true,
            'message' => 'Question choice deleted successfully'
        ]);
    } catch (Throwable $th) {
        return response([
            'status' => false,
            'message' => 'Question choice not found or could not be deleted'
        ], 404);
    }
}

// end crud question choices




// start essay answers
public function indexEssayAnswers()
{
    try {
        $essayAnswers = EssayAnswer::all();
        return response([
            'status' => true,
            'message' => 'Get essay answers successfully',
            'data' => $essayAnswers
        ]);
    } catch (Throwable $th) {
        return response([
            'status' => false,
            'message' => $th->getMessage()
        ], 500);
    }
}

public function storeEssayAnswer(Request $request)
{
    $validator = Validator::make($request->all(), [
        'essay_id' => 'required|integer|exists:essay_questions,essay_id',
        'true_essay_answer' => 'required|string',
        'choice_val' => 'nullable|integer',
        'explanation' => 'required|string',
    ]);

    if ($validator->fails()) {
        return response([
            'status' => false,
            'message' => $validator->errors()->first()
        ], 400);
    }

    try {
        $essayAnswer = EssayAnswer::create($request->all());
        return response([
            'status' => true,
            'message' => 'Essay answer inserted successfully',
            'data' => $essayAnswer
        ], 201);
    } catch (\Exception $e) {
        return response([
            'status' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}


// Show a specific essay answer
public function showEssayAnswer($id)
{
    try {
        $essayAnswer = EssayAnswer::findOrFail($id);
        return response([
            'status' => true,
            'message' => 'Get essay answer successfully',
            'data' => $essayAnswer
        ]);
    } catch (Throwable $th) {
        return response([
            'status' => false,
            'message' => $th->getMessage()
        ], 500);
    }
}

// Method untuk mengupdate EssayAnswer
public function updateEssayAnswer(Request $request, $essay_answer_id)
{
    $validator = Validator::make($request->all(), [
        'essay_id' => 'required|integer|exists:essay_questions,essay_id',
        'true_essay_answer' => 'required|string',
        'choice_val' => 'nullable|integer',
        'explanation' => 'required|string',
    ]);

    if ($validator->fails()) {
        return response([
            'status' => false,
            'message' => $validator->errors()->first()
        ], 400);
    }

    try {
        $essayAnswer = EssayAnswer::find($essay_answer_id);
        if (!$essayAnswer) {
            return response([
                'status' => false,
                'message' => 'Essay answer not found'
            ], 404);
        }

        $essayAnswer->update($request->all());
        return response([
            'status' => true,
            'message' => 'Essay answer updated successfully',
            'data' => $essayAnswer
        ], 200);
    } catch (\Exception $e) {
        return response([
            'status' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}


// Delete a specific essay answer
public function destroyEssayAnswer($id)
{
    try {
        $essayAnswer = EssayAnswer::findOrFail($id);
        $essayAnswer->delete();

        return response([
            'status' => true,
            'message' => 'Essay answer deleted successfully'
        ]);
    } catch (Throwable $th) {
        return response([
            'status' => false,
            'message' => $th->getMessage()
        ], 500);
    }
}
// end essay answers









}


