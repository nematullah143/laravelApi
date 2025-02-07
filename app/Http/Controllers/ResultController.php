<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ResultController extends Controller
{

    public function createResult(Request $request)
    {
        // Validate incoming request data
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|integer',
            'quiz_id' => 'required|integer',
            'total_questions' => 'required|integer',
            'correct_answers' => 'required|integer',
            'score' => 'required|numeric',
            'reattempt' => 'nullable|integer'
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // Get the data from the request
        $data = $request->only([
            'student_id',
            'quiz_id',
            'total_questions',
            'correct_answers',
            'score',
            'reattempt'
        ]);

        // Set default value for reattempt if not provided
        if (!isset($data['reattempt'])) {
            $data['reattempt'] = 0;
        }

        try {
            // Insert the data into the 'student_results' table
            $result = DB::table('student_results')->insertGetId([
                'student_id' => $data['student_id'],
                'quiz_id' => $data['quiz_id'],
                'total_questions' => $data['total_questions'],
                'correct_answers' => $data['correct_answers'],
                'score' => $data['score'],
                'reattempt' => $data['reattempt'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Return a success response
            return response()->json([
                'message' => 'Student result added successfully.',
                'id' => $result
            ], 201);

        } catch (\Exception $e) {
            // Handle any errors that may occur
            return response()->json(['error' => 'Failed to insert data.'], 500);
        }
    }

    public function getResultByUserId($id)
    {
        try {
            // Query to fetch results by student ID with the associated test name
            $results = DB::table('student_results')
                ->join('test', 'student_results.quiz_id', '=', 'test.id')
                ->where('student_results.student_id', $id)
                ->select('student_results.*', 'test.testName')
                ->get();

            // Check if no results were found
            if ($results->isEmpty()) {
                return response()->json(['message' => 'Record not found.'], 404);
            }

            // Return results
            return response()->json($results);

        } catch (\Exception $e) {
            // Handle any errors
            return response()->json(['error' => 'Failed to fetch data.'], 500);
        }
    }

    public function getResultById($testId)
    {
        // Validate the testId
        if (!$testId) {
            return response()->json(['error' => 'Test ID is required.'], 400);
        }

        // Query to fetch the test name based on testId
        try {
            $test = DB::table('test')->where('id', $testId)->first();

            // Handle case where no test is found with the provided ID
            if (!$test) {
                return response()->json(['error' => 'No test found with the provided ID.'], 404);
            }

            // Return the test name
            return response()->json(['testName' => $test->testName], 200);
        } catch (\Exception $e) {
            // Handle any potential errors
            return response()->json(['error' => 'Failed to fetch data.'], 500);
        }
    }
}
