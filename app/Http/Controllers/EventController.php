<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin')->except(['getAllEvents']);
    }

    public function getAllEvents()
    {
        try {
            $events = Event::all();
            return response()->json($events, Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao recuperar eventos',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getEventsByName($name)
    {
        try {
            if (empty($name) || !is_string($name)) {
                return response()->json(['error' => 'Nome inválido.'], 400);
            }

            $events = Event::where('name', 'like', "%{$name}%")->get();

            if ($events->isEmpty()) {
                return response()->json(['message' => 'Nenhum evento encontrado.'], 404);
            }

            return response()->json($events, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Ocorreu um erro ao buscar eventos.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getEventsByStatus($status)
    {
        try {
            if (empty($status) || !is_string($status)) {
                return response()->json(['error' => 'Status inválido.'], 400);
            }

            $events = Event::where('status', $status)->get();

            if ($events->isEmpty()) {
                return response()->json(['message' => 'Nenhum evento encontrado.'], 404);
            }

            return response()->json($events, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Ocorreu um erro ao buscar eventos.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getEventsByDate($day, $month, $year)
    {
        try {
            $dateString = "{$year}-{$month}-{$day}";

            if (!strtotime($dateString)) {
                return response()->json(['error' => 'Data inválida.'], 400);
            }

            $events = Event::whereDate('date_event', $dateString)->get();

            if ($events->isEmpty()) {
                return response()->json(['message' => 'Nenhum evento encontrado para essa data.'], 404);
            }

            return response()->json($events, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Ocorreu um erro ao buscar eventos.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getEventsByCity($city)
    {
        try {
            if (empty($city) || !is_string($city)) {
                return response()->json(['error' => 'Cidade inválida.'], 400);
            }

            $events = Event::where('city', 'like', "%{$city}%")->get();

            if ($events->isEmpty()) {
                return response()->json(['message' => 'Nenhum evento encontrado para essa cidade.'], 404);
            }

            return response()->json($events, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Ocorreu um erro ao buscar eventos.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getEventsByState($state)
    {
        try {
            if (empty($state) || !is_string($state)) {
                return response()->json(['error' => 'Estado inválido.'], 400);
            }

            $events = Event::where('state', 'like', "%{$state}%")->get();

            if ($events->isEmpty()) {
                return response()->json(['message' => 'Nenhum evento encontrado para esse estado.'], 404);
            }

            return response()->json($events, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Ocorreu um erro ao buscar eventos.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function createEvent(StoreEventRequest $request)
    {
        try {
            $event = Event::create([
                'name' => $request->name,
                'city' => $request->city,
                'state' => $request->state,
                'date_event' => $request->date_event,
                'status' => $request->status,
            ]);

            return response()->json([
                'message' => 'Evento criado com sucesso',
                'event' => $event
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao criar evento',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function editEvent(UpdateEventRequest $request, $id)
    {
        $event = Event::find($id);
        if (!$event) {
            return response()->json(['message' => 'Evento não encontrado'], Response::HTTP_NOT_FOUND);
        }

        $event->update($request->validated());

        return response()->json([
            'message' => 'Evento atualizado com sucesso',
            'event' => $event
        ], Response::HTTP_OK);
    }

    public function deleteEvent($id)
    {
        $event = Event::find($id);
        if (!$event) {
            return response()->json(['message' => 'Evento não encontrado'], Response::HTTP_NOT_FOUND);
        }

        $event->delete();

        return response()->json(['message' => 'Evento deletado com sucesso'], Response::HTTP_OK);
    }
}
