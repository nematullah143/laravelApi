<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TestController extends Controller
{
    public function getAllTests()
    {
        try {
            // Execute the query to fetch all tests
            $tests = DB::select('SELECT * FROM test');

            // Return the results as a JSON response
            return response()->json($tests, 200);
        } catch (\Exception $e) {
            // Handle any errors
            return response()->json(['message' => 'Error retrieving tests', 'error' => $e->getMessage()], 500);
        }
    }

    public function getTestsByFeeType()
    {
        // Define the raw SQL query
        $query = "
            SELECT 
              id, 
              user_id, 
              testName, 
              TIME_TO_SEC(duration) / 60 AS durationInMinutes, 
              numberOfQuestions, 
              marks, 
              launchDate, 
              isActive, 
              isFree, 
              otherStates, 
              examId, 
              created_at, 
              updated_at, 
              negative_marking, 
              passId 
            FROM test 
            WHERE isFree = 'free'
        ";

        try {
            // Execute the query using Laravel's DB facade
            $results = DB::select($query);

            // Format the results. $results is an array of stdClass objects.
            $formattedResults = array_map(function ($test) {
                return [
                    'id'              => (string)$test->id,                     // Convert id to string
                    'packageId'       => "N/A",                                 // Placeholder value
                    'name'            => $test->testName,
                    'duration'        => round($test->durationInMinutes),       // Round the duration value
                    'totalQuestions'  => $test->numberOfQuestions,
                    'marks'           => $test->marks,
                    'availableDate'   => "2024-01-14T18:30:00.000Z",            // Hardcoded availableDate
                    'expireDate'      => $test->launchDate,                     // Using launchDate as expireDate
                    'isActive'        => $test->isActive,
                    'feeType'         => $test->isFree,
                    'otherStates'     => $test->otherStates,
                    'created_at'      => $test->created_at,
                    'updated_at'      => $test->updated_at,
                    'negative_marking'=> $test->negative_marking,
                ];
            }, $results);

            // Return the formatted results as JSON
            return response()->json($formattedResults, 200);

        } catch (\Exception $e) {
            // Handle errors by returning a 500 response with the error message
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

}
