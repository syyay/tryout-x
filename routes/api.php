<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\EssayController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\UserPKGController;
use App\Http\Middleware\EnsureSubscription;
use App\Http\Controllers\TryoutPKGController;
use App\Http\Controllers\UniversitasController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('/user', function (Request $request) {
        $expiry = new DateTime();
        $expiry->modify('+6 hour');
        $request->user()->tokens()->update(['expires_at' => $expiry]);
        return $request->user();
    });
    Route::get('/logout', [UserController::class, 'logout']);

    // payment
    Route::post('/payments', [PaymentController::class, 'create']);

    // parse soal
    Route::get('/parseSoal', [TryoutPKGController::class, 'parseSoal']);
});


//admin route
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'dashboardAdmin']);
    Route::get('/admin/getpayment', [AdminController::class, 'payment']);
    Route::get('/admin/getTryoutPkg', [AdminController::class, 'getTryoutPkgs']);
    Route::post('/admin/createTryoutPkg', [AdminController::class, 'createTryoutPkg']);
    Route::post('/admin/updateTryoutPkg/{id}', [AdminController::class, 'updateTryoutPKG']);

    Route::get('/question/{id}', [AdminController::class, 'indexQuestions']);
    Route::get('/question/{id}', [AdminController::class, 'showQuestion']);
    Route::delete('/question/{id}', [AdminController::class, 'destroyQuestion']);
    Route::post('/questions/{id}', [AdminController::class, 'updateQuestion']);
    Route::post('/question', [AdminController::class, 'storeQuestion']);


    // CRUD Essay Answers 
    Route::get('essay-answers', [AdminController::class, 'indexEssayAnswers']);
    Route::post('essay-answers', [AdminController::class, 'storeEssayAnswer']);
    Route::get('essay-answers/{id}', [AdminController::class, 'showEssayAnswer']);
    Route::put('essay-answers/{id}', [AdminController::class, 'updateEssayAnswer']);
    Route::delete('essay-answers/{id}', [AdminController::class, 'destroyEssayAnswer']);



    // CRUD Question Choices 
    Route::get('question-choices', [AdminController::class, 'indexQuestionChoices']);
    Route::post('question-choices', [AdminController::class, 'storeQuestionChoice']);
    Route::get('question-choices/{id}', [AdminController::class, 'showQuestionChoice']);
    Route::put('question-choices/{id}', [AdminController::class, 'updateQuestionChoice']);
    Route::delete('question-choices/{id}', [AdminController::class, 'destroyQuestionChoice']);

    



    
  



});

//user route
Route::middleware(['auth:sanctum', 'subscription'])->group(function () {
    //profile
    Route::post('/user/update', [UserController::class, 'updateUser']);
    Route::get('/user/getuser', [UserController::class, 'getUser']);
    Route::get('/user/getuserimg', [UserController::class, 'getuserimg']);
    Route::post('/user/createimg', [UserController::class, 'createproimg']);
    Route::post('/user/updateimg', [UserController::class, 'updateimg']);
    Route::delete('/user/deletetimg', [UserController::class, 'deleteimg']);
    // dashboard
    Route::get('/user/tryout', [UserPKGController::class, 'getUserTryouts']);
    Route::get('/user/tryout/history', [UserPKGController::class, 'getUserTryoutHistories']);
    Route::get('/user/tryout/upcoming', [UserPKGController::class, 'getUserTryoutUpcoming']);
    Route::get('/user/tryout/notpurchased', [UserPKGController::class, 'getUserTryoutNotPurchased']);
    Route::post('/user/tryout/assign', [TryoutPKGController::class, 'assignTryout']);

    //University and major

    Route::get('/user/universitys', [UniversitasController::class, 'getUniversity']);
    Route::get('/user/majors', [UniversitasController::class, 'getMajors']);
    Route::post('/user/createunimaj', [UniversitasController::class, 'postUnivMaj']);


    // start tryout
    Route::post('/user/tryout/start', [UserPKGController::class, 'startUserTryout']);
    Route::post('/user/tryout/next', [UserPKGController::class, 'nextUserTryout']);
    Route::post('/user/tryout/check', [UserPKGController::class, 'checkUserTryout']);
    Route::post('/user/tryout/end', [UserPKGController::class, 'endUserTryout']);
    Route::post('/user/tryout/questions', [UserPKGController::class, 'getUserTryoutQuestions']);
    Route::post('/user/tryout/answer', [UserPKGController::class, 'answerUserTryout']);

    Route::post('/user/tryout/tryoutid' , [UserPKGController::class, 'getTryoutId']);

    Route::post('/submit/essay/answer', [UserPKGController::class, 'submitEssayAnswer']);
    // tryout review
    Route::post('/user/tryout/sub', [TryoutPKGController::class, 'getTryoutSub']);
});
// tryout util
Route::post('/tryout/eval', [TryoutPKGController::class, 'evalTryout']);

Route::post('/forgotpass', [UserController::class, 'forgotPass'])->name('password.email');
Route::post('/resetpass', [UserController::class, 'resetPass'])->name('password.update');
Route::post('password/email', [UserController::class, 'sendResetLinkEmail']);

// articles
Route::get('/articles', [ArticleController::class, 'getArticles']);
Route::get('/articles/{id}', [ArticleController::class, 'getArticleById']);

Route::post('/payments/webhook/xendit', [PaymentController::class, 'webhook']);
Route::post('auth/register', [UserController::class, 'createUser']);
Route::post('auth/login', [UserController::class, 'loginUser']);

Route::middleware('throttle:rate_limit,1')->group(function () {
    Route::get('/verify-email/{token}', [UserController::class, 'verify'])->name('verification.verify');
});
//Essay
// Route::prefix('essays')->group(function () {
//     Route::get('/', [EssayController::class, 'index']);
//     Route::get('/ {id}', [EssayController::class, 'show']);
// });



