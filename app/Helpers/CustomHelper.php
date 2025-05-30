<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class CustomHelper
{
    public static function log($msg, $type = "info", $output = null): void
    {
        switch ($type){
            case "error":
                Log::error($msg);
                break;
            case "warn":
                Log::warning($msg);
                break;
            default:
                Log::info($msg);
                break;
        }
        if ($output) {
            $output->line($msg);
        }
    }
}
