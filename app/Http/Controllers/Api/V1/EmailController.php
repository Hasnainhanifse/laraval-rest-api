<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendEmailRequest;
use App\Traits\HandleEmail;

class EmailController extends Controller
{
    use HandleEmail;

    public function send(SendEmailRequest $request)
    {
        return $this->sendEmail($request);
    }
}
