<?php
namespace App\Http\Controllers;

use App\Models\Essay;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EssayController extends Controller
{
    public function index()
    {
        $essays = Essay::all();
        foreach ($essays as $essay) {
            $essay->question_type = $essay->isEssay() ? 2 : 1;
        }
        return response()->json(['status' => true, 'data' => $essays], 200);
    }

    public function show($id)
    {
        $essay = Essay::find($id);
        if (!$essay) {
            return response()->json(['status' => false, 'message' => 'Essay not found'], 404);
        }
        return response()->json(['status' => true, 'data' => $essay], 200);
    }
}
