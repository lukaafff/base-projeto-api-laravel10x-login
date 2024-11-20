<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Team;

class TeamsController extends Controller
{
    public function saveTeams(Request $request)
    {
        $request->validate([
            'teams' => 'required|array',
            'teams.*.name' => 'required|string',
            'teams.*.serie' => 'required|in:A,B',
            'teams.*.is_selected' => 'boolean',
        ]);

        foreach ($request->teams as $teamData) {
            Team::updateOrCreate(
                ['name' => $teamData['name']],
                [
                    'serie' => $teamData['serie'],
                    'is_selected' => $teamData['is_selected'] ?? false
                ]
            );
        }

        return response()->json(['message' => 'Times salvos com sucesso.'], 200);
    }

    public function getAllTeams()
    {
        $teams = Team::select('name', 'serie', 'is_selected')->get()->groupBy('serie');

        return response()->json([
            'serie_A' => $teams->get('A') ?? [],
            'serie_B' => $teams->get('B') ?? []
        ]);
    }
}
