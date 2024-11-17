<?php
namespace App\Http\Controllers;

use App\Models\Shipper;
use Illuminate\Http\Request;

class ShipperScoreController extends Controller
{
    public function updateAttendance(Request $request, Shipper $shipper)
    {
        $request->validate([
            'score' => 'required|numeric|min:0|max:10',
        ]);

        $shipper->attendance_score = $request->score;
        $shipper->save();

        return response()->json(['message' => 'Attendance score updated successfully']);
    }

    public function updateVote(Request $request, Shipper $shipper)
    {
        $request->validate([
            'score' => 'required|numeric|min:0|max:5',
        ]);

        $shipper->vote_score = $request->score;
        $shipper->save();

        return response()->json(['message' => 'Vote score updated successfully']);
    }

    public function updateOperatingArea(Request $request, Shipper $shipper)
    {
        $request->validate([
            'operating_area' => 'required|array',
        ]);

        $shipper->operating_area = $request->operating_area;
        $shipper->save();

        return response()->json(['message' => 'Operating area updated successfully']);
    }
}