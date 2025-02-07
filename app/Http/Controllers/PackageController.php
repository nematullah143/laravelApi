<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PackageController extends Controller
{
    public function getPackagesWithTotalTests()
    {
        try {
            $packages = DB::select("
        SELECT 
            e.id, 
            e.examName, 
            COUNT(t.id) AS totalTest
        FROM exam e
        LEFT JOIN test t ON e.id = t.examID
        GROUP BY e.id, e.examName
    ");


            $formattedResults = array_filter(array_map(function ($packageItem) {
                if ($packageItem->totalTest > 0) {
                    return [
                        'id' => (string) $packageItem->id,
                        'name' => $packageItem->examName,
                        'totalTest' => $packageItem->totalTest,
                        'packageDetails' => 'N/A',
                        'packagePrice' => 0.0,
                    ];
                }
                return null;
            }, $packages));

            return response()->json($formattedResults, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500); // Show exact error message
        }
    }

    public function getPrice()
    {
        try {
            // Fetch all records from 'pass' table
            $prices = DB::select("SELECT * FROM pass");

            return response()->json($prices, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch data'], 500);
        }
    }
}
