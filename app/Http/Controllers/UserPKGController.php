<?php

namespace App\Http\Controllers;

use Log;
use DateTime;
use Throwable;
use App\Models\UserPKG;
use App\Models\TryoutPKG;
use App\Models\TryoutSub;
use App\Models\UserAnswer;
use App\Jobs\ProcessTryout;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\TryoutQuestion;
use App\Models\QuestionChoice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class UserPKGController extends Controller
{
    public function getUserTryouts(Request $request): Response
    {
        try {
            $time = new DateTime('Asia/Jakarta');
            $timenow = $time->format('Y-m-d H:i:s');
            $result = $request->user()
                ->tryouts()
                ->where([['tryout_pkgs.end_time', '>=',  $timenow], ['tryout_pkgs.start_time', '<=',  $timenow]])
                ->orderBy('tryout_pkgs.start_time', 'asc')
                ->wherePivotNull('user_end')
                ->get()
                ->groupBy('tryout_id');
            return Response([
                'status' => true,
                'data' => $result,
            ], 200);
        } catch (Throwable $th) {
            return Response([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
    public function getUserTryoutHistories(Request $request): Response
    {
        try {
            $result = $request->user()
                ->tryouts()
                ->wherePivotNotNull('user_end')
                ->orderByPivot('user_end', 'desc')
                ->get()->each(function ($item, $key) {
                    $item->pivot->makeVisible(['score', 'sub_to_score', 'sum_true', 'sum_false']);
                });
            return Response([
                'status' => true,
                'data' => $result,
            ], 200);
        } catch (Throwable $th) {
            return Response([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
    public function getUserTryoutUpcoming(Request $request): Response
    {
        try {
            $time = new DateTime('Asia/Jakarta');
            $timenow = $time->format('Y-m-d H:i:s');
            $result = $request->user()
                ->tryouts()
                ->where('tryout_pkgs.start_time', '>',  $timenow)
                ->orderBy('tryout_pkgs.start_time', 'asc')
                ->wherePivotNull('user_end')
                ->get()
                ->groupBy('tryout_id');
            return Response([
                'status' => true,
                'data' => $result,
            ], 200);
        } catch (Throwable $th) {
            return Response([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
    public function getUserTryoutNotPurchased(Request $request): Response
    {
        try {
            $result = TryoutPKG::whereDoesntHave('users', function (Builder $query) use ($request) {
                $query->where('users.user_id', $request->user()->user_id);
            })->get();
            return Response([
                'status' => true,
                'data' => $result,
            ], 200);
        } catch (Throwable $th) {
            return Response([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
    public function startUserTryout(Request $request): Response
    {
        try {
            $time = new DateTime('Asia/Jakarta');
            $timenow = $time->format('Y-m-d H:i:s');
            $toauth = $request->user()
                ->tryouts()
                ->where([['tryout_pkgs.end_time', '>=',  $timenow], ['tryout_pkgs.start_time', '<=',  $timenow]])
                ->wherePivotNull('user_end')
                ->wherePivot('user_pkg_id', $request->user_pkg_id)
                ->first();
            $ongoing = $request->user()
                ->user_pkgs() 
                ->whereNull('user_end')
                ->whereNotNull('user_start')
                ->count();
            if ($toauth == null) {
                return Response([
                    'status' => false,
                    'message' => 'Unauthorized',
                ], 401);
            } else if ($toauth->pivot->user_start != null) {
                return Response([
                    'status' => true,
                    'message' => 'Tryout continued'
                ], 200);
            } else if ($ongoing >= 1) {
                return Response([
                    'status' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }
            $qid = $request->user()
                ->tryouts()
                ->wherePivot('user_pkg_id', $request->user_pkg_id)
                ->first()
                ->tryout_questions()
                // ->inRandomOrder($toauth->pivot->shuffle_seed)
                ->pluck('question_id');
            $subto = $request->user()
                ->tryouts()
                ->wherePivot('user_pkg_id', $request->user_pkg_id)
                ->first()
                ->tryout_subs()
                ->orderBy('sub_order', 'asc')
                ->first();
            UserPKG::where('user_pkg_id', $request->user_pkg_id)->first()->tryout_questions()->attach($qid);
            $request->user()->user_pkgs()
                ->where('user_pkg_id', $request->user_pkg_id)
                ->update(['sub_start' => $timenow, 'user_start' => $timenow, 'tryout_sub_id' => $subto->tryout_sub_id]);
            ProcessTryout::dispatch($request->user_pkg_id, $timenow, $subto->sub_duration, 0)
                ->delay(now()->addMinutes($subto->sub_duration));
            return Response([
                'status' => true,
                'message' => 'instance created'
            ], 201);
        } catch (Throwable $th) {
            return Response([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
    public function nextUserTryout(Request $request): Response
    {
        try {
            $time = new DateTime('Asia/Jakarta');
            $timenow = $time->format('Y-m-d H:i:s');
            $toauth = $request->user()
                ->tryouts()
                ->where([['tryout_pkgs.end_time', '>=',  $timenow], ['tryout_pkgs.start_time', '<=',  $timenow]])
                ->wherePivotNull('user_end')
                ->wherePivotNotNull('user_start')
                ->wherePivotNotNull('tryout_sub_id')
                ->wherePivot('user_pkg_id', $request->user_pkg_id)
                ->first();
            if ($toauth == null) {
                return Response([
                    'status' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }
            $usr_pkg = UserPKG::where('user_pkg_id', $request->user_pkg_id)
                ->first();
            $sub_to = $usr_pkg->tryouts()
                ->first()
                ->tryout_subs()
                ->orderBy('sub_order', 'asc')
                ->get();
            $sub_idx = $sub_to->search(function ($sb) use ($usr_pkg) {
                return $sb->tryout_sub_id === $usr_pkg->tryout_sub_id;
            });
            $next_to = $sub_to->get($sub_idx + 1);
            if ($next_to == null) {
                return $this->endUserTryout($request);
            }
            $msg = 'continue';
            $last_to = $sub_to->get($sub_idx + 2);
            if ($last_to == null) {
                $msg = 'last';
            }
            $usr_pkg->update(['sub_start' => $timenow, 'tryout_sub_id' => $next_to->tryout_sub_id]);
            ProcessTryout::dispatch($usr_pkg->user_pkg_id, $timenow, $next_to->sub_duration, $sub_idx + 1)
                ->delay(now()->addMinutes($next_to->sub_duration));
            return Response([
                'status' => true,
                'message' => $msg
            ], 200);
        } catch (Throwable $th) {
            return Response([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
    public function checkUserTryout(Request $request): Response
    {
        try {
            $time = new DateTime('Asia/Jakarta');
            $timenow = $time->format('Y-m-d H:i:s');
            $toauth = $request->user()
                ->tryouts()
                ->where([['tryout_pkgs.end_time', '>=',  $timenow], ['tryout_pkgs.start_time', '<=',  $timenow]])
                ->wherePivotNull('user_end')
                ->wherePivotNotNull('user_start')
                ->wherePivotNotNull('tryout_sub_id')
                ->wherePivot('user_pkg_id', $request->user_pkg_id)
                ->first();

            if ($toauth == null) {
                return Response([
                    'status' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }
            $usr_pkg = UserPKG::where('user_pkg_id', $request->user_pkg_id)
                ->first()
                ->tryout_subs()
                ->first();
            $user_time = new DateTime($toauth->pivot->sub_start);
            $user_time->modify("+" . $usr_pkg->sub_duration . " minutes");
            $user_time_format = $user_time->format('Y-m-d H:i:s');
            if ($timenow > $user_time_format) {
                return Response([
                    'status' => false,
                    'message' => 'Out of time',
                ], 401);
            }
            return Response([
                'status' => true,
                'message' => $user_time_format
            ], 200);
        } catch (Throwable $th) {
            return Response([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate score for spesific tryout
     */
    public function scoreCalculate($tryout_id)
    {
        // Find tryout by id
        $tryout = TryoutPKG::where('tryout_id', $tryout_id)->first();
        if ($tryout == null) {
            return Response([
                'status' => false,
                'message' => 'Tryout not found',
            ], 404);
        }

        // Get all questions from tryout
        $questions_weight = $tryout->tryout_questions()->get();
        foreach ($questions_weight as $question) {
            // Calculate weight for each question
            $question->weight_question();
        }

        // Get all user package from tryout
        $result = $tryout->user_pkgs()->whereNotNull('user_end')->get();
        foreach ($result as $rs) {
            // Calculate score for each user package
            $rs->score_user();

            // Make visible score and sub_to_score
            $rs->makeVisible(['score', 'sub_to_score']);
        }
    }

    public function endUserTryout(Request $request): Response
    {
        try {
            $time = new DateTime('Asia/Jakarta');
            $timenow = $time->format('Y-m-d H:i:s');

            // Start transaction
            DB::beginTransaction();

            $toauth = $request->user()
                ->tryouts()
                ->where([['tryout_pkgs.end_time', '>=',  $timenow], ['tryout_pkgs.start_time', '<=',  $timenow]])
                ->wherePivotNull('user_end')
                ->wherePivotNotNull('user_start')
                ->wherePivot('user_pkg_id', $request->user_pkg_id)
                ->first();
            if ($toauth == null) {
                return Response([
                    'status' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }
            $usr_pkg = $request->user()
                ->user_pkgs()
                ->where('user_pkg_id', $request->user_pkg_id)
                ->first();
            $sub_to = $usr_pkg->tryouts()
                ->first()
                ->tryout_subs()
                ->orderBy('sub_order', 'desc')
                ->first();

            $usr_pkg->update(['user_end' => $timenow, 'tryout_sub_id' => $sub_to->tryout_sub_id]);

            // Calculate tryout score
            $this->scoreCalculate($toauth->tryout_id);

            // Commit transaction
            DB::commit();

            return Response([
                'status' => true,
                'message' => 'Tryout ended'
            ], 200);
        } catch (Throwable $th) {
            // Rollback transaction
            DB::rollBack();

            return Response([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
    
    public function getUserTryoutQuestions(Request $request): Response
{
    try {
        // Ambil paket pengguna
        $toauth = $request->user()
            ->user_pkgs()
            ->whereNotNull('user_start')
            ->where('user_pkg_id', $request->user_pkg_id)
            ->first();

        // Periksa apakah paket pengguna ditemukan
        if ($toauth == null) {
            return Response([
                'status' => false,
                'message' => 'Unauthorized: Paket pengguna tidak ditemukan atau belum dimulai.',
            ], 401);
        }

        // Tentukan ID sub tryout
        $sub_id = $request->tryout_sub_id;
        if ($toauth->user_end == null) {
            $sub_id = $toauth->tryout_sub_id;
        }

        // Periksa apakah ID sub tryout valid
        if ($sub_id == null) {
            return Response([
                'status' => false,
                'message' => 'Unauthorized: ID sub tryout tidak valid.',
            ], 401);
        }

        // Ambil pertanyaan tryout pilihan ganda dan esai
        $multipleChoiceQuestions = $request->user()
            ->user_pkgs()
            ->where('user_pkg_id', $request->user_pkg_id)
            ->first()
            ->tryout_questions()
            ->where('tryout_sub_id', $sub_id)
            ->where('question_type', 1) // Kode 1 untuk pilihan ganda
            ->with('question_choices')
            ->get()
            ->each(function ($item, $key) {
                $item->pivot->user_answers;
            });

        $essayQuestions = $request->user()
            ->user_pkgs()
            ->where('user_pkg_id', $request->user_pkg_id)
            ->first()
            ->tryout_questions()
            ->where('tryout_sub_id', $sub_id)
            ->where('question_type', 2) // Kode 2 untuk esai
            ->get()
            ->each(function ($item, $key) {
                $item->pivot->user_answers;
            });

        // Ambil detail sub tryout
        $to_sub = TryoutSub::where('tryout_sub_id', $sub_id)->first();

        // Tampilkan field tertentu jika tryout telah selesai
        if ($toauth->user_end != null) {
            foreach ($multipleChoiceQuestions as $rs) {
                $rs->question_choices->makeVisible(['true_answer', 'explanation', 'choice_val']);
                $rs->pivot->makeVisible(['question_true']);
            }
            foreach ($essayQuestions as $rs) {
                $rs->pivot->makeVisible(['essay_answer']);
            }
        }

        return Response([
            'status' => true,
            'data' => [
                'multiple_choice' => $multipleChoiceQuestions,
                'essay' => $essayQuestions,
            ],
            'tryout_sub' => $to_sub,
        ], 200);
    } catch (Throwable $th) {
        return Response([
            'status' => false,
            'message' => $th->getMessage(),
        ], 500);
    }
}

    public function getTryoutId(Request $request)
    {
        $userPKG = UserPKG::where('user_pkg_id', $request->user_pkg_id)
            ->where('tryout_id',  $request->tryout_id)
            ->with(['tryout_subs'])
            ->first();

        if ($userPKG) {
            return response()->json([
                'success' => true,
                'message' => 'User Package found',
                'data' => $userPKG
            ], 200);
        } else {
            // Jika data tidak ditemukan, kembalikan response JSON dengan pesan error
            return response()->json([
                'success' => false,
                'message' => 'User Package not found',
            ], 404);
        }
    }

    public function answerUserTryout(Request $request): Response
    {
        try {
            $time = new DateTime('Asia/Jakarta');
            $timenow = $time->format('Y-m-d H:i:s');
            $usr_pkg = $request->user()
                ->user_pkgs()
                ->where('user_pkg_id', $request->user_pkg_id)
                ->first();
            $toauth = $request->user()
                ->tryouts()
                ->where([['tryout_pkgs.end_time', '>=',  $timenow], ['tryout_pkgs.start_time', '<=',  $timenow]])
                ->wherePivotNull('user_end')
                ->wherePivotNotNull('user_start')
                ->wherePivot('user_pkg_id', $request->user_pkg_id)
                ->first()
                ->tryout_subs()
                ->where('tryout_sub_id', $usr_pkg->tryout_sub_id)
                ->first()
                ->tryout_questions()
                ->where('question_id', $request->question_id)
                ->first()
                // ->question_choices()
                // ->whereIn('choice_id', $request->choice_id)
                // ->first()
            ;
            if ($toauth == null ||  count($request->choice_id) != count($request->choice_val)) {
                return Response([
                    'status' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }
            $pkg_quest = $usr_pkg->user_pkg_questions()
                ->where('question_id', $request->question_id)
                ->first();
            if (empty($request->choice_id) || $request->choice_id[0] == null) {
                $pkg_quest->update(['flag_question' => $request->flag_question]);
                $pkg_quest->question_choices()->detach();
                return Response([
                    'status' => true,
                    'message' => 'Answer state stored'
                ], 200);
            }
            foreach ($request->choice_id as $ch) {
                if (
                    $toauth->question_choices()
                    ->where('choice_id', $ch)
                    ->first() == null
                ) {
                    return Response([
                        'status' => false,
                        'message' => 'Unauthorized',
                    ], 401);
                }
            }
            // $user_ans = $pkg_quest->user_answers()
            //     ->where('choice_id', $request->choice_id)
            //     ->first();
            // if ($user_ans == null) {
            //     $pkg_quest->question_choices()->attach($request->choice_id);
            // } else {
            //     $pkg_quest->question_choices()->detach($request->choice_id);
            //     return Response([
            //         'status' => true,
            //         'message' => 'Answer detached'
            //     ], 200);
            // }
            $ans = array();
            $q_type = $toauth->question_type;
            for ($i = 0; $i < count($request->choice_id); $i++) {
                $cval = $request->choice_val[$i];
                if ($q_type < 2) {
                    $cval = null;
                }
                $ans[$request->choice_id[$i]] = ['choice_val' => $cval];
            }
            $pkg_quest->question_choices()->detach();
            $pkg_quest->question_choices()->attach($ans);
            $pkg_quest->update(['flag_question' => $request->flag_question]);
            return Response([
                'status' => true,
                'message' => 'Answer state stored'
            ], 200);
        } catch (Throwable $th) {
            return Response([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function submitEssayAnswer(Request $request): Response
{
    try {
        $question = TryoutQuestion::find($request->question_id);

        if (!$question) {
            return Response([
                'status' => false,
                'message' => 'Question not found.',
            ], 404);
        }

        $is_correct = $question->checkEssayAnswer($request->user_pkg_id, $request->user_answer);

        return Response([
            'status' => true,
            'message' => 'Answer submitted successfully.',
            'is_correct' => $is_correct,
        ], 200);
    } catch (Throwable $th) {
        return Response([
            'status' => false,
            'message' => $th->getMessage(),
        ], 500);
    }
}

    // public function getEssayQuestions(Request $request): Response
    // {
    //     try {
    //         // Ambil user_pkg_id dari request
    //         $user_pkg_id = $request->user_pkg_id;
            
    //         // Validasi bahwa user_pkg_id telah disertakan
    //         if (!$user_pkg_id) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => 'User Package ID is required',
    //             ], 400);
    //         }

    //         $essays = TryoutQuestion::where('tryout_id', $request->tryout_id)
    //             ->where('question_type', 'essay')
    //             ->get();

    //         return response()->json([
    //             'status' => true,
    //             'data' => $essays,
    //         ], 200);
    //     } catch (Throwable $th) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => $th->getMessage(),
    //         ], 500);
    //     }
    // }
}
