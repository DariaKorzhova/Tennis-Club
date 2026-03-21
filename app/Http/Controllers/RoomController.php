<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Room;

class RoomController extends Controller
{
    public function showRooms() {
        $rooms = Room::all();
        return view('rooms.rooms', compact('rooms'));
    }

    public function show(Room $room)
{
    return view('rooms.show', compact('room'));
}

}
