<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuestionController extends Controller
{
    public function getQuestionsByTestId($testId)
    {
        try {
            // Query to fetch questions and options related to the test ID
            $results = DB::table('examquestion as eq')
                ->join('test_series as ts', 'eq.quesId', '=', 'ts.id')
                ->join('option as o', 'ts.id', '=', 'o.tId')
                ->join('option as o_correct', function ($join) {
                    $join->on('ts.id', '=', 'o_correct.tId')
                         ->where('o_correct.isAnswer', '=', 'yes');
                })
                ->where('eq.testId', $testId)
                ->select(
                    'eq.id as id',
                    'eq.testId as testId',
                    'ts.q_text as question',
                    'ts.q_image_link as image',
                    'o.value as optionValue',
                    'o.id as optionId',
                    'o_correct.value as correctAnswer',
                    'eq.created_at as createdAt',
                    'eq.updated_at as updatedAt'
                )
                ->orderBy('eq.id')
                ->orderBy('o.id')
                ->get();

            if ($results->isEmpty()) {
                return response()->json(['message' => 'No questions found for the specified test ID.'], 404);
            }

            // Transform the results into the desired format
            $questionsMap = [];

            foreach ($results as $row) {
                $questionId = (string) $row->id;

                // Initialize question data if not exists
                if (!isset($questionsMap[$questionId])) {
                    $questionsMap[$questionId] = [
                        'id' => $questionId,
                        'testId' => (string) $row->testId,
                        'question' => $row->question ?? 'No question provided',
                        'options' => [], // Array to hold options
                        'answer' => null, // Index of the correct answer
                        'image' => $row->image ?? null,
                        'createdAt' => $row->createdAt ?? null,
                        'updatedAt' => $row->updatedAt ?? null,
                    ];
                }

                // Add the option value to the options array
                $currentQuestion = &$questionsMap[$questionId];
                $currentQuestion['options'][] = $row->optionValue ?? 'No option provided';

                // Check if the current option is the correct answer
                if ($row->optionValue === $row->correctAnswer) {
                    $currentQuestion['answer'] = count($currentQuestion['options']) - 1; // Index of the correct option
                }
            }

            // Convert the map to an array and return as response
            return response()->json(array_values($questionsMap));

        } catch (\Exception $e) {
            // Handle errors
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
