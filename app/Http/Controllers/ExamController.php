<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamController extends Controller
{
    public function index()
    {
        try {
            $exams = DB::table('exam')->get();
            return response()->json($exams, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getExamByAll()
    {
        try {
            // SQL query to fetch the first word of `examName` and include total count
            $exams = DB::select("
                SELECT 
                    e.id, 
                    SUBSTRING_INDEX(e.examName, ' ', 1) AS examName,
                    subQuery.totalExam
                FROM 
                    exam e
                INNER JOIN (
                    SELECT 
                        SUBSTRING_INDEX(examName, ' ', 1) AS firstWord,
                        MIN(id) AS minId,
                        COUNT(*) AS totalExam
                    FROM 
                        exam
                    GROUP BY firstWord
                ) subQuery
                ON e.id = subQuery.minId
            ");

            // Format response to match Node.js output
            $formattedResults = array_map(function ($exam) {
                return [
                    'id' => (string) $exam->id, // Convert `id` to string
                    'name' => $exam->examName,  // First word of `examName`
                    'examDetails' => 'N/A',     // Default value
                    'totalPackage' => $exam->totalExam
                ];
            }, $exams);

            return response()->json($formattedResults, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch exam data'], 500);
        }
    }
}
