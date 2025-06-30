<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserCampaignController extends Controller
{
    /**
     * Return the user + their web & company info
     */
      public function index()
    {
        $users = User::with([
                'webInformation',
                'companyInformation',
                'userGmails'
            ])->get();

        return response()->json($users);
    }
    public function show($id)
    {
        $user = User::with(['webInformation', 'companyInformation','userGmails'])
                    ->findOrFail($id);

        return response()->json([
            'id'                   => $user->id,
            'name'                 => $user->name,
            'email'                => $user->email,
            'web_information'      => $user->webInformation,
            'company_information'  => $user->companyInformation,
            'gmail_accounts'      => $user->userGmails, 
        ]);
    }
}
