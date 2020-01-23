<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use App\Entry;
use App\User;
use Validator;

class EntriesController extends Controller
{
    protected $entries;

    public function __construct()
    {
        $this->entries = new Entry;
    }

    /**
     * Get entries.
     *
     * @param
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = new User;
        $entries = new Collection();
        $lastUsers = $users->orderBy("id", "DESC")->get();
        foreach ($lastUsers as $v) {
            $allEntries = $v->entries->slice(0, 3);
            if (count($allEntries)>0) {
                $entries = $entries->merge($allEntries);
            }
        }
        $completeEntries = [];
        foreach ($entries as $e) {
            $c = new Collection();
            $c = $c->merge($e);
            $user = $e->user->username;
            $c = $c->merge(['username'=>$user]);
            array_push($completeEntries, $c);
        }

        return response()->json([
            "success"=>true,
            "data"=>$completeEntries
        ], 200);
    }
    
    public function getMyEntries(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token'=>'required | string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success'=> false,
                'message'=> $validator->messages()->toArray()
            ], 401);
        }
        $token = $request->token;
        $entries = auth('users')->authenticate($token)->entries->paginate(5);
        return response()->json([
            "success"=>true,
            "data"=>$entries
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->middleware('auth:users');
        $validator = Validator::make($request->all(), [
            'token'=>'required | string',
            'title'=>'required | string | min:5',
            'content'=>'required | string | min:10'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success'=> false,
                'message'=> $validator->messages()->toArray()
            ], 401);
        }

        $token = $request->token;
        $user = auth('users')->authenticate($token);

        $this->entries->user_id = $user->id;
        $this->entries->title = $request->title;
        $this->entries->content = $request->content;
        $this->entries->save();

        return response()->json([
            'success'=> true,
            'message'=> 'Entry saved successfully',
            'data'=> $this->entries->id
        ], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Entry $entry)
    {
        return response()->json([
                'success'=>true,
                'data'=>$entry
            ], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request  $request, $id)
    {
        $entry = $this->entries::find($id);
        if (!$entry) {
            return response()->json([
                
                'success'=>false,
                'message'=>'Entry doesnt exist'
            ], 400);
        }

        if (!$this->isYours($id, $entry)) {
            return response()->json([
                'success'=>false,
                'message'=>'You only can edit your entry'
            ], 401);
        }

        $entry->title = $request->title;
        $entry->content = $request->content;
        $entry->save();

        return response()->json([
            "success"=>true,
            "message"=>"Entry updated successfully",
       ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $entry = $this->entries::find($id);
        if (!$entry) {
            return response()->json([
                "success"=>false,
                "message"=>"Entry  doesnt exist"
            ], 400);
        }

        if (!$this->isYours($id, $entry)) {
            return response()->json([
                'success'=>false,
                'message'=>'You only can delete your entry'
            ], 401);
        }
 
        if ($entry->delete()) {
            return response()->json([
                "success"=>true,
                "message"=>"Entry deleted successfully"
            ], 200);
        }
    }

    public function isYours($id, $entry)
    {
        $token = session()->getId();
        $user = auth('users')->authenticate($token);
        return $user->id == $entry->user_id;
    }
}
