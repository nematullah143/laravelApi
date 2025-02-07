<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function paymentDetails(Request $request)
    {
        // Retrieve and validate data
        $validated = $request->validate([
            'userid' => 'required',
            'passid' => 'required',
            'exam_id' => 'required',
            'transactionid' => 'required',
            'time_period' => 'required',
            'amount' => 'required|numeric',
            'status' => 'required',
            'payment_method' => 'required',
            'currency' => 'required',
            'description' => 'nullable|string',
        ]);

        try {
            // Insert payment details into the database
            $inserted = DB::table('payment_details')->insert([
                'userid' => $validated['userid'],
                'passid' => $validated['passid'],
                'exam_id' => $validated['exam_id'],
                'transactionid' => $validated['transactionid'],
                'time_period' => $validated['time_period'],
                'amount' => $validated['amount'],
                'status' => $validated['status'],
                'payment_method' => $validated['payment_method'],
                'currency' => $validated['currency'],
                'description' => $validated['description'] ?? 'N/A',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Return success response
            return response()->json([
                'message' => 'Payment record created successfully',
                'data' => $validated,
            ], 201);
        } catch (\Exception $e) {
            // Log the error for debugging
            //Log::error('Failed to create payment record: ' . $e->getMessage());

            return response()->json(['error' => 'Failed to create payment record', 'details' => $e->getMessage()], 500);
        }
    }

    public function getPackageByPaymentDetails($userId)
    {
        if (!$userId) {
            return response()->json(['error' => 'User ID is required'], 400);
        }
    
        try {
            // Query to fetch payment details with total tests and related exam info
            $results = DB::table('payment_details as pd')
                ->join('exam as e', 'pd.exam_id', '=', 'e.id')
                ->leftJoin('test as t', 'e.id', '=', 't.examId')
                ->where('pd.userid', $userId)
                ->where('pd.status', 'success')
                ->select(
                    'pd.id as paymentId',
                    'pd.exam_id as examId',
                    'pd.userid as userId',
                    'pd.passid as passId',
                    'pd.time_period as month',
                    'pd.created_at as createdAt',
                    'pd.updated_at as updatedAt',
                    'e.examName',
                    'pd.status as paymentStatus',
                    DB::raw('COALESCE(COUNT(t.id), 0) AS totalTest')
                )
                ->groupBy(
                    'pd.id', 'pd.exam_id', 'e.id', 'e.examName', 'pd.amount', 'pd.status', 
                    'pd.userid', 'pd.passid', 'pd.time_period', 'pd.created_at', 'pd.updated_at'
                )
                ->get();
    
            // Format the results
            $formattedResults = $results->map(function ($paymentDetail) {
                return [
                    'id' => (string) $paymentDetail->paymentId, 
                    'examId' => (string) $paymentDetail->examId,
                    'userId' => (string) $paymentDetail->userId,
                    'passId' => (string) $paymentDetail->passId,
                    'months' => $paymentDetail->month,
                    'examName' => $paymentDetail->examName,
                    'packagePrice' => 0.0,  // Placeholder for price
                    'paymentStatus' => $paymentDetail->paymentStatus,
                    'totalTest' => $paymentDetail->totalTest,
                    'createdAt' => $paymentDetail->createdAt,
                    'updatedAt' => $paymentDetail->updatedAt,
                ];
            });
    
            // Return the list of payment details with additional fields
            return response()->json($formattedResults);
    
        } catch (\Exception $e) {
            // Handle errors
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    
    
}
