<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GraphController extends Controller
{
    public function graph()
    {
        $graphData = [
            'dskp_mains' => \App\Models\DskpMain::get()->toArray(),
            'dskp_topics' => \App\Models\DskpTopic::get()->toArray(),
            'dskp_subtopics' => \App\Models\DskpSubtopic::get()->toArray(),
        ];  
        return response()->json($graphData);
    }
}
