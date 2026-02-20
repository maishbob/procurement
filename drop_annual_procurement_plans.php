<?php

use Illuminate\Support\Facades\DB;

// Drop the annual_procurement_plans table if it exists
DB::statement('DROP TABLE IF EXISTS annual_procurement_plans');

echo "annual_procurement_plans table dropped if it existed.\n";
