<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class CustomHelper
{
    public static function log($msg, $type = "info", $data = [] , $output = null): void
    {
        switch ($type){
            case "error":
                Log::error($msg, $data);
                break;
            case "warn":
                Log::warning($msg, $data);
                break;
            default:
                Log::info($msg, $data);
                break;
        }
        if ($output) {
            $output->line($msg);
        }
    }
}
